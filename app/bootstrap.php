<?php
declare(strict_types=1);

set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "ShelfIT could not finish. Check the database configuration and PHP error log.\n");
        exit(1);
    }
    http_response_code(500);
    echo 'ShelfIT is temporarily unavailable. Please contact library staff.';
});

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/presentation.php';
