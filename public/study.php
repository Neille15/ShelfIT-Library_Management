<?php
declare(strict_types=1);

require __DIR__ . '/../app/catalog.php';

$studyId = id($_GET['id'] ?? null);
$study = query('SELECT * FROM studies WHERE id = ? AND archived_at IS NULL', [$studyId])->fetch();
if (!$study) {
    http_response_code(404);
    pageStart('Study unavailable');
    echo '<section class="hero"><h1>Study unavailable</h1><p><a href="index.php">Return to the catalog</a></p></section>';
    pageEnd();
    exit;
}

$filters = catalogFilters($_GET);
$tags = studyTags($studyId);
$copies = array_column(studyCopies($studyId), null, 'id');
$selected = $copies[id($_GET['copy'] ?? null)] ?? reset($copies) ?: null;
$layout = locations();

pageStart($study['title']);
?>
<div><a class="button" href="<?= h(url('index.php', $filters)) ?>">← Back to results</a></div>
<section class="hero">
    <h1><?= h($study['title']) ?></h1>
    <p><?= h($study['authors']) ?></p>
    <p class="muted"><?= h($study['program']) ?> · <?= h(academicYear($study['academic_year'])) ?> · <?= h($study['study_type']) ?></p>
</section>
<div class="split">
    <section class="panel stack">
        <h2>Study details</h2>
        <?php if ($study['adviser'] !== ''): ?><p><strong>Adviser</strong> <?= h($study['adviser']) ?></p><?php endif; ?>
        <?php if ($study['summary'] !== ''): ?><p class="summary"><?= h($study['summary']) ?></p><?php endif; ?>
        <?php if ($tags): ?>
            <ul class="tags" aria-label="Subjects">
                <?php foreach ($tags as $tag): ?>
                    <li><a href="<?= h(url('index.php', ['tag' => $tag['id']])) ?>"><?= h($tag['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($study['adviser'] === '' && $study['summary'] === '' && !$tags): ?>
            <p class="muted">No additional catalog details recorded.</p>
        <?php endif; ?>
    </section>
    <section class="panel stack">
        <h2>Find a physical copy</h2>
        <?php if ($selected): ?>
            <form method="get" class="stack" data-copy-picker>
                <?php foreach (['id' => $studyId, ...$filters] as $name => $value): ?>
                    <?php if ($value !== '' && $value !== null): ?>
                        <input type="hidden" name="<?= h($name) ?>" value="<?= h($value) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <label>Physical copy
                    <select name="copy">
                        <?php foreach ($copies as $copy): ?>
                            <option value="<?= h($copy['id']) ?>" <?= $copy['id'] === $selected['id'] ? 'selected' : '' ?>><?= h($copy['copy_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div><button type="submit">Show shelf</button></div>
            </form>
        <?php endif; ?>
        <div id="copy-location" class="stack" aria-live="polite"><?php drawLocator($selected, $layout); ?></div>
        <?php if (count($copies) > 1): ?>
            <?php foreach ($copies as $copy): ?>
                <template id="copy-location-<?= h($copy['id']) ?>"><?php drawLocator($copy, $layout); ?></template>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
<?php pageEnd(); ?>
