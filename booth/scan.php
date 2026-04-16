<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Ensure booth is selected
$boothId = (int) ($_SESSION['operator_booth_id'] ?? 0);
if (!$boothId) {
    redirect(BASE_URL . '/booth/index.php');
}

try {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM booths WHERE id = ?');
    $stmt->execute([$boothId]);
    $booth = $stmt->fetch();
    if (!$booth) {
        redirect(BASE_URL . '/booth/index.php');
    }
} catch (PDOException $e) {
    die('Database error.');
}

$boothIcons = [
    'COE'  => '⚙️',
    'CAS'  => '📚',
    'CBAHM'  => '💼',
    'COED' => '🏫',
    'CON'  => '🩺',
    'CICT'  => '💻',
    'CCJE' => '⚖️',
    'CAMS' => '🔬',
    'CIT'  => '🛠️',
];
$icon = $boothIcons[$booth['code']] ?? '🏢';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($booth['name']) ?> Scanner — Bestination 2026</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/svg-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/booth.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="booth-scan-page">

<div class="scan-container">
    <!-- Booth Identity -->
    <div class="scan-header">
        <div class="scan-booth-badge" style="align-items: center;">
            <img src="<?= BASE_URL ?>/assets/img/ub-logo-white.png" alt="UB Logo" class="logo-image-sm" style="margin: 0 16px 0 0; height: 40px;">
            <div>
                <div class="scan-booth-name"><?= sanitize($booth['name']) ?></div>
                <div class="scan-booth-label">Scanning Mode Active</div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/booth/index.php" class="change-booth-btn" onclick="return confirm('Switch booth?')">Change Booth</a>
    </div>

    <!-- Result Display (shown after scan) -->
    <div id="scanResult" class="scan-result hidden">
        <div class="result-icon" id="resultIcon"></div>
        <div class="result-title" id="resultTitle"></div>
        <div class="result-name" id="resultName"></div>
        <div class="result-detail" id="resultDetail"></div>
        <div class="result-progress" id="resultProgress"></div>
    </div>

    <!-- Camera Scanner -->
    <div id="scannerWrap" class="scanner-wrap">
        <div id="qr-reader"></div>
        <p class="scanner-hint">Point camera at student's QR passport</p>
    </div>

    <!-- Scan Counter -->
    <div class="scan-counter">
        <span>Scans this session: <strong id="sessionCount">0</strong></span>
    </div>
</div>

<script>
    const BOOTH_ID    = <?= $boothId ?>;
    const SCAN_API    = '<?= BASE_URL ?>/api/submit_scan.php';
    const BOOTH_NAME  = '<?= addslashes(sanitize($booth['name'])) ?>';
</script>
<script src="<?= BASE_URL ?>/assets/js/scanner.js"></script>
</body>
</html>
