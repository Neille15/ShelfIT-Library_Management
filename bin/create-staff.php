<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = $argv[1] ?? '';
if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{2,63}\z/', $username)) {
    fwrite(STDERR, "Usage: php bin/create-staff.php USERNAME\nUsernames must be 3–64 letters, digits, dots, underscores, or hyphens.\n");
    exit(1);
}

$tty = stream_isatty(STDIN);
if ($tty) {
    fwrite(STDERR, 'Password (at least 12 characters): ');
    system('stty -echo', $status);
    if ($status !== 0) {
        exit(1);
    }
}

try {
    $line = fgets(STDIN);
} finally {
    if ($tty) {
        system('stty echo');
        fwrite(STDERR, PHP_EOL);
    }
}
$password = rtrim($line === false ? '' : $line, "\r\n");
if (!mb_check_encoding($password, 'UTF-8') || mb_strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
    fwrite(STDERR, "Use a password with at least 12 characters and at most 72 bytes.\n");
    exit(1);
}

try {
    query('INSERT INTO staff_users (username, password_hash) VALUES (?, ?)', [$username, password_hash($password, PASSWORD_DEFAULT)]);
} catch (PDOException $error) {
    if (($error->errorInfo[1] ?? null) === 1062) {
        fwrite(STDERR, "That staff username already exists.\n");
        exit(1);
    }
    throw $error;
}
fwrite(STDOUT, "Staff account created.\n");
