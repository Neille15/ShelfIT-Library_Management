<?php
declare(strict_types=1);

const PASSWORD_RESET_TTL = 900;

function passwordResetDirectory(): string
{
    return dirname(__DIR__, 2) . '/.shelfit-local/password-resets';
}

function passwordResetParts(string $token): ?array
{
    if (preg_match('/\A([1-9][0-9]{0,9})\.([a-f0-9]{64})\z/', $token, $matches) !== 1) {
        return null;
    }
    return [(int) $matches[1], $matches[2]];
}

function passwordResetAccount(string $token, array $record): ?array
{
    $parts = passwordResetParts($token);
    if (!$parts || !is_string($record['token_hash'] ?? null)
        || !is_string($record['password_stamp'] ?? null)
        || !is_int($record['expires_at'] ?? null) || $record['expires_at'] <= time()
        || !hash_equals($record['token_hash'], hash('sha256', $parts[1]))) {
        return null;
    }
    $account = query('SELECT id, password_hash FROM staff_users WHERE id = ?', [$parts[0]])->fetch();
    return $account && hash_equals($record['password_stamp'], hash('sha256', $account['password_hash']))
        ? $account : null;
}

function validPasswordReset(string $token): bool
{
    $parts = passwordResetParts($token);
    if (!$parts) {
        return false;
    }
    $path = passwordResetDirectory() . '/' . $parts[0] . '.json';
    if (!is_file($path)) {
        return false;
    }
    $file = fopen($path, 'r');
    if (!$file) {
        return false;
    }
    try {
        if (!flock($file, LOCK_SH)) {
            return false;
        }
        $record = json_decode(stream_get_contents($file), true);
        return is_array($record) && passwordResetAccount($token, $record) !== null;
    } finally {
        fclose($file);
    }
}

function createPasswordReset(string $username): string
{
    $account = query('SELECT id, password_hash FROM staff_users WHERE username = ?', [$username])->fetch();
    if (!$account) {
        throw new StaffValidation('No staff account was found with that username.');
    }
    $directory = passwordResetDirectory();
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the private recovery directory.');
    }
    $secret = bin2hex(random_bytes(32));
    $record = json_encode([
        'token_hash' => hash('sha256', $secret),
        'password_stamp' => hash('sha256', $account['password_hash']),
        'expires_at' => time() + PASSWORD_RESET_TTL,
    ], JSON_THROW_ON_ERROR);
    $path = $directory . '/' . $account['id'] . '.json';
    $oldMask = umask(0077);
    try {
        $file = fopen($path, 'c+');
    } finally {
        umask($oldMask);
    }
    if (!$file) {
        throw new RuntimeException('Could not open the private recovery file.');
    }
    try {
        if (!flock($file, LOCK_EX) || !ftruncate($file, 0)
            || fwrite($file, $record) !== strlen($record) || !fflush($file)) {
            throw new RuntimeException('Could not save the recovery link.');
        }
    } finally {
        fclose($file);
    }
    return $account['id'] . '.' . $secret;
}

function resetStaffPassword(): never
{
    $token = $_SESSION['password_reset_token'] ?? '';
    $parts = is_string($token) ? passwordResetParts($token) : null;
    if (!$parts) {
        throw new StaffValidation('This recovery link is invalid or expired. Generate a new link from the server terminal.');
    }
    $password = $_POST['new_password'] ?? null;
    $confirmation = $_POST['confirm_password'] ?? null;
    if (!is_string($password) || !is_string($confirmation) || $password !== $confirmation) {
        throw new StaffValidation('The new passwords do not match.');
    }
    if (!staffPasswordIsValid($password)) {
        throw new StaffValidation('Use a new password with at least 12 characters and at most 72 bytes.');
    }
    $path = passwordResetDirectory() . '/' . $parts[0] . '.json';
    $file = is_file($path) ? fopen($path, 'r+') : false;
    if (!$file) {
        throw new StaffValidation('This recovery link is invalid or expired. Generate a new link from the server terminal.');
    }
    try {
        if (!flock($file, LOCK_EX)) {
            throw new RuntimeException('Could not lock the recovery file.');
        }
        $record = json_decode(stream_get_contents($file), true);
        $account = is_array($record) ? passwordResetAccount($token, $record) : null;
        if (!$account) {
            throw new StaffValidation('This recovery link is invalid or expired. Generate a new link from the server terminal.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $updated = query('UPDATE staff_users SET password_hash = ? WHERE id = ? AND BINARY password_hash = ?',
            [$hash, $account['id'], $account['password_hash']])->rowCount();
        if ($updated !== 1) {
            throw new StaffValidation('The account changed. Generate a new recovery link.');
        }
        // A changed password stamp also makes the link unusable if file cleanup fails.
        ftruncate($file, 0);
        fflush($file);
    } finally {
        fclose($file);
    }
    finishPasswordRecovery();
}

function finishPasswordRecovery(): never
{
    session_regenerate_id(true);
    $_SESSION = [
        'csrf' => bin2hex(random_bytes(32)),
        'password_reset_notice' => 'Password updated. Sign in with your new password.',
    ];
    redirect('index.php');
}
