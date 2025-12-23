<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "legal_assist";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email)) {
        $_SESSION['error'] = "Email is required!";
        header("Location: home_page.php");
        exit();
    }
    
    if (empty($password)) {
        $_SESSION['error'] = "Password is required!";
        header("Location: home_page.php");
        exit();
    }
    
    // Check if user exists
    $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: home_page.php");
        exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "No account found with that email!";
        $stmt->close();
        $conn->close();
        header("Location: home_page.php");
        exit();
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Password is correct, create session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        $conn->close();
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] === 'lawyer') {
            header("Location: lawyer_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Invalid password!";
        $conn->close();
        header("Location: home_page.php");
        exit();
    }
} else {
    // If not POST, redirect to home page
    header("Location: home_page.php");
    exit();
}

$conn->close();
?>