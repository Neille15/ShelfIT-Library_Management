<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

const STUDY_TYPES = ['Thesis', 'Capstone'];
const PAGE_SIZE = 20;

function catalogFilters(array $source): array
{
    $year = id($source['year'] ?? null);
    $type = input($source, 'type');
    return [
        'q' => mb_substr(input($source, 'q'), 0, 150),
        'program' => mb_substr(input($source, 'program'), 0, 120),
        'year' => $year >= 1900 && $year <= 2199 ? $year : null,
        'type' => in_array($type, STUDY_TYPES, true) ? $type : '',
        'tag' => id($source['tag'] ?? null) ?: null,
        'page' => id($source['page'] ?? null) ?: 1,
    ];
}

function catalogResults(array &$filters): array
{
    $where = ['s.archived_at IS NULL'];
    $params = [];
    $columns = ['program' => 'program', 'year' => 'academic_year', 'type' => 'study_type'];
    foreach ($columns as $filter => $column) {
        if ($filters[$filter] !== '' && $filters[$filter] !== null) {
            $where[] = "s.$column = ?";
            $params[] = $filters[$filter];
        }
    }
    if ($filters['tag'] !== null) {
        $where[] = 'EXISTS (SELECT 1 FROM study_tags st WHERE st.study_id = s.id AND st.tag_id = ?)';
        $params[] = $filters['tag'];
    }
    foreach (preg_split('/\s+/u', $filters['q'], -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
        $like = '%' . strtr($word, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $where[] = "(s.title LIKE ? ESCAPE '!' OR s.authors LIKE ? ESCAPE '!'
            OR s.adviser LIKE ? ESCAPE '!' OR EXISTS (
                SELECT 1 FROM study_tags st JOIN tags t ON t.id = st.tag_id
                WHERE st.study_id = s.id AND t.name LIKE ? ESCAPE '!'
            ))";
        array_push($params, $like, $like, $like, $like);
    }
    $clause = implode(' AND ', $where);
    $total = (int) query("SELECT COUNT(*) FROM studies s WHERE $clause", $params)->fetchColumn();
    $pages = max(1, (int) ceil($total / PAGE_SIZE));
    $filters['page'] = min($filters['page'], $pages);
    $offset = ($filters['page'] - 1) * PAGE_SIZE;
    $rows = query("SELECT s.id, s.title, s.authors, s.program, s.academic_year, s.study_type
        FROM studies s WHERE $clause
        ORDER BY s.academic_year DESC, s.title, s.id
        LIMIT ? OFFSET ?", [...$params, PAGE_SIZE, $offset])->fetchAll();
    return [$rows, $total, $pages];
}

function studyTags(int $studyId): array
{
    return query('SELECT t.id, t.name FROM tags t JOIN study_tags st ON st.tag_id = t.id
        WHERE st.study_id = ? ORDER BY t.name', [$studyId])->fetchAll();
}

function studyCopies(int $studyId): array
{
    return query('SELECT cp.*, sh.label AS shelf_label, c.id AS cabinet_id, c.label AS cabinet_label,
        sec.id AS section_id, sec.label AS section_label
        FROM copies cp
        LEFT JOIN shelves sh ON sh.id = cp.shelf_id
        LEFT JOIN cabinets c ON c.id = sh.cabinet_id
        LEFT JOIN sections sec ON sec.id = c.section_id
        WHERE cp.study_id = ? ORDER BY cp.copy_code, cp.id', [$studyId])->fetchAll();
}

function locations(): array
{
    return [
        'sections' => query('SELECT * FROM sections ORDER BY sort_order, label, id')->fetchAll(),
        'cabinets' => query('SELECT c.*, sec.label AS section_label FROM cabinets c
            JOIN sections sec ON sec.id = c.section_id ORDER BY sec.sort_order, sec.label, c.sort_order, c.label, c.id')->fetchAll(),
        'shelves' => query('SELECT sh.*, c.label AS cabinet_label, c.section_id, sec.label AS section_label
            FROM shelves sh JOIN cabinets c ON c.id = sh.cabinet_id JOIN sections sec ON sec.id = c.section_id
            ORDER BY sec.sort_order, sec.label, c.sort_order, c.label, sh.sort_order, sh.label, sh.id')->fetchAll(),
    ];
}

function locationText(array $copy): string
{
    if ($copy['shelf_id'] === null) {
        return 'Location not recorded. Ask library staff for help.';
    }
    return implode(' → ', [$copy['section_label'], $copy['cabinet_label'], $copy['shelf_label']]);
}

function drawLocator(?array $copy, array $locations): void
{
    ?>
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
}
