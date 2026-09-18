<?php
/**
 * Download Attendance Report as PDF
 * Location: Student/downloadAttendanceReport.php
 */

include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/advanced_features.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$admissionNo = $_SESSION['admissionNumber'];
$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];
$sessionTermId = 1; // From active session

// Generate report
$pdfGenerator = new PDFReportGenerator($conn);
$html = $pdfGenerator->generateAttendanceHTML($admissionNo, $classId, $classArmId, $sessionTermId);

// If TCPDF library is available, use it
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
    
    try {
        // Using TCPDF or dompdf
        class MYPDF extends TCPDF {
            public function Header() {
                $this->SetFont('helvetica', 'B', 15);
                $this->Cell(0, 10, 'Attendance Report', 0, false, 'C', 0, '', 0, false);
                $this->Ln(10);
            }

            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false);
            }
        }

        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_PAGE_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        // Output PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Attendance_Report_' . $admissionNo . '_' . date('Y-m-d') . '.pdf"');
        echo $pdf->Output('', 'S');
        exit;
    } catch (Exception $e) {
        // Fallback to HTML if TCPDF fails
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
} else {
    // Fallback: Generate printable HTML
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="Attendance_Report_' . $admissionNo . '_' . date('Y-m-d') . '.html"');
    echo $html;
    exit;
}

?>
