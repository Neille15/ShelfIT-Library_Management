<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('Method not allowed.');
}
require_once __DIR__ . '/../../app/staff.php';
startStaffSession();
header('Referrer-Policy: no-referrer');
if (array_key_exists('token', $_GET)) {
    unset($_SESSION['password_reset_token']);
    $token = input($_GET, 'token');
    if (validPasswordReset($token)) {
        session_regenerate_id(true);
        $_SESSION['password_reset_token'] = $token;
    }
    redirect('reset.php');
}
$canReset = validPasswordReset($_SESSION['password_reset_token'] ?? '');
$error = '';
if (!$canReset) {
    http_response_code(400);
}
require __DIR__ . '/../../templates/staff/reset.php';
