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

## Pilot workflow

1. In **Shelf locations**, create sections, then cabinets, then shelves. Use the labels printed in the library. Set lower sort numbers for locations that should appear earlier in the diagram.
2. Add subjects under **Subjects**. In **Studies**, enter the catalog record using the starting year of the academic year, for example `2025` for 2025–2026.
3. Add a physical copy with its unique copy ID. Leave all location fields blank until staff verify the shelf. When a copy moves, save the new location in its copy form.
4. Search for the new study in **Catalog** and compare the directions and highlighted shelf against the physical copy. Confirm the Excel fields and verify the pilot records with library staff before expanding.
5. Export the `shelfit` database from phpMyAdmin for backups. Archive a study to remove it from public search while keeping its record and copies.

The imported schema starts with an empty catalog. Confirm access to physical copies with library staff.
