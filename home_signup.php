<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projectdb";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle signup form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($full_name)) {
        $_SESSION['error'] = "Full name is required!";
        header("Location: home_page.php");
        exit();
    }
    elseif (empty($email)) {
        $_SESSION['error'] = "Email is required!";
        header("Location: home_page.php");
        exit();
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format!";
        header("Location: home_page.php");
        exit();
    }
    elseif (empty($password)) {
        $_SESSION['error'] = "Password is required!";
        header("Location: home_page.php");
        exit();
    }
    elseif (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long!";
        header("Location: home_page.php");
        exit();
    }
    elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: home_page.php");
        exit();
    }
    else {
        // Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_sql);
        
        if (!$stmt) {
            $_SESSION['error'] = "Database error: " . $conn->error;
            header("Location: home_page.php");
            exit();
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = "Email already registered!";
            $stmt->close();
            $conn->close();
            header("Location: home_page.php");
            exit();
        }
        
        $stmt->close();
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'user')";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            $_SESSION['error'] = "Database error: " . $conn->error;
            $conn->close();
            header("Location: home_page.php");
            exit();
        }
        
        $stmt->bind_param("sss", $full_name, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! You can now login.";
            $stmt->close();
            $conn->close();
            header("Location: home_page.php");
            exit();
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
            $stmt->close();
            $conn->close();
            header("Location: home_page.php");
            exit();
        }
    }
} else {
    // If not POST, redirect to home page
    header("Location: home_page.php");
    exit();
}

$conn->close();
?>


<!-- 
SAVE THIS FILE AS: home_signup.php
Location: C:\xampp\htdocs\project\home_signup.php
-->