<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Already logged in?
if (is_admin_logged_in()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$error = $_GET['error'] ?? '';
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Admin Login — UBBC Bestination 2026</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-login-page">

<div class="admin-login-container">
    <div class="admin-login-card">
        <div class="login-logo-wrap vertical-logo">
            <img src="<?= BASE_URL ?>/assets/img/ub-logo-full.png" alt="UB Logo" class="logo-image-md">
            <span class="logo-main">BESTINATION<span class="logo-year">2026</span></span>
        </div>
        <h2 class="admin-login-title">Administrator Access<small>Please enter your password to continue</small></h2>

        <?php if ($error === 'wrong'): ?>
        <div class="alert alert-error">Incorrect password. Please try again.</div>
        <?php elseif ($error === 'nopassword'): ?>
        <div class="alert alert-warning">Admin password not configured. Please run <a href="<?= BASE_URL ?>/setup.php">setup.php</a> first.</div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/admin/login_submit.php">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="bi bi-lock-fill icon"></i>
                    <input type="password" id="password" name="password" placeholder="Enter admin password" required autofocus>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-with-icon">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </button>
        </form>
    </div>
</div>

</body>
</html>
