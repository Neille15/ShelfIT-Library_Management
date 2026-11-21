<?php
declare(strict_types=1);

// Keep existing form submissions working through the same security gate.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/process.php';
    exit;
}

require_once __DIR__ . '/../../app/staff.php';
startStaffSession();
$account = signedInStaff();
$error = '';
if (!$account) {
    require __DIR__ . '/../../templates/staff/login.php';
    exit;
}
renderStaffPage($account);
