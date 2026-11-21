<?php
declare(strict_types=1);

    pageStart('Staff sign in', '../', true);
    ?>
    <section class="hero"><h1>Staff sign in</h1></section>
    <form method="post" action="process.php" class="panel stack narrow" data-validate>
        <?php staffForm('login'); ?>
        <?php if ($error): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
        <label>Username <input name="username" autocomplete="username" maxlength="64" value="<?= h(input($_POST, 'username')) ?>" required></label>
        <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
        <div><button class="primary" type="submit">Sign in</button></div>
    </form>
    <?php
    pageEnd();
