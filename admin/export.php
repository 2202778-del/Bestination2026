<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin_auth();

try {
    $db = get_db();

    $rows = $db->query(
        'SELECT st.id, st.last_name, st.first_name, st.middle_name, st.email, st.mobile,
                st.gender, st.school_name, st.grade_level,
                COUNT(sc.id) AS booths_visited,
                st.completed_at, st.registered_at
         FROM students st
         LEFT JOIN scans sc ON sc.student_id = st.id
         GROUP BY st.id
         ORDER BY st.registered_at'
    )->fetchAll();

} catch (PDOException $e) {
    die('Export error.');
}

$filename = 'bestination2026_participants_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fwrite($out, "\xEF\xBB\xBF");

// Headers
fputcsv($out, [
    'No.', 'Last Name', 'First Name', 'Middle Name', 'Email', 'Mobile',
    'Gender', 'School', 'Grade Level', 'Booths Visited', 'Completed', 'Registered At'
]);

foreach ($rows as $i => $row) {
    fputcsv($out, [
        $i + 1,
        $row['last_name'],
        $row['first_name'],
        $row['middle_name'] ?? '',
        $row['email'],
        $row['mobile'],
        $row['gender'],
        $row['school_name'],
        $row['grade_level'],
        $row['booths_visited'] . '/' . TOTAL_BOOTHS,
        $row['completed_at'] ? 'Yes (' . date('Y-m-d H:i', strtotime($row['completed_at'])) . ')' : 'No',
        $row['registered_at'],
    ]);
}

fclose($out);
exit;
