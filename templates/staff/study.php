    <?php
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
            <form method="post" action="<?= h(staffProcessUrl($view, $studyId)) ?>" class="panel stack" data-validate>
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
                    <form method="post" action="<?= h(staffProcessUrl($view, $studyId)) ?>">
                        <?php staffForm($record['archived_at'] === null ? 'study-archive' : 'study-restore'); ?>
                        <input type="hidden" name="study_id" value="<?= h($studyId) ?>">
                        <button type="submit"><?= $record['archived_at'] === null ? 'Archive study' : 'Restore to catalog' ?></button>
                    </form>
                </div>
                <?php if ($record['archived_at'] !== null): ?>
                    <details class="panel">
                        <summary>Delete permanently</summary>
                        <form method="post" action="<?= h(staffProcessUrl($view, $studyId)) ?>" class="stack detail-body">
                            <?php staffForm('study-delete'); ?>
                            <input type="hidden" name="study_id" value="<?= h($studyId) ?>">
                            <p>This permanently removes the study and its physical copy records. This action cannot be undone.</p>
                            <div><button class="danger" type="submit">Permanently delete study</button></div>
                        </form>
                    </details>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        <section class="stack">
            <h2>Physical copies</h2>
            <?php if ($studyId): ?>
                <?php foreach ($copies as $copy) copyForm($studyId, $copy, $layout, $error && $action === 'copy-save' && $copy['id'] === id($_POST['copy_id'] ?? null)); ?>
                <?php copyForm($studyId, [], $layout, !$copies || ($error && $action === 'copy-save' && !id($_POST['copy_id'] ?? null))); ?>
            <?php else: ?>
                <p class="panel">Save the study to start adding physical copies.</p>
            <?php endif; ?>
        </section>
    </div>
