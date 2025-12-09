<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projectdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name         = $_POST['full_name'];
    $business_name     = $_POST['business_name'];
    $business_address  = $_POST['business_address'];
    $business_category = $_POST['business_category'];
    $phone_number      = $_POST['phone_number'];

    $stmt = $conn->prepare("
        INSERT INTO trade 
        (full_name, business_name, business_address, business_category, phone_number)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("sssss",
        $full_name,
        $business_name,
        $business_address,
        $business_category,
        $phone_number
    );

    if ($stmt->execute()) {
        header("Location: business-success.html");
        exit();
    } else {
        echo "Insert error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
