<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/qr_helper.php';
require_once __DIR__ . '/includes/mailer.php';

$LiveBaseUrl = get_dynamic_base_url();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($LiveBaseUrl . '/register.php');
}

// CSRF check
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['reg_errors'] = ['Invalid form submission. Please try again.'];
    redirect($LiveBaseUrl . '/register.php');
}

// Check registration open
if (get_setting('registration_open', '1') !== '1') {
    redirect($LiveBaseUrl . '/register.php');
}

// ─── Collect & validate input ─────────────────────────────────────────────────
$fields = [
    'last_name'   => trim($_POST['last_name']   ?? ''),
    'first_name'  => trim($_POST['first_name']  ?? ''),
    'middle_name' => trim($_POST['middle_name'] ?? ''),
    'email'       => strtolower(trim($_POST['email'] ?? '')),
    'mobile'      => trim($_POST['mobile'] ?? ''),
    'gender'      => trim($_POST['gender'] ?? ''),
    'school_name' => trim($_POST['school_name'] ?? ''),
    'grade_level' => trim($_POST['grade_level'] ?? ''),
    'interested_booth_id' => trim($_POST['interested_booth_id'] ?? ''),
];

$validGenders = ['Male', 'Female'];
$validGrades  = ['Grade 11', 'Grade 12'];

$errors = [];
if (empty($fields['last_name']))   $errors[] = 'Last name is required.';
if (empty($fields['first_name']))  $errors[] = 'First name is required.';
if (empty($fields['email']) || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL))
    $errors[] = 'A valid email address is required.';
if (!is_valid_ph_mobile($fields['mobile']))
    $errors[] = 'Mobile number must be in the format 09XXXXXXXXX.';
if (!in_array($fields['gender'], $validGenders))
    $errors[] = 'Please select a valid gender.';
if (empty($fields['school_name']))
    $errors[] = 'School name is required.';
if (!in_array($fields['grade_level'], $validGrades))
    $errors[] = 'Please select a valid grade level.';

// Validate interested_booth_id
if (empty($fields['interested_booth_id'])) {
    $errors[] = 'Please select the college program you are interested in.';
} else {
    try {
        $db = get_db();
        $stmt = $db->prepare('SELECT id FROM booths WHERE id = ?');
        $stmt->execute([$fields['interested_booth_id']]);
        if ($stmt->fetch() === false) {
            $errors[] = 'Please select a valid college program.';
        }
    } catch (Exception $e) { /* Ignore DB error during validation */ }
}

if (!empty($errors)) {
    $_SESSION['reg_errors'] = $errors;
    $_SESSION['reg_old']    = $fields;
    redirect($LiveBaseUrl . '/register.php');
}

// ─── Check duplicate email ────────────────────────────────────────────────────
try {
    $db = get_db();
    $stmt = $db->prepare('SELECT id, qr_token FROM students WHERE email = ?');
    $stmt->execute([$fields['email']]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Resend to existing student's passport page
        redirect($LiveBaseUrl . '/passport.php?token=' . urlencode($existing['qr_token']) . '&dup=1');
    }

    // ─── Generate token & insert student ─────────────────────────────────────
    // The check for duplicate mobile is now handled by the database UNIQUE constraint and the catch block.
    $token = generate_token();

    $insert = $db->prepare(
        'INSERT INTO students (last_name, first_name, middle_name, email, mobile, gender, school_name, grade_level, interested_booth_id, qr_token)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([
        $fields['last_name'],
        $fields['first_name'],
        $fields['middle_name'] ?: null,
        $fields['email'],
        $fields['mobile'],
        $fields['gender'],
        $fields['school_name'],
        $fields['grade_level'],
        $fields['interested_booth_id'] ?: null,
        $token,
    ]);
    $studentId = (int) $db->lastInsertId();

    // ─── Generate QR code image ───────────────────────────────────────────────
    generate_qr($token, $LiveBaseUrl);

    // ─── Send registration email ──────────────────────────────────────────────
    $student = array_merge($fields, ['id' => $studentId, 'qr_token' => $token]);
    $sent = send_registration_email($student, $LiveBaseUrl);
    if ($sent) {
        $db->prepare('UPDATE students SET email_sent = 1 WHERE id = ?')->execute([$studentId]);
    } else {
        error_log("Failed to send registration email to student ID: {$studentId} ({$student['email']})");
    }

    // ─── Redirect to passport page ────────────────────────────────────────────
    redirect($LiveBaseUrl . '/passport.php?token=' . urlencode($token));

} catch (PDOException $e) {
    // Check for specific error codes to provide better user feedback.
    // SQLSTATE '23000' is an integrity constraint violation (e.g., duplicate entry).
    if ($e->getCode() === '23000') {
        $db = get_db();
        // Check if it was a duplicate email (likely a race condition)
        $stmt = $db->prepare('SELECT qr_token FROM students WHERE email = ?');
        $stmt->execute([$fields['email']]);
        if ($existing = $stmt->fetch()) {
            redirect($LiveBaseUrl . '/passport.php?token=' . urlencode($existing['qr_token']) . '&dup=1');
        }

        // Check if it was a duplicate mobile number
        $stmt = $db->prepare('SELECT id FROM students WHERE mobile = ?');
        $stmt->execute([$fields['mobile']]);
        if ($stmt->fetch()) {
            $_SESSION['reg_errors'] = ['This mobile number has already been registered. Please use a different one.'];
            $_SESSION['reg_old']    = $fields;
            redirect($LiveBaseUrl . '/register.php');
        }
    }

    // For all other database errors, log it and show a generic message.
    error_log('Registration DB error: ' . $e->getMessage());
    $_SESSION['reg_errors'] = ['A system error occurred. Please try again.'];
    $_SESSION['reg_old']    = $fields;
    redirect($LiveBaseUrl . '/register.php');
}
