<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['application_number'])) {
    $appNumber = trim($_POST['application_number']);
    
    // Fetch application details
    $stmt = $conn->prepare("SELECT id, full_name, email, contact_number, service, center_name, appointment_date, appointment_time, payment_status FROM meeseva_applications WHERE application_number = ?");
    $stmt->bind_param("s", $appNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $application = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'application' => [
                'id' => $application['id'],
                'full_name' => $application['full_name'],
                'email' => $application['email'],
                'contact_number' => $application['contact_number'],
                'service' => $application['service'],
                'center_name' => $application['center_name'],
                'appointment_date' => $application['appointment_date'],
                'appointment_time' => $application['appointment_time'],
                'payment_status' => $application['payment_status'],
                'application_number' => $appNumber
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Application not found. Please check your Application Number.'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

$conn->close();
?>