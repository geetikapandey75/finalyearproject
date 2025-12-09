<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "projectdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name     = $_POST['full_name'] ?? '';
    $business_name = $_POST['business_name'] ?? '';
    $email         = $_POST['email'] ?? '';
    $phone_number         = $_POST['phone_number'] ?? '';

    // Prepared insert
    $stmt = $conn->prepare("
        INSERT INTO food
        (full_name, business_name, email, phone_number)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("ssss",
        $full_name,
        $business_name,
        $email,
        $phone_number
    );

    if ($stmt->execute()) {

        // Redirect to success page
        header("Location: business-success.html");
        exit();

    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
