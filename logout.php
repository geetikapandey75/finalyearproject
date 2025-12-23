<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to home page with success message
session_start(); // Restart to set one last message
$_SESSION['success'] = "You have been logged out successfully!";

header("Location: home_page.php");
exit();
?>