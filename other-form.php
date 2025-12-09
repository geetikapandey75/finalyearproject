<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projectdb";

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect form data safely
    $full_name   = $_POST['full_name'] ?? '';
    $email       = $_POST['email'] ?? '';
    $phone_number      = $_POST['phone_number'] ?? '';
    $licence_type = $_POST['licence_type'] ?? '';

    // Prepared statement
    $stmt = $conn->prepare("
        INSERT INTO other
        (full_name, email, phone_number, licence_type)
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssss", 
        $full_name,
        $email,
        $phone_number,
        $licence_type
    );

    if ($stmt->execute()) {
        // Redirect after success
        header("Location: business-success.html");
        exit();
    } else {
        echo "Error inserting data: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
