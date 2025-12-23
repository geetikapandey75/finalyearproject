<?php
session_start();
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Razorpay Configuration
$razorpay_key_secret = "fliKTmw84hX8mblSM1CyRQ0D";

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$razorpay_order_id = $input['razorpay_order_id'] ?? '';
$razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
$razorpay_signature = $input['razorpay_signature'] ?? '';
$payment_id = (int)($input['payment_id'] ?? 0);
$service_type = $input['service_type'] ?? '';
$service_id = (int)($input['service_id'] ?? 0);

// Verify signature
$generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $razorpay_key_secret);

if ($generated_signature === $razorpay_signature) {
    // Payment verified successfully
    
    // Update payment record
    $stmt = $conn->prepare("UPDATE payments SET payment_status = 'success', razorpay_payment_id = ?, razorpay_signature = ? WHERE id = ?");
    $stmt->bind_param("ssi", $razorpay_payment_id, $razorpay_signature, $payment_id);
    $stmt->execute();
    $stmt->close();
    
    // Update service-specific table based on service type
    switch ($service_type) {
        case 'passport':
            $stmt = $conn->prepare("UPDATE passport_appointments SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            
            // Get tracking ID for redirect
            $stmt2 = $conn->prepare("SELECT tracking_id FROM passport_appointments WHERE id = ?");
            $stmt2->bind_param("i", $service_id);
            $stmt2->execute();
            $result = $stmt2->get_result();
            $tracking_id = '';
            if ($row = $result->fetch_assoc()) {
                $tracking_id = $row['tracking_id'];
            }
            $stmt2->close();
            $stmt->close();
            
            // Store in session for display
            $_SESSION['payment_success'] = true;
            $_SESSION['tracking_id'] = $tracking_id;
            
           
            break;
            
        case 'meeseva':
            $stmt = $conn->prepare("UPDATE meeseva_applications SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'redirect_url' => 'meeseva.php?payment_success=1'
            ]);
            break;
            
        case 'challan':
            $stmt = $conn->prepare("UPDATE check_challan SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'redirect_url' => 'e-challan.php?payment_success=1'
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => true, 
                'redirect_url' => 'payment_success.php?payment_id=' . $payment_id
            ]);
            break;
    }
    
} else {
    // Payment verification failed
    $stmt = $conn->prepare("UPDATE payments SET payment_status = 'failed' WHERE id = ?");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
}

$conn->close();
?>