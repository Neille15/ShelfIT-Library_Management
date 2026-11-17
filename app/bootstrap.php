<?php
declare(strict_types=1);

set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "ShelfIT could not finish. Check the database configuration and PHP error log.\n");
        exit(1);
    }
    http_response_code(500);
    echo 'ShelfIT is temporarily unavailable. Please contact library staff.';
});

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = dirname(__DIR__) . '/config.local.php';
    $local = is_file($path) ? require $path : [];
    $dsn = getenv('SHELFIT_DB_DSN') ?: ($local['dsn'] ?? '');
    if ($dsn === '') {
        throw new RuntimeException('Configure ShelfIT in config.local.php.');
    }
    $user = getenv('SHELFIT_DB_USER');
    $password = getenv('SHELFIT_DB_PASSWORD');
    return $pdo = new PDO($dsn, $user === false ? ($local['user'] ?? '') : $user,
        $password === false ? ($local['password'] ?? '') : $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
}

function query(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    foreach (array_values($params) as $index => $value) {
        $type = match (true) {
            is_int($value) => PDO::PARAM_INT,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
        $statement->bindValue($index + 1, $value, $type);
    }
    $statement->execute();
    return $statement;
}

function h(string|int|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(array $source, string $name): string
{
    return is_string($source[$name] ?? null) ? trim($source[$name]) : '';
}

function id(mixed $value): int
{
    return (int) (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0);
}

function url(string $path, array $params = []): string
{
    $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null);
    return $path . ($params ? '?' . http_build_query($params) : '');
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function academicYear(int $year): string
{
    return $year . '–' . ($year + 1);
}

function pageStart(string $title, string $base = '', bool $staff = false): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?> | ShelfIT</title>
        <link rel="stylesheet" href="<?= h($base) ?>assets/shelfit.css">
        <script defer src="<?= h($base) ?>assets/shelfit.js"></script>
    </head>
    <body>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header">
        <div class="shell site-bar">
            <a class="brand" href="<?= h($base) ?>index.php">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span><strong>ShelfIT</strong><small>PLV CEIT Library</small></span>
            </a>
            <nav aria-label="Main navigation">
                <a href="<?= h($base) ?>index.php" <?= $staff ? '' : 'aria-current="page"' ?>>Catalog</a>
                <a href="<?= h($base) ?>staff/index.php" <?= $staff ? 'aria-current="page"' : '' ?>>Staff editor</a>
            </nav>
        </div>
    </header>
    <main class="shell page" id="main">
    <?php
}

function pageEnd(): void
{
    ?>
    </main>
    <footer class="site-footer"><div class="shell">PLV CEIT Library · Thesis and capstone catalog</div></footer>
    </body>
    </html>
    <?php
}
