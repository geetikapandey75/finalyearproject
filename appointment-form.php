<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if all fields are received
$name     = isset($_GET['name']) ? $_GET['name'] : '';
$email    = isset($_GET['email']) ? $_GET['email'] : '';
$phone    = isset($_GET['phone']) ? $_GET['phone'] : '';
$date     = isset($_GET['date']) ? $_GET['date'] : '';
$time     = isset($_GET['time']) ? $_GET['time'] : '';
$service  = isset($_GET['service']) ? $_GET['service'] : '';
$message  = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appointment Confirmation</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans">

  <div class="max-w-xl mx-auto mt-10 bg-white p-8 rounded-2xl shadow-lg border border-gray-200">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Appointment Details</h2>

    <div class="space-y-4">
      <p><strong>Name:</strong> <?php echo htmlspecialchars($name); ?></p>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
      <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
      <p><strong>Date:</strong> <?php echo htmlspecialchars($date); ?></p>
      <p><strong>Time:</strong> <?php echo htmlspecialchars($time); ?></p>
      <p><strong>Service:</strong> <?php echo htmlspecialchars($service); ?></p>
      <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($message)); ?></p>
    </div>
  </div>

</body>
</html>
