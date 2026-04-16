<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$token = $_COOKIE[ADMIN_COOKIE_NAME] ?? '';
if ($token) {
    destroy_admin_session($token);
    setcookie(ADMIN_COOKIE_NAME, '', time() - 3600, '/');
}

redirect(BASE_URL . '/admin/login.php');
