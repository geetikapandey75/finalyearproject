<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "legal_assist";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ==================== HANDLE APPLICATION FORM SUBMISSION ====================
if (isset($_POST['fullName'], $_POST['email'], $_POST['service'], $_POST['contactNumber'], 
          $_POST['center'], $_POST['appointment_date'], $_POST['appointment_time'])) {
    
    $full_name = $conn->real_escape_string($_POST['fullName']);
    $email = $conn->real_escape_string($_POST['email']);
    $service = $conn->real_escape_string($_POST['service']);
    $contact_number = $conn->real_escape_string($_POST['contactNumber']);
    $center = $conn->real_escape_string($_POST['center']);
    $appointment_date = $conn->real_escape_string($_POST['appointment_date']);
    $appointment_time = $conn->real_escape_string($_POST['appointment_time']);

    // Validation
    if (empty($full_name) || empty($email) || empty($service) || empty($contact_number) || 
        empty($center) || empty($appointment_date) || empty($appointment_time)) {
        die("<div class='bg-red-100 border border-red-400 text-red-700 p-4 rounded-lg max-w-2xl mx-auto mt-6'>
                All fields are required.
             </div>");
    }

    // Generate unique application number
    $application_number = 'MS' . date('Y') . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    // Insert into database
    $sql = "INSERT INTO meeseva_applications 
            (full_name, email, service, contact_number, center_name, 
             appointment_date, appointment_time, application_number, status) 
            VALUES 
            ('$full_name', '$email', '$service', '$contact_number', '$center', 
             '$appointment_date', '$appointment_time', '$application_number', 'Appointment Booked')";

    if ($conn->query($sql) === TRUE) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Appointment Confirmed - MeeSeva</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-blue-50">
            <div class="container mx-auto px-6 py-12">
                <div class="bg-white rounded-xl shadow-2xl p-8 max-w-2xl mx-auto">
                    <div class="text-center mb-6">
                        <div class="inline-block p-4 bg-green-100 rounded-full mb-4">
                            <svg class="w-20 h-20 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-green-600 mb-2">✅ Appointment Booked Successfully!</h2>
                        <p class="text-gray-600">Your appointment has been confirmed</p>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 mb-6">
                        <h3 class="text-xl font-bold text-blue-900 mb-4 text-center">📋 Appointment Details</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between border-b pb-2">
                                <span class="font-semibold text-blue-900">Application Number:</span>
                                <span class="font-mono bg-yellow-100 px-3 py-1 rounded"><?php echo $application_number; ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="font-semibold text-blue-900">Name:</span>
                                <span><?php echo $full_name; ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="font-semibold text-blue-900">Service:</span>
                                <span><?php echo $service; ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="font-semibold text-blue-900">Center:</span>
                                <span><?php echo $center; ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="font-semibold text-blue-900">Date:</span>
                                <span><?php echo date('d F Y', strtotime($appointment_date)); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold text-blue-900">Time:</span>
                                <span><?php echo $appointment_time; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <p class="text-sm text-yellow-800">
                            <strong>📧 Important:</strong> Save your Application Number: <strong><?php echo $application_number; ?></strong><br>
                            You'll need it to download your appointment certificate.
                        </p>
                    </div>

                    <div class="flex gap-4">
                        <a href="meeseva.html#download" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg text-center font-semibold hover:bg-blue-700 transition">
                            📥 Download Certificate
                        </a>
                        <a href="meeseva.html" class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg text-center font-semibold hover:bg-gray-300 transition">
                            ← Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        echo "<div class='bg-red-100 border border-red-400 text-red-700 p-4 rounded-lg max-w-2xl mx-auto mt-6'>
                Error: " . $conn->error . "
              </div>";
    }
}

// ==================== HANDLE STATUS CHECK ====================
elseif (isset($_POST['application_number']) && !isset($_POST['action'])) {
    $appNumber = $conn->real_escape_string($_POST['application_number']);

    $sql = "SELECT * FROM meeseva_applications WHERE application_number = '$appNumber'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Application Status - MeeSeva</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-blue-50">
            <div class="container mx-auto px-6 py-12">
                <div class="bg-white rounded-xl shadow-lg p-8 max-w-3xl mx-auto">
                    <h2 class="text-3xl font-bold text-blue-800 mb-6 text-center">Application Status</h2>
                    
                    <div class="bg-blue-50 p-6 rounded-lg mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Application Number</p>
                                <p class="font-bold text-lg"><?php echo $row['application_number']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Status</p>
                                <p class="font-bold text-lg text-green-600"><?php echo $row['status']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Applicant Name</p>
                                <p class="font-semibold"><?php echo $row['full_name']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Service</p>
                                <p class="font-semibold"><?php echo $row['service']; ?></p>
                            </div>
                            <?php if (!empty($row['center_name'])): ?>
                            <div>
                                <p class="text-sm text-gray-600">Center</p>
                                <p class="font-semibold"><?php echo $row['center_name']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Appointment Date</p>
                                <p class="font-semibold"><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="meeseva.html" class="block w-full bg-blue-600 text-white px-6 py-3 rounded-lg text-center font-semibold hover:bg-blue-700">
                        ← Back to Home
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
    } else {
        echo "<div class='bg-red-100 border border-red-400 text-red-700 p-4 rounded-lg max-w-2xl mx-auto mt-6'>
                Application number not found!
              </div>";
    }
}

$conn->close();
?>