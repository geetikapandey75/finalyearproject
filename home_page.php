<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Legal Assist – Welcome</title>
  <link rel="stylesheet" href="style.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    /* Alert Styles */
    .alert {
      padding: 15px 20px;
      margin: 20px auto;
      max-width: 600px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      display: none;
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 9999;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
      from {
        top: -100px;
        opacity: 0;
      }
      to {
        top: 20px;
        opacity: 1;
      }
    }
    
    @keyframes slideUp {
      from {
        top: 20px;
        opacity: 1;
      }
      to {
        top: -100px;
        opacity: 0;
      }
    }
    
    .alert.show {
      display: block;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .alert .close-btn {
      float: right;
      font-size: 20px;
      font-weight: bold;
      cursor: pointer;
      color: inherit;
      background: none;
      border: none;
      padding: 0;
      margin-left: 15px;
    }
    
    .alert .close-btn:hover {
      opacity: 0.7;
    }
  </style>
</head>
<body>

  <!-- Alert Messages -->
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error show" id="alertBox">
      <button class="close-btn" onclick="closeAlert()">&times;</button>
      <strong>Error!</strong> <?php echo htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success show" id="alertBox">
      <button class="close-btn" onclick="closeAlert()">&times;</button>
      <strong>Success!</strong> <?php echo htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <!-- Top Navigation Links -->
  <div class="top-features-bar">
    <span><a href="about-us.html">About Us</a></span>
    <span><a href="jobs.html">Jobs</a></span>
  </div>

  <!-- Login/Signup -->
  <div class="top-links">
    <a href="#" onclick="showForm('login')">Login</a> |
    <a href="#" onclick="showForm('signup')">Sign Up</a>
  </div>

  <!-- Hero Banner -->
  <header class="hero">
    <div class="overlay">
      <h1>Legal Assist</h1>
      <p class="tagline">Your trusted guide for all legal and license needs</p>
      <p style="margin-top: 20px; font-size: 18px;">Login or Sign Up to access our services</p>
    </div>
  </header>

  <!-- Auth Section -->
  <section id="auth-section" class="auth-container">
    <div class="form-box" id="loginForm">
      <h2>Login to Legal Assist</h2>
      <form action="home_login.php" method="POST">
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit">Login</button>
        <p>Don't have an account? <a href="#" onclick="showForm('signup'); return false;">Sign Up</a></p>
      </form>
    </div>

    <div class="form-box hidden" id="signupForm">
      <h2>Create Your Account</h2>
      <form action="home_signup.php" method="POST" id="signupFormElement">
        <input type="text" name="full_name" placeholder="Full Name" required />
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Create Password" minlength="8" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" minlength="8" required />
        <button type="submit">Sign Up</button>
        <p>Already have an account? <a href="#" onclick="showForm('login'); return false;">Login</a></p>
      </form>
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

  <!-- JavaScript -->
  <script>
    function showForm(formType) {
      document.getElementById("auth-section").classList.remove("hidden");
      document.getElementById("loginForm").classList.add("hidden");
      document.getElementById("signupForm").classList.add("hidden");

      if (formType === "login") {
        document.getElementById("loginForm").classList.remove("hidden");
      } else {
        document.getElementById("signupForm").classList.remove("hidden");
      }

      window.scrollTo({
        top: document.getElementById("auth-section").offsetTop - 40,
        behavior: "smooth"
      });
    }

    function closeAlert() {
      const alertBox = document.getElementById('alertBox');
      if (alertBox) {
        alertBox.style.animation = 'slideUp 0.3s ease-out';
        setTimeout(() => {
          alertBox.style.display = 'none';
        }, 300);
      }
    }

    // Auto-hide alert after 5 seconds
    window.onload = function() {
      const alertBox = document.getElementById('alertBox');
      if (alertBox) {
        setTimeout(() => {
          closeAlert();
        }, 5000);
      }
    };

    // Client-side validation
    document.getElementById('signupFormElement').addEventListener('submit', function(e) {
      const form = e.target;
      const password = form.querySelector('input[name="password"]').value;
      const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
      
      if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
      }
      
      if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long!');
        return false;
      }
    });
  </script>

</body>
</html>