<?php
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) die("DB Error");

$email = $_POST['email'];
$password = $_POST['password'];
$confirm = $_POST['confirm'];

if ($password !== $confirm) {
    die("Passwords do not match");
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed, $email);
$stmt->execute();

echo "<script>alert('Password updated successfully'); window.location='home_page.php';</script>";
