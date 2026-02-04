<?php
session_start();
header('Content-Type: application/json');

// Load config
$config = require(__DIR__ . '/../config/razorpay.php');

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Get POST data
$serviceType = $_POST['service_type'] ?? '';
$serviceRecordId = $_POST['service_record_id'] ?? '';
$amount = $_POST['amount'] ?? 0;

// Validate inputs
if (empty($serviceType) || empty($serviceRecordId) || $amount <= 0) {
    die(json_encode(['error' => 'Invalid payment details']));
}

try {
    // Prepare order data
    $orderData = [
        'receipt' => $serviceType . '_' . $serviceRecordId . '_' . time(),
        'amount' => $amount * 100, // Convert to paise
        'currency' => 'INR',
        'notes' => [
            'service_type' => $serviceType,
            'service_record_id' => $serviceRecordId
        ]
    ];
    
    // Create Razorpay Order using cURL (NO SDK NEEDED)
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_USERPWD, $config['key_id'] . ':' . $config['key_secret']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to create order: ' . $response);
    }
    
    $order = json_decode($response, true);
    
    // Insert payment record (WITHOUT user_id column)
    $stmt = $conn->prepare("
        INSERT INTO payments (
            service_type,
            service_record_id,
            razorpay_order_id,
            amount,
            currency,
            payment_status,
            created_at
        ) VALUES (?, ?, ?, ?, 'INR', 'created', NOW())
    ");
    
    $stmt->bind_param("sisi", $serviceType, $serviceRecordId, $order['id'], $amount);
    $stmt->execute();
    $stmt->close();
    
    // Return order details
    echo json_encode([
        'success' => true,
        'order_id' => $order['id'],
        'key' => $config['key_id'],
        'amount' => $amount,
        'currency' => 'INR',
        'name' => 'Legal Assist',
        'description' => ucfirst($serviceType) . ' Service Payment'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>