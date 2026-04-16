<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin_auth();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    redirect(BASE_URL . '/admin/participants.php');
}

$db = get_db();
$stmt = $db->prepare('SELECT * FROM students WHERE id = ?');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Participant not found.'];
    redirect(BASE_URL . '/admin/participants.php');
}

$errors = $_SESSION['edit_errors'] ?? [];
$old = $_SESSION['edit_old'] ?? $student;
unset($_SESSION['edit_errors'], $_SESSION['edit_old']);

$csrfToken = csrf_token();
$gradeOptions = ['Grade 11', 'Grade 12'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Participant — UBBC Bestination 2026 Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-page">
<?php $activePage = 'participants'; require_once __DIR__ . '/admin_nav.php'; ?>

<div class="admin-content">
    <div class="admin-page-header">
        <h1>Edit Participant</h1>
    </div>