    <form method="post" action="<?= h(staffProcessUrl($view, $studyId)) ?>" class="panel stack narrow" data-validate>
        <?php staffForm('account-save'); ?>
        <label>Username
            <input name="username" autocomplete="username" minlength="3" maxlength="64"
                value="<?= h($error && $action === 'account-save' ? input($_POST, 'username') : $account['username']) ?>" required>
        </label>
        <label>Current password
            <input type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <fieldset class="stack">
            <legend>Change password</legend>
            <p class="muted">Use at least 12 characters. Leave both fields blank to keep your current password.</p>
            <label>New password <input type="password" name="new_password" autocomplete="new-password" minlength="12"></label>
            <label>Confirm new password <input type="password" name="confirm_password" autocomplete="new-password" minlength="12"></label>
        </fieldset>
        <div><button class="primary" type="submit">Save account</button></div>
    </form>
