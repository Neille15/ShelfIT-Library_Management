<?php
declare(strict_types=1);

final class StaffValidation extends RuntimeException
{
}

function textField(string $name, string $label, int $max, bool $required = true): string
{
    $value = input($_POST, $name);
    if ($required && $value === '') {
        throw new StaffValidation("$label is required.");
    }
    if (!mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $max) {
        throw new StaffValidation("$label must contain at most $max characters.");
    }
    return $value;
}

function requireRecord(string $table, int $recordId): void
{
    if (!in_array($table, ['studies', 'copies', 'tags', 'sections', 'cabinets', 'shelves'], true)) {
        throw new LogicException('Unsupported record table.');
    }
    if (!$recordId || !query("SELECT id FROM $table WHERE id = ?", [$recordId])->fetchColumn()) {
        throw new StaffValidation('That record could not be found.');
    }
}
