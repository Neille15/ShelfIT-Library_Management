<?php
declare(strict_types=1);

function copyForm(int $studyId, array $copy, array $layout, bool $open = false): void
{
    $copyId = $copy['id'] ?? null;
    ?>
    <details class="panel" <?= $open ? 'open' : '' ?>>
        <summary><?= $copyId ? h($copy['copy_code']) : 'Add physical copy' ?></summary>
        <form method="post" class="stack detail-body" data-location-form data-validate>
            <?php staffForm('copy-save'); ?>
            <input type="hidden" name="study_id" value="<?= h($studyId) ?>">
            <input type="hidden" name="copy_id" value="<?= h($copyId) ?>">
            <?php if ($copyId): ?><p class="muted"><?= h(locationText($copy)) ?></p><?php endif; ?>
            <label>Copy ID <input name="copy_code" required maxlength="80" value="<?= h($copy['copy_code'] ?? '') ?>"></label>
            <p class="muted">Leave all three location fields blank when the shelf is unverified.</p>
            <div class="fields location-fields">
                <label>Section
                    <select name="section_id">
                        <option value="">No section</option>
                        <?php foreach ($layout['sections'] as $section): ?>
                            <option value="<?= h($section['id']) ?>" <?= $section['id'] === ($copy['section_id'] ?? null) ? 'selected' : '' ?>>
                                <?= h($section['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Cabinet
                    <select name="cabinet_id">
                        <option value="">No cabinet</option>
                        <?php foreach ($layout['cabinets'] as $cabinet): ?>
                            <option data-parent="<?= h($cabinet['section_id']) ?>" value="<?= h($cabinet['id']) ?>"
                                <?= $cabinet['id'] === ($copy['cabinet_id'] ?? null) ? 'selected' : '' ?>>
                                <?= h($cabinet['section_label']) ?> → <?= h($cabinet['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Shelf
                    <select name="shelf_id">
                        <option value="">No shelf</option>
                        <?php foreach ($layout['shelves'] as $shelf): ?>
                            <option data-parent="<?= h($shelf['cabinet_id']) ?>" value="<?= h($shelf['id']) ?>"
                                <?= $shelf['id'] === ($copy['shelf_id'] ?? null) ? 'selected' : '' ?>>
                                <?= h($shelf['section_label']) ?> → <?= h($shelf['cabinet_label']) ?> → <?= h($shelf['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div><button class="primary" type="submit"><?= $copyId ? 'Save copy' : 'Add copy' ?></button></div>
        </form>
        <?php if ($copyId): ?>
            <form method="post" class="detail-body" data-confirm="Remove this copy from the catalog?">
                <?php staffForm('copy-delete'); ?>
                <input type="hidden" name="study_id" value="<?= h($studyId) ?>">
                <input type="hidden" name="copy_id" value="<?= h($copyId) ?>">
                <button type="submit" class="danger">Remove copy</button>
            </form>
        <?php endif; ?>
    </details>
    <?php
}

function locationForm(string $kind, array $layout, array $record = []): void
{
    [$label, $parentColumn, $parentTable] = LOCATION_TYPES[$kind];
    $recordId = $record['id'] ?? null;
    $parents = $parentTable ? $layout[$parentTable] : [];
    ?>
    <details class="panel" <?= !$recordId && !$layout[$kind] ? 'open' : '' ?>>
        <summary>
            <?= $recordId ? h(implode(' → ', array_filter([$record['section_label'] ?? '', $record['cabinet_label'] ?? '', $record['label']], static fn ($part) => $part !== ''))) : 'Add ' . h(strtolower($label)) ?>
        </summary>
        <?php if (!$parentTable || $parents): ?>
            <form method="post" class="stack detail-body" data-validate>
                <?php staffForm('location-save'); ?>
                <input type="hidden" name="kind" value="<?= h($kind) ?>">
                <input type="hidden" name="record_id" value="<?= h($recordId) ?>">
                <?php if ($parentTable): ?>
                    <label><?= h(LOCATION_TYPES[$parentTable][0]) ?>
                        <select name="parent_id" required>
                            <option value="">Select a location</option>
                            <?php foreach ($parents as $parent): ?>
                                <?php $parentLabel = isset($parent['section_label']) ? $parent['section_label'] . ' → ' . $parent['label'] : $parent['label']; ?>
                                <option value="<?= h($parent['id']) ?>" <?= $parent['id'] === ($record[$parentColumn] ?? null) ? 'selected' : '' ?>>
                                    <?= h($parentLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <div class="fields two-fields">
                    <label><?= h($label) ?> name <input name="label" required maxlength="100" value="<?= h($record['label'] ?? '') ?>"></label>
                    <label>Sort order <input name="sort_order" type="number" min="0" max="65535" value="<?= h($record['sort_order'] ?? 0) ?>" required></label>
                </div>
                <div><button class="primary" type="submit"><?= $recordId ? 'Save ' : 'Add ' ?><?= h(strtolower($label)) ?></button></div>
            </form>
        <?php else: ?>
            <p class="detail-body">Add a <?= h(strtolower(LOCATION_TYPES[$parentTable][0])) ?> first.</p>
        <?php endif; ?>
        <?php if ($recordId): ?>
            <form method="post" class="detail-body" data-confirm="Remove this location?">
                <?php staffForm('location-delete'); ?>
                <input type="hidden" name="kind" value="<?= h($kind) ?>">
                <input type="hidden" name="record_id" value="<?= h($recordId) ?>">
                <button type="submit" class="danger">Remove <?= h(strtolower($label)) ?></button>
            </form>
        <?php endif; ?>
    </details>
    <?php
}

$headings = ['studies' => 'Manage studies', 'study' => 'Study record', 'tags' => 'Manage subjects', 'locations' => 'Manage shelf locations'];
$studyId = id($_GET['id'] ?? null);
$record = [];
$studyMissing = false;
if ($view === 'study' && $studyId) {
    $record = query('SELECT * FROM studies WHERE id = ?', [$studyId])->fetch() ?: [];
    $studyMissing = !$record;
    if ($studyMissing) {
        http_response_code(404);
    }
}
pageStart($headings[$view], '../', true);
?>
<nav class="staff-nav" aria-label="Staff tools">
    <a class="button" href="<?= h(staffUrl()) ?>" <?= in_array($view, ['studies', 'study'], true) ? 'aria-current="page"' : '' ?>>Studies</a>
    <a class="button" href="<?= h(staffUrl('tags')) ?>" <?= $view === 'tags' ? 'aria-current="page"' : '' ?>>Subjects</a>
    <a class="button" href="<?= h(staffUrl('locations')) ?>" <?= $view === 'locations' ? 'aria-current="page"' : '' ?>>Shelf locations</a>
    <form method="post"><?php staffForm('logout'); ?><button type="submit">Sign out</button></form>
</nav>
<section class="hero"><h1><?= h($headings[$view]) ?></h1></section>
<?php if ($error): ?><p role="alert" class="error"><?= h($error) ?></p><?php endif; ?>
<?php if ($notice): ?><p role="status" class="notice"><?= h($notice) ?></p><?php endif; ?>

<?php if ($view === 'studies'): ?>
    <?php
    $includeArchived = input($_GET, 'archived') === '1';
    $state = $includeArchived ? 'archived_at IS NOT NULL' : 'archived_at IS NULL';
    $staffStudies = query("SELECT id, title, authors, archived_at FROM studies WHERE $state ORDER BY academic_year DESC, title, id")->fetchAll();
    ?>
    <div class="actions">
        <a class="button primary" href="<?= h(staffUrl('study')) ?>">Add study</a>
        <a class="button" href="<?= h(staffUrl('studies', $includeArchived ? [] : ['archived' => 1])) ?>">
            <?= $includeArchived ? 'Show active studies' : 'Show archived studies' ?>
        </a>
    </div>
    <?php if (!$staffStudies): ?><p class="panel">No <?= $includeArchived ? 'archived' : 'active' ?> studies.</p><?php endif; ?>
    <ul class="results">
        <?php foreach ($staffStudies as $study): ?>
            <li class="panel result">
                <div><h2><?= h($study['title']) ?></h2><p class="muted"><?= h($study['authors']) ?></p></div>
                <a class="button" href="<?= h(staffUrl('study', ['id' => $study['id']])) ?>">Edit study</a>
            </li>
        <?php endforeach; ?>
    </ul>

<?php elseif ($view === 'study' && $studyMissing): ?>
    <p class="panel">That study could not be found. <a href="<?= h(staffUrl()) ?>">Back to studies</a></p>

<?php elseif ($view === 'study'): ?>
    <?php
    $tags = query('SELECT id, name FROM tags ORDER BY name')->fetchAll();
    $assigned = $studyId ? array_column(studyTags($studyId), 'id') : [];
    $fields = $record;
    if ($error && $action === 'study-save') {
        $fields = [];
        foreach (['title', 'authors', 'adviser', 'program', 'academic_year', 'study_type', 'summary'] as $field) {
            $fields[$field] = input($_POST, $field);
        }
        $assigned = is_array($_POST['tags'] ?? null) ? array_map('id', $_POST['tags']) : [];
    }
    ?>
    <div class="split">
        <section class="stack">
            <h2><?= $studyId ? 'Edit study details' : 'Add study' ?></h2>
            <form method="post" class="panel stack" data-validate>
                <?php staffForm('study-save'); ?>
                <input type="hidden" name="study_id" value="<?= h($studyId ?: null) ?>">
                <label>Title <input name="title" maxlength="255" value="<?= h($fields['title'] ?? '') ?>" required></label>
                <label>Authors <input name="authors" maxlength="500" value="<?= h($fields['authors'] ?? '') ?>" required></label>
                <label>Adviser <input name="adviser" maxlength="255" value="<?= h($fields['adviser'] ?? '') ?>"></label>
                <label>Degree program <input name="program" maxlength="120" value="<?= h($fields['program'] ?? '') ?>" required></label>
                <div class="fields two-fields">
                    <label>Academic year starts in <input name="academic_year" type="number" min="1900" max="2199" placeholder="2025"
                        value="<?= h($fields['academic_year'] ?? '') ?>" required></label>
                    <label>Study type <select name="study_type" required>
                        <option value="">Choose a study type</option>
                        <?php foreach (STUDY_TYPES as $type): ?>
                            <option <?= $type === ($fields['study_type'] ?? '') ? 'selected' : '' ?>><?= h($type) ?></option>
                        <?php endforeach; ?>
                    </select></label>
                </div>
                <label>Abstract or summary <textarea name="summary" maxlength="5000" rows="4"><?= h($fields['summary'] ?? '') ?></textarea></label>
                <?php if ($tags): ?>
                    <fieldset><legend>Subjects</legend><div class="choices">
                        <?php foreach ($tags as $tag): ?>
                            <label><input type="checkbox" name="tags[]" value="<?= h($tag['id']) ?>"
                                <?= in_array($tag['id'], $assigned, true) ? 'checked' : '' ?>> <?= h($tag['name']) ?></label>
                        <?php endforeach; ?>
                    </div></fieldset>
                <?php else: ?>
                    <p class="muted">You can add subject tags from <a href="<?= h(staffUrl('tags')) ?>">Subjects</a>.</p>
                <?php endif; ?>
                <div><button class="primary" type="submit">Save study</button></div>
            </form>
            <?php if ($studyId): ?>
                <div class="actions">
                    <?php if ($record['archived_at'] === null): ?>
                        <a class="button" href="<?= h(url('../study.php', ['id' => $studyId])) ?>">View in catalog</a>
                    <?php endif; ?>
                    <form method="post">
                        <?php staffForm($record['archived_at'] === null ? 'study-archive' : 'study-restore'); ?>
                        <input type="hidden" name="study_id" value="<?= h($studyId) ?>">
                        <button type="submit"><?= $record['archived_at'] === null ? 'Archive study' : 'Restore to catalog' ?></button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
        <section class="stack">
            <h2>Physical copies</h2>
            <?php if ($studyId): ?>
                <?php $layout = locations(); ?>
                <?php $copies = studyCopies($studyId); ?>
                <?php foreach ($copies as $copy) copyForm($studyId, $copy, $layout, $error && $action === 'copy-save' && $copy['id'] === id($_POST['copy_id'] ?? null)); ?>
                <?php copyForm($studyId, [], $layout, !$copies || ($error && $action === 'copy-save' && !id($_POST['copy_id'] ?? null))); ?>
            <?php else: ?>
                <p class="panel">Save the study to start adding physical copies.</p>
            <?php endif; ?>
        </section>
    </div>

<?php elseif ($view === 'tags'): ?>
    <form method="post" class="panel stack narrow" data-validate>
        <?php staffForm('tag-save'); ?>
        <label>Subject name <input name="name" maxlength="100" required></label>
        <div><button class="primary" type="submit">Add subject</button></div>
    </form>
    <div class="stack">
        <?php foreach (query('SELECT id, name FROM tags ORDER BY name')->fetchAll() as $tag): ?>
            <details class="panel">
                <summary><?= h($tag['name']) ?></summary>
                <form method="post" class="stack detail-body" data-validate>
                    <?php staffForm('tag-save'); ?>
                    <input type="hidden" name="record_id" value="<?= h($tag['id']) ?>">
                    <label>Subject name <input name="name" maxlength="100" value="<?= h($tag['name']) ?>" required></label>
                    <div><button class="primary" type="submit">Save subject</button></div>
                </form>
                <form method="post" class="detail-body" data-confirm="Remove this subject and its study assignments?">
                    <?php staffForm('tag-delete'); ?>
                    <input type="hidden" name="record_id" value="<?= h($tag['id']) ?>">
                    <button class="danger" type="submit">Remove subject</button>
                </form>
            </details>
        <?php endforeach; ?>
    </div>

<?php elseif ($view === 'locations'): ?>
    <?php $layout = locations(); ?>
    <p>Configure sections first, then cabinets and shelves. Lower sort numbers appear first.</p>
    <div class="location-columns">
        <?php foreach (['sections' => 'Sections', 'cabinets' => 'Cabinets', 'shelves' => 'Shelves'] as $kind => $title): ?>
            <section class="stack">
                <h2><?= h($title) ?></h2>
                <?php locationForm($kind, $layout); ?>
                <?php foreach ($layout[$kind] as $location) locationForm($kind, $layout, $location); ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php pageEnd(); ?>
