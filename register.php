<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$LiveBaseUrl = get_dynamic_base_url();

// Check if registration is open
$regOpen = get_setting('registration_open', '1');

$errors = $_SESSION['reg_errors'] ?? [];
$old    = $_SESSION['reg_old'] ?? [];
unset($_SESSION['reg_errors'], $_SESSION['reg_old']);

$csrfToken = csrf_token();

$gradeOptions = ['Grade 11', 'Grade 12'];

// Load booths for the interest dropdown
try {
    $db = get_db();
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Register — UBBC Bestination 2026</title>
    <link rel="stylesheet" href="<?= $LiveBaseUrl ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= $LiveBaseUrl ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= $LiveBaseUrl ?>/assets/css/register.css">
</head>
<body class="register-page">

<div class="register-container">
    <!-- Header -->
    <div class="register-header">
        <div class="event-logo" style="margin-bottom: 8px; justify-content: center; flex-direction: column;">
            <img src="<?= $LiveBaseUrl ?>/assets/img/ub-logo-full.png" alt="UB Logo" class="logo-image-md" style="margin: 0 auto 12px;">
            <div class="logo-text">
                <span class="logo-main">BESTINATION</span>
                <span class="logo-year">2026</span>
            </div>
        </div>
        <p class="register-subtitle">Fill out the form to get your QR Passport!</p>
    </div>

    <?php if ($regOpen !== '1'): ?>
    <div class="alert alert-warning">
        <strong>Registration is currently closed.</strong> Please check back later.
    </div>
    <?php else: ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $e): ?>
            <li><?= sanitize($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form id="registerForm" action="<?= $LiveBaseUrl ?>/register_submit.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="form-row form-row-2">
            <div class="form-group">
                <label for="last_name">Last Name <span class="required">*</span></label>
                <input type="text" id="last_name" name="last_name" value="<?= sanitize($old['last_name'] ?? '') ?>" placeholder="dela Cruz" required>
                <span class="field-error" id="err_last_name"></span>
            </div>
            <div class="form-group">
                <label for="first_name">First Name <span class="required">*</span></label>
                <input type="text" id="first_name" name="first_name" value="<?= sanitize($old['first_name'] ?? '') ?>" placeholder="Juan" required>
                <span class="field-error" id="err_first_name"></span>
            </div>
        </div>

        <div class="form-group">
            <label for="middle_name">Middle Name <span class="optional">(optional)</span></label>
            <input type="text" id="middle_name" name="middle_name" value="<?= sanitize($old['middle_name'] ?? '') ?>" placeholder="Santos">
        </div>

        <div class="form-group">
            <label for="email">Email Address <span class="required">*</span></label>
            <input type="email" id="email" name="email" inputmode="email" value="<?= sanitize($old['email'] ?? '') ?>" placeholder="juan@email.com" required>
            <span class="field-error" id="err_email"></span>
        </div>

        <div class="form-group">
            <label for="mobile">Mobile Number <span class="required">*</span></label>
            <input type="tel" id="mobile" name="mobile" inputmode="tel" value="<?= sanitize($old['mobile'] ?? '') ?>" placeholder="09XXXXXXXXX" maxlength="11" required>
            <span class="field-error" id="err_mobile"></span>
        </div>

        <div class="form-group">
            <label for="gender">Gender <span class="required">*</span></label>
            <select id="gender" name="gender" required>
                <option value="">— Select Gender —</option>
                <?php foreach (['Male','Female'] as $g): ?>
                <option value="<?= $g ?>" <?= ($old['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
            <span class="field-error" id="err_gender"></span>
        </div>

        <div class="form-group">
            <label for="school_name">Name of School <span class="required">*</span></label>
            <input type="text" id="school_name" name="school_name" value="<?= sanitize($old['school_name'] ?? '') ?>" placeholder="e.g. Batangas National High School" required>
            <span class="field-error" id="err_school_name"></span>
        </div>

        <div class="form-group">
            <label for="grade_level">Grade Level <span class="required">*</span></label>
            <select id="grade_level" name="grade_level" required>
                <option value="">— Select Grade Level —</option>
                <?php foreach ($gradeOptions as $g): ?>
                <option value="<?= $g ?>" <?= ($old['grade_level'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
            <span class="field-error" id="err_grade_level"></span>
        </div>

        <div class="form-group">
            <label for="interested_booth_id">College program you are interested in <span class="required">*</span></label>
            <select id="interested_booth_id" name="interested_booth_id" required>
                <option value="">— Select a College Program —</option>
                <?php foreach ($booths as $booth): ?>
                <option value="<?= $booth['id'] ?>" <?= (($old['interested_booth_id'] ?? '') == $booth['id']) ? 'selected' : '' ?>>
                    <?= sanitize($booth['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <span class="field-error" id="err_interested_booth_id"></span>
        </div>

        <button type="submit" class="btn btn-primary btn-full" id="submitBtn">
            <span class="btn-text">Register &amp; Get My Passport</span>
            <span class="btn-loading" style="display:none;">Registering...</span>
        </button>
    </form>
    <?php endif; ?>

    <div class="register-footer">
        <p>UBBC Bestination 2026 &bull; University of Batangas</p>
    </div>
</div>

<script src="<?= $LiveBaseUrl ?>/assets/js/register.js"></script>
</body>
</html>
