<?php
declare(strict_types=1);

function pageStart(string $title, string $base = '', bool $staff = false, ?array $account = null): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    // A content version makes browsers fetch updated assets after a code change.
    $cssVersion = substr(hash_file('sha256', __DIR__ . '/../public/assets/shelfit.css'), 0, 12);
    $jsVersion = substr(hash_file('sha256', __DIR__ . '/../public/assets/shelfit.js'), 0, 12);
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
