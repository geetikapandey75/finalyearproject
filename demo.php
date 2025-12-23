<?php
session_start();

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header("Location: home_page.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ================= DB ================= */
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die("DB Connection Failed");
}

$errorMsg = "";

/* ================= BOOK APPOINTMENT ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_appointment'])) {

    $trackingID = "PASS-" . date("Y") . "-" . strtoupper(substr(md5(uniqid()), 0, 8));

    $firstName = trim($_POST['firstName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pskLocation = trim($_POST['pskLocation'] ?? '');
    $appointmentDate = trim($_POST['appointmentDate'] ?? '');
    $timeSlot = trim($_POST['timeSlot'] ?? '');
    $serviceType = trim($_POST['serviceType'] ?? '');
    $paymentAmount = (int)($_POST['paymentAmount'] ?? 0);

    if (!$firstName || !$email || !$phone || !$pskLocation || !$appointmentDate || !$timeSlot || !$serviceType || $paymentAmount <= 0) {
        $errorMsg = "All fields are required.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO passport_appointments
            (tracking_id, first_name, email, phone, psk_location, appointment_date, time_slot, service_type, amount, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            "ssssssssi",
            $trackingID,
            $firstName,
            $email,
            $phone,
            $pskLocation,
            $appointmentDate,
            $timeSlot,
            $serviceType,
            $paymentAmount
        );

        if ($stmt->execute()) {
            $_SESSION['payment'] = [
                'service' => 'passport',
                'id' => $conn->insert_id,
                'amount' => $paymentAmount,
                'tracking_id' => $trackingID
            ];
            header("Location: payment_gateway_integration.php");
            exit();
        } else {
            $errorMsg = "Booking failed.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Passport Appointment Booking</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 900px;
    margin: 30px auto;
    background: white;
    padding: 25px;
    border-radius: 8px;
}
h1 {
    color: #1f2937;
}
.section {
    margin-bottom: 30px;
}
label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
}
input, select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
}
button {
    background: #16a34a;
    color: white;
    border: none;
    padding: 12px 20px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 20px;
    border-radius: 5px;
}
button:disabled {
    background: #9ca3af;
}
.error {
    color: red;
    font-weight: bold;
}
.info-box {
    background: #f0fdf4;
    padding: 15px;
    border-left: 5px solid #22c55e;
    margin-top: 20px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
table td, table th {
    border: 1px solid #ddd;
    padding: 10px;
}
table th {
    background: #e5e7eb;
}
.footer-note {
    font-size: 14px;
    color: #6b7280;
    margin-top: 20px;
}
</style>
</head>

<body>
<div class="container">
<h1>Passport Appointment Booking</h1>

<?php if ($errorMsg): ?>
<p class="error"><?= htmlspecialchars($errorMsg) ?></p>
<?php endif; ?>

<form method="POST" id="appointmentForm">
<input type="hidden" name="submit_appointment" value="1">

<div class="section">
<h3>Applicant Details</h3>

<label>Full Name</label>
<input type="text" name="firstName" required>

<label>Email</label>
<input type="email" name="email" required>

<label>Phone</label>
<input type="text" name="phone" required>
</div>

<div class="section">
<h3>Appointment Details</h3>

<label>PSK Location</label>
<select name="pskLocation" required>
<option value="">Select</option>
<option value="Hyderabad">Hyderabad</option>
<option value="Bangalore">Bangalore</option>
<option value="Delhi">Delhi</option>
</select>

<label>Appointment Date</label>
<input type="date" name="appointmentDate" required>

<label>Time Slot</label>
<select name="timeSlot" required>
<option value="">Select</option>
<option>09:00 - 10:00</option>
<option>10:00 - 11:00</option>
<option>11:00 - 12:00</option>
</select>

<label>Service Type</label>
<select name="serviceType" id="serviceType" required>
<option value="">Select</option>
<option value="Normal">Normal Passport</option>
<option value="Tatkal">Tatkal Passport</option>
</select>
</div>

<div class="section">
<h3>Fees</h3>
<table>
<tr><th>Service</th><th>Amount (₹)</th></tr>
<tr><td>Normal Passport</td><td>1500</td></tr>
<tr><td>Tatkal Passport</td><td>3500</td></tr>
</table>
</div>

<input type="hidden" name="paymentAmount" id="paymentAmount">

<button type="submit" id="submitBtn">Proceed to Payment</button>
</form>

<div class="info-box">
<h4>Important Notes</h4>
<ul>
<li>Carry original documents</li>
<li>Police verification is mandatory</li>
<li>Payment confirmation required</li>
</ul>
</div>

<p class="footer-note">
After successful payment, you will receive a tracking ID.
</p>
</div>

<script>
const form = document.getElementById("appointmentForm");
const serviceType = document.getElementById("serviceType");
const paymentAmount = document.getElementById("paymentAmount");
const submitBtn = document.getElementById("submitBtn");

form.addEventListener("submit", function () {

    if (serviceType.value === "Normal") {
        paymentAmount.value = 1500;
    } else if (serviceType.value === "Tatkal") {
        paymentAmount.value = 3500;
    } else {
        alert("Select service type");
        event.preventDefault();
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerText = "Processing...";
});
</script>

</body>
</html>