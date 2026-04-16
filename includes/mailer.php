<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/qr_helper.php';

// Load PHPMailer if available
$phpmailerPath = BASE_PATH . '/lib/PHPMailer/src/';
if (is_dir($phpmailerPath)) {
    require_once $phpmailerPath . 'Exception.php';
    require_once $phpmailerPath . 'PHPMailer.php';
    require_once $phpmailerPath . 'SMTP.php';
}

function send_registration_email(array $student): bool {
    $qrFile = generate_qr($student['qr_token']);

    $subject = 'Your Bestination 2026 Passport is Ready, ' . $student['first_name'] . '!';
    $passportLink = BASE_URL . '/passport.php?token=' . urlencode($student['qr_token']);

    $html = build_registration_html($student, $passportLink);
    $plain = build_registration_plain($student, $passportLink);

    return dispatch_email($student['email'], $student['first_name'] . ' ' . $student['last_name'], $subject, $html, $plain, $qrFile);
}

function send_completion_email(array $student, array $scans): bool {
    $subject = 'Mission Complete! Congratulations, ' . $student['first_name'] . '!';
    $html = build_completion_html($student, $scans);
    $plain = build_completion_plain($student, $scans);
    return dispatch_email($student['email'], $student['first_name'] . ' ' . $student['last_name'], $subject, $html, $plain);
}

function dispatch_email(string $toEmail, string $toName, string $subject, string $html, string $plain, string $qrFile = ''): bool {
    // Use PHPMailer if loaded
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return send_via_phpmailer($toEmail, $toName, $subject, $html, $plain, $qrFile);
    }
    // Fallback: PHP mail()
    return send_via_mail($toEmail, $toName, $subject, $html);
}

function send_via_phpmailer(string $toEmail, string $toName, string $subject, string $html, string $plain, string $qrFile = ''): bool {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        // Enable verbose debug output
        // $mail->SMTPDebug = PHPMailer\PHPMailer\PHPMailer::DEBUG_SERVER;
        // $mail->Debugoutput = function($str, $level) {
        //     error_log("debug level $level; message: $str");
        // };

        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;

        // Embed the UB logo
        $logoPath = BASE_PATH . '/assets/img/ub-logo-white.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'ub_logo_white', ''); // Pass empty name
        }

        // Embed QR code if it exists
        if ($qrFile && file_exists($qrFile)) {
            $mail->addEmbeddedImage($qrFile, 'passport_qr');
        }

        $mail->isHTML(true);
        $mail->Body    = str_replace('cid:ub_logo_white_placeholder', 'cid:ub_logo_white', $html);
        $mail->AltBody = $plain;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $e->getMessage());
        return false;
    }
}

function send_via_mail(string $toEmail, string $toName, string $subject, string $html): bool {
    $boundary = md5(time());
    $headers  = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'Reply-To: ' . MAIL_FROM_ADDRESS,
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);
    $body  = "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n--{$boundary}--";
    return mail($toEmail, $subject, $body, $headers);
}

// ─── Email Templates ──────────────────────────────────────────────────────────

function build_registration_html(array $s, string $link): string {
    $name  = htmlspecialchars($s['first_name'] . ' ' . $s['last_name']);
    $fname = htmlspecialchars($s['first_name']);
    $school = htmlspecialchars($s['school_name']);
    $grade  = htmlspecialchars($s['grade_level']);
    $qrSrc  = 'cid:passport_qr';
    $logoSrc = 'cid:ub_logo_white_placeholder'; // Use a placeholder

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:20px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#862334,#a82d47);padding:40px 30px;text-align:center;">
    <img src="{$logoSrc}" alt="UB Logo" width="90" style="display:block;margin:0 auto 20px;height:auto;">
    <h1 style="color:#FFC553;margin:0;font-size:28px;letter-spacing:2px;">UBBC BESTINATION 2026</h1>
    <p style="color:#fff;margin:8px 0 0;font-size:14px;opacity:0.9;">Explore. Discover. Belong.</p>
  </td></tr>
  <!-- Body -->
  <tr><td style="padding:40px 30px;">
    <h2 style="color:#862334;margin:0 0 16px;">Your Passport is Ready, {$fname}!</h2>
    <p style="color:#555;line-height:1.6;margin:0 0 24px;">Welcome to Bestination 2026! Your registration is confirmed. Your mission is to visit all <strong>9 college booths</strong> and get your passport scanned at each one!</p>
    <!-- QR Code Box -->
    <table width="100%" cellpadding="0" cellspacing="0">
    <tr><td align="center" style="background:#fdf8f8;border:2px dashed #862334;border-radius:12px;padding:30px;margin-bottom:24px;">
      <p style="color:#862334;font-weight:bold;font-size:16px;margin:0 0 16px;">YOUR PASSPORT QR CODE</p>
      <img src="{$qrSrc}" width="220" height="220" alt="Your QR Passport" style="display:block;margin:0 auto;border-radius:8px;">
      <p style="color:#666;font-size:12px;margin:16px 0 0;">Show this QR code at each college booth to get scanned!</p>
    </td></tr>
    </table>
    <!-- Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;background:#fdf8f8;border-radius:8px;padding:20px;">
    <tr><td style="padding:6px 0;"><strong style="color:#862334;">Name:</strong> <span style="color:#333;">{$name}</span></td></tr>
    <tr><td style="padding:6px 0;"><strong style="color:#862334;">School:</strong> <span style="color:#333;">{$school}</span></td></tr>
    <tr><td style="padding:6px 0;"><strong style="color:#862334;">Grade Level:</strong> <span style="color:#333;">{$grade}</span></td></tr>
    </table>
    <p style="color:#555;line-height:1.6;margin:0 0 16px;">You can also view your progress and access your QR code anytime by visiting your passport page:</p>
    <table width="100%" cellpadding="0" cellspacing="0">
    <tr><td align="center">
      <a href="{$link}" style="display:inline-block;background:#FFC553;color:#862334;font-weight:bold;padding:14px 32px;border-radius:8px;text-decoration:none;font-size:16px;">View My Passport Page</a>
    </td></tr>
    </table>
  </td></tr>
  <!-- Footer -->
  <tr><td style="background:#862334;padding:24px 30px;text-align:center;">
    <p style="color:#FFC553;font-weight:bold;margin:0 0 4px;">University of Batangas</p>
    <p style="color:#aac4ff;font-size:12px;margin:0;">Bestination 2026 Organizing Committee</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
}

