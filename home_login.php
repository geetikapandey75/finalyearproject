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

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email)) {
        echo "❌ Email is required!";
        exit();
    }
    
    if (empty($password)) {
        echo "❌ Password is required!";
        exit();
    }

    // Check if user exists
    $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "❌ Database error: " . $conn->error;
        exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "❌ No account found with that email!";
        $stmt->close();
        $conn->close();
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
        
        echo "✅ Login successful! Redirecting...";
        $conn->close();
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("refresh:2; url=admin_dashboard.php");
        } elseif ($user['role'] === 'lawyer') {
            header("refresh:2; url=lawyer_dashboard.php");
        } else {
            header("refresh:2; url=user_dashboard.php");
        }
        exit();
    } else {
        echo "❌ Invalid password!";
        $conn->close();
        exit();
    }
}

$conn->close();
?>