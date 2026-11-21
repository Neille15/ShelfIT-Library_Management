<?php
declare(strict_types=1);

const LOCATION_TYPES = [
    'sections' => ['Section', null, null],
    'cabinets' => ['Cabinet', 'section_id', 'sections'],
    'shelves' => ['Shelf', 'cabinet_id', 'cabinets'],
];

function saveLocation(): never
{
    $kind = input($_POST, 'kind');
    [$label, $parentColumn, $parentTable] = LOCATION_TYPES[$kind] ?? [null, null, null];
    if (!$label) {
        throw new StaffValidation('Choose a location type.');
    }
    $locationId = id($_POST['record_id'] ?? null);
    if ($locationId) {
        requireRecord($kind, $locationId);
    }
    $name = textField('label', "$label name", 100);
    $order = filter_var(input($_POST, 'sort_order'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 65535]]);
    if ($order === false) {
        throw new StaffValidation('Sort order must be a whole number between 0 and 65535.');
    }
    $fields = ['label' => $name, 'sort_order' => $order];
    if ($parentColumn) {
        $parentId = id($_POST['parent_id'] ?? null);
        requireRecord($parentTable, $parentId);
        $fields[$parentColumn] = $parentId;
    }
    if ($locationId) {
        $assignments = implode(', ', array_map(static fn ($column) => "$column = ?", array_keys($fields)));
        query("UPDATE $kind SET $assignments WHERE id = ?", [...array_values($fields), $locationId]);
    } else {
        $columns = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        query("INSERT INTO $kind ($columns) VALUES ($placeholders)", array_values($fields));
    }
    staffDone("$label saved.", staffUrl('locations'));
}

function deleteLocation(): never
{
    $kind = input($_POST, 'kind');
    [$label] = LOCATION_TYPES[$kind] ?? [null];
    if (!$label) {
        throw new StaffValidation('Choose a location type.');
    }
    $recordId = id($_POST['record_id'] ?? null);
    requireRecord($kind, $recordId);
    query("DELETE FROM $kind WHERE id = ?", [$recordId]);
    staffDone("$label removed.", staffUrl('locations'));
}
