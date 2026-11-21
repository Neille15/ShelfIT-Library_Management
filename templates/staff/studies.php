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
