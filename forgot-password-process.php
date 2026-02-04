<?php
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) die("DB Error");

$email = $_POST['email'];

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    header("Location: reset-password.php?email=$email");
} else {
    echo "<script>alert('Email not found'); window.location='forgot-password.php';</script>";
}
