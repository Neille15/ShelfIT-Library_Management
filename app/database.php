<?php
declare(strict_types=1);

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
