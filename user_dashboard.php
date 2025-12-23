<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    $_SESSION['error'] = "Please login to access services.";
    header("Location: home_page.php");
    exit();
}

$user_name = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Legal Assist – Dashboard</title>
  <link rel="stylesheet" href="style.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    /* Logout Button Styles */
    .top-links {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 100;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .user-greeting {
      color: #fff;
      font-weight: 600;
      font-size: 16px;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
    }
    
    .logout-btn {
      background-color: #dc3545;
      color: white;
      padding: 8px 20px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }
    
    .logout-btn:hover {
      background-color: #c82333;
    }
  </style>
</head>
<body>

  <!-- User Greeting & Logout -->
  <div class="top-links">
    <span class="user-greeting">Welcome, <?php echo htmlspecialchars($user_name); ?>!</span>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>

  <!-- Hero Banner -->
  <header class="hero">
    <div class="overlay">
      <h1>Legal Assist</h1>
      <p class="tagline">Your trusted guide for all legal and license needs</p>
    </div>
  </header>

  <!-- Help Section -->
  <section class="help-section">
    <h2>What do you need help with?</h2>
    <div class="choice-container">
      <a href="law.html" class="choice-card">Legal Help</a>
      <a href="license.html" class="choice-card">Apply for a Licence</a>
      <a href="police-complaint.php" class="choice-card">File a Police Complaint</a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <p>📍 Address: 8-3-/17-D, 234, Kalayan Nagar, Hyderabad, India</p>
      <p>📞 Phone: <a href="tel:+919876543210">+91 9876543210</a></p>
      <p>📧 Email: <a href="mailto:contact@legalwebsite.com">contact@legalwebsite.com</a></p>
      <p>© 2025 Legal Assist. All rights reserved.</p>
    </div>
  </footer>

</body>
</html>