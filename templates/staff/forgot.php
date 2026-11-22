<?php
declare(strict_types=1);
pageStart('Forgot password', '../', true);
?>
<section class="hero"><h1>Forgot password?</h1></section>
<form method="post" action="process.php" class="panel stack narrow" data-validate>
    <?php staffForm('recovery-code-reset'); ?>
    <p>Use an unused recovery code saved from your Account page to choose a new password.</p>
    <?php if ($error): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <label>Username <input name="username" autocomplete="username" maxlength="64" value="<?= h(input($_POST, 'username')) ?>" required></label>
    <label>Recovery code <input name="recovery_code" autocomplete="off" spellcheck="false" autocapitalize="characters" maxlength="64" required></label>
    <label>New password <input type="password" name="new_password" autocomplete="new-password" minlength="12" required></label>
    <label>Confirm password <input type="password" name="confirm_password" autocomplete="new-password" minlength="12" required></label>
    <p class="muted">Use at least 12 characters and at most 72 bytes. Each recovery code works once.</p>
    <div><button class="primary" type="submit">Update password</button></div>
    <p><a href="index.php">Back to sign in</a></p>
</form>
<details class="panel narrow">
    <summary>Lost your recovery codes?</summary>
    <p>Ask the person who manages this server to generate a private recovery link for your staff username. If you manage the server, follow the password recovery steps in the project README.</p>
</details>
<?php pageEnd(); ?>
