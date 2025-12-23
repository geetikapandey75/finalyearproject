<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    $_SESSION['error'] = "Please login to access police services.";
    header("Location: home_page.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="police-complaint.css">
  <meta charset="UTF-8" />
  <title>File a Police Complaint – Legal Assist</title>
  <link rel="stylesheet" href="police-complaint.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body>
  <header class="hero small-hero">
    <div class="overlay">
      <h1>File a Police Complaint</h1>
      <p>Your safety is our priority – report issues quickly and securely.</p>
    </div>
  </header>
  
  <section class="info-section">
    <h2>When to File a Complaint?</h2>
    <p>If you've experienced theft, cybercrime, harassment, domestic violence, or any other unlawful activity, this page helps you begin the process of reporting it.</p>
    <p>We help you draft a complaint and guide you to the appropriate authorities.</p>
  </section>
  
  <section class="services-grid">
    <a href="missing.php" class="service-box green">Missing Persons</a>
    <a href="vehicle.php" class="service-box pink">Unclaimed Vehicles</a>
    <a href="passport.php" class="service-box red">Passport Verification</a>
    <a href="meeseva.html" class="service-box red">Meeseva Services</a>
    <a href="e-challan.html" class="service-box red">E-Challan Details</a>
  </section>
  
  <section class="emergency">
    <h3>⚠️ Emergency?</h3>
    <p>If you're in danger or need urgent help, call <strong>100</strong> immediately.</p>
    <a href="https://cybercrime.gov.in/" target="_blank" class="emergency-link">Report Cyber Crime</a>
  </section>
  
  <div class="car-animation-container">
    <img src="car.png" class="animated-car">
  </div>
  
  <style>
    #whatsapp-button {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 9999;
    }
    #whatsapp-button img {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      box-shadow: 0 4px 6px rgba(0,0,0,0.2);
      cursor: pointer;
    }
  </style>
  
  <div class="footer">
    <footer class="footer">
      <div class="container">
        <p>📍 Address: 8-3-/17-D,234, Kalayan Nagar, Hyderabad, India</p>
        <p>📞 Phone: <a href="tel:+919876543210">+91 9876543210</a></p>
        <p>📧 Email: <a href="mailto:contact@legalwebsite.com">contact@legalwebsite.com</a></p>
        <p>© 2025 Legal Assist. All rights reserved.</p>
      </div>
    </footer>
  </div>
  
  <a href="https://wa.me/8897752518?text=Hello%20Legal%20Assist,%20I%20need%20help%20with%20your%20services" target="_blank" id="whatsapp-button">
    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="Chat with us on WhatsApp">
  </a>
  
</body>
</html>