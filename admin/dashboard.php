<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin_auth();

try {
    $db = get_db();

    $totalReg   = (int) $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $totalComp  = (int) $db->query('SELECT COUNT(*) FROM students WHERE completed_at IS NOT NULL')->fetchColumn();
    $inProgress = (int) $db->query(
        'SELECT COUNT(DISTINCT s.student_id) FROM scans s
         JOIN students st ON st.id = s.student_id
         WHERE st.completed_at IS NULL'
    )->fetchColumn();

    $perBooth = $db->query(
        'SELECT b.id, b.name, b.code, COUNT(s.id) AS scan_count
         FROM booths b LEFT JOIN scans s ON s.booth_id = b.id
         GROUP BY b.id ORDER BY b.sort_order'
    )->fetchAll();

} catch (PDOException $e) {
    exit('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Dashboard — UBBC Bestination 2026 Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php $activePage = 'dashboard'; require_once __DIR__ . '/admin_nav.php'; ?>

<div class="admin-content">
    <div class="admin-page-header">
        <h1>Live Dashboard</h1>
        <span class="live-badge"><i class="bi bi-broadcast"></i> LIVE</span>
        <span class="last-updated" id="lastUpdated">Updated just now</span>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon-flat flat-accent"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value" id="statTotal"><?= $totalReg ?></div>
            <div class="stat-label">Total Registered</div>
        </div>
        <div class="stat-card stat-card-success">
            <div class="icon-flat flat-success"><i class="bi bi-patch-check-fill"></i></div>
            <div class="stat-value" id="statCompleted"><?= $totalComp ?></div>
            <div class="stat-label">Completed All 9</div>
            <div class="stat-pct" id="statPct"><?= $totalReg > 0 ? round(($totalComp / $totalReg) * 100) : 0 ?>%</div>
        </div>
        <div class="stat-card stat-card-warn">
            <div class="icon-flat flat-warning"><i class="bi bi-person-walking"></i></div>
            <div class="stat-value" id="statProgress"><?= $inProgress ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
            <div class="icon-flat flat-default"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value" id="statNotStarted"><?= max(0, $totalReg - $totalComp - $inProgress) ?></div>
            <div class="stat-label">Not Started</div>
        </div>
    </div>

    <div class="dashboard-columns">
        <!-- Per-Booth Counts -->
        <div class="admin-card">
            <div class="card-header">
                <h3>Booth Scan Counts</h3>
            </div>
            <table class="data-table" id="boothTable">
                <thead>
                    <tr><th>#</th><th>College</th><th>Scans</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($perBooth as $b): ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><?= sanitize($b['name']) ?></td>
                        <td><strong><?= $b['scan_count'] ?></strong></td>
                        <td>
                            <div class="mini-bar-wrap">
                                <div class="mini-bar" style="width:<?= $totalReg > 0 ? round(($b['scan_count'] / $totalReg) * 100) : 0 ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Completions -->
        <div class="admin-card">
            <div class="card-header">
                <h3>Recent Completions</h3>
            </div>
            <div id="recentCompletions" class="recent-list">
                <p class="text-muted loading-msg">Loading...</p>
            </div>
        </div>
    </div>

    <!-- Student Progress Table -->
    <div class="admin-card">
        <div class="card-header">
            <h3>All Students Progress</h3>
            <input type="text" id="progressSearch" class="table-search" placeholder="Search name or school...">
        </div>
        <div class="table-wrap">
            <table class="data-table" id="progressTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>School</th>
                        <th>Grade</th>
                        <th>Booths</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody id="progressBody">
                    <tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const DASHBOARD_API = '<?= BASE_URL ?>/api/dashboard_data.php';
    const TOTAL_BOOTHS  = <?= TOTAL_BOOTHS ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
