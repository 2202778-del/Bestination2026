<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function require_admin_auth(): void {
    $token = $_COOKIE[ADMIN_COOKIE_NAME] ?? '';
    if (!$token || !validate_admin_session($token)) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function validate_admin_session(string $token): bool {
    if (strlen($token) !== 64) return false;
    try {
        $db = get_db();
        $stmt = $db->prepare(
            'SELECT session_token FROM admin_sessions WHERE session_token = ? AND expires_at > NOW()'
        );
        $stmt->execute([$token]);
        return (bool) $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

function create_admin_session(): string {
    $token = bin2hex(random_bytes(32));
    $db = get_db();
    $stmt = $db->prepare(
        'INSERT INTO admin_sessions (session_token, created_at, expires_at)
         VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR))'
    );
    $stmt->execute([$token, ADMIN_SESSION_HOURS]);
    return $token;
}

function destroy_admin_session(string $token): void {
    try {
        $db = get_db();
        $stmt = $db->prepare('DELETE FROM admin_sessions WHERE session_token = ?');
        $stmt->execute([$token]);
    } catch (Exception $e) {}
}

function is_admin_logged_in(): bool {
    $token = $_COOKIE[ADMIN_COOKIE_NAME] ?? '';
    return $token && validate_admin_session($token);
}
