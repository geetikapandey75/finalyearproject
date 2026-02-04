<!DOCTYPE html>
<html>
<head>
  <title>Reset Password</title>
  <style>
    body {
      font-family: Poppins, sans-serif;
      background: #eef2ff;
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
      background: #1e40af;
      color: white;
      border: none;
      border-radius: 8px;
    }
  </style>
</head>
<body>

<div class="box">
  <h2>Set New Password</h2>
  <form action="reset-password-process.php" method="POST">
    <input type="hidden" name="email" value="<?php echo $_GET['email']; ?>">
    <input type="password" name="password" placeholder="New Password" required minlength="8">
    <input type="password" name="confirm" placeholder="Confirm Password" required minlength="8">
    <button type="submit">Update Password</button>
  </form>
</div>

</body>
</html>
