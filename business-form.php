<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "projectdb";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Collect form inputs (MATCHING HTML NAMES)
$full_name            = $_POST['full_name'] ?? '';
$email                = $_POST['email'] ?? '';
$business_name        = $_POST['business_name'] ?? '';
$business_address     = $_POST['business_address'] ?? '';
$phone_number         = $_POST['phone'] ?? '';
$business_description = $_POST['description'] ?? '';

// Insert data
$sql = "INSERT INTO business (full_name, email, business_name, business_address, phone_number, business_description)
        VALUES ('$full_name', '$email', '$business_name', '$business_address', '$phone_number', '$business_description')";

if ($conn->query($sql) === TRUE) {
    header("Location: business-success.html");
    exit();
} else {
    echo "Error: " . $conn->error;
}

