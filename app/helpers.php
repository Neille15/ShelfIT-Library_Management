<?php
declare(strict_types=1);

function h(string|int|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(array $source, string $name): string
{
    return is_string($source[$name] ?? null) ? trim($source[$name]) : '';
}

function id(mixed $value): int
{
    return (int) (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0);
}

function url(string $path, array $params = []): string
{
    $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null);
    return $path . ($params ? '?' . http_build_query($params) : '');
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function academicYear(int $year): string
{
    return $year . '–' . ($year + 1);
}
