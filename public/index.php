<?php
declare(strict_types=1);

require __DIR__ . '/../app/catalog.php';

$filters = catalogFilters($_GET);
[$studies, $total, $pages] = catalogResults($filters);
['programs' => $programs, 'years' => $years, 'tags' => $tags] = catalogChoices();
$choices = [
    'program' => ['Program', 'All programs', array_map(static fn ($program) => [$program, $program], $programs)],
    'year' => ['Academic year', 'All years', array_map(static fn ($year) => [$year, academicYear($year)], $years)],
    'type' => ['Study type', 'All study types', array_map(static fn ($type) => [$type, $type], STUDY_TYPES)],
    'tag' => ['Subject', 'All subjects', array_map(static fn ($tag) => [$tag['id'], $tag['name']], $tags)],
];
require __DIR__ . '/../templates/catalog.php';
