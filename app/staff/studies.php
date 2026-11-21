<?php
declare(strict_types=1);

function saveStudy(): never
{
    $studyId = id($_POST['study_id'] ?? null);
    $year = id($_POST['academic_year'] ?? null);
    $type = input($_POST, 'study_type');
    if ($year < 1900 || $year > 2199) {
        throw new StaffValidation('Enter a starting academic year between 1900 and 2199.');
    }
    if (!in_array($type, STUDY_TYPES, true)) {
        throw new StaffValidation('Choose Thesis or Capstone.');
    }
    $data = [
        textField('title', 'Title', 255),
        textField('authors', 'Authors', 500),
        textField('adviser', 'Adviser', 255, false),
        textField('program', 'Degree program', 120),
        $year, $type,
        textField('summary', 'Abstract or summary', 5000, false),
    ];
    $postedTags = $_POST['tags'] ?? [];
    if (!is_array($postedTags)) {
        throw new StaffValidation('Choose subjects from the list.');
    }
    $tagIds = array_unique(array_map('id', $postedTags));
    if (in_array(0, $tagIds, true)) {
        throw new StaffValidation('Choose subjects from the list.');
    }

    db()->beginTransaction();
    try {
        if ($studyId) {
            if (!query('SELECT id FROM studies WHERE id = ? FOR UPDATE', [$studyId])->fetchColumn()) {
                throw new StaffValidation('That study could not be found.');
            }
            query('UPDATE studies SET title = ?, authors = ?, adviser = ?, program = ?,
                academic_year = ?, study_type = ?, summary = ? WHERE id = ?', [...$data, $studyId]);
        } else {
            query('INSERT INTO studies (title, authors, adviser, program, academic_year, study_type, summary)
                VALUES (?, ?, ?, ?, ?, ?, ?)', $data);
            $studyId = (int) db()->lastInsertId();
        }
        query('DELETE FROM study_tags WHERE study_id = ?', [$studyId]);
        foreach ($tagIds as $tagId) {
            query('INSERT INTO study_tags (study_id, tag_id) VALUES (?, ?)', [$studyId, $tagId]);
        }
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    staffDone('Study saved.', staffUrl('study', ['id' => $studyId]));
}

function saveCopy(): never
{
    $studyId = id($_POST['study_id'] ?? null);
    requireRecord('studies', $studyId);
    $copyId = id($_POST['copy_id'] ?? null);
    if ($copyId && !query('SELECT id FROM copies WHERE id = ? AND study_id = ?', [$copyId, $studyId])->fetchColumn()) {
        throw new StaffValidation('That copy could not be found for this study.');
    }
    $code = textField('copy_code', 'Copy ID', 80);
    $selection = [input($_POST, 'section_id'), input($_POST, 'cabinet_id'), input($_POST, 'shelf_id')];
    $shelfId = null;
    if ($selection !== ['', '', '']) {
        [$sectionId, $cabinetId, $shelfId] = array_map('id', $selection);
        if (!query('SELECT sh.id FROM shelves sh JOIN cabinets c ON c.id = sh.cabinet_id
            WHERE sh.id = ? AND sh.cabinet_id = ? AND c.section_id = ?', [$shelfId, $cabinetId, $sectionId])->fetchColumn()) {
            throw new StaffValidation('Choose a shelf in the selected cabinet and section, or clear all location fields.');
        }
    }
    try {
        if ($copyId) {
            query('UPDATE copies SET copy_code = ?, shelf_id = ? WHERE id = ? AND study_id = ?', [$code, $shelfId, $copyId, $studyId]);
        } else {
            query('INSERT INTO copies (study_id, copy_code, shelf_id) VALUES (?, ?, ?)', [$studyId, $code, $shelfId]);
        }
    } catch (PDOException $error) {
        if (($error->errorInfo[1] ?? null) === 1062) {
            throw new StaffValidation('That copy ID is already in use.');
        }
        throw $error;
    }
    staffDone('Copy saved.', staffUrl('study', ['id' => $studyId]));
}

function deleteArchivedStudy(int $studyId): never
{
    db()->beginTransaction();
    try {
        $study = query('SELECT archived_at FROM studies WHERE id = ? FOR UPDATE', [$studyId])->fetch();
        if (!$study) {
            throw new StaffValidation('That study could not be found.');
        }
        if ($study['archived_at'] === null) {
            throw new StaffValidation('Archive the study before deleting it permanently.');
        }

        query('DELETE FROM copies WHERE study_id = ?', [$studyId]);
        query('DELETE FROM studies WHERE id = ?', [$studyId]);
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    staffDone('Archived study permanently deleted.', staffUrl('studies', ['archived' => 1]));
}

function setStudyArchived(bool $archive): never
{
    $studyId = id($_POST['study_id'] ?? null);
    requireRecord('studies', $studyId);

    $timestamp = $archive ? 'CURRENT_TIMESTAMP' : 'NULL';
    query("UPDATE studies SET archived_at = $timestamp WHERE id = ?", [$studyId]);
    staffDone($archive ? 'Study archived.' : 'Study restored.', staffUrl('study', ['id' => $studyId]));
}

function deleteCopy(): never
{
    $studyId = id($_POST['study_id'] ?? null);
    $removed = query('DELETE FROM copies WHERE id = ? AND study_id = ?', [id($_POST['copy_id'] ?? null), $studyId])->rowCount();
    if (!$removed) {
        throw new StaffValidation('That copy could not be found for this study.');
    }
    staffDone('Copy removed.', staffUrl('study', ['id' => $studyId]));
}
