<?php
declare(strict_types=1);

function staffView(string $requested): string
{
    return in_array($requested, ['studies', 'study', 'tags', 'locations', 'account'], true)
        ? $requested : 'studies';
}

function staffPageData(string $view, int $studyId, bool $includeArchived): array
{
    $record = $view === 'study' && $studyId ? findStudy($studyId, false) : [];
    $studyMissing = $view === 'study' && $studyId && !$record;
    $staffStudies = [];
    if ($view === 'studies') {
        $state = $includeArchived ? 'archived_at IS NOT NULL' : 'archived_at IS NULL';
        $staffStudies = query("SELECT id, title, authors, archived_at FROM studies WHERE $state
            ORDER BY academic_year DESC, title, id")->fetchAll();
    }
    $tags = in_array($view, ['study', 'tags'], true) ? subjects() : [];
    $assigned = $view === 'study' && $record ? array_column(studyTags($studyId), 'id') : [];
    $copies = $view === 'study' && $record ? studyCopies($studyId) : [];
    $layout = in_array($view, ['study', 'locations'], true) ? locations() : [];
    return compact('record', 'studyMissing', 'staffStudies', 'tags', 'assigned', 'copies', 'layout');
}
