<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/login.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    redirect(BASE_URL . '/admin/login.php?error=csrf');
}

$password = $_POST['password'] ?? '';

$hash = get_setting('admin_password_hash', '');
if (empty($hash)) {
    redirect(BASE_URL . '/admin/login.php?error=nopassword');
}

if (!password_verify($password, $hash)) {
    redirect(BASE_URL . '/admin/login.php?error=wrong');
}

// Create session
$token = create_admin_session();
$expires = time() + (ADMIN_SESSION_HOURS * 3600);
setcookie(ADMIN_COOKIE_NAME, $token, [
    'expires'  => $expires,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

redirect(BASE_URL . '/admin/dashboard.php');
