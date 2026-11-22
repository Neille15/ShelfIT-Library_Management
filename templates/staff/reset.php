<?php
declare(strict_types=1);
pageStart('Reset password', '../', true);
?>
<section class="hero"><h1>Reset password</h1></section>
<?php if ($canReset): ?>
    <form method="post" action="process.php" class="panel stack narrow" data-validate>
        <?php staffForm('password-reset'); ?>
        <?php if ($error): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
        <p>Use at least 12 characters and at most 72 bytes.</p>
        <label>New password <input type="password" name="new_password" autocomplete="new-password" minlength="12" required></label>
        <label>Confirm password <input type="password" name="confirm_password" autocomplete="new-password" minlength="12" required></label>
        <div><button class="primary" type="submit">Update password</button></div>
        <p><a href="index.php">Back to sign in</a></p>
    </form>
<?php else: ?>
    <section class="panel stack narrow">
        <p class="error" role="alert">This recovery link is invalid or expired. Generate a new link from the server terminal.</p>
        <p><a href="forgot.php">Password recovery instructions</a></p>
        <p><a href="index.php">Back to sign in</a></p>
    </section>
<?php endif; ?>
<?php pageEnd(); ?>
