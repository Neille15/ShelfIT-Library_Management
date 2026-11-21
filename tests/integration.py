#!/usr/bin/env python3
"""Exercise ShelfIT against disposable MariaDB/PHP servers, never the configured database.

Run: python3 tests/integration.py
Requires Python 3 and XAMPP at /opt/lampp (override SHELFIT_TEST_XAMPP).
"""
import html
import http.cookiejar
import os
from pathlib import Path
import re
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
XAMPP = Path(os.environ.get('SHELFIT_TEST_XAMPP', '/opt/lampp'))
PASSWORD = 'ShelfIT-test-password-123'


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Browser:
    def __init__(self, base):
        self.base = base
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(self.cookies), NoRedirect())
        self.csrf = ''

    def request(self, path, data=None, expected=200):
        form = {(key + '[]' if isinstance(value, list) else key): value for key, value in (data or {}).items()}
        body = urllib.parse.urlencode(form, doseq=True).encode() if data is not None else None
        try:
            response = self.opener.open(self.base + path, body, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        text = response.read().decode()
        assert response.code == expected, (path, response.code, expected, text[:500])
        assert 'Fatal error' not in text and 'Warning:' not in text, text[:500]
        tokens = re.findall(r'name="csrf" value="([^"]+)"', text)
        if tokens:
            self.csrf = tokens[0]
        return text, response.headers

    def post(self, action, expected=303, path='/staff/process.php', **values):
        return self.request(path, {'action': action, 'csrf': self.csrf, **values}, expected)

    def login(self):
        self.request('/staff/index.php')
        self.post('login', username='tester', password=PASSWORD)
        self.request('/staff/index.php')


def run():
    with tempfile.TemporaryDirectory(prefix='shelfit-test-') as directory:
        temp = Path(directory)
        datadir = temp / 'db'
        db_socket = temp / 'mysql.sock'
        sessions = temp / 'sessions'
        sessions.mkdir()
        with (temp / 'servers.log').open('w+') as log:
            subprocess.run([str(XAMPP / 'bin/mysql_install_db'), '--no-defaults', '--auth-root-authentication-method=normal',
                            f'--basedir={XAMPP}', f'--datadir={datadir}'],
                           stdout=log, stderr=log, check=True)
            database = subprocess.Popen([str(XAMPP / 'sbin/mysqld'), '--no-defaults',
                                         f'--basedir={XAMPP}', f'--datadir={datadir}',
                                         f'--socket={db_socket}', f'--pid-file={temp / "mysql.pid"}',
                                         '--skip-networking', '--innodb-buffer-pool-size=32M'],
                                        stdout=log, stderr=log)
            server = None
            try:
                mysql = [str(XAMPP / 'bin/mysql'), '--no-defaults', f'--socket={db_socket}', '-u', 'root', '-N', '-B']
                for _ in range(100):
                    if database.poll() is not None:
                        raise RuntimeError('Test MariaDB stopped: ' + (temp / 'servers.log').read_text())
                    if db_socket.exists():
                        break
                    time.sleep(.1)

                def sql(statement):
                    result = subprocess.run(mysql, input=statement, text=True, capture_output=True)
                    assert result.returncode == 0, result.stderr
                    return result.stdout.strip()

                sql('CREATE DATABASE shelfit_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n'
                    'USE shelfit_test;\n' + (ROOT / 'schema.sql').read_text())
                environment = {**os.environ, 'SHELFIT_DB_DSN': f'mysql:unix_socket={db_socket};dbname=shelfit_test;charset=utf8mb4',
                               'SHELFIT_DB_USER': 'root', 'SHELFIT_DB_PASSWORD': ''}
                subprocess.run([str(XAMPP / 'bin/php'), str(ROOT / 'bin/create-staff.php'), 'tester'],
                               input=PASSWORD + '\n', text=True, env=environment, check=True, capture_output=True)
                with socket.socket() as port_socket:
                    port_socket.bind(('127.0.0.1', 0))
                    port = port_socket.getsockname()[1]
                base = f'http://127.0.0.1:{port}'
                server = subprocess.Popen([str(XAMPP / 'bin/php'), '-d', f'session.save_path={sessions}',
                                           '-d', 'display_errors=0', '-S', f'127.0.0.1:{port}', '-t', str(ROOT / 'public')],
                                          cwd=ROOT, env=environment, stdout=log, stderr=log)
                browser = Browser(base)
                for _ in range(100):
                    try:
                        browser.request('/')
                        break
                    except urllib.error.URLError:
                        time.sleep(.1)
                else:
                    raise RuntimeError('Test PHP server did not start')

                for private in ['/config.local.php', '/schema.sql', '/app/database.php', '/templates/header.php']:
                    browser.request(private, expected=404)
                _, headers = browser.request('/staff/process.php?action=study-delete', expected=405)
                assert headers['Allow'] == 'POST'
                browser.request('/staff/index.php')
                assert any(c.name == 'shelfit_staff' and c.path == '/staff/' and 'HttpOnly' in c._rest
                           and c._rest.get('SameSite') == 'Lax' for c in browser.cookies)
                browser.post('study-save', expected=403)
                browser.post('login', expected=403, csrf='bad', username='tester', password=PASSWORD)
                browser.post('login', expected=200, username="' OR 1=1 --", password=PASSWORD)
                old_session = next(c.value for c in browser.cookies if c.name == 'shelfit_staff')
                browser.post('login', username='tester', password=PASSWORD)
                browser.request('/staff/index.php')
                assert next(c.value for c in browser.cookies if c.name == 'shelfit_staff') != old_session
                browser.post('study-save', expected=403, csrf='bad')
                browser.post('unrecognized', expected=422)
                browser.request('/staff/index.php?view=../../config.local.php')

                # Exercise all feature groups through the shared POST gate.
                for kind, parent in [('sections', None), ('cabinets', 1), ('shelves', 1)]:
                    browser.post('location-save', kind=kind, label=kind.title(), sort_order=0,
                                 **({'parent_id': parent} if parent else {}))
                browser.post('location-save', kind='sections', label='Second section', sort_order=1)
                browser.post('location-save', kind='sections', record_id=1, label='Section A', sort_order=0)
                browser.post('tag-save', name='Web development')
                browser.post('tag-save', name='Other subject')
                browser.post('tag-save', record_id=2, name='Renamed subject')
                browser.post('tag-delete', record_id=2)
                title = '<script>alert("test")</script> Inventory'
                study = dict(title=title, authors='Test Author', adviser='Test Adviser', program='BSIT',
                             academic_year=2026, study_type='Capstone', summary='Test summary', tags=[1])
                _, headers = browser.post('study-save', **study)
                assert headers['Location'] == 'index.php?view=study&id=1'
                browser.request('/staff/index.php?view=study&id=1')
                browser.post('study-save', path='/staff/index.php?view=study&id=1', study_id=1, **study)
                text, _ = browser.request('/?q=Inventory&program=BSIT&year=2026&type=Capstone&tag=1')
                assert '1 study found' in text and html.escape(title, quote=True) in text and title not in text
                text, _ = browser.request('/?q=' + urllib.parse.quote("' OR 1=1 --"))
                assert '0 studies found' in text
                text, _ = browser.request('/?q=absent')
                assert '0 studies found' in text
                browser.post('copy-save', study_id=1, copy_code='COPY-1', section_id=1, cabinet_id=1, shelf_id=1)
                browser.post('copy-save', study_id=1, copy_code='COPY-2')
                text, _ = browser.request('/study.php?id=1&copy=1')
                assert 'Section A → Cabinets → Shelves' in text and 'shelf-selected' in text
                text, _ = browser.request('/study.php?id=1&copy=2')
                assert 'Location not recorded.' in text
                browser.post('copy-save', copy_id=2, study_id=1, copy_code='COPY-2', section_id=1, cabinet_id=1, shelf_id=1)
                text, _ = browser.post('copy-save', expected=422, copy_id=1, study_id=1,
                                       copy_code='Retained copy', section_id=2, cabinet_id=1, shelf_id=1)
                assert 'Retained copy' in text and 'Choose a shelf' in text
                browser.post('copy-save', expected=422, study_id=1, copy_code='COPY-1')
                text, _ = browser.post('study-save', expected=422, study_id=1, **{**study, 'title': 'Retained title', 'academic_year': 1800})
                assert 'Retained title' in text
                text, _ = browser.post('location-save', expected=422, kind='sections', label='Retained section', sort_order=-1)
                assert 'Retained section' in text
                browser.post('location-delete', expected=422, kind='sections', record_id=1)
                browser.post('location-save', expected=422, kind='staff_users', label='Bad', sort_order=0)

                # Failed association updates roll back the study and its existing tags.
                browser.post('study-save', expected=422, study_id=1, **{**study, 'title': 'Must roll back', 'tags': [999999]})
                assert sql('USE shelfit_test; SELECT title FROM studies WHERE id=1;') == title
                assert sql('USE shelfit_test; SELECT tag_id FROM study_tags WHERE study_id=1;') == '1'
                browser.post('study-save', **{**study, 'title': 'Another study', 'tags': []})
                browser.post('copy-save', expected=422, copy_id=1, study_id=2, copy_code='Hijacked')
                browser.post('copy-delete', expected=422, copy_id=1, study_id=2)
                browser.post('copy-delete', copy_id=2, study_id=1)
                browser.post('study-delete', expected=422, study_id=1)
                browser.post('study-archive', study_id=1)
                browser.request('/study.php?id=1', expected=404)
                text, _ = browser.request('/staff/index.php?archived=1')
                assert html.escape(title, quote=True) in text
                browser.post('study-restore', study_id=1)
                browser.request('/study.php?id=1')
                browser.post('study-archive', study_id=1)
                browser.post('study-delete', study_id=1)
                assert sql('USE shelfit_test; SELECT COUNT(*) FROM copies WHERE study_id=1;') == '0'
                assert sql('USE shelfit_test; SELECT COUNT(*) FROM study_tags WHERE study_id=1;') == '0'
                assert sql('USE shelfit_test; SELECT COUNT(*) FROM tags;') == '1'
                browser.post('location-delete', kind='shelves', record_id=1)
                browser.post('location-delete', kind='cabinets', record_id=1)
                browser.post('location-delete', kind='sections', record_id=1)
                browser.request('/staff/index.php?view=study&id=999999', expected=404)

                # Seed enough isolated records to check pagination and bounds.
                for number in range(22):
                    sql("USE shelfit_test; INSERT INTO studies (title,authors,program,academic_year,study_type,summary) "
                        f"VALUES ('Page {number}', 'Author', 'BSIT', 2026, 'Thesis', '');")
                text, _ = browser.request('/?page=2')
                assert 'Page 2 of 2' in text
                text, _ = browser.request('/?page=999')
                assert 'Page 2 of 2' in text

                other = Browser(base)
                other.login()
                text, _ = browser.post('account-save', expected=422, username='tester',
                                       current_password='wrong', new_password='', confirm_password='')
                assert 'value="wrong"' not in text
                browser.post('account-save', username='renamed', current_password=PASSWORD,
                             new_password='', confirm_password='')
                browser.request('/staff/index.php?view=account')
                other.request('/staff/index.php')  # Username changes do not revoke other sessions.
                browser.post('account-save', username='renamed', current_password=PASSWORD,
                             new_password='New-test-password-456', confirm_password='New-test-password-456')
                browser.request('/staff/index.php?view=account')
                text, _ = other.request('/staff/index.php')
                assert 'Staff sign in' in text
                browser.post('logout')
                text, _ = browser.request('/staff/index.php')
                assert 'Staff sign in' in text
                if os.environ.get('SHELFIT_TEST_PLAYWRIGHT'):
                    subprocess.run(['node', str(ROOT / 'tests/browser.cjs')], check=True,
                                   env={**os.environ, 'SHELFIT_TEST_BASE': base})
                log.flush()
                errors = (temp / 'servers.log').read_text()
                assert 'PHP Warning' not in errors and 'PHP Fatal' not in errors, errors[-3000:]
                print('PASS: isolated database; routing, security gates, escaping, all staff features, rollback, pagination, and session invalidation')
            finally:
                for process in [server, database]:
                    if process is not None:
                        process.terminate()
                        try:
                            process.wait(timeout=10)
                        except subprocess.TimeoutExpired:
                            process.kill()
                            process.wait()


if __name__ == '__main__':
    run()
