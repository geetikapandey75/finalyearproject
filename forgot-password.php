<!DOCTYPE html>
<html>
<head>
  <title>Forgot Password | Legal Assist</title>
  <style>
    body {
      font-family: Poppins, sans-serif;
      background: #f4f6fb;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .box {
      background: white;
      padding: 35px;
      border-radius: 14px;
      width: 380px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    input, button {
      width: 100%;
      padding: 14px;
      margin-top: 12px;
    }
    button {
      background: #2563eb;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }
  </style>
</head>
<body>

<div class="box">
  <h2>Forgot Password</h2>
  <form action="forgot-password-process.php" method="POST">
    <input type="email" name="email" placeholder="Enter registered email" required>
    <button type="submit">Continue</button>
  </form>
</div>

</body>
</html>
