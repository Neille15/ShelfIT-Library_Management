<?php
declare(strict_types=1);

require_once __DIR__ . '/catalog.php';

const LOCATION_TYPES = [
    'sections' => ['Section', null, null],
    'cabinets' => ['Cabinet', 'section_id', 'sections'],
    'shelves' => ['Shelf', 'cabinet_id', 'cabinets'],
];

final class StaffValidation extends RuntimeException
{
}

function startStaffSession(): void
{
    header('Cache-Control: no-store');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('shelfit_staff');
    session_set_cookie_params([
        'path' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function staffForm(string $action): void
{
    ?>
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <input type="hidden" name="action" value="<?= h($action) ?>">
    <?php
}

function validCsrf(): bool
{
    return hash_equals($_SESSION['csrf'], input($_POST, 'csrf'));
}

function staffUrl(string $view = 'studies', array $params = []): string
{
    return url('index.php', ['view' => $view, ...$params]);
}

function staffDone(string $message, string $target): never
{
    $_SESSION['notice'] = $message;
    redirect($target);
}

function textField(string $name, string $label, int $max, bool $required = true): string
{
    $value = input($_POST, $name);
    if ($required && $value === '') {
        throw new StaffValidation("$label is required.");
    }
    if (!mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $max) {
        throw new StaffValidation("$label must contain at most $max characters.");
    }
    return $value;
}

function requireRecord(string $table, int $recordId): void
{
    if (!$recordId || !query("SELECT id FROM $table WHERE id = ?", [$recordId])->fetchColumn()) {
        throw new StaffValidation('That record could not be found.');
    }
}

function saveStudy(): never
{
    $studyId = id($_POST['study_id'] ?? null);
    $year = id($_POST['academic_year'] ?? null);
    $type = input($_POST, 'study_type');
    if ($year < 1900 || $year > 2199) {
        throw new StaffValidation('Enter a starting academic year between 1900 and 2199.');
    }
    if (!in_array($type, STUDY_TYPES, true)) {
        throw new StaffValidation('Choose Thesis or Capstone.');
    }
    $data = [
        textField('title', 'Title', 255),
        textField('authors', 'Authors', 500),
        textField('adviser', 'Adviser', 255, false),
        textField('program', 'Degree program', 120),
        $year, $type,
        textField('summary', 'Abstract or summary', 5000, false),
    ];
    $postedTags = $_POST['tags'] ?? [];
    if (!is_array($postedTags)) {
        throw new StaffValidation('Choose subjects from the list.');
    }
    $tagIds = array_unique(array_map('id', $postedTags));
    if (in_array(0, $tagIds, true)) {
        throw new StaffValidation('Choose subjects from the list.');
    }

    db()->beginTransaction();
    try {
        if ($studyId) {
            if (!query('SELECT id FROM studies WHERE id = ? FOR UPDATE', [$studyId])->fetchColumn()) {
                throw new StaffValidation('That study could not be found.');
            }
            query('UPDATE studies SET title = ?, authors = ?, adviser = ?, program = ?,
                academic_year = ?, study_type = ?, summary = ? WHERE id = ?', [...$data, $studyId]);
        } else {
            query('INSERT INTO studies (title, authors, adviser, program, academic_year, study_type, summary)
                VALUES (?, ?, ?, ?, ?, ?, ?)', $data);
            $studyId = (int) db()->lastInsertId();
        }
        query('DELETE FROM study_tags WHERE study_id = ?', [$studyId]);
        foreach ($tagIds as $tagId) {
            query('INSERT INTO study_tags (study_id, tag_id) VALUES (?, ?)', [$studyId, $tagId]);
        }
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    staffDone('Study saved.', staffUrl('study', ['id' => $studyId]));
}

function saveCopy(): never
{
    $studyId = id($_POST['study_id'] ?? null);
    requireRecord('studies', $studyId);
    $copyId = id($_POST['copy_id'] ?? null);
    if ($copyId && !query('SELECT id FROM copies WHERE id = ? AND study_id = ?', [$copyId, $studyId])->fetchColumn()) {
        throw new StaffValidation('That copy could not be found for this study.');
    }
    $code = textField('copy_code', 'Copy ID', 80);
    $selection = [input($_POST, 'section_id'), input($_POST, 'cabinet_id'), input($_POST, 'shelf_id')];
    $shelfId = null;
    if ($selection !== ['', '', '']) {
        [$sectionId, $cabinetId, $shelfId] = array_map('id', $selection);
        if (!query('SELECT sh.id FROM shelves sh JOIN cabinets c ON c.id = sh.cabinet_id
            WHERE sh.id = ? AND sh.cabinet_id = ? AND c.section_id = ?', [$shelfId, $cabinetId, $sectionId])->fetchColumn()) {
            throw new StaffValidation('Choose a shelf in the selected cabinet and section, or clear all location fields.');
        }
    }
    try {
        if ($copyId) {
            query('UPDATE copies SET copy_code = ?, shelf_id = ? WHERE id = ? AND study_id = ?', [$code, $shelfId, $copyId, $studyId]);
        } else {
            query('INSERT INTO copies (study_id, copy_code, shelf_id) VALUES (?, ?, ?)', [$studyId, $code, $shelfId]);
        }
    } catch (PDOException $error) {
        if (($error->errorInfo[1] ?? null) === 1062) {
            throw new StaffValidation('That copy ID is already in use.');
        }
        throw $error;
    }
    staffDone('Copy saved.', staffUrl('study', ['id' => $studyId]));
}

function saveLocation(): never
{
    $kind = input($_POST, 'kind');
    [$label, $parentColumn, $parentTable] = LOCATION_TYPES[$kind] ?? [null, null, null];
    if (!$label) {
        throw new StaffValidation('Choose a location type.');
    }
    $locationId = id($_POST['record_id'] ?? null);
    if ($locationId) {
        requireRecord($kind, $locationId);
    }
    $name = textField('label', "$label name", 100);
    $order = filter_var(input($_POST, 'sort_order'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 65535]]);
    if ($order === false) {
        throw new StaffValidation('Sort order must be a whole number between 0 and 65535.');
    }
    $fields = ['label' => $name, 'sort_order' => $order];
    if ($parentColumn) {
        $parentId = id($_POST['parent_id'] ?? null);
        requireRecord($parentTable, $parentId);
        $fields[$parentColumn] = $parentId;
    }
    if ($locationId) {
        $assignments = implode(', ', array_map(static fn ($column) => "$column = ?", array_keys($fields)));
        query("UPDATE $kind SET $assignments WHERE id = ?", [...array_values($fields), $locationId]);
    } else {
        $columns = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        query("INSERT INTO $kind ($columns) VALUES ($placeholders)", array_values($fields));
    }
    staffDone("$label saved.", staffUrl('locations'));
}

function handleStaffAction(string $action): never
{
    switch ($action) {
        case 'logout':
            $_SESSION = [];
            session_destroy();
            redirect('index.php');
        case 'study-save':
            saveStudy();
        case 'study-archive':
        case 'study-restore':
            $studyId = id($_POST['study_id'] ?? null);
            requireRecord('studies', $studyId);
            $archive = $action === 'study-archive';
            $timestamp = $archive ? 'CURRENT_TIMESTAMP' : 'NULL';
            query("UPDATE studies SET archived_at = $timestamp WHERE id = ?", [$studyId]);
            staffDone($archive ? 'Study archived.' : 'Study restored.', staffUrl('study', ['id' => $studyId]));
        case 'copy-save':
            saveCopy();
        case 'copy-delete':
            $studyId = id($_POST['study_id'] ?? null);
            $removed = query('DELETE FROM copies WHERE id = ? AND study_id = ?', [id($_POST['copy_id'] ?? null), $studyId])->rowCount();
            if (!$removed) {
                throw new StaffValidation('That copy could not be found for this study.');
            }
            staffDone('Copy removed.', staffUrl('study', ['id' => $studyId]));
        case 'tag-save':
            $tagId = id($_POST['record_id'] ?? null);
            $name = textField('name', 'Subject name', 100);
            if ($tagId) {
                requireRecord('tags', $tagId);
                query('UPDATE tags SET name = ? WHERE id = ?', [$name, $tagId]);
            } else {
                query('INSERT INTO tags (name) VALUES (?)', [$name]);
            }
            staffDone('Subject saved.', staffUrl('tags'));
        case 'tag-delete':
            $tagId = id($_POST['record_id'] ?? null);
            requireRecord('tags', $tagId);
            query('DELETE FROM tags WHERE id = ?', [$tagId]);
            staffDone('Subject and its study assignments removed.', staffUrl('tags'));
        case 'location-save':
            saveLocation();
        case 'location-delete':
            $kind = input($_POST, 'kind');
            [$label] = LOCATION_TYPES[$kind] ?? [null];
            if (!$label) {
                throw new StaffValidation('Choose a location type.');
            }
            $recordId = id($_POST['record_id'] ?? null);
            requireRecord($kind, $recordId);
            query("DELETE FROM $kind WHERE id = ?", [$recordId]);
            staffDone("$label removed.", staffUrl('locations'));
    }
    throw new StaffValidation('Unknown staff action.');
}
