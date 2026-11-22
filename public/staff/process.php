<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/staff.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

startStaffSession();
$account = signedInStaff();
$action = input($_POST, 'action');
$error = '';
if (!$account && !in_array($action, ['login', 'password-reset', 'recovery-code-reset'], true)) {
    http_response_code(403);
    $error = 'Sign in to make changes.';
} elseif (!validCsrf()) {
    http_response_code(403);
    $error = 'This form expired. Reload the page and try again.';
} elseif (in_array($action, ['password-reset', 'recovery-code-reset'], true)) {
    try {
        if ($action === 'recovery-code-reset') {
            resetWithRecoveryCode();
        }
        resetStaffPassword();
    } catch (StaffValidation $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    }
} elseif (!$account) {
    $error = attemptStaffLogin();
} else {
    try {
        handleStaffAction($action, $account);
    } catch (StaffValidation $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        $error = match ($exception->errorInfo[1] ?? null) {
            1062 => 'That name is already in use at this level.',
            1451 => 'This location is in use. Move its copies or child locations before removing it.',
            1452 => 'A related record changed. Reload the page and select it again.',
            default => throw $exception,
        };
        http_response_code(422);
    }
}

if ($action === 'recovery-code-reset') {
    header('Referrer-Policy: no-referrer');
    require __DIR__ . '/../../templates/staff/forgot.php';
    exit;
}

if ($action === 'password-reset') {
    header('Referrer-Policy: no-referrer');
    $canReset = validPasswordReset($_SESSION['password_reset_token'] ?? '');
    require __DIR__ . '/../../templates/staff/reset.php';
    exit;
}

if (!$account) {
    require __DIR__ . '/../../templates/staff/login.php';
    exit;
}
renderStaffPage($account, $error, $action);
