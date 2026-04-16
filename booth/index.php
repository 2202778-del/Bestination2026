<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$error = '';

// Handle booth selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $boothId = (int) ($_POST['booth_id'] ?? 0);
    if ($boothId >= 1 && $boothId <= 9) {
        $_SESSION['operator_booth_id'] = $boothId;
        redirect(BASE_URL . '/booth/scan.php');
    } else {
        $error = 'Please select a valid booth.';
    }
}

// Load booths
try {
    $db = get_db();
    $booths = $db->query('SELECT * FROM booths ORDER BY sort_order')->fetchAll();
} catch (PDOException $e) {
    die('Database error. Please ensure setup has been run.');
}

// Booth icon map
$boothIcons = [
    'COE'  => '⚙️',
    'CAS'  => '📚',
    'CBAHM' => '💼',
    'COED' => '🏫',
    'CON'  => '🩺',
    'CICT' => '💻',
    'CCJE' => '⚖️',
    'CAMS' => '🔬',
    'CIT'  => '🛠️',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booth Operator — UBBC Bestination 2026</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/svg-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/booth.css">
</head>
<body class="booth-page">

<div class="booth-select-container">
    <div class="booth-select-header">
        <div class="event-logo small" style="margin-bottom: 8px; justify-content: center; flex-direction: column;">
            <img src="<?= BASE_URL ?>/assets/img/ub-logo-full.png" alt="UB Logo" class="logo-image-md" style="margin: 0 auto 12px; height: 56px;">
            <div class="logo-text">
                <span class="logo-main">BESTINATION</span>
                <span class="logo-year">2026</span>
            </div>
        </div>
        <h2>Booth Operator Panel</h2>
        <p>Select your college booth to begin scanning.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="booth-select-grid">
            <?php foreach ($booths as $booth): ?>
            <button type="submit" name="booth_id" value="<?= $booth['id'] ?>" class="booth-select-btn">
                <div class="icon-flat flat-accent" style="margin-bottom: 12px;">
                    <?= $boothIcons[$booth['code']] ?? '🏢' ?>
                </div>
                <span class="booth-select-name"><?= sanitize($booth['name']) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="booth-admin-link">
        <a href="<?= BASE_URL ?>/admin/login.php">Admin Panel</a>
    </div>
</div>

</body>
</html>
