<?php
// police_services.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die("DB Connection failed");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name        = $_POST['name'];
    $mobile      = $_POST['mobile'];
    $service     = $_POST['service_type'];
    $amount      = $_POST['amount'];

    // Optional fields
    $appointment_date = $_POST['appointment_date'] ?? null;
    $vehicle_no       = $_POST['vehicle_no'] ?? null;
    $challan_no       = $_POST['challan_no'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO police_services
        (name, mobile, service_type, appointment_date, vehicle_no, challan_no, amount)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssd",
        $name,
        $mobile,
        $service,
        $appointment_date,
        $vehicle_no,
        $challan_no,
        $amount
    );

    if ($stmt->execute()) {
        $service_id = $conn->insert_id;

        header("Location: payment.php?service=$service&id=$service_id&amount=$amount");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Police Services | Legal Assist</title>
<script src="https://cdn.tailwindcss.com"></script>

<script>
function handleServiceChange(value) {
    document.getElementById("appointment").style.display = "none";
    document.getElementById("challan").style.display = "none";

    if (value === "meeseva" || value === "passport") {
        document.getElementById("appointment").style.display = "block";
    }

    if (value === "echallan") {
        document.getElementById("challan").style.display = "block";
    }
}
</script>
</head>

<body class="bg-blue-50 min-h-screen flex items-center justify-center">
<div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-xl">

<h2 class="text-2xl font-bold text-blue-900 text-center mb-6">
Police Services Application
</h2>

<form action="order.php"  method="POST">

<!-- Common Fields -->
<label class="block mb-2 font-semibold">Full Name</label>
<input type="text" name="name" required class="w-full mb-4 p-2 border rounded">

<label class="block mb-2 font-semibold">Mobile Number</label>
<input type="text" name="mobile" required class="w-full mb-4 p-2 border rounded">

<label class="block mb-2 font-semibold">Select Service</label>
<select name="service_type" required onchange="handleServiceChange(this.value)"
class="w-full mb-4 p-2 border rounded">
<option value="">-- Choose Service --</option>
<option value="meeseva">MeeSeva Appointment</option>
<option value="passport">Passport Appointment</option>
<option value="echallan">e-Challan Payment</option>
</select>

<!-- Appointment Section -->
<div id="appointment" style="display:none">
<label class="block mb-2 font-semibold">Preferred Appointment Date</label>
<input type="date" name="appointment_date" class="w-full mb-4 p-2 border rounded">
</div>

<!-- Challan Section -->
<div id="challan" style="display:none">
<label class="block mb-2 font-semibold">Vehicle Number</label>
<input type="text" name="vehicle_no" class="w-full mb-4 p-2 border rounded">

<label class="block mb-2 font-semibold">Challan Number</label>
<input type="text" name="challan_no" class="w-full mb-4 p-2 border rounded">
</div>

<label class="block mb-2 font-semibold">Amount (₹)</label>
<input type="number" name="amount" required class="w-full mb-6 p-2 border rounded">

<button class="w-full bg-blue-900 text-white py-3 rounded-lg font-semibold hover:bg-blue-800">
Proceed to Payment
</button>

<script>
function setAmount() {
  const service = document.getElementById("service").value;
  let amt = 0;

  if(service === "passport") amt = 500;
  if(service === "meeseva") amt = 500;
  if(service === "echallan") amt = 500;

  document.getElementById("amount").value = amt;
}

function payNow() {
  let name = document.getElementById("name").value;
  let mobile = document.getElementById("mobile").value;
  let service = document.getElementById("service").value;
  let amount = document.getElementById("amount").value;

  if(!name || !mobile || !service) {
    alert("Please fill all fields");
    return;
  }

  var options = {
    "key": "rzp_test_RuyUcsfbG8XaIT",  // 🔴 Replace with your key
    "amount": amount * 100,
    "currency": "INR",
    "name": "Legal Assist",
    "description": service + " Service Payment",
    "handler": function (response){
      // Redirect after payment
      window.location.href =
      "payment_success.php?payment_id=" + response.razorpay_payment_id +
      "&service=" + service +
      "&amount=" + amount +
      "&name=" + name +
      "&mobile=" + mobile;
    },
    "prefill": {
      "name": name,
      "contact": mobile
    },
    "theme": {
      "color": "#1e3a8a"
    }
  };

  var rzp1 = new Razorpay(options);
  rzp1.open();
}
</script>

</form>
</div>
</body>
</html>
