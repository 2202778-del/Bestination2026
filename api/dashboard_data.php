<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Public endpoint for total_registered only (for landing page counter)
// Full data requires admin auth
$isAdmin = is_admin_logged_in();

try {
    $db = get_db();

    $totalReg  = (int) $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $totalComp = (int) $db->query('SELECT COUNT(*) FROM students WHERE completed_at IS NOT NULL')->fetchColumn();

    if (!$isAdmin) {
        // Return limited public data
        echo json_encode(['total_registered' => $totalReg, 'total_completed' => $totalComp]);
        exit;
    }

    // ─── Full admin data ──────────────────────────────────────────────────────
    $totalActiveBooths = (int) $db->query('SELECT COUNT(*) FROM booths WHERE is_active = 1')->fetchColumn();
    $inProgress = (int) $db->query(
        'SELECT COUNT(DISTINCT s.student_id) FROM scans s
         JOIN students st ON st.id = s.student_id
         WHERE st.completed_at IS NULL'
    )->fetchColumn();

    $perBooth = $db->query(
        'SELECT b.id AS booth_id, b.name, COUNT(s.id) AS scan_count
         FROM booths b LEFT JOIN scans s ON s.booth_id = b.id
         GROUP BY b.id ORDER BY b.sort_order'
    )->fetchAll();

    $recentComp = $db->query(
        'SELECT CONCAT(first_name, " ", last_name) AS student_name, completed_at
         FROM students WHERE completed_at IS NOT NULL
         ORDER BY completed_at DESC LIMIT 10'
    )->fetchAll();

    $allStudents = $db->query(
        'SELECT st.id, CONCAT(st.last_name, ", ", st.first_name) AS full_name,
                st.school_name AS school, st.grade_level AS grade,
                st.registered_at,
                COUNT(sc.id) AS scan_count,
                (st.completed_at IS NOT NULL) AS completed
         FROM students st
         LEFT JOIN scans sc ON sc.student_id = st.id
         GROUP BY st.id
         ORDER BY st.registered_at DESC'
    )->fetchAll();

    // Cast booleans
    foreach ($allStudents as &$s) {
        $s['scan_count'] = (int) $s['scan_count'];
        $s['completed']  = (bool) $s['completed'];
    }
    foreach ($perBooth as &$b) {
        $b['scan_count'] = (int) $b['scan_count'];
        $b['booth_id']   = (int) $b['booth_id'];
    }

    echo json_encode([
        'total_registered'  => $totalReg,
        'total_completed'   => $totalComp,
        'in_progress'       => $inProgress,
        'total_active_booths' => $totalActiveBooths,
        'per_booth_counts'  => $perBooth,
        'recent_completions'=> $recentComp,
        'students_progress' => $allStudents,
    ]);

} catch (PDOException $e) {
    error_log('Dashboard API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
