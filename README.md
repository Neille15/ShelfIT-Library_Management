# ShelfIT · PLV CEIT Library

ShelfIT is a PHP study catalog and physical shelf finder for theses and capstones. Visitors can search the catalog and find recorded copies. Staff sign in to maintain studies, subjects, copies, and physical locations.

## Set up on Linux XAMPP

Use the installed XAMPP distribution at `/opt/lampp`. ShelfIT needs PHP 8.2 or newer with `pdo_mysql`, `mbstring`, and sessions. Use XAMPP's PHP for the account setup command.

On this device, the running ShelfIT pilot and [its phpMyAdmin instance](http://127.0.0.1:8085/phpmyadmin/) share the same `shelfit` database. The database login is stored in the private local file `.shelfit-local/phpmyadmin-login.txt`. The following setup steps are for a fresh installation.

### 1. Create the database in phpMyAdmin

1. Start Apache and MySQL from the XAMPP control panel, or run `sudo /opt/lampp/lampp startapache` and `sudo /opt/lampp/lampp startmysql`.
2. Open [local phpMyAdmin](http://localhost/phpmyadmin/) with your existing database administrator account.
3. Create a database named `shelfit` using `utf8mb4_unicode_ci`. Select it and use **Import** to load `schema.sql`.
4. In phpMyAdmin's **User accounts**, create `shelfit_app` for host `localhost` with a unique password. Grant it SELECT, INSERT, UPDATE, and DELETE on `shelfit` only. Keep phpMyAdmin and its administrator credentials restricted to database administrators.

### 2. Configure and create the first staff account

From the repository directory:

```bash
cp config.example.php config.local.php
chmod 600 config.local.php
```

Enter the database username and password in `config.local.php`. The example connects to XAMPP's local MariaDB socket. Adjust the DSN if your XAMPP installation uses a different socket or a local TCP connection. Grant the Apache runtime user read access to the configuration through file ownership or a restricted group if Apache runs under a different user. Keep the configuration outside the document root.

Create the initial account from the repository directory, then enter the requested password at the prompt:

```bash
/opt/lampp/bin/php bin/create-staff.php librarian
```

### 3. Serve the public directory with Apache

Add this local virtual host to XAMPP's Apache configuration, replacing the path with the absolute path to this checkout. Add the `Listen` directive only if the selected port is otherwise unused. The Apache runtime must be able to traverse the parent directories and read the public files and PHP application files.

```apache
Listen 127.0.0.1:8085
<VirtualHost 127.0.0.1:8085>
    ServerName localhost
    DocumentRoot "/absolute/path/to/ShelfIT-Library_Management/public"
    <Directory "/absolute/path/to/ShelfIT-Library_Management/public">
        Options -Indexes
        AllowOverride None
        Require all granted
        DirectoryIndex index.php
    </Directory>
</VirtualHost>
```

Check the configuration with `sudo /opt/lampp/bin/apachectl -t`, then restart Apache. Open [ShelfIT](http://127.0.0.1:8085/) and select **Staff editor** to sign in. Keep the pilot on localhost. Use HTTPS and restrict administrative tools before making the site accessible across the campus network.

Staff can change their own username or password under **Staff editor → Account** by entering their current password. Changing the password signs the account out of its other sessions.

## Set up on Windows XAMPP

These steps assume XAMPP is installed at `C:\xampp` and the project is at `C:\Projects\ShelfIT-Library_Management`. Adjust the paths if yours differ. Use PHP 8.2 or newer. Git supplies the code only: each Windows computer needs its own database and ignored `config.local.php`.

### 1. Install XAMPP and get the project

1. Install [XAMPP for Windows](https://www.apachefriends.org/), then open the XAMPP Control Panel and start **Apache** and **MySQL**.
2. Clone the repository outside `htdocs`, so the default Apache site cannot expose private configuration or recovery files. In PowerShell:

```powershell
New-Item -ItemType Directory -Force C:\Projects
Set-Location C:\Projects
git clone https://github.com/Neille15/ShelfIT-Library_Management.git
Set-Location C:\Projects\ShelfIT-Library_Management
```

If `git` is not recognized, install Git for Windows or download and extract the repository instead. If you already cloned it, open that folder instead. Check XAMPP PHP:

```powershell
& "C:\xampp\php\php.exe" -v
& "C:\xampp\php\php.exe" -m
```

Confirm `pdo_mysql` and `mbstring` appear in the module list. If missing, enable their extension entries in `C:\xampp\php\php.ini` and restart Apache. Use XAMPP's PHP executable in all commands below.

### 2. Create or import the local database

1. Open [phpMyAdmin](http://localhost/phpmyadmin/) and sign in using your local database administrator account.
2. Create `shelfit` with collation `utf8mb4_unicode_ci`. Select it, choose **Import**, and load `schema.sql` for a fresh, empty catalog.
3. To copy an existing installation, import its SQL export into the Windows database instead. Git does not copy catalog records or staff accounts. Do not replace an existing database when only updating the code.
4. Under **User accounts**, create `shelfit_app` for host `127.0.0.1` with a unique password. Grant only SELECT, INSERT, UPDATE, and DELETE on the `shelfit` database; leave global privileges unchecked.

### 3. Configure the Windows database connection

From the project folder in PowerShell:

```powershell
Copy-Item config.example.php config.local.php
```

For an existing installation, edit its current configuration rather than overwriting it. Replace the Linux socket DSN in `config.local.php` with this TCP configuration:

```php
<?php
return [
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=shelfit;charset=utf8mb4',
    'user' => 'shelfit_app',
    'password' => 'REPLACE_WITH_YOUR_DATABASE_PASSWORD',
];
```

Use the password from step 2. If MySQL uses a different port, update `3306` to match. Keep this file private and untracked. In Windows folder security settings, allow your Windows account and the Apache runtime account the access they need; Apache also needs write access to `.shelfit-local` for recovery records. Do not grant broad access to other users.

### 4. Create the first staff account

Skip this step if you imported a database that already contains your staff account. From the project folder, use PowerShell's masked credential prompt and pipe the password to the existing PHP script:

```powershell
$OutputEncoding = [System.Text.UTF8Encoding]::new($false)
$staffCredential = Get-Credential -UserName "librarian" -Message "Create ShelfIT staff account: use a password with at least 12 characters"
$staffCredential.GetNetworkCredential().Password | & "C:\xampp\php\php.exe" bin\create-staff.php $staffCredential.UserName
Remove-Variable staffCredential
```

The password must contain at least 12 characters and at most 72 bytes. Expect `Staff account created.` The piped input avoids the script's Linux-only `stty` terminal handling. This staff password is separate from the database password in `config.local.php`.

### 5. Serve only the public folder

Back up `C:\xampp\apache\conf\httpd.conf`, then ensure this include is enabled (without a leading `#`):

```apache
Include conf/extra/httpd-vhosts.conf
```

Back up `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, then append the following. Use forward slashes in Apache paths. Add the `Listen` line only once across the Apache configuration files:

```apache
Listen 127.0.0.1:8085
<VirtualHost 127.0.0.1:8085>
    ServerName localhost
    DocumentRoot "C:/Projects/ShelfIT-Library_Management/public"
    <Directory "C:/Projects/ShelfIT-Library_Management/public">
        Options -Indexes
        AllowOverride None
        Require all granted
        DirectoryIndex index.php
    </Directory>
</VirtualHost>
```

Check the configuration in PowerShell:

```powershell
& "C:\xampp\apache\bin\httpd.exe" -t
```

After `Syntax OK`, stop and start Apache in the XAMPP Control Panel. Open [ShelfIT](http://localhost:8085/) and select **Staff editor** to sign in. No Windows hosts-file edit is needed. The ShelfIT listener is restricted to this computer; this does not change the listeners for other XAMPP sites.

### 6. Verify the setup and recovery

- Confirm the catalog and staff sign-in page load. A fresh database has an empty catalog.
- Sign in, open **Account → Password recovery codes**, verify your current password, and save the generated codes privately.
- Confirm `http://localhost:8085/config.local.php` and `http://localhost:8085/.shelfit-local/` return 404. If private files are accessible, correct the document root before continuing.
- For server-assisted recovery, run this from the project folder, replacing `librarian` with the existing username:

```powershell
& "C:\xampp\php\php.exe" bin\create-password-reset.php librarian
```

If Apache will not start, check **Apache → Logs** in the control panel and resolve duplicate `Listen` lines or port conflicts. If the database connection fails, check that MySQL is running and that the DSN port, username, host, and password match. For PHP setup and Apache listener details, see the [XAMPP Windows FAQ](https://www.apachefriends.org/faq_windows.html) and [Apache address and port documentation](https://httpd.apache.org/docs/2.4/bind.html).

## Recover a forgotten staff password

While signed in, open **Account → Password recovery codes**, enter your current password, and select **Generate new recovery codes**. Save the eight codes in a password manager or print them: the full codes are shown only once. Generating another set invalidates every previous code.

If you forget your password, select **Forgot password?** on the sign-in page. Enter your username, an unused recovery code, and the new password twice. After a successful reset, sign in with the new password. The used code cannot be reused; the remaining codes stay valid. Five incorrect code attempts for an account pause recovery-code verification for 15 minutes, including across new browser sessions. Codes contain 128 random bits, accept upper/lowercase and optional spaces/hyphens, and are stored only as hashes in private files under `.shelfit-local/recovery-codes/`.

Recovery codes are saved until used or replaced; they do not have the 15-minute expiration of a terminal-generated link. Anyone with a code can reset the account password, so keep codes private. Keep the private recovery directory protected and include it in restricted backups. Both recovery methods update the existing database's password hash and invalidate existing sessions. No database migration is required.

The login password is hidden by default. Use **Show** to reveal it and **Hide** to mask it again; these buttons do not submit the form.

### Server-assisted fallback

If you lost both the password and recovery codes, the person who manages the server can generate a private, one-time recovery link from the repository directory:

```bash
/opt/lampp/bin/php bin/create-password-reset.php librarian
```

Replace `librarian` with the existing staff username. The command prints a link for `http://localhost:8085`; pass the site's base URL as a second argument if needed. For example, append `https://library.example.edu` when using HTTPS hosting. The URL is supplied by the server operator rather than a browser request.

Open the private link within 15 minutes. Enter **New password** and **Confirm password**, then select **Update password**. The password must match in both fields and contain at least 12 characters and at most 72 bytes. The existing database is updated with a password hash, the link becomes unusable, and previous sessions are invalidated. Sign in again with the new password.

Generating another link for the same account replaces its previous link. Recovery records contain only a hash of the random token, a password stamp, and an expiration time in the ignored `.shelfit-local/password-resets/` directory outside the public document root. No database migration is required. Run the command as the same operating-system user as the Apache runtime so it can access these private files. Do not share recovery links except with the account owner.

## Pilot workflow

1. In **Shelf locations**, create sections, then cabinets, then shelves. Use the labels printed in the library. Set lower sort numbers for locations that should appear earlier in the diagram.
2. Add subjects under **Subjects**. In **Studies**, enter the catalog record using the starting year of the academic year, for example `2025` for 2025–2026.
3. Add a physical copy with its unique copy ID. Leave all location fields blank until staff verify the shelf. When a copy moves, save the new location in its copy form.
4. Search for the new study in **Catalog** and compare the directions and highlighted shelf against the physical copy. Confirm the Excel fields and verify the pilot records with library staff before expanding.
5. Export the `shelfit` database from phpMyAdmin for backups. Archive a study to remove it from public search while keeping its record and copies.
6. To remove an archived study permanently, open it from **Show archived studies**, expand **Delete permanently**, and confirm. Its copy records and subject assignments are removed with the study. Shared subjects and shelf locations are preserved.

The imported schema starts with an empty catalog. Confirm access to physical copies with library staff.

## Code organization

ShelfIT uses procedural PHP with separate page templates and one staff form processor:

- `public/` contains browser entry points and assets. Catalog pages load data and render templates. `staff/index.php` displays staff pages; `staff/process.php` accepts POST submissions only. Existing POST submissions to the staff index delegate to the same processor.
- `app/bootstrap.php` initializes error handling and loads database, general, and presentation helpers. Database configuration still comes from the existing `config.local.php` or `SHELFIT_DB_*` environment variables.
- `app/catalog.php` loads catalog data. `app/staff/` separates authentication/account logic, validation, studies/copies, subjects, locations, action dispatch, data loading, and rendering helpers.
- `templates/` contains the shared header/footer, catalog pages, shelf diagram, and staff forms/pages. Templates do not execute SQL. View selection uses fixed paths in a switch; request input never becomes an include path.

All staff mutations pass through the shared processor and CSRF checks. Normal staff actions require sign-in; password recovery requires an unused recovery code or a valid, unexpired one-time recovery link generated from the server terminal. Keep queries parameterized, dynamic SQL identifiers allowlisted, displayed values escaped, and passwords hashed. Study/tag updates and permanent deletion retain their transactions. Password changes invalidate other sessions.

Updating an existing installation requires no database import or migration. Keep the current database and private configuration, and keep Apache's document root set to `public/`. Deploy the complete `app/`, `templates/`, and `public/` directories together.

### Local viewing and syntax checks

Run syntax checks with XAMPP PHP:

```bash
find app public templates bin -name '*.php' -exec /opt/lampp/bin/php -l {} \;
```

View the local installation at [http://localhost:8085/](http://localhost:8085/). It uses the existing `shelfit` database and private database configuration. Start the configured Apache instance and XAMPP MySQL before opening the site.

### Remaining login hardening

The current sign-in delay is session-based, so starting a fresh session bypasses it. Before expanding beyond the localhost pilot, add persistent account/source throttling as a separate security change. The restructuring retains the existing behavior.
