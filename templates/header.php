    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?> | ShelfIT</title>
        <link rel="stylesheet" href="<?= h($base) ?>assets/shelfit.css?v=<?= h($cssVersion) ?>">
        <script defer src="<?= h($base) ?>assets/shelfit.js?v=<?= h($jsVersion) ?>"></script>
    </head>
    <body>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header">
        <div class="shell site-bar">
            <a class="brand" href="<?= h($base) ?>index.php">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span><strong>ShelfIT</strong><small>PLV CEIT Library</small></span>
            </a>
            <nav aria-label="Main navigation">
                <a href="<?= h($base) ?>index.php" <?= $staff ? '' : 'aria-current="page"' ?>>Catalog</a>
                <a href="<?= h($base) ?>staff/index.php" <?= $staff ? 'aria-current="page"' : '' ?>>Staff editor</a>
                <?php if ($account !== null): ?>
                    <details class="account-dropdown" data-account-dropdown>
                        <summary>Account <span aria-hidden="true">▾</span></summary>
                        <div class="account-dropdown-panel">
                            <a href="<?= h($base) ?>staff/index.php?view=account" <?= input($_GET, 'view') === 'account' ? 'aria-current="page"' : '' ?>>Your account</a>
                            <form method="post" action="<?= h($base) ?>staff/process.php">
                                <?php staffForm('logout'); ?>
                                <button type="submit">Sign out</button>
                            </form>
                        </div>
                    </details>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="shell page" id="main">
