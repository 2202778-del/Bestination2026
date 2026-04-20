<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/qr_helper.php';

$token = trim($_GET['token'] ?? '');
$isDup  = isset($_GET['dup']);

if (!$token || strlen($token) !== 64) {
    http_response_code(404);
    exit('Invalid passport link.');
}

try {
    $db = get_db();

    // Load student
    $stmt = $db->prepare('SELECT * FROM students WHERE qr_token = ?');
    $stmt->execute([$token]);
    $student = $stmt->fetch();
    if (!$student) {
        http_response_code(404);
        exit('Passport not found.');
    }

    // Load all booths
    $booths = $db->query('SELECT * FROM booths ORDER BY sort_order')->fetchAll();

    // Load this student's scans
    $scanStmt = $db->prepare('SELECT booth_id, scanned_at FROM scans WHERE student_id = ?');
    $scanStmt->execute([$student['id']]);
    $scansRaw = $scanStmt->fetchAll();

    $visitedBooths = [];
    foreach ($scansRaw as $s) {
        $visitedBooths[$s['booth_id']] = $s['scanned_at'];
    }

    $scanCount = count($visitedBooths);
    $completed = $student['completed_at'] !== null;
    $fullName  = sanitize($student['first_name'] . ' ' . $student['last_name']);

    // Booth icon map
    $boothIcons = [
        'COE'   => 'bi-gear-fill',
        'CAS'   => 'bi-book-fill',
        'CBAHM' => 'bi-briefcase-fill',
        'CEDU'  => 'bi-mortarboard-fill',
        'CON'   => 'bi-bandaid-fill',
        'CICT'  => 'bi-pc-display',
        'CCJE'  => 'bi-shield-lock-fill',
        'CAMS'  => 'bi-clipboard2-pulse-fill',
        'CIT'   => 'bi-tools',
    ];

    // Ensure QR exists
    generate_qr($token);
    $qrUrl = qr_image_url($token);

} catch (PDOException $e) {
    error_log('Passport load error: ' . $e->getMessage());
    exit('System error. Please try again.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700;800&display=swap" rel="stylesheet">
    <title>My Passport — UBBC Bestination 2026</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/passport.css">
</head>
<body class="passport-page">

<div class="passport-container">

    <!-- Header -->
    <div class="passport-header <?= $completed ? 'completed' : '' ?>">
        <div class="event-logo small" style="margin-bottom: 8px; justify-content: center; flex-direction: column;">
            <img src="<?= BASE_URL ?>/assets/img/ub-logo-white.png" alt="UB Logo" class="logo-image-md" style="margin: 0 auto 12px;">
            <div class="logo-text">
                <span class="logo-main" style="color: #862334;">BESTINATION</span>
                <span class="logo-year">2026</span>
            </div>
        </div>
        <h2 class="passport-name"><?= $fullName ?></h2>
        <p class="passport-sub"><?= sanitize($student['school_name']) ?> &bull; <?= sanitize($student['grade_level']) ?></p>
    </div>

    <?php if ($isDup): ?>
    <div class="alert alert-info">
        You are already registered! Here is your existing passport.
    </div>
    <?php endif; ?>

    <?php if ($completed): ?>
    <div class="completion-banner">
        <div class="icon-flat flat-primary flat-lg"><i class="bi bi-check-circle-fill"></i></div>
        <h3>Mission Complete!</h3>
        <p>You visited all 9 booths on <?= date('F j, Y', strtotime($student['completed_at'])) ?>!</p>
        <a href="<?= BASE_URL ?>/certificate.php?token=<?= urlencode($token) ?>" target="_blank" class="btn btn-primary btn-sm" style="margin-top: 16px;">
            <i class="bi bi-download"></i> Download Certificate
        </a>
    </div>
    <?php endif; ?>

    <!-- QR Code -->
    <div class="qr-card">
        <p class="qr-label">Your Passport QR Code</p>
        <img src="<?= $qrUrl ?>" alt="Your QR Code Passport" class="qr-image" id="passportQr">
        <p class="qr-hint">Show this QR at each college booth to get scanned.</p>
    </div>

    <!-- Progress -->
    <div class="progress-section">
        <div class="progress-header">
            <span class="progress-label">Booth Progress</span>
            <span class="progress-count" id="progressCount"><?= $scanCount ?> / <?= TOTAL_BOOTHS ?></span>
        </div>
        <div class="progress-bar-wrap">
            <div class="progress-bar" id="progressBar" style="width: <?= round(($scanCount / TOTAL_BOOTHS) * 100) ?>%"></div>
        </div>
    </div>

    <!-- Booth Grid -->
    <div class="booth-grid" id="boothGrid">
        <?php foreach ($booths as $booth): ?>
        <?php
            $bid     = $booth['id'];
            $visited = isset($visitedBooths[$bid]);
            $iconClass = $boothIcons[$booth['code']] ?? 'bi-question-lg';
            $time    = $visited ? date('g:i A', strtotime($visitedBooths[$bid])) : '';
        ?>
        <div class="booth-card <?= $visited ? 'visited' : 'pending' ?>" data-booth-id="<?= $bid ?>">
            <div class="booth-stamp">
                <?php if ($visited): ?>
                <span class="stamp-check"><i class="bi bi-check-lg"></i></span>
                <?php else: ?>
                <i class="bi <?= $iconClass ?>"></i>
                <?php endif; ?>
            </div>
            <div class="booth-info">
                <span class="booth-name"><?= sanitize($booth['name']) ?></span>
                <?php if ($visited): ?>
                <span class="booth-time"><?= $time ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="passport-footer">
        <p>UBBC Bestination 2026 &bull; University of Batangas</p>
        <p class="update-notice" id="updateNotice">Auto-updates every 15 seconds</p>
    </div>
</div>

<script>
    const PASSPORT_TOKEN = '<?= addslashes($token) ?>';
    const PROGRESS_API   = '<?= BASE_URL ?>/api/progress.php';
    const TOTAL_BOOTHS   = <?= TOTAL_BOOTHS ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/passport.js"></script>
</body>
</html>
