<?php
$conn = new mysqli("localhost", "root", "", "legal_assist");

$name = $_GET['name'];
$mobile = $_GET['mobile'];
$service = $_GET['service'];
$amount = $_GET['amount'];
$payment_id = $_GET['payment_id'];

$stmt = $conn->prepare(
  "INSERT INTO police_services 
  (name, mobile, service, amount, payment_id, status)
  VALUES (?, ?, ?, ?, ?, 'PAID')"
);

$stmt->bind_param("sssds",
  $name, $mobile, $service, $amount, $payment_id
);

$stmt->execute();
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment Successful</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 flex justify-center items-center h-screen">

<div class="bg-white p-6 rounded-xl shadow text-center">
<h2 class="text-2xl font-bold text-green-700">Payment Successful</h2>
<p class="mt-2">Your application has been submitted.</p>
<p class="mt-1 font-semibold">Payment ID: <?= $payment_id ?></p>
</div>

</body>
</html>
