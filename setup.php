<?php
/**
 * UBBC Bestination 2026 — One-Time Setup Script
 * Visit: http://localhost/Bestination2026/setup.php
 * The database is created automatically on page load.
 * DELETE THIS FILE after setup is complete!
 */

define('BASE_PATH', __DIR__);
define('QR_CODES_DIR', __DIR__ . '/qr_codes/');

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bestination2026';

$autoMessages = [];
$autoErrors   = [];
$formMessages = [];
$formErrors   = [];

// ─── AUTO: Create database & tables on every page load (idempotent) ───────────
try {
    // Connect without selecting a DB so we can CREATE DATABASE
    $pdo = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");

    // --- Schema migration checks (add missing columns/indexes to existing tables) ---
    // This makes the script safe to run even if tables from a previous version exist.

    // Check for interested_booth_id in students table
    $colCheck = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_NAME = 'students' AND COLUMN_NAME = 'interested_booth_id' LIMIT 1");
    if ($colCheck && $colCheck->fetch() === false) {
        $pdo->exec("ALTER TABLE students ADD COLUMN interested_booth_id TINYINT UNSIGNED DEFAULT NULL AFTER grade_level");
        $autoMessages[] = '&#10003; Added missing column `interested_booth_id` to `students` table.';
    }

    // Check for UNIQUE constraint on mobile
    $idxCheck = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_NAME = 'students' AND INDEX_NAME = 'mobile' LIMIT 1");
    if ($idxCheck && $idxCheck->fetch() === false) {
        // Check if the column has duplicate values before adding a unique index
        $dupeCheck = $pdo->query("SELECT COUNT(*) FROM (SELECT 1 FROM students GROUP BY mobile HAVING COUNT(*) > 1) as dupes")->fetchColumn();
        if ($dupeCheck > 0) {
            $autoErrors[] = '&#10060; Cannot add UNIQUE index to `mobile` column because duplicate mobile numbers exist. Please clean the data manually in the `students` table.';
        } else {
            $pdo->exec("ALTER TABLE students ADD UNIQUE KEY `mobile` (`mobile`)");
            $autoMessages[] = '&#10003; Added missing `UNIQUE` index to `mobile` column.';
        }
    }

    // Run all DDL statements (CREATE TABLE IF NOT EXISTS + INSERT IGNORE — safe to repeat)
    $statements = [
        // Students
        "CREATE TABLE IF NOT EXISTS students (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            last_name           VARCHAR(100) NOT NULL,
            first_name          VARCHAR(100) NOT NULL,
            middle_name         VARCHAR(100) DEFAULT NULL,
            email               VARCHAR(191) NOT NULL UNIQUE,
            mobile              VARCHAR(20)  NOT NULL UNIQUE,
            gender              ENUM('Male','Female') NOT NULL,
            school_name         VARCHAR(200) NOT NULL,
            grade_level         VARCHAR(50)  NOT NULL,
            interested_booth_id TINYINT UNSIGNED DEFAULT NULL,
            qr_token            CHAR(64)     NOT NULL UNIQUE,
            registered_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at        DATETIME     DEFAULT NULL,
            email_sent          TINYINT(1)   NOT NULL DEFAULT 0,
            complete_email_sent TINYINT(1)   NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Booths
        "CREATE TABLE IF NOT EXISTS booths (
            id         TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code       VARCHAR(10)  NOT NULL UNIQUE,
            name       VARCHAR(150) NOT NULL,
            sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Booth seed data (INSERT IGNORE = skip if already exists)
        "INSERT IGNORE INTO booths (code, name, sort_order) VALUES
            ('COE', 'College of Engineering', 1),
            ('CAS', 'College of Arts and Sciences', 2),
            ('CBAHM', 'College of Business, Accountancy, and Hospitality Management', 3),
            ('CEDU', 'College of Education', 4),
            ('CON', 'College of Nursing', 5),
            ('CICT', 'College of Information and Communications Technology', 6),
            ('CCJE', 'College of Criminal Justice Education', 7),
            ('CAMS', 'College of Allied Medical Sciences', 8),
            ('CIT', 'College of Industrial Technology', 9)",

        // Scans
        "CREATE TABLE IF NOT EXISTS scans (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED     NOT NULL,
            booth_id   TINYINT UNSIGNED NOT NULL,
            scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_student_booth (student_id, booth_id),
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (booth_id)   REFERENCES booths(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Admin sessions
        "CREATE TABLE IF NOT EXISTS admin_sessions (
            session_token CHAR(64)  NOT NULL PRIMARY KEY,
            created_at    DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at    DATETIME  NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Settings
        "CREATE TABLE IF NOT EXISTS settings (
            key_name VARCHAR(100) NOT NULL PRIMARY KEY,
            value    TEXT         NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Settings seed data
        "INSERT IGNORE INTO settings (key_name, value) VALUES
            ('admin_password_hash', ''),
            ('event_name', 'UBBC Bestination 2026'),
            ('registration_open', '1')",
    ];

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    $autoMessages[] = '&#10003; Database <strong>bestination2026</strong> is ready — all tables and seed data are in place.';

} catch (PDOException $e) {
    $autoErrors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
}

// ─── Handle admin password form ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($password)) {
        $formErrors[] = 'Password cannot be empty.';
    } elseif ($password !== $password2) {
        $formErrors[] = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $formErrors[] = 'Password must be at least 6 characters.';
    } else {
        try {
            $pwPdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $hash  = password_hash($password, PASSWORD_BCRYPT);
            $stmt  = $pwPdo->prepare("UPDATE settings SET value = ? WHERE key_name = 'admin_password_hash'");
            $stmt->execute([$hash]);
            $formMessages[] = '&#10003; Admin password set successfully! You can now log in to the admin panel.';
        } catch (PDOException $e) {
            $formErrors[] = 'Error saving password: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// ─── Check current state ──────────────────────────────────────────────────────
$dbReady     = false;
$passIsSet   = false;
$qrWritable  = is_writable(QR_CODES_DIR);
$hasQrLib    = file_exists(__DIR__ . '/lib/phpqrcode/qrlib.php');
$hasMailer   = is_dir(__DIR__ . '/lib/PHPMailer/src');

try {
    $checkPdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $checkPdo->query('SELECT 1 FROM students LIMIT 1');
    $dbReady = true;

    $hash = $checkPdo->query("SELECT value FROM settings WHERE key_name = 'admin_password_hash'")->fetchColumn();
    $passIsSet = !empty($hash);
} catch (PDOException $e) {}

$allDone = $dbReady && $passIsSet;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — UBBC Bestination 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #862334 0%, #c53c54 50%, #e8587a 100%);
            min-height: 100vh;
            padding: 36px 16px;
        }
        .setup-wrap { max-width: 680px; margin: 0 auto; }

        /* Header */
        .setup-header {
            text-align: center;
            color: #fff;
            margin-bottom: 32px;
        }
        .setup-header .icon { font-size: 3.2rem; display: block; margin-bottom: 12px; }
        .setup-header h1 { font-size: 2rem; font-weight: 900; letter-spacing: 1px; color: #FFC553; }
        .setup-header p  { font-size: 0.95rem; color: rgba(255,255,255,0.85); margin-top: 8px; }

        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: slideInUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #862334;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
        }

        /* Status rows */
        .status-list { display: flex; flex-direction: column; gap: 0; }
        .status-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
        }
        .status-row:last-child { border-bottom: none; }
        .status-icon { font-size: 1.2rem; width: 24px; text-align: center; flex-shrink: 0; }
        .status-label { flex: 1; color: #555; }
        .badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 800;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-family: 'Poppins', sans-serif;
        }
        .badge-ok    { background: rgba(111, 207, 151, 0.2); color: #1d663a; }
        .badge-warn  { background: rgba(242, 153, 74, 0.2); color: #8b4513; }
        .badge-error { background: rgba(220, 21, 0, 0.2); color: #8b0a00; }

        /* Alerts */
        .alert {
            padding: 16px 18px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 0.9rem;
            line-height: 1.6;
            border-left: 4px solid;
            animation: slideInDown 0.4s ease;
            font-family: 'Poppins', sans-serif;
        }
        .alert ul { padding-left: 18px; margin: 0; }
        .alert li  { margin-top: 6px; }
        .alert-success { background: rgba(111, 207, 151, 0.1); color: #1d663a; border-left-color: #6FCF97; }
        .alert-error   { background: rgba(220, 21, 0, 0.1); color: #8b0a00; border-left-color: #DC1500; }
        .alert-info    { background: rgba(8, 128, 174, 0.1); color: #034477; border-left-color: #0880AE; }

        /* Forms */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 700;
            color: #862334;
            margin-bottom: 8px;
            font-family: 'Poppins', sans-serif;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #EAEAEA;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }
        .form-group input:focus {
            outline: none;
            border-color: #862334;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(134, 35, 52, 0.1);
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Poppins', sans-serif;
        }
        .btn-primary {
            background: linear-gradient(135deg, #862334 0%, #a82d47 100%);
            color: #fff;
            width: 100%;
            box-shadow: 0 4px 16px rgba(134, 35, 52, 0.25);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #a82d47 0%, #862334 100%);
            box-shadow: 0 8px 24px rgba(134, 35, 52, 0.35);
            transform: translateY(-2px);
        }

        /* Done box */
        .done-box {
            background: linear-gradient(135deg, #6FCF97 0%, #4db876 100%);
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            color: #fff;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(111, 207, 151, 0.25);
            animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .done-box .done-icon { font-size: 3rem; display: block; margin-bottom: 12px; }
        .done-box h2 { font-size: 1.5rem; font-weight: 900; margin-bottom: 10px; font-family: 'Poppins', sans-serif; }
        .done-box p  { font-size: 0.95rem; color: rgba(255,255,255,0.9); margin-bottom: 24px; font-family: 'Poppins', sans-serif; }
        .quick-links { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
        .quick-link {
            background: #FFC553;
            color: #862334;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 800;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 12px rgba(255, 197, 83, 0.2);
        }
        .quick-link:hover {
            background: #ffe44d;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 197, 83, 0.3);
        }

        code {
            background: rgba(0, 0, 0, 0.08);
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.85em;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #1e1e2e;
            color: #cdd6f4;
            padding: 16px 18px;
            border-radius: 10px;
            font-size: 0.8rem;
            overflow-x: auto;
            margin-top: 12px;
            line-height: 1.6;
            font-family: 'Courier New', monospace;
        }

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
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
<div class="setup-wrap">

    <!-- Header -->
    <div class="setup-header">
        <div class="icon-flat flat-gold" style="margin: 0 auto 16px;">🛠️</div>
        <h1>BESTINATION 2026</h1>
        <p>System Setup &mdash; University of Batangas</p>
    </div>

    <!-- Auto DB Result -->
    <?php if (!empty($autoErrors)): ?>
    <div class="alert alert-error">
        <strong>&#10060; Database Error:</strong><br>
        <?= implode('<br>', $autoErrors) ?>
    </div>
    <?php elseif (!empty($autoMessages)): ?>
    <div class="alert alert-success">
        <?= implode('<br>', $autoMessages) ?>
    </div>
    <?php endif; ?>

    <!-- System Status -->
    <div class="card">
        <div class="card-title">📊 System Status</div>
        <div class="status-list">
            <div class="status-row">
                <span class="status-icon"><?= $dbReady ? '✅' : '❌' ?></span>
                <span class="status-label">Database &amp; Tables (<code>bestination2026</code>)</span>
                <span class="badge <?= $dbReady ? 'badge-ok' : 'badge-error' ?>"><?= $dbReady ? 'Ready' : 'Failed' ?></span>
            </div>
            <div class="status-row">
                <span class="status-icon"><?= $passIsSet ? '✅' : '🔒' ?></span>
                <span class="status-label">Admin Password</span>
                <span class="badge <?= $passIsSet ? 'badge-ok' : 'badge-warn' ?>"><?= $passIsSet ? 'Set' : 'Not Set' ?></span>
            </div>
            <div class="status-row">
                <span class="status-icon"><?= $qrWritable ? '✅' : '❌' ?></span>
                <span class="status-label">QR Codes Directory (writable)</span>
                <span class="badge <?= $qrWritable ? 'badge-ok' : 'badge-error' ?>"><?= $qrWritable ? 'Writable' : 'Not Writable' ?></span>
            </div>
            <div class="status-row">
                <span class="status-icon"><?= $hasQrLib ? '✅' : '⚠️' ?></span>
                <span class="status-label">phpqrcode Library</span>
                <span class="badge <?= $hasQrLib ? 'badge-ok' : 'badge-warn' ?>"><?= $hasQrLib ? 'Installed' : 'API Fallback' ?></span>
            </div>
            <div class="status-row">
                <span class="status-icon"><?= $hasMailer ? '✅' : '⚠️' ?></span>
                <span class="status-label">PHPMailer Library</span>
                <span class="badge <?= $hasMailer ? 'badge-ok' : 'badge-warn' ?>"><?= $hasMailer ? 'Installed' : 'mail() Fallback' ?></span>
            </div>
        </div>
    </div>

    <!-- Admin Password Form -->
    <div class="card">
        <div class="card-title">🔑 <?= $passIsSet ? 'Change' : 'Set' ?> Admin Password</div>

        <?php if (!empty($formMessages)): ?>
        <div class="alert alert-success"><?= implode('<br>', $formMessages) ?></div>
        <?php endif; ?>
        <?php if (!empty($formErrors)): ?>
        <div class="alert alert-error"><ul><?php foreach ($formErrors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <?php if ($passIsSet): ?>
        <div class="alert alert-info" style="margin-bottom:16px;">Admin password is already set. Use the form below to change it.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="password">New Password <span style="color:#dc3545;">*</span></label>
                <input type="password" id="password" name="password" placeholder="At least 6 characters" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="password2">Confirm Password <span style="color:#dc3545;">*</span></label>
                <input type="password" id="password2" name="password2" placeholder="Repeat the password" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">
                <?= $passIsSet ? '🔄 Update Admin Password' : '🔒 Set Admin Password' ?>
            </button>
        </form>
    </div>

    <!-- SMTP Config Reminder -->
    <div class="card">
        <div class="card-title">✉️ Configure Email (SMTP)</div>
        <p style="font-size:0.88rem;color:#555;margin-bottom:10px;">
            Edit <code>includes/config.php</code> and fill in your email credentials so the system can send QR passports and completion emails:
        </p>
        <pre>define('SMTP_HOST',         'smtp.gmail.com');
define('SMTP_PORT',         587);
define('SMTP_USER',         'your_email@gmail.com');
define('SMTP_PASS',         'your_gmail_app_password');
define('MAIL_FROM_ADDRESS', 'your_email@gmail.com');
define('BASE_URL',          'http://localhost/Bestination2026');</pre>
        <p style="font-size:0.78rem;color:#888;margin-top:10px;">
            For Gmail: enable 2FA and generate an <strong>App Password</strong> at myaccount.google.com &rarr; Security &rarr; App Passwords.
        </p>
    </div>

    <!-- Done! -->
    <?php if ($allDone): ?>
    <div class="done-box">
        <div class="icon-flat flat-success flat-xl" style="margin: 0 auto 16px;">🎉</div>
        <h2>Setup Complete!</h2>
        <p>The system is ready to use. Remember to delete <code>setup.php</code> from your server before the event!</p>
        <div class="quick-links">
            <a href="http://localhost/Bestination2026/" class="quick-link">🏠 Landing Page</a>
            <a href="http://localhost/Bestination2026/register.php" class="quick-link">📝 Registration</a>
            <a href="http://localhost/Bestination2026/booth/index.php" class="quick-link">📱 Booth Scanner</a>
            <a href="http://localhost/Bestination2026/admin/login.php" class="quick-link">🛡️ Admin Panel</a>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
