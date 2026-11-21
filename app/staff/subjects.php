<?php
declare(strict_types=1);

function saveSubject(): never
{
    $tagId = id($_POST['record_id'] ?? null);
    $name = textField('name', 'Subject name', 100);
    if ($tagId) {
        requireRecord('tags', $tagId);
        query('UPDATE tags SET name = ? WHERE id = ?', [$name, $tagId]);
    } else {
        query('INSERT INTO tags (name) VALUES (?)', [$name]);
    }
    staffDone('Subject saved.', staffUrl('tags'));
}

function deleteSubject(): never
{
    $tagId = id($_POST['record_id'] ?? null);
    requireRecord('tags', $tagId);
    query('DELETE FROM tags WHERE id = ?', [$tagId]);
    staffDone('Subject and its study assignments removed.', staffUrl('tags'));
}
