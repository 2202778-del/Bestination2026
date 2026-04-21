<?php
require_once __DIR__ . '/config.php';

function generate_token(): string {
    return bin2hex(random_bytes(32));
}

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function passport_url(string $token, string $baseUrl = BASE_URL): string {
    return $baseUrl . '/passport.php?token=' . urlencode($token);
}

/**
 * Detects the actual server base URL to ensure links work on both localhost and live servers.
 * This is more reliable than just using the BASE_URL constant from config.php.
 *
 * @return string The dynamically detected base URL.
 */
function get_dynamic_base_url(): string {
    static $liveBaseUrl = null;
    if ($liveBaseUrl === null) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Normalize paths to use forward slashes for reliable replacement.
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $basePath = str_replace('\\', '/', BASE_PATH);

        // Find the web path by removing the document root from the base path (case-insensitive).
        $path = (stripos($basePath, $docRoot) === 0) ? substr($basePath, strlen($docRoot)) : '';

        $liveBaseUrl = rtrim($protocol . '://' . $host . $path, '/');
    }
    return $liveBaseUrl;
}

function format_datetime(string $datetime): string {
    return date('F j, Y g:i A', strtotime($datetime));
}

function get_setting(string $key, string $default = ''): string {
    try {
        $db = get_db();
        $stmt = $db->prepare('SELECT value FROM settings WHERE key_name = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function is_valid_ph_mobile(string $mobile): bool {
    return (bool) preg_match('/^09\d{9}$/', $mobile);
}
