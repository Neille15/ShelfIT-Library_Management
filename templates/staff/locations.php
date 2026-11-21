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
