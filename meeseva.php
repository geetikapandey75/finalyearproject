<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fullName'])) {
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $contactNumber = $_POST['contactNumber'];
    $service = $_POST['service'];
    $center = $_POST['center'];
    $appointmentDate = $_POST['appointment_date'];
    $appointmentTime = $_POST['appointment_time'];
    
    // Generate application number
    $applicationNumber = 'MS' . time() . rand(1000, 9999);
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO meeseva_applications (full_name, email, contact_number, service, center_name, appointment_date, appointment_time, application_number, status, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'pending', NOW())");
    
    $stmt->bind_param("ssssssss", $fullName, $email, $contactNumber, $service, $center, $appointmentDate, $appointmentTime, $applicationNumber);
    
    if ($stmt->execute()) {
        $application_id = $conn->insert_id;
        
        // Store data in session for payment
        $_SESSION['payment_data'] = [
            'service_type' => 'meeseva',
            'service_record_id' => $application_id,
            'amount' => 0, // Will be set based on service selection in payment page
            'name' => $fullName,
            'email' => $email,
            'phone' => $contactNumber,
            'tracking_id' => $applicationNumber
        ];
        
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted | Legal Assist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50">
    <div class="container mx-auto px-6 py-12">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl mx-auto text-center">
            <div class="mb-6">
                <svg class="w-20 h-20 text-green-600 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-green-600 mb-4">Application Submitted Successfully!</h2>
            <div class="bg-blue-50 rounded-lg p-6 mb-6 text-left">
                <p class="mb-2"><strong>Application Number:</strong> <span class="text-blue-600 font-mono text-lg">' . $applicationNumber . '</span></p>
                <p class="mb-2"><strong>Name:</strong> ' . htmlspecialchars($fullName) . '</p>
                <p class="mb-2"><strong>Service:</strong> ' . htmlspecialchars($service) . '</p>
                <p class="mb-2"><strong>Center:</strong> ' . htmlspecialchars($center) . '</p>
                <p class="mb-2"><strong>Date:</strong> ' . htmlspecialchars($appointmentDate) . '</p>
                <p class="mb-2"><strong>Time:</strong> ' . htmlspecialchars($appointmentTime) . '</p>
            </div>
            <p class="text-gray-600 mb-6">Please note your application number for future reference. You can now proceed to payment.</p>
            <a href="meeseva.html" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-500 transition">
                Proceed to Payment
            </a>
        </div>
    </div>
    
    <script>
        // Store data in sessionStorage for payment
        sessionStorage.setItem("latest_meeseva_id", "' . $application_id . '");
        sessionStorage.setItem("applicant_name", "' . addslashes($fullName) . '");
        sessionStorage.setItem("applicant_email", "' . addslashes($email) . '");
        sessionStorage.setItem("applicant_phone", "' . addslashes($contactNumber) . '");
    </script>
</body>
</html>';
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Handle application status check
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['application_number'])) {
    $appNumber = $_POST['application_number'];
    
    $stmt = $conn->prepare("SELECT * FROM meeseva_applications WHERE application_number = ?");
    $stmt->bind_param("s", $appNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status | Legal Assist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50">
    <div class="container mx-auto px-6 py-12">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl mx-auto">
            <h2 class="text-3xl font-bold text-blue-800 mb-6 text-center">Application Status</h2>
            
            <div class="bg-blue-50 rounded-lg p-6 mb-6">
                <p class="mb-2"><strong>Application Number:</strong> ' . htmlspecialchars($row['application_number']) . '</p>
                <p class="mb-2"><strong>Name:</strong> ' . htmlspecialchars($row['full_name']) . '</p>
                <p class="mb-2"><strong>Service:</strong> ' . htmlspecialchars($row['service']) . '</p>
                <p class="mb-2"><strong>Status:</strong> <span class="text-green-600 font-semibold">' . htmlspecialchars($row['status']) . '</span></p>
                <p class="mb-2"><strong>Payment Status:</strong> <span class="text-' . ($row['payment_status'] == 'paid' ? 'green' : 'orange') . '-600 font-semibold">' . ucfirst($row['payment_status']) . '</span></p>
                <p class="mb-2"><strong>Appointment Date:</strong> ' . htmlspecialchars($row['appointment_date']) . '</p>
                <p class="mb-2"><strong>Appointment Time:</strong> ' . htmlspecialchars($row['appointment_time']) . '</p>
            </div>
            
            <div class="text-center">
                <a href="meeseva.html" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-500 transition">
                    Back to MeeSeva
                </a>
            </div>
        </div>
    </div>
</body>
</html>';
        exit();
    } else {
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Not Found | Legal Assist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50">
    <div class="container mx-auto px-6 py-12">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl mx-auto text-center">
            <div class="mb-6">
                <svg class="w-20 h-20 text-red-600 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-red-600 mb-4">Application Not Found</h2>
            <p class="text-gray-600 mb-6">No application found with this number. Please check and try again.</p>
            <a href="meeseva.html" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-500 transition">
                Back to MeeSeva
            </a>
        </div>
    </div>
</body>
</html>';
        exit();
    }
    
    $stmt->close();
}


$conn->close();
?>