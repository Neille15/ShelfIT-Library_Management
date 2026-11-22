<?php
declare(strict_types=1);

const RECOVERY_CODE_COUNT = 8;

function recoveryCodesFile(int $staffId): string
{
    return dirname(__DIR__, 2) . '/.shelfit-local/recovery-codes/' . $staffId . '.json';
}

function writeRecoveryCodes($file, array $record): void
{
    $json = json_encode($record, JSON_THROW_ON_ERROR);
    rewind($file);
    if (!ftruncate($file, 0) || fwrite($file, $json) !== strlen($json) || !fflush($file)) {
        throw new RuntimeException('Could not save recovery codes.');
    }
}

function recoveryCodeCount(int $staffId): int
{
    $path = recoveryCodesFile($staffId);
    if (!is_file($path)) {
        return 0;
    }
    $file = fopen($path, 'r');
    if (!$file) {
        throw new RuntimeException('Could not read recovery codes.');
    }
    try {
        if (!flock($file, LOCK_SH)) {
            throw new RuntimeException('Could not lock recovery codes.');
        }
        $record = json_decode(stream_get_contents($file), true);
        return is_array($record['hashes'] ?? null) ? count($record['hashes']) : 0;
    } finally {
        fclose($file);
    }
}

function generateRecoveryCodes(array $account): never
{
    $password = $_POST['current_password'] ?? null;
    if (!is_string($password) || strlen($password) > 72 || str_contains($password, "\0")
        || !password_verify($password, $account['password_hash'])) {
        throw new StaffValidation('The current password is incorrect.');
    }
    $path = recoveryCodesFile($account['id']);
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the private recovery directory.');
    }
    $oldMask = umask(0077);
    try {
        $file = fopen($path, 'c+');
    } finally {
        umask($oldMask);
    }
    if (!$file) {
        throw new RuntimeException('Could not open recovery codes.');
    }
    try {
        if (!flock($file, LOCK_EX)) {
            throw new RuntimeException('Could not lock recovery codes.');
        }
        $current = query('SELECT password_hash FROM staff_users WHERE id = ?', [$account['id']])->fetchColumn();
        if (!$current || !hash_equals($account['password_hash'], $current)) {
            throw new StaffValidation('Your account changed. Sign in again before generating recovery codes.');
        }
        $codes = [];
        for ($index = 0; $index < RECOVERY_CODE_COUNT; $index++) {
            $codes[] = implode('-', str_split(strtoupper(bin2hex(random_bytes(16))), 8));
        }
        writeRecoveryCodes($file, [
            'hashes' => array_map(static fn ($code) => hash('sha256', str_replace('-', '', $code)), $codes),
            'failed_attempts' => 0,
            'retry_after' => 0,
        ]);
    } finally {
        fclose($file);
    }
    // Display plaintext only in this response; never store it in the session or database.
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    require __DIR__ . '/../../templates/staff/recovery-codes.php';
    exit;
}

function resetWithRecoveryCode(): never
{
    $password = $_POST['new_password'] ?? null;
    $confirmation = $_POST['confirm_password'] ?? null;
    if (!is_string($password) || !is_string($confirmation) || $password !== $confirmation) {
        throw new StaffValidation('The new passwords do not match.');
    }
    if (!staffPasswordIsValid($password)) {
        throw new StaffValidation('Use a new password with at least 12 characters and at most 72 bytes.');
    }
    $message = 'The username or recovery code is incorrect, already used, or temporarily unavailable. Try again later or use server-assisted recovery.';
    $code = strtoupper(str_replace(['-', ' '], '', input($_POST, 'recovery_code')));
    if (preg_match('/\A[A-F0-9]{32}\z/', $code) !== 1) {
        throw new StaffValidation($message);
    }
    $account = query('SELECT id, password_hash FROM staff_users WHERE username = ?', [input($_POST, 'username')])->fetch();
    $path = $account ? recoveryCodesFile($account['id']) : '';
    $file = $path !== '' && is_file($path) ? fopen($path, 'r+') : false;
    if (!$file) {
        throw new StaffValidation($message);
    }
    try {
        if (!flock($file, LOCK_EX)) {
            throw new RuntimeException('Could not lock recovery codes.');
        }
        $record = json_decode(stream_get_contents($file), true);
        if (!is_array($record) || !is_array($record['hashes'] ?? null)
            || ($record['retry_after'] ?? 0) > time()) {
            throw new StaffValidation($message);
        }
        $match = null;
        $hash = hash('sha256', $code);
        foreach ($record['hashes'] as $index => $candidate) {
            if (is_string($candidate) && hash_equals($candidate, $hash)) {
                $match = $index;
            }
        }
        if ($match === null) {
            $record['failed_attempts'] = (int) ($record['failed_attempts'] ?? 0) + 1;
            if ($record['failed_attempts'] >= 5) {
                $record['retry_after'] = time() + 900;
                $record['failed_attempts'] = 0;
            }
            writeRecoveryCodes($file, $record);
            throw new StaffValidation($message);
        }
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        unset($record['hashes'][$match]);
        $record['hashes'] = array_values($record['hashes']);
        $record['failed_attempts'] = 0;
        $record['retry_after'] = 0;
        // Consume before changing the password: a crash cannot leave a reusable code.
        writeRecoveryCodes($file, $record);
        $changed = query('UPDATE staff_users SET password_hash = ? WHERE id = ? AND BINARY password_hash = ?',
            [$newHash, $account['id'], $account['password_hash']])->rowCount();
        if ($changed !== 1) {
            throw new StaffValidation('The account changed. Use another recovery code or server-assisted recovery.');
        }
    } finally {
        fclose($file);
    }
    finishPasswordRecovery();
}
