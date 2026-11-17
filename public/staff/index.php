<?php
declare(strict_types=1);

require __DIR__ . '/../../app/staff.php';

startStaffSession();
$isStaff = isset($_SESSION['staff_id']);
$action = input($_POST, 'action');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isStaff && $action !== 'login') {
        http_response_code(403);
        $error = 'Sign in to make changes.';
    } elseif (!validCsrf()) {
        http_response_code(403);
        $error = 'This form expired. Reload the page and try again.';
    } elseif (!$isStaff) {
        $username = input($_POST, 'username');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if (($_SESSION['retry_after'] ?? 0) > time()) {
            $error = 'Too many sign-in attempts. Try again in a minute.';
        } else {
            $user = query('SELECT id, password_hash FROM staff_users WHERE username = ?', [$username])->fetch();
            $hash = $user['password_hash'] ?? '$2y$12$Xv32lcY1UK0c36JKNi3jduNiTaLJVY3Yzb1Yw8lBQXgR8aV.hO9f2';
            if (strlen($password) <= 72 && password_verify($password, $hash) && $user) {
                session_regenerate_id(true);
                $_SESSION = ['staff_id' => $user['id'], 'csrf' => bin2hex(random_bytes(32))];
                redirect('index.php');
            }
            $attempts = (int) ($_SESSION['failed_logins'] ?? 0) + 1;
            $_SESSION['failed_logins'] = $attempts % 5;
            if ($attempts >= 5) {
                $_SESSION['retry_after'] = time() + 60;
            }
            $error = 'The username or password is incorrect.';
        }
    } else {
        try {
            handleStaffAction($action);
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
}

if (!$isStaff) {
    pageStart('Staff sign in', '../', true);
    ?>
    <section class="hero"><h1>Staff sign in</h1></section>
    <form method="post" class="panel stack narrow" data-validate>
        <?php staffForm('login'); ?>
        <?php if ($error): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
        <label>Username <input name="username" autocomplete="username" maxlength="64" value="<?= h(input($_POST, 'username')) ?>" required></label>
        <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
        <div><button class="primary" type="submit">Sign in</button></div>
    </form>
    <?php
    pageEnd();
    exit;
}

$view = input($_GET, 'view');
$view = in_array($view, ['studies', 'study', 'tags', 'locations'], true) ? $view : 'studies';
$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);
require __DIR__ . '/../../app/staff-views.php';
