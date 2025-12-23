<?php
session_start();
require('fpdf/fpdf.php');

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");

// Security check
if (!isset($_SESSION['verified_app']) || 
    !isset($_GET['app']) || 
    $_SESSION['verified_app'] !== $_GET['app'] ||
    (time() - $_SESSION['verified_at']) > 600) { // 10 minute window
    die("Unauthorized access. Please verify OTP again.");
}

$app = $conn->real_escape_string($_GET['app']);

// Fetch appointment details
$res = $conn->query("SELECT * FROM meeseva_applications 
                     WHERE application_number='$app' 
                     AND certificate_issued=1");

if ($res->num_rows == 0) {
    die("Appointment confirmation not available.");
}

$data = $res->fetch_assoc();

// Create PDF
class AppointmentPDF extends FPDF {
    
    function Header() {
        // Government header
        $this->SetFillColor(30, 58, 138);
        $this->Rect(0, 0, 210, 35, 'F');
        
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 20);
        $this->SetY(8);
        $this->Cell(0, 8, 'GOVERNMENT OF TELANGANA', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 6, 'Department of Revenue - MeeSeva Services', 0, 1, 'C');
        $this->Cell(0, 6, 'Appointment Confirmation Certificate', 0, 1, 'C');
        
        $this->SetY(40);
    }
    
    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100);
        
        $this->Cell(0, 4, 'This is a computer-generated certificate and does not require a physical signature.', 0, 1, 'C');
        $this->Cell(0, 4, 'Please carry this certificate along with valid ID proof on the day of appointment.', 0, 1, 'C');
        $this->Cell(0, 4, 'For queries: support@legalassist.com | Toll-Free: 1800-XXX-XXXX', 0, 1, 'C');
    }
}

// ✅ FIXED: Create PDF without AddFont() calls - FPDF handles Arial automatically
$pdf = new AppointmentPDF();
$pdf->AddPage();
$pdf->SetMargins(20, 45, 20);

// Certificate Number and Date
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);
$pdf->Cell(85, 6, 'Certificate No: ' . $data['application_number'], 0, 0, 'L');
$pdf->Cell(85, 6, 'Issue Date: ' . date('d-M-Y'), 0, 1, 'R');
$pdf->Ln(5);

// Title
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(153, 0, 0);
$pdf->Cell(0, 10, 'APPOINTMENT CONFIRMATION', 0, 1, 'C');
$pdf->Ln(3);

// Decorative line
$pdf->SetDrawColor(30, 58, 138);
$pdf->SetLineWidth(0.5);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(8);

// Main content
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0);
$pdf->MultiCell(0, 6, "This is to confirm that an appointment has been successfully scheduled for the following applicant at MeeSeva Service Center.", 0, 'L');
$pdf->Ln(5);

// Applicant Details Box
$pdf->SetFillColor(245, 247, 250);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'APPLICANT DETAILS', 0, 1, 'L', true);

$pdf->SetFont('Arial', '', 11);
$details = [
    ['Name:', $data['full_name']],
    ['Email:', $data['email']],
    ['Contact Number:', $data['contact_number']],
    ['Service Requested:', $data['service']]
];

foreach ($details as $detail) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(50, 7, $detail[0], 0, 0, 'L');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, $detail[1], 0, 1, 'L');
}

$pdf->Ln(5);

// Appointment Details Box
$pdf->SetFillColor(239, 246, 255);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(30, 58, 138);
$pdf->Cell(0, 8, 'APPOINTMENT DETAILS', 0, 1, 'L', true);

$pdf->SetTextColor(0);
$pdf->SetFont('Arial', '', 11);
$appointment_details = [
    ['MeeSeva Center:', $data['center_name']],
    ['Appointment Date:', date('l, d F Y', strtotime($data['appointment_date']))],
    ['Appointment Time:', date('h:i A', strtotime($data['appointment_time']))]
];

foreach ($appointment_details as $detail) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(50, 7, $detail[0], 0, 0, 'L');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, $detail[1], 0, 1, 'L');
}

$pdf->Ln(8);

// Important Instructions
$pdf->SetFillColor(255, 243, 205);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(133, 77, 14);
$pdf->Cell(0, 7, 'IMPORTANT INSTRUCTIONS', 0, 1, 'L', true);

$pdf->SetTextColor(0);
$pdf->SetFont('Arial', '', 10);
$instructions = [
    '1. Please arrive at the center 15 minutes before your scheduled time.',
    '2. Carry this printed confirmation certificate along with valid photo ID.',
    '3. Bring all required documents as per the service requirements.',
    '4. Late arrivals may result in appointment cancellation.',
    '5. This appointment is non-transferable and valid only for the mentioned date and time.'
];

foreach ($instructions as $instruction) {
    $pdf->MultiCell(0, 5, $instruction, 0, 'L');
}

$pdf->Ln(10);

// Status stamp
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(34, 139, 34);
$pdf->SetDrawColor(34, 139, 34);
$pdf->SetLineWidth(1.5);
$pdf->Cell(0, 12, 'CONFIRMED', 1, 1, 'C');

$pdf->Ln(8);

// Digital signature area
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100);
$pdf->Cell(85, 6, '', 0, 0);
$pdf->Cell(85, 6, 'Digitally Generated By', 0, 1, 'C');
$pdf->Cell(85, 6, '', 0, 0);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0);
$pdf->Cell(85, 6, 'MeeSeva Service Portal', 0, 1, 'C');
$pdf->Cell(85, 6, '', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100);
$pdf->Cell(85, 6, 'Generated on: ' . date('d-M-Y h:i A'), 0, 1, 'C');

// Output PDF
$filename = "Appointment_Confirmation_" . $data['application_number'] . ".pdf";
$pdf->Output("D", $filename);

// Clear session
unset($_SESSION['verified_app']);
unset($_SESSION['verified_at']);

$conn->close();
?>