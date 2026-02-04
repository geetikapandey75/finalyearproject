<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$action = $_POST['action'] ?? '';

/* ======================================================
   APPLY FOR DRIVING LICENSE
====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($action)) {

    $name  = $conn->real_escape_string($_POST['full_name']);
    $dob   = $conn->real_escape_string($_POST['date_of_birth']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone_number']);

    $application_id = 'DL' . time() . rand(100, 999);

    $sql = "INSERT INTO driving_license_applications 
            (application_id, full_name, date_of_birth, email, phone_number, status, created_at)
            VALUES 
            ('$application_id', '$name', '$dob', '$email', '$phone', 'Submitted', NOW())";

    if ($conn->query($sql)) {

        $internal_id = $conn->insert_id;

        $conn->query("INSERT INTO application_status_history
            (application_id, status, step, comments, created_at)
            VALUES
            ($internal_id, 'Submitted', 'Application Received',
            'Your application has been submitted successfully', NOW())");

        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Application Submitted</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center min-h-screen p-4'>
            <div class='bg-white p-10 rounded-2xl shadow-2xl text-center max-w-lg w-full border-t-4 border-green-500'>
                <div class='mb-6'>
                    <svg class='w-20 h-20 mx-auto text-green-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                    </svg>
                </div>
                <h2 class='text-3xl font-bold text-gray-800 mb-2'>Application Submitted!</h2>
                <p class='text-gray-600 mb-6'>Your application has been received successfully</p>
                
                <div class='bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl mb-6'>
                    <p class='text-sm text-gray-600 mb-2'>Your Application ID</p>
                    <p class='text-4xl font-bold text-blue-600 tracking-wider'>$application_id</p>
                    <p class='text-xs text-gray-500 mt-3'>Save this ID to track your application status</p>
                </div>
                
                <div class='space-y-3'>
                    <a href='license.html' class='block bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5'>
                        Back to Home
                    </a>
                </div>
            </div>
        </body>
        </html>";
        exit;
    } else {
        die("Insert failed: " . $conn->error);
    }
}

/* ======================================================
   CHECK APPLICATION STATUS
====================================================== */
elseif ($action === 'check_status') {

    $application_id = $conn->real_escape_string($_POST['application_id']);

    $result = $conn->query("SELECT * FROM driving_license_applications 
                            WHERE application_id='$application_id'");

    if ($result->num_rows > 0) {

        $app = $result->fetch_assoc();

        $history = $conn->query("SELECT * FROM application_status_history 
                                 WHERE application_id={$app['id']}
                                 ORDER BY created_at DESC");

        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Status Tracking</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gradient-to-br from-gray-50 to-blue-50 p-6 min-h-screen'>
            <div class='max-w-4xl mx-auto'>
                <div class='bg-white rounded-2xl shadow-2xl overflow-hidden'>
                    <div class='bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-white'>
                        <h2 class='text-3xl font-bold mb-2'>Application Status Tracker</h2>
                        <p class='text-blue-100'>Track your driving license application progress</p>
                    </div>
                    
                    <div class='p-8'>
                        <div class='bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl mb-8 border border-blue-200'>
                            <div class='grid md:grid-cols-2 gap-4'>
                                <div>
                                    <p class='text-sm text-gray-600 mb-1'>Application ID</p>
                                    <p class='text-xl font-bold text-blue-600'>{$app['application_id']}</p>
                                </div>
                                <div>
                                    <p class='text-sm text-gray-600 mb-1'>Applicant Name</p>
                                    <p class='text-xl font-semibold text-gray-800'>{$app['full_name']}</p>
                                </div>
                                <div>
                                    <p class='text-sm text-gray-600 mb-1'>Current Status</p>
                                    <p class='text-lg font-bold text-green-600'>{$app['status']}</p>
                                </div>
                                <div>
                                    <p class='text-sm text-gray-600 mb-1'>Applied On</p>
                                    <p class='text-lg font-semibold text-gray-800'>{$app['created_at']}</p>
                                </div>
                            </div>
                        </div>

                        <h3 class='text-2xl font-bold mb-6 text-gray-800'>Status Timeline</h3>
                        <div class='space-y-4'>";

        while ($row = $history->fetch_assoc()) {
            echo "
            <div class='relative pl-8 pb-8 border-l-4 border-blue-400 last:border-l-0 last:pb-0'>
                <div class='absolute -left-3 top-0 w-6 h-6 bg-blue-500 rounded-full border-4 border-white shadow'></div>
                <div class='bg-white p-5 rounded-xl shadow-md hover:shadow-lg transition-shadow border border-gray-100'>
                    <p class='text-lg font-bold text-gray-800 mb-1'>{$row['step']}</p>
                    <p class='text-gray-600 mb-2'>{$row['comments']}</p>
                    <p class='text-sm text-gray-500'>
                        <svg class='inline w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                        </svg>
                        {$row['created_at']}
                    </p>
                </div>
            </div>";
        }

        echo "
                        </div>
                        
                        <div class='mt-8 flex gap-4'>
                            <a href='license.html' class='flex-1 text-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl'>
                                Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        exit;
    } else {
        echo "<script>alert('Application ID not found'); window.location='license.html';</script>";
        exit;
    }
}

/* ======================================================
   BOOK TEST SLOT
====================================================== */
elseif ($action === 'book_slot') {

    $name     = $conn->real_escape_string($_POST['applicant_name']);
    $age      = $conn->real_escape_string($_POST['applicant_age']);
    $address  = $conn->real_escape_string($_POST['applicant_address']);
    $date     = $conn->real_escape_string($_POST['test_date']);
    $time     = $conn->real_escape_string($_POST['test_time']);
    $location = $conn->real_escape_string($_POST['test_location']);

    $booking_id = 'SLOT' . time();

    $sql = "INSERT INTO test_slot_bookings
            (booking_id, applicant_name, applicant_age, applicant_address, test_date, test_time, test_location, status, created_at)
            VALUES
            ('$booking_id', '$name', '$age', '$address', '$date', '$time', '$location', 'Confirmed', NOW())";

    if ($conn->query($sql)) {

        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Slot Booked</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gradient-to-br from-green-50 to-emerald-100 flex items-center justify-center min-h-screen p-4'>
            <div class='bg-white p-10 rounded-2xl shadow-2xl max-w-2xl w-full border-t-4 border-green-500'>
                <div class='text-center mb-8'>
                    <svg class='w-24 h-24 mx-auto text-green-500 mb-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                    </svg>
                    <h2 class='text-3xl font-bold text-gray-800 mb-2'>Test Slot Booked!</h2>
                    <p class='text-gray-600'>Your driving test has been scheduled successfully</p>
                </div>
                
                <div class='bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-xl mb-6 border border-green-200'>
                    <div class='grid md:grid-cols-2 gap-4'>
                        <div>
                            <p class='text-sm text-gray-600 mb-1'>Booking ID</p>
                            <p class='text-lg font-bold text-green-600'>$booking_id</p>
                        </div>
                        <div>
                            <p class='text-sm text-gray-600 mb-1'>Applicant Name</p>
                            <p class='text-lg font-semibold text-gray-800'>$name</p>
                        </div>
                        <div>
                            <p class='text-sm text-gray-600 mb-1'>Age</p>
                            <p class='text-lg font-semibold text-gray-800'>$age years</p>
                        </div>
                        <div>
                            <p class='text-sm text-gray-600 mb-1'>Test Date</p>
                            <p class='text-lg font-semibold text-gray-800'>$date</p>
                        </div>
                        <div>
                            <p class='text-sm text-gray-600 mb-1'>Test Time</p>
                            <p class='text-lg font-semibold text-gray-800'>$time</p>
                        </div>
                        <div>
                            <p class='text-sm text-gray-600 mb-1'>Test Location</p>
                            <p class='text-lg font-semibold text-gray-800'>$location</p>
                        </div>
                    </div>
                    <div class='mt-4 pt-4 border-t border-green-200'>
                        <p class='text-sm text-gray-600 mb-1'>Address</p>
                        <p class='text-base font-medium text-gray-800'>$address</p>
                    </div>
                </div>
                
                <div class='bg-blue-50 p-4 rounded-lg mb-6 border-l-4 border-blue-500'>
                    <p class='text-sm text-blue-800'>
                        <strong>Important:</strong> Please arrive 15 minutes before your scheduled time. Bring your learner's license and a valid ID proof.
                    </p>
                </div>
                
                <a href='license.html' class='block text-center bg-gradient-to-r from-green-600 to-emerald-600 text-white px-8 py-4 rounded-xl font-semibold hover:from-green-700 hover:to-emerald-700 transition-all shadow-lg hover:shadow-xl'>
                    Back to Home
                </a>
            </div>
        </body>
        </html>";
        exit;
    } else {
        die("Slot booking failed: " . $conn->error);
    }
}

$conn->close();
?>