function build_registration_plain(array $s, string $link): string {
    return "Hi {$s['first_name']}!\n\nYour Bestination 2026 registration is confirmed.\n\nVisit your passport page to view your QR code and track your booth progress:\n{$link}\n\nSee you at the event!\nUBBC Bestination 2026 Organizing Committee";
}

function build_completion_html(array $s, array $scans): string {
    $fname = htmlspecialchars($s['first_name']);
    $name  = htmlspecialchars($s['first_name'] . ' ' . $s['last_name']);
    $completed = date('F j, Y g:i A', strtotime($s['completed_at'] ?? 'now'));
    $scanRows = '';
    foreach ($scans as $sc) {
        $boothName = htmlspecialchars($sc['booth_name']);
        $time = date('g:i A', strtotime($sc['scanned_at']));
        $scanRows .= "<tr><td style='padding:8px 12px;'>&#10003;</td><td style='padding:8px 12px;color:#333;'>{$boothName}</td><td style='padding:8px 12px;color:#666;font-size:12px;'>{$time}</td></tr>";
    }
    $logoSrc = 'cid:ub_logo_white_placeholder'; // Use a placeholder
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:20px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
  <tr><td style="background:linear-gradient(135deg,#862334,#a82d47);padding:40px 30px;text-align:center;">
    <img src="{$logoSrc}" alt="UB Logo" width="90" style="display:block;margin:0 auto 20px;height:auto;">
    <div style="font-size:60px;margin-bottom:10px;">&#127881;</div>
    <h1 style="color:#FFC553;margin:0;font-size:28px;">MISSION COMPLETE!</h1>
    <p style="color:#fff;margin:8px 0 0;font-size:16px;">UBBC Bestination 2026</p>
  </td></tr>
  <tr><td style="padding:40px 30px;text-align:center;">
    <h2 style="color:#862334;margin:0 0 8px;">Congratulations, {$fname}!</h2>
    <p style="color:#555;line-height:1.6;margin:0 0 24px;">You have successfully visited all <strong>9 college booths</strong> of the University of Batangas!</p>
    <p style="color:#888;font-size:13px;margin:0 0 24px;">Completed on: <strong>{$completed}</strong></p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:8px;overflow:hidden;border:1px solid #e0e0e0;margin-bottom:30px;">
      <tr style="background:#862334;"><td style="padding:10px 12px;color:#FFC553;font-weight:bold;font-size:13px;" colspan="3">Booths Visited</td></tr>
      {$scanRows}
    </table>
    <p style="color:#555;line-height:1.6;margin:0 0 16px;">Thank you for exploring everything UB has to offer. We hope to see you as a future Batangueno!</p>
    <div style="font-size:40px;">&#127968;</div>
  </td></tr>
  <tr><td style="background:#862334;padding:24px 30px;text-align:center;">
    <p style="color:#FFC553;font-weight:bold;margin:0 0 4px;">University of Batangas</p>
    <p style="color:#aac4ff;font-size:12px;margin:0;">Bestination 2026 Organizing Committee</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
}

function build_completion_plain(array $s, array $scans): string {
    $lines = ["Congratulations, {$s['first_name']}!", "", "You have completed all 9 booths at UBBC Bestination 2026!", ""];
    foreach ($scans as $sc) $lines[] = '✓ ' . $sc['booth_name'];
    $lines[] = "\nThank you!\nUBBC Bestination 2026 Organizing Committee";
    return implode("\n", $lines);
}
