<?php
declare(strict_types=1);

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

function staffUsernameIsValid(string $username): bool
{
    return preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{2,63}\z/', $username) === 1;
}

function staffPasswordIsValid(string $password): bool
{
    return strlen($password) <= 72 && !str_contains($password, "\0")
        && mb_check_encoding($password, 'UTF-8') && mb_strlen($password) >= 12;
}

function signInStaff(array $account): void
{
    session_regenerate_id(true);
    $_SESSION = [
        'staff_id' => $account['id'],
        'staff_password_stamp' => hash('sha256', $account['password_hash']),
        'csrf' => bin2hex(random_bytes(32)),
    ];
}

function signedInStaff(): ?array
{
    if (!isset($_SESSION['staff_id'])) {
        return null;
    }
    $account = query('SELECT id, username, password_hash FROM staff_users WHERE id = ?', [$_SESSION['staff_id']])->fetch();
    $stamp = $_SESSION['staff_password_stamp'] ?? '';
    if ($account && hash_equals(hash('sha256', $account['password_hash']), $stamp)) {
        return $account;
    }
    session_regenerate_id(true);
    $_SESSION = ['csrf' => bin2hex(random_bytes(32))];
    return null;
}

function validCsrf(): bool
{
    return hash_equals($_SESSION['csrf'], input($_POST, 'csrf'));
}

function saveStaffAccount(array $account): never
{
    $currentPassword = $_POST['current_password'] ?? '';
    if (!is_string($currentPassword) || strlen($currentPassword) > 72
        || str_contains($currentPassword, "\0") || !password_verify($currentPassword, $account['password_hash'])) {
        throw new StaffValidation('The current password is incorrect.');
    }

    $username = input($_POST, 'username');
    if (!staffUsernameIsValid($username)) {
        throw new StaffValidation('Use 3–64 letters, digits, dots, underscores, or hyphens for the username. Start with a letter or digit.');
    }

    $newPassword = $_POST['new_password'] ?? '';
    $confirmation = $_POST['confirm_password'] ?? '';
    if (!is_string($newPassword) || !is_string($confirmation) || $newPassword !== $confirmation) {
        throw new StaffValidation('The new passwords do not match.');
    }
    if ($newPassword !== '' && !staffPasswordIsValid($newPassword)) {
        throw new StaffValidation('Use a new password with at least 12 characters and at most 72 bytes.');
    }
    if ($username === $account['username'] && $newPassword === '') {
        staffDone('No account changes to save.', staffUrl('account'));
    }

    $oldHash = $account['password_hash'];
    $newHash = $newPassword === '' ? $oldHash : password_hash($newPassword, PASSWORD_DEFAULT);
    try {
        $updated = query('UPDATE staff_users SET username = ?, password_hash = ?
            WHERE id = ? AND BINARY username = ? AND BINARY password_hash = ?',
            [$username, $newHash, $account['id'], $account['username'], $oldHash])->rowCount();
    } catch (PDOException $error) {
        if (($error->errorInfo[1] ?? null) === 1062) {
            throw new StaffValidation('That username is already in use.');
        }
        throw $error;
    }
    if (!$updated) {
        throw new StaffValidation('Your account changed. Reload the page and try again.');
    }
    $account['password_hash'] = $newHash;
    signInStaff($account);
    staffDone('Account updated.', staffUrl('account'));
}


function attemptStaffLogin(): string
{
    $username = input($_POST, 'username');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (($_SESSION['retry_after'] ?? 0) > time()) {
        return 'Too many sign-in attempts. Try again in a minute.';
    } else {
        $user = query('SELECT id, password_hash FROM staff_users WHERE username = ?', [$username])->fetch();
        $hash = $user['password_hash'] ?? '$2y$12$Xv32lcY1UK0c36JKNi3jduNiTaLJVY3Yzb1Yw8lBQXgR8aV.hO9f2';
        if (strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $hash) && $user) {
            signInStaff($user);
            redirect('index.php');
        }
        $attempts = (int) ($_SESSION['failed_logins'] ?? 0) + 1;
        $_SESSION['failed_logins'] = $attempts % 5;
        if ($attempts >= 5) {
            $_SESSION['retry_after'] = time() + 60;
        }
        return 'The username or password is incorrect.';
    }
}
