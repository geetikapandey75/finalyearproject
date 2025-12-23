<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "legal_assist";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// ✅ FIXED: Include PHPMailer with correct path
require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'geetikapandey75@gmail.com');
define('SMTP_PASSWORD', 'llda biay lxzr gilr');
define('SMTP_FROM_EMAIL', 'geetikapandey75@gmail.com');
define('SMTP_FROM_NAME', 'MeeSeva Services - Legal Assist');

// Function to send email
function sendEmail($to, $to_name, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $to_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // ✅ IMPROVED: Better error logging
        error_log("Email Error: " . $mail->ErrorInfo);
        error_log("Exception: " . $e->getMessage());
        return false;
    }
}

// Handle AJAX requests
header('Content-Type: application/json');

// ==================== SEND OTP FOR APPOINTMENT CONFIRMATION ====================
if (isset($_POST['action']) && $_POST['action'] === 'sendAppointmentOTP') {
    
    $application_number = $conn->real_escape_string($_POST['application_number'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    
    if (empty($application_number) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Application number and email are required']);
        exit;
    }
    
    // Check if application exists with appointment details
    $sql = "SELECT * FROM meeseva_applications 
            WHERE application_number = '$application_number' 
            AND email = '$email'
            AND appointment_date IS NOT NULL
            AND center_name IS NOT NULL";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid application number or email. Please ensure you have booked an appointment.'
        ]);
        exit;
    }
    
    $appointment = $result->fetch_assoc();
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));
    
    // Update OTP in database
    $update_sql = "UPDATE meeseva_applications 
                   SET otp = '$otp', 
                       otp_expiry = '$expiry'
                   WHERE application_number = '$application_number'";
    
    if (!$conn->query($update_sql)) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate OTP']);
        exit;
    }
    
    // Format appointment date and time
    $formatted_date = date('d F Y', strtotime($appointment['appointment_date']));
    $formatted_time = date('h:i A', strtotime($appointment['appointment_time']));
    
    // Send OTP via email with appointment details
    $email_subject = "Your MeeSeva Appointment Confirmation OTP";
    $email_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { 
                background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); 
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
                border-radius: 10px 10px 0 0;
            }
            .content { 
                background: #ffffff; 
                padding: 30px; 
                border: 1px solid #e5e7eb;
            }
            .otp-box { 
                background: #eff6ff; 
                padding: 25px; 
                text-align: center; 
                font-size: 36px; 
                font-weight: bold; 
                color: #1e3a8a; 
                letter-spacing: 8px; 
                margin: 25px 0; 
                border: 3px dashed #3b82f6;
                border-radius: 10px;
            }
            .appointment-details {
                background: #f9fafb;
                padding: 20px;
                border-left: 4px solid #3b82f6;
                margin: 20px 0;
            }
            .detail-row {
                padding: 8px 0;
                border-bottom: 1px solid #e5e7eb;
            }
            .detail-row:last-child {
                border-bottom: none;
            }
            .detail-label {
                font-weight: bold;
                color: #1e3a8a;
                display: inline-block;
                width: 150px;
            }
            .footer { 
                text-align: center; 
                padding: 20px; 
                color: #6b7280; 
                font-size: 12px; 
                background: #f9fafb;
                border-radius: 0 0 10px 10px;
            }
            .warning-box {
                background: #fef3c7;
                border: 1px solid #fbbf24;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 28px;'>🏛️ MeeSeva Appointment Confirmation</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>Legal Assist Portal</p>
            </div>
            
            <div class='content'>
                <h2 style='color: #1e3a8a; margin-top: 0;'>Dear {$appointment['full_name']},</h2>
                
                <p>Your appointment has been successfully scheduled. To download your appointment confirmation certificate, please use the OTP below:</p>
                
                <div class='otp-box'>$otp</div>
                
                <p style='text-align: center; color: #ef4444; font-weight: bold;'>⏱️ Valid for 10 minutes only</p>
                
                <div class='appointment-details'>
                    <h3 style='margin-top: 0; color: #1e3a8a;'>📋 Your Appointment Details</h3>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Application No:</span>
                        <span>{$appointment['application_number']}</span>
                    </div>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Service:</span>
                        <span>{$appointment['service']}</span>
                    </div>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Center:</span>
                        <span>{$appointment['center_name']}</span>
                    </div>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Date:</span>
                        <span>$formatted_date</span>
                    </div>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Time:</span>
                        <span>$formatted_time</span>
                    </div>
                    
                    <div class='detail-row'>
                        <span class='detail-label'>Contact:</span>
                        <span>{$appointment['contact_number']}</span>
                    </div>
                </div>
                
                <div class='warning-box'>
                    <strong>⚠️ Important:</strong>
                    <ul style='margin: 10px 0; padding-left: 20px;'>
                        <li>Please arrive 15 minutes before your scheduled time</li>
                        <li>Bring all required documents</li>
                        <li>Carry a printed copy of your confirmation certificate</li>
                        <li>This appointment is non-transferable</li>
                    </ul>
                </div>
                
                <p style='margin-top: 20px;'>If you did not request this appointment, please contact our support team immediately.</p>
            </div>
            
            <div class='footer'>
                <p style='margin: 5px 0;'><strong>MeeSeva Services - Legal Assist</strong></p>
                <p style='margin: 5px 0;'>This is an automated email. Please do not reply.</p>
                <p style='margin: 5px 0;'>For support: support@legalassist.com | Toll-Free: 1800-XXX-XXXX</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    if (sendEmail($email, $appointment['full_name'], $email_subject, $email_body)) {
        echo json_encode([
            'success' => true, 
            'message' => 'OTP sent successfully to your registered email',
            'email_masked' => substr($email, 0, 3) . '***@' . explode('@', $email)[1],
            'appointment_date' => $formatted_date,
            'appointment_time' => $formatted_time
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP email. Please try again.']);
    }
    
    exit;
}

// ==================== VERIFY OTP ====================
if (isset($_POST['action']) && $_POST['action'] === 'verifyAppointmentOTP') {
    
    $application_number = $conn->real_escape_string($_POST['application_number'] ?? '');
    $otp = $conn->real_escape_string($_POST['otp'] ?? '');
    
    if (empty($application_number) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Application number and OTP are required']);
        exit;
    }
    
    // Get application details
    $sql = "SELECT * FROM meeseva_applications 
            WHERE application_number = '$application_number'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid application number']);
        exit;
    }
    
    $application = $result->fetch_assoc();
    
    // Check if OTP is expired
    if (empty($application['otp_expiry']) || strtotime($application['otp_expiry']) < time()) {
        echo json_encode([
            'success' => false, 
            'message' => 'OTP has expired. Please request a new OTP.'
        ]);
        exit;
    }
    
    // Verify OTP
    if ($application['otp'] !== $otp) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid OTP. Please check and try again.'
        ]);
        exit;
    }
    
    // OTP verified successfully - mark certificate as issued
    $conn->query("UPDATE meeseva_applications 
                  SET certificate_issued = 1,
                      status = 'Appointment Confirmed',
                      otp = NULL,
                      otp_expiry = NULL
                  WHERE application_number = '$application_number'");
    
    // Store verification in session
    $_SESSION['verified_app'] = $application_number;
    $_SESSION['verified_at'] = time();
    
    echo json_encode([
        'success' => true, 
        'message' => 'OTP verified successfully',
        'appointment_data' => [
            'application_number' => $application['application_number'],
            'full_name' => $application['full_name'],
            'service' => $application['service'],
            'center_name' => $application['center_name'],
            'appointment_date' => date('d F Y', strtotime($application['appointment_date'])),
            'appointment_time' => date('h:i A', strtotime($application['appointment_time'])),
            'contact_number' => $application['contact_number']
        ]
    ]);
    
    exit;
}

$conn->close();
?>