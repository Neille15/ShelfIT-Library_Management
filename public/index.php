<?php
declare(strict_types=1);

require __DIR__ . '/../app/catalog.php';

$filters = catalogFilters($_GET);
[$studies, $total, $pages] = catalogResults($filters);
$programs = query('SELECT DISTINCT program FROM studies WHERE archived_at IS NULL ORDER BY program')->fetchAll(PDO::FETCH_COLUMN);
$years = query('SELECT DISTINCT academic_year FROM studies WHERE archived_at IS NULL ORDER BY academic_year DESC')->fetchAll(PDO::FETCH_COLUMN);
$tags = query('SELECT t.id, t.name FROM tags t WHERE EXISTS (
    SELECT 1 FROM study_tags st JOIN studies s ON s.id = st.study_id
    WHERE st.tag_id = t.id AND s.archived_at IS NULL
) ORDER BY t.name')->fetchAll();
$choices = [
    'program' => ['Program', 'All programs', array_map(static fn ($program) => [$program, $program], $programs)],
    'year' => ['Academic year', 'All years', array_map(static fn ($year) => [$year, academicYear($year)], $years)],
    'type' => ['Study type', 'All study types', array_map(static fn ($type) => [$type, $type], STUDY_TYPES)],
    'tag' => ['Subject', 'All subjects', array_map(static fn ($tag) => [$tag['id'], $tag['name']], $tags)],
];
pageStart('Study catalog');
?>
<section class="hero">
    <h1>Find a thesis or capstone</h1>
    <form method="get" action="index.php" class="stack" data-catalog-filters>
        <div>
            <label for="search">Search by title, author, adviser, or subject</label>
            <div class="search-bar">
                <input type="search" name="q" id="search" maxlength="150" value="<?= h($filters['q']) ?>">
                <button class="primary" type="submit">Search</button>
            </div>
        </div>
        <div class="fields filter-fields">
            <?php foreach ($choices as $name => [$label, $placeholder, $options]): ?>
                <label><?= h($label) ?>
                    <select name="<?= h($name) ?>">
                        <option value=""><?= h($placeholder) ?></option>
                        <?php foreach ($options as [$value, $text]): ?>
                            <option value="<?= h($value) ?>" <?= $value === $filters[$name] ? 'selected' : '' ?>><?= h($text) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endforeach; ?>
        </div>
        <div><a class="button" href="index.php" data-clear-search>Clear search and filters</a></div>
    </form>
</section>
<section class="stack" aria-labelledby="result-count">
    <h2 id="result-count"><?= h($total) ?> <?= $total === 1 ? 'study found' : 'studies found' ?></h2>
    <?php if (!$studies): ?>
        <p class="panel">Try a different keyword or clear the filters.</p>
    <?php endif; ?>
    <ul class="results">
        <?php foreach ($studies as $study): ?>
            <li class="panel result">
                <div>
                    <h3><?= h($study['title']) ?></h3>
                    <p><?= h($study['authors']) ?></p>
                    <p class="muted"><?= h($study['program']) ?> · <?= h(academicYear($study['academic_year'])) ?> · <?= h($study['study_type']) ?></p>
                </div>
                <a class="button" aria-label="View study: <?= h($study['title']) ?>"
                    href="<?= h(url('study.php', ['id' => $study['id'], ...$filters])) ?>">View study →</a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Catalog pages">
            <?php if ($filters['page'] > 1): ?>
                <a class="button" rel="prev" href="<?= h(url('index.php', [...$filters, 'page' => $filters['page'] - 1])) ?>">Previous</a>
            <?php endif; ?>
            <span>Page <?= h($filters['page']) ?> of <?= h($pages) ?></span>
            <?php if ($filters['page'] < $pages): ?>
                <a class="button" rel="next" href="<?= h(url('index.php', [...$filters, 'page' => $filters['page'] + 1])) ?>">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
<?php pageEnd(); ?>
