<?php
session_start();
header('Content-Type: application/json');

$config = require(__DIR__ . '/../config/razorpay.php');

$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Get POST data
$razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
$razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
$razorpaySignature = $_POST['razorpay_signature'] ?? '';

if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature)) {
    die(json_encode(['error' => 'Missing payment details']));
}

try {
    // Verify payment signature manually (NO SDK NEEDED)
    $generatedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $config['key_secret']);
    
    if ($generatedSignature !== $razorpaySignature) {
        throw new Exception('Payment signature verification failed');
    }
    
    // Update payments table
    $stmt = $conn->prepare("
        UPDATE payments 
        SET 
            razorpay_payment_id = ?,
            razorpay_signature = ?,
            payment_status = 'paid',
            updated_at = NOW()
        WHERE razorpay_order_id = ?
    ");
    
    $stmt->bind_param("sss", $razorpayPaymentId, $razorpaySignature, $razorpayOrderId);
    $stmt->execute();
    
    // Get service details
    $stmt2 = $conn->prepare("
        SELECT service_type, service_record_id 
        FROM payments 
        WHERE razorpay_order_id = ?
    ");
    
    $stmt2->bind_param("s", $razorpayOrderId);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $payment = $result->fetch_assoc();
    
    // Update service-specific table
    if ($payment['service_type'] === 'passport') {
        $stmt3 = $conn->prepare("
            UPDATE passport_appointments 
            SET payment_status = 'paid' 
            WHERE id = ?
        ");
        $stmt3->bind_param("i", $payment['service_record_id']);
        $stmt3->execute();
        $stmt3->close();
    } elseif ($payment['service_type'] === 'meeseva') {
        $stmt3 = $conn->prepare("
            UPDATE meeseva_applications 
            SET status = 'Submitted', payment_status = 'paid' 
            WHERE id = ?
        ");
        $stmt3->bind_param("i", $payment['service_record_id']);
        $stmt3->execute();
        $stmt3->close();
    } elseif ($payment['service_type'] === 'challan') {
        $stmt3 = $conn->prepare("
            UPDATE check_challan 
            SET status = 'Paid' 
            WHERE id = ?
        ");
        $stmt3->bind_param("i", $payment['service_record_id']);
        $stmt3->execute();
        $stmt3->close();
    }
    
    $stmt->close();
    $stmt2->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'service_type' => $payment['service_type'],
        'service_record_id' => $payment['service_record_id']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Payment verification failed: ' . $e->getMessage()]);
}

$conn->close();
?>