<?php
declare(strict_types=1);

    pageStart('Staff sign in', '../', true);
    $success = $_SESSION['password_reset_notice'] ?? '';
    unset($_SESSION['password_reset_notice']);
    ?>
    <section class="hero"><h1>Staff sign in</h1></section>
    <form method="post" action="process.php" class="panel stack narrow" data-validate>
        <?php staffForm('login'); ?>
        <?php if ($success): ?><p class="notice" role="status"><?= h($success) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
        <label>Username <input name="username" autocomplete="username" maxlength="64" value="<?= h(input($_POST, 'username')) ?>" required></label>
        <div class="stack password-control">
            <label for="login-password">Password</label>
            <div class="password-field">
                <input id="login-password" type="password" name="password" autocomplete="current-password" required>
                <button type="button" data-password-toggle="login-password" aria-controls="login-password" aria-label="Show password" aria-pressed="false" hidden>Show</button>
            </div>
        </div>
        <div><button class="primary" type="submit">Sign in</button></div>
        <p><a href="forgot.php">Forgot password?</a></p>
    </form>
    <?php
    pageEnd();
