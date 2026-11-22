<?php
declare(strict_types=1);

$headings = [
    'studies' => 'Manage studies',
    'study' => 'Study record',
    'tags' => 'Manage subjects',
    'locations' => 'Manage shelf locations',
    'account' => 'Your account',
];
pageStart($headings[$view], '../', true, $account);
?>
<nav class="staff-nav" aria-label="Staff tools">
    <a class="button" href="<?= h(staffUrl()) ?>" <?= in_array($view, ['studies', 'study'], true) ? 'aria-current="page"' : '' ?>>Studies</a>
    <a class="button" href="<?= h(staffUrl('tags')) ?>" <?= $view === 'tags' ? 'aria-current="page"' : '' ?>>Subjects</a>
    <a class="button" href="<?= h(staffUrl('locations')) ?>" <?= $view === 'locations' ? 'aria-current="page"' : '' ?>>Shelf locations</a>
</nav>
<section class="hero"><h1><?= h($headings[$view]) ?></h1></section>
<?php if ($error): ?><p role="alert" class="error"><?= h($error) ?></p><?php endif; ?>
<?php if ($notice): ?><p role="status" class="notice"><?= h($notice) ?></p><?php endif; ?>

<?php
switch ($view) {
    case 'studies':
        require __DIR__ . '/studies.php';
        break;
    case 'study':
        if ($studyMissing) {
            require __DIR__ . '/missing.php';
        } else {
            require __DIR__ . '/study.php';
        }
        break;
    case 'tags':
        require __DIR__ . '/subjects.php';
        break;
    case 'account':
        require __DIR__ . '/account.php';
        break;
    case 'locations':
        require __DIR__ . '/locations.php';
        break;
}
pageEnd();
