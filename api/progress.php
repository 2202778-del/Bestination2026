<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

$token = trim($_GET['token'] ?? '');

if (!$token || strlen($token) !== 64 || !ctype_xdigit($token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

try {
    $db = get_db();

    $stmt = $db->prepare('SELECT id, first_name, last_name, completed_at FROM students WHERE qr_token = ?');
    $stmt->execute([$token]);
    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(404);
        echo json_encode(['error' => 'Student not found']);
        exit;
    }

    $scanStmt = $db->prepare(
        'SELECT s.booth_id, s.scanned_at, b.name AS booth_name
         FROM scans s
         JOIN booths b ON b.id = s.booth_id
         WHERE s.student_id = ?
         ORDER BY s.scanned_at'
    );
    $scanStmt->execute([$student['id']]);
    $scans = $scanStmt->fetchAll();

    $visitedIds = array_column($scans, 'booth_id');

    echo json_encode([
        'student_name'  => $student['first_name'] . ' ' . $student['last_name'],
        'scan_count'    => count($scans),
        'completed'     => $student['completed_at'] !== null,
        'completed_at'  => $student['completed_at'],
        'visited_booths'=> array_map('intval', $visitedIds),
    ]);

} catch (PDOException $e) {
    error_log('Progress API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
