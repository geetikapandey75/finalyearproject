<?php
session_start();

if (isset($_SESSION['success_message'])) {
    echo "<h2>" . $_SESSION['success_message'] . "</h2>";
    unset($_SESSION['success_message']);
} else {
    // If no message is set, redirect to login or home
    header("Location: home_login.php");
    exit();
}
?>

<a href="home_login.php">Login</a> |
<a href="home_signup.php">Sign Up</a>
