<?php
declare(strict_types=1);

require __DIR__ . '/../app/catalog.php';

$studyId = id($_GET['id'] ?? null);
$study = findStudy($studyId);
if (!$study) {
    http_response_code(404);
    require __DIR__ . '/../templates/study-missing.php';
    exit;
}

$filters = catalogFilters($_GET);
$tags = studyTags($studyId);
$copies = array_column(studyCopies($studyId), null, 'id');
$selected = $copies[id($_GET['copy'] ?? null)] ?? reset($copies) ?: null;
$layout = locations();

require __DIR__ . '/../templates/study.php';
