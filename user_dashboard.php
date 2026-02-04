<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    $_SESSION['error'] = "Please login to access services.";
    header("Location: home_page.php");
    exit();
}
$user_name = $_SESSION['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Legal Assist – Dashboard</title>
  <link rel="stylesheet" href="style.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    /* Enhanced glassmorphism top bar */
    .top-links {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 20px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px) saturate(180%);
      -webkit-backdrop-filter: blur(20px) saturate(180%);
      padding: 20px 60px;
      box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.4),
        0 2px 8px rgba(0, 0, 0, 0.1);
      border-bottom: 1.5px solid rgba(255, 255, 255, 0.35);
      transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      animation: fadeInDown 0.6s ease-out;
    }
    
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .user-greeting {
      color: #ffffff;
      font-weight: 600;
      font-size: 16px;
      letter-spacing: 0.3px;
      text-shadow: 
        0 2px 4px rgba(0, 0, 0, 0.3),
        0 4px 8px rgba(0, 0, 0, 0.2);
      display: flex;
      align-items: center;
      gap: 8px;
      padding-right: 20px;
      position: relative;
    }
    
    .user-greeting::before {
      content: '👋';
      font-size: 18px;
      animation: wave 2s ease-in-out infinite;
      display: inline-block;
    }
    
    @keyframes wave {
      0%, 100% { transform: rotate(0deg); }
      10%, 30% { transform: rotate(14deg); }
      20% { transform: rotate(-8deg); }
      40%, 60% { transform: rotate(0deg); }
    }
    
    .user-greeting::after {
      content: '';
      position: absolute;
      right: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 1px;
      height: 24px;
      background: linear-gradient(
        to bottom,
        rgba(255, 255, 255, 0),
        rgba(255, 255, 255, 0.5),
        rgba(255, 255, 255, 0)
      );
    }
    
    .logout-btn {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 50%, #d63447 100%);
      color: #ffffff;
      padding: 10px 24px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      letter-spacing: 0.5px;
      transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 
        0 4px 15px rgba(214, 52, 71, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
      border: none;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .logout-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
      );
      transition: left 0.5s ease;
    }
    
    .logout-btn:hover::before {
      left: 100%;
    }
    
    .logout-btn:hover {
      background: linear-gradient(135deg, #ee5a6f 0%, #d63447 50%, #c62641 100%);
      transform: translateY(-2px);
      box-shadow: 
        0 8px 25px rgba(214, 52, 71, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
    }
    
    .logout-btn:active {
      transform: translateY(0);
      box-shadow: 
        0 2px 10px rgba(214, 52, 71, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }
    
    .logout-btn::after {
      content: '→';
      font-size: 16px;
      transition: transform 0.3s ease;
    }
    
    .logout-btn:hover::after {
      transform: translateX(3px);
    }

    /* Enhanced Help Section */
    .help-section {
      padding: 80px 40px;
      background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
      animation: fadeIn 0.8s ease-out 0.2s both;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .help-section h2 {
      text-align: center;
      font-size: 2.5rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 60px;
      position: relative;
      padding-bottom: 20px;
    }

    .help-section h2::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: linear-gradient(90deg, #667eea, #764ba2);
      border-radius: 2px;
    }

    .choice-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 40px;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .choice-card {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      padding: 50px 40px;
      border-radius: 24px;
      text-decoration: none;
      color: #2c3e50;
      font-size: 1.5rem;
      font-weight: 600;
      text-align: center;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 
        0 10px 40px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
      border: 2px solid rgba(255, 255, 255, 0.5);
      position: relative;
      overflow: hidden;
    }

    .choice-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #1e3a8a, #2563eb);
  opacity: 0;
  transition: opacity 0.4s ease;
  z-index: -1;
}


    .choice-card:hover::before {
      opacity: 1;
    }

    .choice-card:hover {
  transform: translateY(-12px);
  color: #ffffff;
  box-shadow: 
    0 20px 60px rgba(37, 99, 235, 0.45),
    inset 0 1px 0 rgba(255, 255, 255, 0.3);
  border: 2px solid rgba(255, 255, 255, 0.8);
}

.choice-card:active {
  transform: translateY(-8px);
  box-shadow: 
    0 12px 35px rgba(37, 99, 235, 0.5),
    inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

    /* Add icons to cards */
    .choice-card:nth-child(1)::after {
      content: '';
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 2rem;
      opacity: 0.6;
      transition: all 0.4s ease;
    }

    .choice-card:nth-child(2)::after {
      content: '';
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 2rem;
      opacity: 0.6;
      transition: all 0.4s ease;
    }

    .choice-card:nth-child(3)::after {
      content: '';
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 2rem;
      opacity: 0.6;
      transition: all 0.4s ease;
    }

    .choice-card:hover::after {
      transform: scale(1.2) rotate(10deg);
      opacity: 1;
    }

    /* Enhanced Footer */
    /* ===== Improved Footer ===== */
.footer {
  background: linear-gradient(135deg, #1f2933, #2c3e50);
  color: rgba(255, 255, 255, 0.9);
  padding: 35px 16px 20px; /* reduced height */
  text-align: center;
}

.footer .container {
  max-width: 650px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 16px; /* tighter spacing */
}

.footer-item {
  display: flex;
  gap: 10px;
  justify-content: center;
  align-items: flex-start;
}

.footer-item .icon {
  font-size: 18px; /* smaller icons */
  margin-top: 2px;
}

.footer-item p {
  font-size: 14px; /* slightly smaller text */
  line-height: 1.5;
  margin: 0;
}

.footer-item strong {
  color: #ffffff;
  font-weight: 600;
}

.footer a {
  color: #8ab4ff;
  text-decoration: none;
  font-weight: 500;
}

.footer a:hover {
  color: #ffffff;
  text-decoration: underline;
}

.footer-bottom {
  margin-top: 10px;
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
}


    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .top-links {
        padding: 16px 30px;
        gap: 15px;
        flex-wrap: wrap;
        justify-content: center;
      }
      
      .user-greeting {
        font-size: 15px;
      }
      
      .user-greeting::after {
        display: none;
      }
      
      .logout-btn {
        padding: 9px 20px;
        font-size: 13px;
      }

      .help-section h2 {
        font-size: 2rem;
        margin-bottom: 40px;
      }

      .choice-container {
        gap: 30px;
        grid-template-columns: 1fr;
      }

      .choice-card {
        padding: 40px 30px;
        font-size: 1.3rem;
      }

      .footer .container {
        grid-template-columns: 1fr;
        gap: 15px;
      }
    }
    
    @media (max-width: 480px) {
      .top-links {
        flex-direction: column;
        gap: 12px;
        padding: 14px 20px;
        text-align: center;
      }
      
      .user-greeting::after {
        display: none;
      }
      
      .logout-btn {
        width: 100%;
        justify-content: center;
      }

      .help-section {
        padding: 60px 20px;
      }

      .help-section h2 {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>
  <!-- Enhanced Welcome Message & Logout Button -->
  <div class="top-links">
    <span class="user-greeting"><?php echo htmlspecialchars($user_name); ?></span>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
  
  <!-- Hero Banner (SAME BG - unchanged) -->
  <header class="hero">
    <div class="overlay">
      <h1>Legal Assist</h1>
      <p class="tagline">Your trusted guide for all legal and license needs</p>
    </div>
  </header>
  
  <!-- Enhanced Help Section -->
  <section class="help-section">
    <h2>What do you need help with?</h2>
    <div class="choice-container">
            <a href="police-complaint.php" class="choice-card">Citizen Police Services</a>

      <a href="law.html" class="choice-card">Legal Help</a>
      <a href="license.html" class="choice-card">Apply for a Licence</a>
    </div>
  </section>
  
  <!-- Enhanced Footer -->
 <footer class="footer">
  <div class="container">

    <div class="footer-item">
      <span class="icon">📍</span>
      <p>
        <strong>Address</strong><br>
        8-3-/17-D, 234, Banjara Hills,<br>
        Hyderabad, India
      </p>
    </div>

    <div class="footer-item">
      <span class="icon">📞</span>
      <p>
        <strong>Phone</strong><br>
        <a href="tel:+919876543210">+91 9876543210</a>
      </p>
    </div>

    <div class="footer-item">
      <span class="icon">📧</span>
      <p>
        <strong>Email</strong><br>
        <a href="mailto:contact@legalwebsite.com">
          contact@legalwebsite.com
        </a>
      </p>
    </div>

    <div class="footer-bottom">
      © 2025 <strong>Legal Assist</strong>. All rights reserved.
    </div>

  </div>
</footer>

</body>
</html>