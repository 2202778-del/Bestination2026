<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/qr_helper.php';

// --- START DYNAMIC BASE_URL LOGIC ---
// This detects the actual server address to ensure QR codes and links work on a live server,
// even if BASE_URL in config.php is set to 'localhost'.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Derive the web path from the filesystem path
$path = str_replace($_SERVER['DOCUMENT_ROOT'], '', BASE_PATH);
$LiveBaseUrl = rtrim($protocol . '://' . $host . str_replace('\\', '/', $path), '/');
// --- END DYNAMIC BASE_URL LOGIC ---

// Generate the registration QR (pointing to register.php)
$regUrl     = $LiveBaseUrl . '/register.php';
$regQrFile  = QR_CODES_DIR . 'registration_qr.png';
$regQrUrl   = $LiveBaseUrl . '/qr_codes/registration_qr.png';

// Generate registration QR using the centralized helper function.
create_qr_code_file($regUrl, $regQrFile, 10, 4);

// Live count
$totalReg = 0;
try {
    $db = get_db();
    $totalReg = (int) $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
} catch (Exception $e) { /* DB not set up yet */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>UBBC Bestination 2026 — Registration</title>
    <link rel="stylesheet" href="<?= $LiveBaseUrl ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= $LiveBaseUrl ?>/assets/css/animations.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #862334 0%, #c53c54 50%, #e8587a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .landing-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            padding: 48px 40px;
            max-width: 580px;
            width: 100%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .landing-logo { margin-bottom: 12px; }
        .landing-logo .logo-icon {
            font-size: 3.8rem;
            display: block;
            margin-bottom: 8px;
            filter: drop-shadow(0 4px 8px rgba(134, 35, 52, 0.2));
        }
        .landing-logo .logo-main {
            font-size: 2.2rem;
            font-weight: 900;
            color: #862334;
            letter-spacing: 2px;
            display: block;
            font-family: 'Poppins', sans-serif;
        }
        .landing-logo .logo-year {
            font-size: 0.85rem;
            font-weight: 800;
            color: #FFC553;
            letter-spacing: 3px;
            display: block;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-family: 'Poppins', sans-serif;
        }
        .landing-tagline {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 32px;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            font-style: italic;
        }
        .qr-section {
            background: linear-gradient(135deg, rgba(134, 35, 52, 0.08) 0%, rgba(8, 128, 174, 0.08) 100%);
            border: 2px dashed rgba(134, 35, 52, 0.25);
            border-radius: 20px;
            padding: 32px 24px;
            margin-bottom: 32px;
            backdrop-filter: blur(4px);
        }
        .qr-prompt {
            font-size: 1.15rem;
            font-weight: 800;
            color: #862334;
            margin-bottom: 6px;
            font-family: 'Poppins', sans-serif;
        }
        .qr-subprompt {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 20px;
            font-family: 'Poppins', sans-serif;
        }
        .reg-qr-img {
            width: 280px;
            height: 280px;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(134, 35, 52, 0.2);
            display: block;
            margin: 0 auto 24px;
            border: 2px solid rgba(134, 35, 52, 0.1);
        }
        .reg-link {
            display: inline-block;
            background: linear-gradient(135deg, #862334 0%, #a82d47 100%);
            color: #FFFFFF;
            font-weight: 800;
            padding: 15px 36px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 16px rgba(134, 35, 52, 0.25);
        }
        .reg-link:hover {
            background: linear-gradient(135deg, #a82d47 0%, #862334 100%);
            box-shadow: 0 8px 24px rgba(134, 35, 52, 0.35);
            transform: translateY(-2px);
        }
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin-top: 32px;
            padding-top: 28px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }
        .stat-item { text-align: center; }
        .stat-num {
            font-size: 2rem;
            font-weight: 900;
            color: #862334;
            font-family: 'Poppins', sans-serif;
        }
        .stat-lbl {
            font-size: 0.8rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
        }
        .footer-note {
            margin-top: 28px;
            font-size: 0.85rem;
            color: #888;
            font-family: 'Poppins', sans-serif;
        }
        .footer-note a {
            color: #862334;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer-note a:hover { color: #0880AE; }
        .booths-info {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 28px;
            line-height: 1.7;
            font-family: 'Poppins', sans-serif;
        }
        .booths-info strong { color: #862334; font-weight: 800; }
        
        /* Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<div class="landing-card">
    <div class="landing-logo" style="flex-direction: column; text-align: center; margin-bottom: 24px;">
        <img src="<?= $LiveBaseUrl ?>/assets/img/ub-logo-full.png" alt="University of Batangas Logo" class="logo-image-lg" style="max-height: 100px; margin-bottom: 16px;">
        <span class="logo-main">BESTINATION</span>
        <span class="logo-year">2026</span>
    </div>
    <p class="landing-tagline" style="margin-bottom: 24px;">Explore. Discover. Belong.</p>

    <div class="qr-section">
        <p class="qr-prompt"><i class="bi bi-qr-code-scan"></i> Scan to Register!</p>
        <p class="qr-subprompt">Use your phone camera to scan the QR code below</p>

        <?php if (file_exists($regQrFile)): ?>
        <img src="<?= $regQrUrl ?>?v=<?= filemtime($regQrFile) ?>" alt="Registration QR Code" class="reg-qr-img">
        <?php else: ?>
        <div style="width:260px;height:260px;background:#f0f0f0;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:#999;font-size:0.85rem;text-align:center;padding:20px;">
            QR Code will appear here.<br>Run setup.php first.
        </div>
        <?php endif; ?>

        <a href="<?= $regUrl ?>" class="reg-link">Or Click Here to Register</a>
    </div>

    <p class="booths-info">
        Visit all <strong>9 college booths</strong> of the University of Batangas and collect your digital stamps!
        Get your QR passport scanned at each college booth to complete your mission.
    </p>

    <div class="stats-bar">
        <div class="stat-item">
            <div class="icon-flat flat-accent flat-sm" style="margin: 0 auto 8px;"><i class="bi bi-people-fill"></i></div>
            <div class="stat-num" id="liveCount"><?= $totalReg ?></div>
            <div class="stat-lbl">Registered</div>
        </div>
        <div class="stat-item">
            <div class="icon-flat flat-primary flat-sm" style="margin: 0 auto 8px;"><i class="bi bi-building"></i></div>
            <div class="stat-num"><?= TOTAL_BOOTHS ?></div>
            <div class="stat-lbl">Colleges</div>
        </div>
    </div>

    <div class="footer-note">
        University of Batangas &bull; UBBC Bestination 2026<br>
        <a href="<?= $LiveBaseUrl ?>/admin/login.php">Admin</a> &bull;
        <a href="<?= $LiveBaseUrl ?>/booth/index.php">Booth Operator</a>
    </div>
</div>

<script>
// Auto-refresh registration count every 30 seconds
setInterval(function() {
    fetch('<?= $LiveBaseUrl ?>/api/dashboard_data.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.total_registered !== undefined) {
                var el = document.getElementById('liveCount');
                if (el) el.textContent = d.total_registered;
            }
        }).catch(function(){});
}, 30000);
</script>
</body>
</html>
