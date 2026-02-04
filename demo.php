<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Legal Assist – Welcome</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #f4f6fb;
      color: #222;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    /* Top Bars */
    .top-features-bar,
    .top-links {
      display: flex;
      justify-content: flex-end;
      gap: 20px;
      padding: 12px 40px;
      font-size: 14px;
      background: #ffffff;
      border-bottom: 1px solid #e5e7eb;
    }

    .top-features-bar {
      justify-content: flex-start;
    }

    .top-links a {
      font-weight: 500;
      color: #2563eb;
    }

    /* Hero Section */
    .hero {
      height: 90vh;
      background: linear-gradient(
        rgba(0,0,0,0.55),
        rgba(0,0,0,0.55)
      ),
      url("https://images.unsplash.com/photo-1589829545856-d10d557cf95f") center/cover no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: #fff;
    }

    .hero h1 {
      font-size: 56px;
      font-weight: 700;
      letter-spacing: 1px;
    }

    .hero .tagline {
      margin-top: 12px;
      font-size: 18px;
      opacity: 0.9;
    }

    /* Auth Section */
    .auth-container {
      padding: 80px 20px;
      display: flex;
      justify-content: center;
    }

    .form-box {
      width: 100%;
      max-width: 420px;
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(12px);
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.15);
      animation: fadeUp 0.5s ease;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-box h2 {
      text-align: center;
      margin-bottom: 25px;
      font-weight: 600;
      color: #1e3a8a;
    }

    .form-box input {
      width: 100%;
      padding: 14px;
      margin-bottom: 15px;
      border-radius: 8px;
      border: 1px solid #d1d5db;
      font-size: 15px;
      transition: 0.3s;
    }

    .form-box input:focus {
      outline: none;
      border-color: #2563eb;
      box-shadow: 0 0 0 2px rgba(37,99,235,0.15);
    }

    .form-box button {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #2563eb, #1e40af);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .form-box button:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(37,99,235,0.35);
    }

    .form-box p {
      margin-top: 18px;
      font-size: 14px;
      text-align: center;
      color: #374151;
    }

    .form-box p a {
      color: #2563eb;
      font-weight: 500;
    }

    .hidden {
      display: none;
    }

    /* Footer */
    .footer {
      background: #0f172a;
      color: #cbd5f5;
      padding: 30px 20px;
      text-align: center;
      font-size: 14px;
    }

    .footer a {
      color: #93c5fd;
    }

  </style>
</head>

<body>

  <!-- Top Navigation -->
  <div class="top-features-bar">
    <span><a href="about-us.html">About Us</a></span>
    <span><a href="jobs.html">Jobs</a></span>
  </div>

  <div class="top-links">
    <a href="#" onclick="showForm('login')">Login</a> |
    <a href="#" onclick="showForm('signup')">Sign Up</a>
  </div>

  <!-- Hero -->
  <header class="hero">
    <div>
      <h1>Legal Assist</h1>
      <p class="tagline">Your trusted guide for all legal & license services</p>
      <p style="margin-top: 20px;">Login or Sign Up to continue</p>
    </div>
  </header>

  <!-- Auth -->
  <section id="auth-section" class="auth-container hidden">

    <div class="form-box" id="loginForm">
      <h2>Login</h2>
      <form action="home_login.php" method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <p style="text-align:right; font-size:13px; margin-top:-8px;">
  <a href="forgot-password.php" style="color:#2563eb;">
    Forgot password?
  </a>
</p>

        <button type="submit">Login</button>
        <p>Don’t have an account? <a href="#" onclick="showForm('signup')">Sign Up</a></p>
      </form>
    </div>

    <div class="form-box hidden" id="signupForm">
      <h2>Create Account</h2>
      <form action="home_signup.php" method="POST" id="signupFormElement">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" minlength="8" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" minlength="8" required>
        <button type="submit">Sign Up</button>
        <p>Already have an account? <a href="#" onclick="showForm('login')">Login</a></p>
      </form>
    </div>

  </section>

  <!-- Footer -->
  <footer class="footer">
    <p>📍 Hyderabad, India</p>
    <p>📞 +91 9876543210</p>
    <p>📧 contact@legalwebsite.com</p>
    <p>© 2025 Legal Assist</p>
  </footer>

  <!-- JS (unchanged logic) -->
  <script>
    function showForm(type) {
      document.getElementById("auth-section").classList.remove("hidden");
      document.getElementById("loginForm").classList.add("hidden");
      document.getElementById("signupForm").classList.add("hidden");

      document.getElementById(type === 'login' ? "loginForm" : "signupForm")
        .classList.remove("hidden");

      window.scrollTo({
        top: document.getElementById("auth-section").offsetTop - 40,
        behavior: "smooth"
      });
    }

    document.getElementById('signupFormElement').addEventListener('submit', function(e) {
      const p = this.password.value;
      const cp = this.confirm_password.value;
      if (p !== cp) {
        e.preventDefault();
        alert("Passwords do not match");
      }
    });
  </script>

</body>
</html>
