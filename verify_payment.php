<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Razorpay Configuration
$razorpay_key_secret = "fliKTmw84hX8mblSM1CyRQ0D"; // Your Test Key Secret

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$razorpay_order_id = $data['razorpay_order_id'] ?? '';
$razorpay_payment_id = $data['razorpay_payment_id'] ?? '';
$razorpay_signature = $data['razorpay_signature'] ?? '';
$payment_id = $data['payment_id'] ?? 0;
$service_type = $data['service_type'] ?? '';
$service_id = $data['service_id'] ?? 0;

// Verify signature
$generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $razorpay_key_secret);

if ($generated_signature === $razorpay_signature) {
    // Signature is valid - update payment record
    $stmt = $conn->prepare("
        UPDATE payments 
        SET razorpay_payment_id = ?, 
            razorpay_signature = ?, 
            payment_status = 'paid',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $razorpay_payment_id, $razorpay_signature, $payment_id);
    
    if ($stmt->execute()) {
        // Update service-specific tables and get tracking ID
        $tracking_id = updateServiceStatus($conn, $service_type, $service_id);
        
        // Build redirect URL with service type and tracking ID
        $redirect_url = "payment_success.php?service_type=" . urlencode($service_type) . "&tracking_id=" . urlencode($tracking_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully',
            'payment_id' => $razorpay_payment_id,
            'redirect_url' => $redirect_url
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update payment record'
        ]);
    }
} else {
    // Signature verification failed
    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_status = 'failed',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => false,
        'message' => 'Payment signature verification failed'
    ]);
}

$conn->close();

// Function to update service-specific status and return tracking ID
function updateServiceStatus($conn, $service_type, $service_id) {
    $tracking_id = '';
    
    switch ($service_type) {
        case 'passport':
            $stmt = $conn->prepare("UPDATE passport_appointments SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            
            // Get tracking ID
            $stmt = $conn->prepare("SELECT tracking_id FROM passport_appointments WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $tracking_id = $row['tracking_id'];
            }
            break;
            
        case 'meeseva':
            $stmt = $conn->prepare("UPDATE meeseva_applications SET payment_status = 'paid', status = 'Payment Completed' WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            
            // Get application number
            $stmt = $conn->prepare("SELECT application_number FROM meeseva_applications WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $tracking_id = $row['application_number'];
            }
            break;
            
        case 'challan':
            $stmt = $conn->prepare("UPDATE check_challan SET payment_status = 'paid', status = 'Paid', payment_date = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            
            // Get challan number for tracking
            $stmt = $conn->prepare("SELECT challan_no, vehicle_no FROM check_challan WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $tracking_id = $row['challan_no'] . '-' . $row['vehicle_no'];
            }
            break;
            
        case 'criminal_lawyer':
        case 'family_lawyer':
        case 'corporate_lawyer':
            // Update lawyer consultation booking status if you have such a table
            $tracking_id = 'CONSULT-' . strtoupper($service_type) . '-' . time();
            break;
    }
    
    return $tracking_id;
}
?>