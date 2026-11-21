
    <p class="directions"><?= h($copy ? locationText($copy) : 'No physical copies are listed. Ask library staff for help.') ?></p>
    <?php if ($copy && $copy['shelf_id'] !== null): ?>
        <h3><?= h($copy['section_label']) ?></h3>
        <div class="shelf-map" aria-label="<?= h($copy['section_label']) ?> shelf diagram">
            <?php foreach ($locations['cabinets'] as $cabinet):
                if ($cabinet['section_id'] !== $copy['section_id']) {
                    continue;
                }
                ?>
                <section class="cabinet <?= $cabinet['id'] === $copy['cabinet_id'] ? 'cabinet-selected' : '' ?>">
                    <h4><?= h($cabinet['label']) ?></h4>
                    <ul>
                        <?php foreach ($locations['shelves'] as $shelf):
                            if ($shelf['cabinet_id'] !== $cabinet['id']) {
                                continue;
                            }
                            $selected = $shelf['id'] === $copy['shelf_id'];
                            ?>
                            <li <?= $selected ? 'class="shelf-selected" aria-current="location"' : '' ?>>
                                <?= h($shelf['label']) ?><?= $selected ? ' · Selected' : '' ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>
        <p class="muted">Ask library staff to confirm access to this copy.</p>
    <?php endif;
