    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?> | ShelfIT</title>
        <link rel="stylesheet" href="<?= h($base) ?>assets/shelfit.css">
        <script defer src="<?= h($base) ?>assets/shelfit.js"></script>
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
            </nav>
        </div>
    </header>
    <main class="shell page" id="main">
