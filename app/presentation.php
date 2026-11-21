<?php
declare(strict_types=1);

function pageStart(string $title, string $base = '', bool $staff = false): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    require __DIR__ . '/../templates/header.php';
}

function pageEnd(): void
{
    require __DIR__ . '/../templates/footer.php';
}

function drawLocator(?array $copy, array $locations): void
{
    require __DIR__ . '/../templates/locator.php';
}
