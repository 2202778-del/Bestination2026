<?php
require_once __DIR__ . '/config.php';

/**
 * Generates a QR code for a given URL and saves it to a file path.
 * Tries local phpqrcode, falls back to qrserver.com API, then a GD placeholder.
 *
 * @param string $url The URL to encode.
 * @param string $filePath The full path to save the PNG file.
 * @param int $pixelSize The pixel size of each module for phpqrcode.
 * @param int $margin The margin size in modules for phpqrcode.
 * @param bool $force If true, will overwrite an existing file.
 * @return bool True if the file was created successfully (even placeholder).
 */
function create_qr_code_file(string $url, string $filePath, int $pixelSize = 8, int $margin = 2, bool $force = false): bool {
    if (!$force && file_exists($filePath) && filesize($filePath) > 0) {
        return true;
    }

    // Attempt 1: local phpqrcode library
    $libFile = BASE_PATH . '/lib/phpqrcode/qrlib.php';
    if (file_exists($libFile)) {
        require_once $libFile;
        QRcode::png($url, $filePath, QR_ECLEVEL_M, $pixelSize, $margin);
        if (file_exists($filePath) && filesize($filePath) > 0) {
            return true;
        }
    }

    // Attempt 2: qrserver.com free API (requires internet)
    $apiSize = ($pixelSize * 35 < 1000) ? $pixelSize * 35 : 1000; // Rough conversion for API
    $apiUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $apiSize . 'x' . $apiSize . '&data=' . urlencode($url);
    $context = stream_context_create(['http' => ['timeout' => 10]]);
    $imgData = @file_get_contents($apiUrl, false, $context);
    if ($imgData !== false && file_put_contents($filePath, $imgData)) {
        return true;
    }

    // Attempt 3: generate a simple placeholder image using GD
    return generate_placeholder_qr($filePath, $url);
}

/**
 * Generate a QR code PNG for the given student token.
 * Saves to qr_codes/{token}.png and returns the file path.
 */
function generate_qr(string $token, string $baseUrl = BASE_URL): string {
    $url      = $baseUrl . '/passport.php?token=' . urlencode($token);
    $filePath = QR_CODES_DIR . $token . '.png';
    create_qr_code_file($url, $filePath, 8, 2);
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

function qr_image_url(string $token, string $baseUrl = BASE_URL): string {
    return $baseUrl . '/qr_codes/' . $token . '.png';
}
