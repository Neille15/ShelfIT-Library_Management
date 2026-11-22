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
    <section class="panel stack narrow">
        <h2>Password recovery codes</h2>
        <p>You have <?= h($recoveryCount) ?> unused recovery codes. Save a set now so you can reset a forgotten password without the server terminal.</p>
        <p>Generating a new set replaces every previous code. The new codes are shown only once.</p>
        <form method="post" action="<?= h(staffProcessUrl('account')) ?>" class="stack" data-validate>
            <?php staffForm('recovery-codes-generate'); ?>
            <label>Current password to generate recovery codes
                <input type="password" name="current_password" autocomplete="current-password" required>
            </label>
            <div><button type="submit">Generate new recovery codes</button></div>
        </form>
    </section>
