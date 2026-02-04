<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die("DB Connection Failed");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date     = $conn->real_escape_string($_POST['test_date']);
    $time     = $conn->real_escape_string($_POST['test_time']);
    $location = $conn->real_escape_string($_POST['test_location']);

    $booking_id = 'DL-SLOT-' . time();

    $sql = "INSERT INTO driving_test_slots 
            (booking_id, test_date, test_time, test_location)
            VALUES 
            ('$booking_id', '$date', '$time', '$location')";

    if ($conn->query($sql)) {

        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Slot Booked</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center min-h-screen'>
            <div class='bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center'>
                <div class='text-green-500 text-4xl mb-3'>✔</div>
                <h2 class='text-2xl font-bold mb-4'>Slot Booked Successfully</h2>

                <div class='text-left space-y-2 text-gray-700'>
                    <p><strong>Booking ID:</strong> $booking_id</p>
                    <p><strong>Date:</strong> $date</p>
                    <p><strong>Time:</strong> $time</p>
                    <p><strong>Location:</strong> $location</p>
                    <p><strong>Status:</strong> Confirmed</p>
                </div>

                <a href='license.html'
                   class='mt-6 inline-block bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-lg font-semibold'>
                   Back to Dashboard
                </a>
            </div>
        </body>
        </html>";
        exit;
    } else {
        die("Insert Failed: " . $conn->error);
    }
}
?>
