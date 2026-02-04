<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// INCLUDE PHPMailer (YOUR PATH)
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "invalid";
    exit;
}

$email   = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

if (!$email || !$message) {
    echo "invalid";
    exit;
}

$mail = new PHPMailer(true);

try {
    // SMTP CONFIG
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'geetikapandey75@gmail.com';   // CHANGE THIS
    $mail->Password   = 'llda biay lxzr gilr';          // CHANGE THIS
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // EMAIL DETAILS
    $mail->setFrom('geetikapandey75@gmail.com', 'Legal Assist');
    $mail->addAddress($email);
    $mail->Subject = 'Missing Person Media Alert';
    $mail->Body    = $message;

    $mail->send();
    echo "success";

} catch (Exception $e) {
    echo "failed";
}
