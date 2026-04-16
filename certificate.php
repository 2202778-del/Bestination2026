<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Ensure FPDF library exists
$fpdfPath = BASE_PATH . '/lib/fpdf/fpdf.php';
if (!file_exists($fpdfPath)) {
    die('FPDF library not found. Please install it in /lib/fpdf/');
}
require_once $fpdfPath;

$token = trim($_GET['token'] ?? '');

if (!$token || strlen($token) !== 64) {
    http_response_code(400);
    die('Invalid certificate link.');
}

try {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM students WHERE qr_token = ? AND completed_at IS NOT NULL');
    $stmt->execute([$token]);
    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(403);
        die('Certificate is not yet available for this participant or the link is invalid.');
    }

    $fullName = $student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'][0] . '. ' : '') . $student['last_name'];
    $completionDate = date('F j, Y', strtotime($student['completed_at']));

    // --- PDF Generation using FPDF ---

    class PDF extends FPDF {
        // Page footer
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->SetTextColor(150);
            $this->Cell(0,10,'UBBC Bestination 2026 - Certificate ID: ' . substr($GLOBALS['token'], 0, 16), 0, 0, 'C');
        }
    }

    // A4 paper, landscape orientation
    $pdf = new PDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetTitle('Certificate of Completion - ' . $fullName);
    $pdf->SetAutoPageBreak(false);

    // Border
    $pdf->SetLineWidth(1.5);
    $pdf->SetDrawColor(134, 35, 52); // UB Red
    $pdf->Rect(5, 5, 287, 200); // A4 Landscape is 297x210

    // UB Logo
    $pdf->Image(BASE_PATH . '/assets/img/ub-logo-full.png', 125, 15, 50);

    // Main Title
    $pdf->SetFont('Arial', 'B', 28);
    $pdf->SetTextColor(134, 35, 52);
    $pdf->Ln(55);
    $pdf->Cell(0, 10, 'Certificate of Completion', 0, 1, 'C');

    // "This certifies that"
    $pdf->SetFont('Arial', '', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(15);
    $pdf->Cell(0, 10, 'This certificate is awarded to', 0, 1, 'C');

    // Student Name
    $pdf->SetFont('Arial', 'B', 36);
    $pdf->SetTextColor(255, 197, 83); // UB Gold
    $pdf->Ln(10);
    $pdf->Cell(0, 15, $fullName, 0, 1, 'C');

    // "for successfully visiting..."
    $pdf->SetFont('Arial', '', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'for successfully visiting all college booths during the', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, EVENT_NAME, 0, 1, 'C');
    $pdf->SetFont('Arial', '', 16);
    $pdf->Cell(0, 10, 'held on ' . $completionDate . '.', 0, 1, 'C');

    $pdf->Output('I', 'Bestination2026-Certificate-' . $student['last_name'] . '.pdf');

} catch (Exception $e) {
    error_log('Certificate generation error: ' . $e->getMessage());
    die('A system error occurred while generating the certificate.');
}