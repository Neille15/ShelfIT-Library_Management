<?php
declare(strict_types=1);

function copyForm(int $studyId, array $copy, array $layout, bool $open = false): void
{
    $copyId = $copy['id'] ?? null;
    if (input($_POST, 'action') === 'copy-save' && id($_POST['copy_id'] ?? null) === ($copyId ?? 0)) {
        $copy['copy_code'] = input($_POST, 'copy_code');
        foreach (['section_id', 'cabinet_id', 'shelf_id'] as $field) {
            $copy[$field] = id($_POST[$field] ?? null) ?: null;
        }
    }
    ?>
    <details class="panel" <?= $open ? 'open' : '' ?>>
        <summary><?= $copyId ? h($copy['copy_code']) : 'Add physical copy' ?></summary>
        <form method="post" action="<?= h(staffProcessUrl('study', $studyId)) ?>" class="stack detail-body" data-location-form data-validate>
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
            <form method="post" action="<?= h(staffProcessUrl('study', $studyId)) ?>" class="detail-body" data-confirm="Remove this copy from the catalog?">
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
    $failed = input($_POST, 'action') === 'location-save' && input($_POST, 'kind') === $kind
        && id($_POST['record_id'] ?? null) === ($recordId ?? 0);
    if ($failed) {
        $record['label'] = input($_POST, 'label');
        $record['sort_order'] = input($_POST, 'sort_order');
        if ($parentColumn) {
            $record[$parentColumn] = id($_POST['parent_id'] ?? null);
        }
    }
    $parents = $parentTable ? $layout[$parentTable] : [];
    ?>
    <details class="panel" <?= $failed || (!$recordId && !$layout[$kind]) ? 'open' : '' ?>>
        <summary>
            <?= $recordId ? h(implode(' → ', array_filter([$record['section_label'] ?? '', $record['cabinet_label'] ?? '', $record['label']], static fn ($part) => $part !== ''))) : 'Add ' . h(strtolower($label)) ?>
        </summary>
        <?php if (!$parentTable || $parents): ?>
            <form method="post" action="<?= h(staffProcessUrl('locations')) ?>" class="stack detail-body" data-validate>
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
            <form method="post" action="<?= h(staffProcessUrl('locations')) ?>" class="detail-body" data-confirm="Remove this location?">
                <?php staffForm('location-delete'); ?>
                <input type="hidden" name="kind" value="<?= h($kind) ?>">
                <input type="hidden" name="record_id" value="<?= h($recordId) ?>">
                <button type="submit" class="danger">Remove <?= h(strtolower($label)) ?></button>
            </form>
        <?php endif; ?>
    </details>
    <?php
}
