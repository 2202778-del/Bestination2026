<?php
require_once __DIR__ . '/config.php';

/**
 * Generate a QR code PNG for the given student token.
 * Saves to qr_codes/{token}.png and returns the file path.
 * Tries local phpqrcode library first, falls back to qrserver.com API.
 */
function generate_qr(string $token): string {
    $url      = BASE_URL . '/passport.php?token=' . urlencode($token);
    $filePath = QR_CODES_DIR . $token . '.png';

    if (file_exists($filePath)) {
        return $filePath;
    }

    // Attempt 1: local phpqrcode library
    $libFile = BASE_PATH . '/lib/phpqrcode/qrlib.php';
    if (file_exists($libFile)) {
        require_once $libFile;
        QRcode::png($url, $filePath, QR_ECLEVEL_M, 8, 2);
        if (file_exists($filePath) && filesize($filePath) > 0) {
            return $filePath;
        }
    }

    // Attempt 2: qrserver.com free API (requires internet)
    $apiUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
    $context = stream_context_create(['http' => ['timeout' => 10]]);
    $imgData = @file_get_contents($apiUrl, false, $context);
    if ($imgData !== false) {
        file_put_contents($filePath, $imgData);
        return $filePath;
    }

    // Attempt 3: generate a simple placeholder image using GD
    generate_placeholder_qr($filePath, $url);
    return $filePath;
}

function generate_placeholder_qr(string $filePath, string $url): bool {
    // Check if the GD extension is loaded, as it's required for image functions.
    if (!extension_loaded('gd') || !function_exists('imagecreate')) {
        return false;
    }

    $img = @imagecreate(300, 300);
    if (!$img) {
        return false; // Failed to create image resource.
    }

    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $white);
    imagestring($img, 3, 10, 140, 'QR Pending', $black);
    imagestring($img, 1, 10, 160, substr($url, 0, 50), $black);
    $success = imagepng($img, $filePath);
    imagedestroy($img);
    return $success;
}

function qr_image_url(string $token): string {
    return BASE_URL . '/qr_codes/' . $token . '.png';
}
