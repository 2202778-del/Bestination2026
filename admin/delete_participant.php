<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin_auth();

$id = (int)($_GET['id'] ?? 0);

if (!verify_csrf($_GET['csrf_token'] ?? '')) {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid request. Please try again.'];
    redirect(BASE_URL . '/admin/participants.php');
}

if ($id) {
    try {
        $db = get_db();
        $stmt = $db->prepare('DELETE FROM students WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'Participant has been deleted successfully.'];
    } catch (PDOException $e) {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Error deleting participant.'];
    }
}

redirect(BASE_URL . '/admin/participants.php');