<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/staff.php';
startStaffSession();
header('Referrer-Policy: no-referrer');
$error = '';
require __DIR__ . '/../../templates/staff/forgot.php';
