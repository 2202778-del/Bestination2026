<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin_auth();

$message = $_SESSION['admin_message'] ?? null;
if ($message) {
    unset($_SESSION['admin_message']);
}

// Filters
$search     = trim($_GET['search'] ?? '');
$filterGrade = trim($_GET['grade'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

try {
    $db = get_db();

    $where  = [];
    $params = [];

    if ($search) {
        $where[]  = '(CONCAT(st.first_name, " ", st.last_name) LIKE ? OR st.email LIKE ? OR st.school_name LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($filterGrade) {
        $where[]  = 'st.grade_level = ?';
        $params[] = $filterGrade;
    }
    if ($filterStatus === 'completed') {
        $where[] = 'st.completed_at IS NOT NULL';
    } elseif ($filterStatus === 'incomplete') {
        $where[] = 'st.completed_at IS NULL';
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $students = $db->prepare(
        "SELECT st.*, COUNT(sc.id) AS scan_count
         FROM students st
         LEFT JOIN scans sc ON sc.student_id = st.id
         {$whereSQL}
         GROUP BY st.id
         ORDER BY st.registered_at DESC"
    );
    $students->execute($params);
    $rows = $students->fetchAll();

    $grades = $db->query('SELECT DISTINCT grade_level FROM students ORDER BY grade_level')->fetchAll(PDO::FETCH_COLUMN);
    $totalCount = count($rows);

} catch (PDOException $e) {
    die('Database error.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Participants — UBBC Bestination 2026 Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php $activePage = 'participants'; require_once __DIR__ . '/admin_nav.php'; ?>

<div class="admin-content">
    <div class="admin-page-header">
        <h1>Participants <span class="count-badge"><?= $totalCount ?></span></h1>
        <a href="<?= BASE_URL ?>/admin/export.php" class="btn btn-secondary btn-sm btn-with-icon">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $message['type'] === 'success' ? 'success' : 'error' ?>">
        <?= sanitize($message['text']) ?>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" action="" class="filter-form">
            <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search name, email, school..." class="filter-search">
            <select name="grade" class="filter-select">
                <option value="">All Grade Levels</option>
                <?php foreach ($grades as $g): ?>
                <option value="<?= sanitize($g) ?>" <?= $filterGrade === $g ? 'selected' : '' ?>><?= sanitize($g) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="incomplete" <?= $filterStatus === 'incomplete' ? 'selected' : '' ?>>Incomplete</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if ($search || $filterGrade || $filterStatus): ?>
            <a href="<?= BASE_URL ?>/admin/participants.php" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="admin-card">
        <div class="table-wrap">
            <table class="data-table participants-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>School</th>
                        <th>Grade</th>
                        <th>Gender</th>
                        <th>Booths</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="text-center text-muted">No participants found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($rows as $i => $row): ?>
                    <?php
                        $completed = $row['completed_at'] !== null;
                        $csrf = csrf_token();
                        $rowClass  = $completed ? 'row-complete' : ($row['scan_count'] > 0 ? 'row-progress' : '');
                    ?>
                    <tr class="<?= $rowClass ?>" data-student-id="<?= $row['id'] ?>" style="animation-delay: <?= min($i * 0.03, 0.5) ?>s">
                        <td><?= $i + 1 ?></td>
                        <td class="td-name">
                            <a href="<?= passport_url($row['qr_token']) ?>" target="_blank" title="View Passport">
                                <?= sanitize($row['last_name'] . ', ' . $row['first_name'] . ($row['middle_name'] ? ' ' . $row['middle_name'][0] . '.' : '')) ?>
                            </a>
                        </td>
                        <td class="td-email"><?= sanitize($row['email']) ?></td>
                        <td><?= sanitize($row['mobile']) ?></td>
                        <td><?= sanitize($row['school_name']) ?></td>
                        <td><?= sanitize($row['grade_level']) ?></td>
                        <td><?= sanitize($row['gender']) ?></td>
                        <td class="td-center">
                            <span class="booth-pill <?= $completed ? 'pill-complete' : ($row['scan_count'] > 0 ? 'pill-progress' : 'pill-none') ?>">
                                <?= $row['scan_count'] ?>/<?= TOTAL_BOOTHS ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($completed): ?>
                            <span class="status-badge badge-complete"><i class="bi bi-check-circle-fill"></i> Complete</span>
                            <?php elseif ($row['scan_count'] > 0): ?>
                            <span class="status-badge badge-progress">In Progress</span>
                            <?php else: ?>
                            <span class="status-badge badge-none">Not Started</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-date"><?= date('M j, Y g:i A', strtotime($row['registered_at'])) ?></td>
                        <td class="td-actions">
                            <a href="<?= BASE_URL ?>/admin/edit_participant.php?id=<?= $row['id'] ?>" class="btn-action btn-edit" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/delete_participant.php?id=<?= $row['id'] ?>&csrf_token=<?= $csrf ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this participant? This action cannot be undone.')">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
