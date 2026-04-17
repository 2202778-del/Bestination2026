<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin_auth();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/participants.php');
}

// CSRF check
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid form submission. Please try again.'];
    redirect(BASE_URL . '/admin/participants.php');
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    redirect(BASE_URL . '/admin/participants.php');
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
if (empty($fields['interested_booth_id'])) {
    $errors[] = 'Please select the college program.';
} else {
    // Also validate that the booth ID exists
    try {
        $db = get_db();
        $stmt = $db->prepare('SELECT id FROM booths WHERE id = ?');
        $stmt->execute([$fields['interested_booth_id']]);
        if ($stmt->fetch() === false) {
            $errors[] = 'Please select a valid college program.';
        }
    } catch (Exception $e) { /* Ignore DB error during validation */ }
}

// Check for duplicate email/mobile, excluding the current student
try {
    $db = get_db();
    $stmt = $db->prepare('SELECT id FROM students WHERE email = ? AND id != ?');
    $stmt->execute([$fields['email'], $id]);
    if ($stmt->fetch()) $errors[] = 'This email address is already used by another participant.';

    $stmt = $db->prepare('SELECT id FROM students WHERE mobile = ? AND id != ?');
    $stmt->execute([$fields['mobile'], $id]);
    if ($stmt->fetch()) $errors[] = 'This mobile number is already used by another participant.';

} catch (PDOException $e) {
    $errors[] = 'Database validation error.';
}

if (!empty($errors)) {
    $_SESSION['edit_errors'] = $errors;
    $_SESSION['edit_old']    = $fields;
    redirect(BASE_URL . '/admin/edit_participant.php?id=' . $id);
}

// ─── Update student record ────────────────────────────────────────────────────
try {
    $db = get_db();
    $sql = "UPDATE students SET
                last_name = ?, first_name = ?, middle_name = ?, email = ?, mobile = ?,
                gender = ?, school_name = ?, grade_level = ?, interested_booth_id = ?
            WHERE id = ?";

    $stmt = $db->prepare($sql);
    // Execute with an explicitly ordered array to prevent issues if field order changes.
    $stmt->execute([
        $fields['last_name'],
        $fields['first_name'],
        $fields['middle_name'] ?: null,
        $fields['email'],
        $fields['mobile'],
        $fields['gender'],
        $fields['school_name'],
        $fields['grade_level'],
        $fields['interested_booth_id'] ?: null,
        $id
    ]);

    $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'Participant details updated successfully.'];
    redirect(BASE_URL . '/admin/participants.php');

} catch (PDOException $e) {
    error_log('Admin edit error: ' . $e->getMessage());
    $_SESSION['edit_errors'] = ['A system error occurred while saving the changes.'];
    $_SESSION['edit_old']    = $fields;
    redirect(BASE_URL . '/admin/edit_participant.php?id=' . $id);
}