<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/staff.php';

$username = $argv[1] ?? '';
$base = rtrim($argv[2] ?? 'http://localhost:8085', '/');
$parts = parse_url($base);
if (!staffUsernameIsValid($username) || !$parts || !isset($parts['host'], $parts['scheme'])
    || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
    || !in_array($parts['scheme'], ['http', 'https'], true)
    || ($parts['scheme'] === 'http' && !in_array($parts['host'], ['localhost', '127.0.0.1', '[::1]'], true))) {
    fwrite(STDERR, "Usage: php bin/create-password-reset.php USERNAME [SITE_URL]\nUse HTTPS except for localhost.\n");
    exit(1);
}
try {
    $token = createPasswordReset($username);
} catch (StaffValidation $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
fwrite(STDOUT, "Open this private one-time link within 15 minutes:\n"
    . $base . '/staff/reset.php?token=' . rawurlencode($token) . PHP_EOL);
