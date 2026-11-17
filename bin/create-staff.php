<?php
declare(strict_types=1);

require __DIR__ . '/../app/staff.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = $argv[1] ?? '';
if (!staffUsernameIsValid($username)) {
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
if (!staffPasswordIsValid($password)) {
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
