<?php
// ─── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'bestination2026');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── Application ──────────────────────────────────────────────────────────────
// TODO: Change this to your live domain before deployment
define('BASE_URL', 'http://localhost/Bestination2026');
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('QR_CODES_DIR')) {
    define('QR_CODES_DIR', BASE_PATH . '/qr_codes/');
}
define('TOTAL_BOOTHS', 9);

// ─── SMTP (PHPMailer) ─────────────────────────────────────────────────────────
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'bestination.ubbc@ub.edu.ph');       // ← change this
define('SMTP_PASS', 'cimr nxhe mfap wzyd');           // ← change this (Gmail App Password)
define('MAIL_FROM_ADDRESS', 'bestination.ubbc@ub.edu.ph'); // ← change this
define('MAIL_FROM_NAME', 'UBBC Bestination 2026');

// ─── Admin ────────────────────────────────────────────────────────────────────
define('ADMIN_SESSION_HOURS', 12);
define('ADMIN_COOKIE_NAME', 'bestination_admin');

// ─── Event ────────────────────────────────────────────────────────────────────
define('EVENT_NAME', 'UBBC Bestination 2026');
define('EVENT_TAGLINE', 'Explore. Discover. Belong.');
