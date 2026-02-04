<?php
require_once __DIR__ . '/razorpay-php/Razorpay.php';
use Razorpay\Api\Api;

$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) die("DB Error");

$order_id = "";
$amount = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name    = $_POST['name'] ?? '';
    $email   = $_POST['email'] ?? '';
    $phone   = $_POST['phone'] ?? '';
    $service = $_POST['service'] ?? '';
    $amount  = $_POST['amount'] * 100; // paise
    $additional_info = $_POST['additional_info'] ?? '';
    $date    = $_POST['date'] ?? '';
    $time    = $_POST['time'] ?? '';

    $api = new Api("rzp_test_RuyUcsfbG8XaIT", "fliKTmw84hX8mblSM1CyRQ0D");

    $order = $api->order->create([
        'receipt' => uniqid(),
        'amount' => $amount,
        'currency' => 'INR'
    ]);

    $order_id = $order['id'];

    // Make sure your table has these columns: additional_info, appointment_date, appointment_time
    $stmt = $conn->prepare(
      "INSERT INTO lawyer_services 
      (name,email,phone,service,additional_info,appointment_date,appointment_time,amount,razorpay_order_id)
       VALUES (?,?,?,?,?,?,?,?,?)"
    );

    if ($stmt === false) die("Prepare failed: " . $conn->error);

    $stmt->bind_param("sssssssss", $name, $email, $phone, $service, $additional_info, $date, $time, $_POST['amount'], $order_id);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lawyer Services Payment</title>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-2xl bg-white p-10 rounded-2xl shadow-lg">

<h2 class="text-3xl font-bold mb-6 text-center text-blue-800">Book a Lawyer Appointment</h2>

<form method="POST" class="space-y-4">

  <input name="name" type="text" required placeholder="Full Name" class="w-full p-3 border rounded-lg">
  <input name="email" type="email" required placeholder="Email" class="w-full p-3 border rounded-lg">
  <input name="phone" type="tel" required placeholder="Phone" class="w-full p-3 border rounded-lg">

  <select name="service" required class="w-full p-3 border rounded-lg">
    <option value="">Select Service</option>
    <option>Civil Law Services</option>
    <option>Criminal Law Services</option>
    <option>Family Law Services</option>
    <option>Corporate Law Services</option>
  </select>

  <input name="date" type="date" required class="w-full p-3 border rounded-lg">
  <input name="time" type="time" required class="w-full p-3 border rounded-lg">
  <textarea name="additional_info" rows="3" placeholder="Additional Information" class="w-full p-3 border rounded-lg"></textarea>
  <input name="amount" type="number" required placeholder="Amount (INR)" class="w-full p-3 border rounded-lg">

  <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">Pay & Book Appointment</button>
</form>

<?php if (!empty($order_id)) { ?>
<script>
var options = {
  "key": "rzp_test_RuyUcsfbG8XaIT",
  "amount": "<?php echo $amount; ?>",
  "currency": "INR",
  "name": "Legal Assist",
  "description": "<?php echo $service; ?>",
  "order_id": "<?php echo $order_id; ?>",
  "handler": function (response) {
    window.location = "lawyer-success.php?payment_id=" + response.razorpay_payment_id + 
                      "&order_id=<?php echo $order_id; ?>";
  },
  "prefill": {
    "name": "<?php echo $name; ?>",
    "email": "<?php echo $email; ?>",
    "contact": "<?php echo $phone; ?>"
  }
};
var rzp = new Razorpay(options);
rzp.open();
</script>
<?php } ?>

</div>
</body>
</html>
