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

// Load booths for the interest dropdown
try {
    $booths = $db->query('SELECT id, name FROM booths ORDER BY sort_order')->fetchAll();
} catch (Exception $e) {
    $booths = []; // Gracefully fail if DB is not ready
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Edit Participant — UBBC Bestination 2026 Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php $activePage = 'participants'; require_once __DIR__ . '/admin_nav.php'; ?>

<div class="admin-content">
    <div class="admin-page-header">
        <a href="<?= BASE_URL ?>/admin/participants.php" class="btn btn-secondary btn-sm btn-with-icon">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <h1>Edit Participant: <?= sanitize($student['first_name'] . ' ' . $student['last_name']) ?></h1>
    </div>

    <div class="admin-card">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin: 24px;">
            <ul>
                <?php foreach ($errors as $e): ?>
                <li><?= sanitize($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form class="form-grid" action="<?= BASE_URL ?>/admin/edit_participant_submit.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" value="<?= $student['id'] ?>">

            <div class="form-group">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <input type="text" id="last_name" name="last_name" value="<?= sanitize($old['last_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="first_name">First Name <span class="required">*</span></label>
                <input type="text" id="first_name" name="first_name" value="<?= sanitize($old['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="middle_name">Middle Name <span class="optional">(optional)</span></label>
                <input type="text" id="middle_name" name="middle_name" value="<?= sanitize($old['middle_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" value="<?= sanitize($old['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="mobile">Mobile Number <span class="required">*</span></label>
                <input type="tel" id="mobile" name="mobile" value="<?= sanitize($old['mobile'] ?? '') ?>" maxlength="11" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender <span class="required">*</span></label>
                <select id="gender" name="gender" required>
                    <option value="">— Select Gender —</option>
                    <?php foreach (['Male','Female'] as $g): ?>
                    <option value="<?= $g ?>" <?= ($old['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="school_name">Name of School <span class="required">*</span></label>
                <input type="text" id="school_name" name="school_name" value="<?= sanitize($old['school_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="grade_level">Grade Level <span class="required">*</span></label>
                <select id="grade_level" name="grade_level" required>
                    <option value="">— Select Grade Level —</option>
                    <?php foreach ($gradeOptions as $g): ?>
                    <option value="<?= $g ?>" <?= ($old['grade_level'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="interested_booth_id">Interested Program <span class="required">*</span></label>
                <select id="interested_booth_id" name="interested_booth_id" required>
                    <option value="">— Select a College Program —</option>
                    <?php foreach ($booths as $booth): ?>
                    <option value="<?= $booth['id'] ?>" <?= (($old['interested_booth_id'] ?? '') == $booth['id']) ? 'selected' : '' ?>>
                        <?= sanitize($booth['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-with-icon">
                    <i class="bi bi-check-circle-fill"></i> Save Changes
                </button>
                <a href="<?= BASE_URL ?>/admin/participants.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>