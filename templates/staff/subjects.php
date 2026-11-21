    <form method="post" action="<?= h(staffProcessUrl($view, $studyId)) ?>" class="panel stack narrow" data-validate>
        <?php staffForm('tag-save'); ?>
        <label>Subject name <input name="name" maxlength="100" value="<?= h($error && $action === 'tag-save' && !id($_POST['record_id'] ?? null) ? input($_POST, 'name') : '') ?>" required></label>
        <div><button class="primary" type="submit">Add subject</button></div>
    </form>
    <div class="stack">
        <?php foreach ($tags as $tag): ?>
            <details class="panel" <?= $error && $action === 'tag-save' && id($_POST['record_id'] ?? null) === $tag['id'] ? 'open' : '' ?>>
                <summary><?= h($tag['name']) ?></summary>
                <form method="post" action="<?= h(staffProcessUrl($view, $studyId)) ?>" class="stack detail-body" data-validate>
                    <?php staffForm('tag-save'); ?>
                    <input type="hidden" name="record_id" value="<?= h($tag['id']) ?>">
                    <label>Subject name <input name="name" maxlength="100" value="<?= h($error && $action === 'tag-save' && id($_POST['record_id'] ?? null) === $tag['id'] ? input($_POST, 'name') : $tag['name']) ?>" required></label>
                    <div><button class="primary" type="submit">Save subject</button></div>
                </form>
                <form method="post" action="<?= h(staffProcessUrl($view, $studyId)) ?>" class="detail-body" data-confirm="Remove this subject and its study assignments?">
                    <?php staffForm('tag-delete'); ?>
                    <input type="hidden" name="record_id" value="<?= h($tag['id']) ?>">
                    <button class="danger" type="submit">Remove subject</button>
                </form>
            </details>
        <?php endforeach; ?>
    </div>
