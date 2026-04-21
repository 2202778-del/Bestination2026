<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/mailer.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$LiveBaseUrl = get_dynamic_base_url();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Try form data
    $input = $_POST;
}

$token   = trim($input['token']   ?? '');
$boothId = (int) ($input['booth_id'] ?? 0);

// Validate inputs
if (!$token || strlen($token) !== 64 || !ctype_xdigit($token)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid QR code']);
    exit;
}
if ($boothId < 1 || $boothId > 9) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid booth']);
    exit;
}

try {
    $db = get_db();

    // Look up student by QR token
    $stmt = $db->prepare('SELECT * FROM students WHERE qr_token = ?');
    $stmt->execute([$token]);
    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'QR code not recognized. Please register first.']);
        exit;
    }

    $studentName = $student['first_name'] . ' ' . $student['last_name'];

    // Attempt to insert scan (UNIQUE constraint prevents duplicates)
    try {
        $insertStmt = $db->prepare(
            'INSERT INTO scans (student_id, booth_id) VALUES (?, ?)'
        );
        $insertStmt->execute([$student['id'], $boothId]);

    } catch (PDOException $e) {
        // Error 1062 = Duplicate entry (already scanned at this booth)
        if ($e->getCode() === '23000') {
            $prevStmt = $db->prepare(
                'SELECT scanned_at FROM scans WHERE student_id = ? AND booth_id = ?'
            );
            $prevStmt->execute([$student['id'], $boothId]);
            $prev = $prevStmt->fetch();
            $prevTime = $prev ? date('g:i A, M j', strtotime($prev['scanned_at'])) : 'earlier';

            echo json_encode([
                'status'       => 'duplicate',
                'student_name' => $studentName,
                'message'      => "Already scanned at this booth on {$prevTime}",
            ]);
            exit;
        }
        throw $e;
    }

    // Count total scans for this student
    $countStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM scans WHERE student_id = ?');
    $countStmt->execute([$student['id']]);
    $scanCount = (int) $countStmt->fetch()['cnt'];

    $justCompleted = false;

    // Check for completion (all 9 booths)
    if ($scanCount >= TOTAL_BOOTHS && !$student['complete_email_sent']) {
        // Atomic update — only one process wins
        $updateStmt = $db->prepare(
            'UPDATE students SET completed_at = NOW(), complete_email_sent = 1
             WHERE id = ? AND complete_email_sent = 0'
        );
        $updateStmt->execute([$student['id']]);

        if ($updateStmt->rowCount() === 1) {
            $justCompleted = true;
            // Reload student to get completed_at
            $stmt2 = $db->prepare('SELECT * FROM students WHERE id = ?');
            $stmt2->execute([$student['id']]);
            $student = $stmt2->fetch();

            // Get all scans for email
            $scanListStmt = $db->prepare(
                'SELECT s.scanned_at, b.name AS booth_name
                 FROM scans s JOIN booths b ON b.id = s.booth_id
                 WHERE s.student_id = ? ORDER BY s.scanned_at'
            );
            $scanListStmt->execute([$student['id']]);
            $allScans = $scanListStmt->fetchAll();

            send_completion_email($student, $allScans, $totalActiveBooths, $LiveBaseUrl);
        }
    }

    // Get booth name for response
    $boothStmt = $db->prepare('SELECT name FROM booths WHERE id = ?');
    $boothStmt->execute([$boothId]);
    $boothName = $boothStmt->fetch()['name'] ?? 'Unknown Booth';

    echo json_encode([
        'status'        => 'success',
        'student_name'  => $studentName,
        'scan_count'    => $scanCount,
        'total_booths'  => TOTAL_BOOTHS,
        'completed'     => $scanCount >= TOTAL_BOOTHS,
        'just_completed'=> $justCompleted,
        'booth_name'    => $boothName,
        'message'       => $justCompleted
            ? "MISSION COMPLETE! {$studentName} has visited all " . TOTAL_BOOTHS . " booths!"
            : "Visit logged! ({$scanCount}/" . TOTAL_BOOTHS . " booths)",
    ]);

} catch (PDOException $e) {
    error_log('Scan API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error. Please try again.']);
}
