<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    $_SESSION['error'] = "Please login to access this service.";
    header("Location: home_page.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "legal_assist";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$errorMsg   = "";
$trackingID = "";
$statusResult = "";

// Handle appointment form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_appointment'])) {

    // Generate UNIQUE TRACKING ID for passport
    $trackingID = "PASS-" . date("Y") . "-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

    // Collect form data safely
    $firstName       = trim($_POST['firstName'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $pskLocation     = trim($_POST['pskLocation'] ?? '');
    $appointmentDate = trim($_POST['appointmentDate'] ?? '');
    $timeSlot        = trim($_POST['timeSlot'] ?? '');
    $serviceType     = trim($_POST['serviceType'] ?? '');
    $paymentAmount   = (int)($_POST['paymentAmount'] ?? 0);

    // Validate required fields
    if (
        empty($firstName) ||
        empty($email) ||
        empty($phone) ||
        empty($pskLocation) ||
        empty($appointmentDate) ||
        empty($timeSlot) ||
        empty($serviceType) ||
        $paymentAmount <= 0
    ) {
        $errorMsg = "All fields are required and amount must be greater than 0.";
    } else {

        // Insert into passport_appointments table with payment_status = 'pending'
        $stmt = $conn->prepare("
            INSERT INTO passport_appointments
            (tracking_id, first_name, email, phone, psk_location, appointment_date, time_slot, service_type, amount, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        if (!$stmt) {
            $errorMsg = "Prepare failed: " . $conn->error;
        } else {

            // Bind parameters
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

            // Execute
            if ($stmt->execute()) {
                $inserted_id = $conn->insert_id;
                $stmt->close();
                
                // Redirect to payment gateway
                header("Location: payment_gateway.php?service=passport&id=" . $inserted_id . "&amount=" . $paymentAmount);
                exit();
            } else {
                $errorMsg = "Failed to book appointment: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

// Handle status tracking
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['check_status'])) {
    $searchTrackingID = trim($_POST['tracking_id'] ?? '');
    
    if (empty($searchTrackingID)) {
        $statusResult = "error";
        $errorMsg = "Please enter a tracking ID.";
    } else {
        // Search for the tracking ID in database
        $stmt = $conn->prepare("
            SELECT tracking_id, first_name, appointment_date, service_type, psk_location, time_slot, created_at, payment_status
            FROM passport_appointments 
            WHERE tracking_id = ?
        ");
        
        $stmt->bind_param("s", $searchTrackingID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Check if payment is completed
            if ($row['payment_status'] !== 'paid') {
                $statusResult = "payment_pending";
                $errorMsg = "Payment for this application is still pending. Please complete the payment to proceed.";
            } else {
                $statusResult = "found";
                
                // Calculate days since appointment
                $appointmentDate = new DateTime($row['appointment_date']);
                $today = new DateTime();
                $daysPassed = $today->diff($appointmentDate)->days;
                
                // If appointment is in future, use 0 days
                if ($today < $appointmentDate) {
                    $daysPassed = 0;
                }
                
                // Status timeline based on days passed
                $statuses = [
                    'submitted' => ['day' => 0, 'label' => 'Application Submitted', 'completed' => false],
                    'verified' => ['day' => 5, 'label' => 'Documents Verified', 'completed' => false],
                    'police_init' => ['day' => 10, 'label' => 'Police Verification Initiated', 'completed' => false],
                    'police_complete' => ['day' => 25, 'label' => 'Police Verification Complete', 'completed' => false],
                    'printed' => ['day' => 35, 'label' => 'Passport Printed', 'completed' => false],
                    'dispatched' => ['day' => 40, 'label' => 'Dispatched', 'completed' => false],
                    'delivered' => ['day' => 45, 'label' => 'Delivered', 'completed' => false]
                ];
                
                // Mark completed statuses
                foreach ($statuses as $key => $status) {
                    if ($daysPassed >= $status['day']) {
                        $statuses[$key]['completed'] = true;
                    }
                }
                
                // Calculate estimated delivery date (45 days from appointment)
                $deliveryDate = clone $appointmentDate;
                $deliveryDate->modify('+45 days');
                $estimatedDelivery = $deliveryDate->format('F d, Y');
                
                // Store for display
                $trackingData = [
                    'tracking_id' => $row['tracking_id'],
                    'name' => $row['first_name'],
                    'appointment_date' => $appointmentDate->format('F d, Y'),
                    'service_type' => $row['service_type'],
                    'psk_location' => $row['psk_location'],
                    'time_slot' => $row['time_slot'],
                    'days_passed' => $daysPassed,
                    'statuses' => $statuses,
                    'estimated_delivery' => $estimatedDelivery
                ];
            }
            
        } else {
            $statusResult = "not_found";
            $errorMsg = "No application found with this tracking ID.";
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Passport Services | Legal Assist</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 font-sans text-gray-900">

  <!-- Navigation Bar -->
  <header class="bg-blue-900 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold">Legal Assist</h1>
      <nav class="space-x-4">
        <a href="vehicle.html" class="hover:underline">Unclaimed Vehicles</a>
        <a href="missing.php" class="hover:underline">Missing Persons</a>
        <a href="e-challan.html" class="hover:underline font-semibold text-yellow-300">E-Challan</a>
      </nav>
    </div>
  </header>

  <!-- Error Message Alert -->
  <?php if($errorMsg && !isset($trackingData)): ?>
  <div class="container mx-auto px-6 py-4">
    <div class="bg-red-50 border-2 border-red-400 p-4 rounded-lg text-center">
      <p class="text-red-800 font-semibold">❌ <?php echo htmlspecialchars($errorMsg); ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Hero Section -->
  <section class="text-center py-16 bg-gradient-to-r from-blue-800 to-teal-600 text-white">
    <h2 class="text-4xl font-bold mb-4">Passport Services</h2>
    <p class="mb-6 text-lg max-w-2xl mx-auto">Easily apply for new passports, renewals, or check your passport application status through our official portal.</p>
  </section>

  <!-- Live Application Status Tracker - MOVED TO TOP -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-4xl mx-auto">
      <h3 class="text-2xl font-bold text-blue-800 mb-6 text-center">🔍 Live Application Status Tracker</h3>
      <p class="text-center text-gray-600 mb-6">Enter your tracking ID to check real-time status of your passport application</p>
      
      <form method="POST" action="passport.php" class="mb-6">
        <div class="flex flex-col md:flex-row gap-4">
          <input 
            type="text" 
            name="tracking_id" 
            placeholder="Enter Tracking ID (e.g., PASS-2025-XXXXXXXX)" 
            class="flex-1 p-4 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
            value="<?php echo isset($trackingData) ? $trackingData['tracking_id'] : ''; ?>"
            required>
          <button 
            type="submit" 
            name="check_status"
            class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-500 transition md:w-auto w-full">
            Check Status
          </button>
        </div>
      </form>

      <?php if ($statusResult === "found" && isset($trackingData)): ?>
      <!-- Status Found - Show Timeline -->
      <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6 mb-6">
        <div class="grid md:grid-cols-2 gap-4 mb-6">
          <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Applicant Name</p>
            <p class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($trackingData['name']); ?></p>
          </div>
          <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Service Type</p>
            <p class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($trackingData['service_type']); ?></p>
          </div>
          <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">PSK Location</p>
            <p class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($trackingData['psk_location']); ?></p>
          </div>
          <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-sm text-gray-600">Appointment Date</p>
            <p class="font-bold text-lg text-gray-800"><?php echo $trackingData['appointment_date']; ?></p>
          </div>
        </div>

        <!-- Progress Timeline -->
        <div class="bg-white rounded-lg p-6 shadow-md">
          <h4 class="font-bold text-xl text-gray-800 mb-4 text-center">Application Progress</h4>
          
          <div class="space-y-4">
            <?php foreach($trackingData['statuses'] as $key => $status): ?>
            <div class="flex items-center gap-4">
              <div class="flex-shrink-0">
                <?php if($status['completed']): ?>
                  <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-lg">✓</div>
                <?php else: ?>
                  <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600">○</div>
                <?php endif; ?>
              </div>
              
              <div class="flex-1">
                <p class="font-semibold text-gray-800 <?php echo $status['completed'] ? 'text-green-700' : 'text-gray-500'; ?>">
                  <?php echo $status['label']; ?>
                </p>
                <p class="text-xs text-gray-500">Day <?php echo $status['day']; ?></p>
              </div>
              
              <?php if($status['completed']): ?>
                <div class="text-green-600 font-bold text-sm">COMPLETED</div>
              <?php else: ?>
                <div class="text-gray-400 font-bold text-sm">PENDING</div>
              <?php endif; ?>
            </div>
            
            <?php if($key !== 'delivered'): ?>
            <div class="ml-5 border-l-2 <?php echo $status['completed'] ? 'border-green-500' : 'border-gray-300'; ?> h-6"></div>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>

          <!-- Estimated Delivery -->
          <div class="mt-6 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-4 text-white text-center">
            <p class="text-sm mb-1">Estimated Delivery Date</p>
            <p class="text-2xl font-bold"><?php echo $trackingData['estimated_delivery']; ?></p>
            <p class="text-xs mt-1 text-blue-100">
              <?php 
                $remaining = 45 - $trackingData['days_passed'];
                if ($remaining > 0) {
                    echo "Approximately {$remaining} days remaining";
                } else {
                    echo "Your passport should be delivered";
                }
              ?>
            </p>
          </div>
        </div>
      </div>

      <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
        <p class="text-sm text-blue-800">
          <strong>ℹ️ Note:</strong> Status updates automatically based on your appointment date. 
          The system progresses through each stage at standard processing intervals.
        </p>
      </div>

      <?php elseif ($statusResult === "payment_pending"): ?>
      <!-- Payment Pending Message -->
      <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-8 text-center">
        <div class="text-5xl mb-4">⚠️</div>
        <h4 class="text-xl font-bold text-yellow-800 mb-2">Payment Pending</h4>
        <p class="text-yellow-700 mb-4"><?php echo htmlspecialchars($errorMsg); ?></p>
        <p class="text-sm text-gray-600">Please complete your payment to activate your appointment.</p>
      </div>

      <?php elseif ($statusResult === "not_found"): ?>
      <!-- Not Found Message -->
      <div class="bg-red-50 border-2 border-red-300 rounded-lg p-8 text-center">
        <div class="text-5xl mb-4">❌</div>
        <h4 class="text-xl font-bold text-red-800 mb-2">Application Not Found</h4>
        <p class="text-red-600">No passport application found with this tracking ID.</p>
        <p class="text-sm text-gray-600 mt-2">Please check your tracking ID and try again.</p>
      </div>

      <?php else: ?>
      <!-- Initial State - Show Instructions -->
      <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-6 text-center">
        <div class="text-5xl mb-4">📋</div>
        <h4 class="text-xl font-bold text-blue-800 mb-2">Track Your Application</h4>
        <p class="text-gray-700">Enter your tracking ID above to see your passport application status in real-time.</p>
        <p class="text-sm text-gray-600 mt-2">Your tracking ID was provided when you booked your appointment.</p>
      </div>
      <?php endif; ?>

    </div>
  </section>

  <!-- Service Overview -->
  <section class="container mx-auto px-6 py-12">
    <h3 class="text-3xl font-bold text-center text-blue-800 mb-10">Our Passport Services</h3>
    <div class="grid md:grid-cols-3 gap-8">
      <div class="bg-white rounded-xl shadow-lg p-6 text-center hover:shadow-2xl transition">
        <h4 class="text-xl font-semibold text-blue-700 mb-3">🆕 New Passport</h4>
        <p>Apply for a new passport quickly with guided online submission and verification support.</p>
      </div>
      <div class="bg-white rounded-xl shadow-lg p-6 text-center hover:shadow-2xl transition">
        <h4 class="text-xl font-semibold text-blue-700 mb-3">♻️ Renewal / Reissue</h4>
        <p>Renew your existing passport or reissue for changes in address, name, or expiry.</p>
      </div>
      <div class="bg-white rounded-xl shadow-lg p-6 text-center hover:shadow-2xl transition">
        <h4 class="text-xl font-semibold text-blue-700 mb-3">📄 Track Status</h4>
        <p>Check the real-time status of your passport application using your file number.</p>
      </div>
    </div>
  </section>

  <!-- Application Steps -->
  <section class="bg-blue-100 py-12">
    <div class="container mx-auto px-6">
      <h3 class="text-3xl font-bold text-center text-blue-800 mb-10">How to Apply</h3>
      <div class="grid md:grid-cols-4 gap-8 text-center">
        <div class="bg-white rounded-xl p-6 shadow-md">
          <h4 class="text-blue-700 font-semibold mb-2">Step 1</h4>
          <p>Register on the official Passport Seva portal.</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-md">
          <h4 class="text-blue-700 font-semibold mb-2">Step 2</h4>
          <p>Fill in the application form and upload required documents.</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-md">
          <h4 class="text-blue-700 font-semibold mb-2">Step 3</h4>
          <p>Pay the applicable fee and schedule an appointment.</p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-md">
          <h4 class="text-blue-700 font-semibold mb-2">Step 4</h4>
          <p>Visit the Passport Seva Kendra (PSK) with original documents.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Document Checklist -->
  <section class="container mx-auto px-6 py-12">
    <h3 class="text-3xl font-bold text-center text-blue-800 mb-10">Documents Required</h3>
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-3xl mx-auto">
      <ul class="list-disc list-inside space-y-3 text-gray-700">
        <li>Proof of Date of Birth (Birth Certificate, 10th Marksheet, etc.)</li>
        <li>Proof of Address (Aadhaar, Voter ID, Electricity Bill, etc.)</li>
        <li>Proof of Nationality (PAN Card, Aadhaar, or Voter ID)</li>
        <li>Old Passport (if applying for renewal)</li>
        <li>Passport-size Photographs (as per official guidelines)</li>
      </ul>
    </div>
  </section>

  <!-- Book Appointment & Pay Online -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-3xl mx-auto">
      <h3 class="text-2xl font-bold text-blue-800 mb-6 text-center">Book Appointment & Pay Online</h3>
      <p class="mb-6 text-gray-700 text-center">
        Schedule your Passport Seva Kendra (PSK) appointment and pay applicable fees securely online.
      </p>

      <!-- Appointment Form -->
      <form method="POST" action="passport.php" id="appointmentForm" class="space-y-6">

        <div>
          <label class="block font-semibold mb-2">First Name</label>
          <input type="text" id="firstName" name="firstName" class="w-full p-4 border rounded-lg" placeholder="Enter your first name" required>
        </div>

        <div>
          <label class="block font-semibold mb-2">Email Address</label>
          <input type="email" id="email" name="email"  class="w-full p-4 border rounded-lg" placeholder="Enter your email" required>
        </div>

        <div>
          <label class="block font-semibold mb-2">Phone Number</label>
          <input type="tel" id="phone" name="phone" class="w-full p-4 border rounded-lg" placeholder="Enter your phone number" required>
        </div>

        <div>
          <label class="block font-semibold mb-2">Select PSK Location</label>
          <select id="pskLocation" name="pskLocation"  class="w-full p-4 border rounded-lg" required>
            <option value="">Select PSK</option>
            <option value="Secunderabad PSK">Secunderabad PSK</option>
            <option value="Hyderabad PSK">Hyderabad PSK</option>
            <option value="Kukatpally PSK">Kukatpally PSK</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold mb-2">Select Date</label>
          <input type="date" id="appointmentDate" name="appointmentDate" class="w-full p-4 border rounded-lg" required>
        </div>

        <div>
          <label class="block font-semibold mb-2">Select Time Slot</label>
          <select id="timeSlot"  name="timeSlot" class="w-full p-4 border rounded-lg" required>
            <option value="">Select Time</option>
            <option value="10:00 AM">10:00 AM</option>
            <option value="11:00 AM">11:00 AM</option>
            <option value="12:00 PM">12:00 PM</option>
            <option value="01:00 PM">01:00 PM</option>
            <option value="02:00 PM">02:00 PM</option>
            <option value="03:00 PM">03:00 PM</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold mb-2">Service Type</label>
          <select id="serviceType" name="serviceType" class="w-full p-4 border rounded-lg" required>
            <option value="">Select Service</option>
            <option value="New Passport">New Passport</option>
            <option value="Renewal / Reissue">Renewal / Reissue</option>
            <option value="Tatkal">Tatkal</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold mb-2">Amount to Pay (INR)</label>
          <input type="text" id="paymentAmount" name="paymentAmount"  class="w-full p-4 border rounded-lg bg-gray-100" value="0" readonly>
        </div>

        <button type="submit"
        name="submit_appointment"
        class="bg-green-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-green-500 w-full">
  Proceed to Pay & Book Appointment
</button>

      </form>
    </div>
  </section>

  <!-- Fees Calculator -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-3xl mx-auto">
      <h3 class="text-2xl font-bold text-blue-800 mb-6 text-center">Fees Calculator</h3>
      <form id="feesForm" class="space-y-6">
        <div>
          <label class="block font-semibold mb-2">Service Type</label>
          <select id="feesService" class="w-full p-4 border rounded-lg">
            <option value="normal">New / Renewal (Normal)</option>
            <option value="tatkal">Tatkal</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold mb-2">Number of Pages</label>
          <select id="pages" class="w-full p-4 border rounded-lg">
            <option value="36">36</option>
            <option value="60">60</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold mb-2">Validity Period (Years)</label>
          <input type="number" id="validity" class="w-full p-4 border rounded-lg" value="10">
        </div>
        <button type="button" onclick="calculateFees()" class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-500 w-full">Calculate Fees</button>
      </form>
      <p class="mt-4 font-semibold text-gray-800">Total Fees: ₹<span id="totalFees">0</span></p>
    </div>
  </section>

  <!-- Appointment Slot Finder -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-4xl mx-auto">
      <h3 class="text-2xl font-bold text-blue-800 mb-6 text-center">Appointment Slot Finder</h3>
      
      <div class="mb-6">
        <label class="block font-semibold mb-2">Select Passport Seva Kendra (PSK)</label>
        <select id="pskSelect" class="w-full p-4 border rounded-lg">
          <option value="madhapur">Madhapur PSK</option>
          <option value="kukatpally">Kukatpally PSK</option>
          <option value="secunderabad">Secunderabad PSK</option>
        </select>
      </div>

      <div class="mb-6">
        <label class="block font-semibold mb-2">Select Date</label>
        <input type="date" id="slotAppointmentDate" class="w-full p-4 border rounded-lg">
      </div>

      <div class="mb-6">
        <label class="block font-semibold mb-2">Available Time Slots</label>
        <select id="slotTimeSlot" class="w-full p-4 border rounded-lg">
          <option value="">Select a time slot</option>
        </select>
      </div>

      <button onclick="findEarliestSlot()" class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-500 w-full mb-4">
        Find Earliest Available Slot
      </button>

      <button onclick="confirmAppointmentSlot()" class="bg-green-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-green-500 w-full">
        Confirm Appointment & Add to Google Calendar
      </button>

      <p id="confirmationMsg" class="mt-4 text-gray-700 font-semibold hidden"></p>
    </div>
  </section>

  <!-- Tatkal vs Normal Comparison -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-5xl mx-auto">
      <h3 class="text-2xl font-bold text-blue-800 mb-6 text-center">Tatkal vs Normal Passport Services</h3>
      
      <div class="overflow-x-auto mb-6">
        <table class="min-w-full border border-gray-200">
          <thead class="bg-blue-100">
            <tr>
              <th class="py-3 px-6 text-left">Feature</th>
              <th class="py-3 px-6 text-left">Normal</th>
              <th class="py-3 px-6 text-left">Tatkal</th>
            </tr>
          </thead>
          <tbody class="text-gray-700">
            <tr class="border-t">
              <td class="py-3 px-6">Processing Time</td>
              <td class="py-3 px-6">15-30 days</td>
              <td class="py-3 px-6">1-7 days</td>
            </tr>
            <tr class="border-t">
              <td class="py-3 px-6">Cost</td>
              <td class="py-3 px-6">₹1,500 (36 pages)</td>
              <td class="py-3 px-6">₹3,500 (36 pages)</td>
            </tr>
            <tr class="border-t">
              <td class="py-3 px-6">Documents Required</td>
              <td class="py-3 px-6">Basic documents (DOB, Address proof, ID)</td>
              <td class="py-3 px-6">Same as normal + additional verification</td>
            </tr>
            <tr class="border-t">
              <td class="py-3 px-6">Appointment Availability</td>
              <td class="py-3 px-6">Regular slots</td>
              <td class="py-3 px-6">Limited Tatkal slots daily</td>
            </tr>
            <tr class="border-t">
              <td class="py-3 px-6">Real User Processing Data</td>
              <td class="py-3 px-6">Average 20 days</td>
              <td class="py-3 px-6">Average 4 days</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-blue-50 p-6 rounded-lg">
        <h4 class="text-xl font-semibold text-blue-700 mb-4">Is Tatkal Worth It?</h4>
        <p class="mb-4">Estimate the value of choosing Tatkal based on urgency and cost.</p>

        <div class="grid md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block font-semibold mb-2">Days Needed Urgently</label>
            <input type="number" id="urgentDays" class="w-full p-3 border rounded-lg" placeholder="e.g., 5" min="1">
          </div>
          <div>
            <label class="block font-semibold mb-2">Willingness to Pay Extra (₹)</label>
            <input type="number" id="extraCost" class="w-full p-3 border rounded-lg" placeholder="e.g., 2000" min="0">
          </div>
        </div>

        <button onclick="calculateTatkal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-500">Check Recommendation</button>
        <p id="tatkalResult" class="mt-4 font-semibold text-gray-700"></p>
      </div>
    </div>
  </section>

  <!-- Police Verification Preparation -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-4xl mx-auto">
      <h3 class="text-2xl font-bold text-blue-800 mb-6 text-center">Police Verification Preparation</h3>
      
      <p class="mb-4 text-gray-700">Prepare for the police verification step of your passport application. This ensures a smooth verification process at your residence.</p>
    
    <ul class="list-disc list-inside space-y-3 text-gray-700">
      <li><strong>What police will verify:</strong> Identity, address, and background details as provided in your application.</li>
      <li><strong>Questions they might ask:</strong> Employment details, family members, neighbors, and purpose of passport.</li>
      <li><strong>Documents to keep ready at home:</strong> Original proof of address, proof of identity, and supporting documents for verification.</li>
      <li><strong>Neighbor verification requirements:</strong> Neighbors may be asked to confirm your residence and identity.</li>
      <li><strong>Landlord NOC:</strong> If you are renting, keep a No Objection Certificate ready from your landlord.</li>
      <li><strong>Timeline expectations:</strong> Police verification usually completes within 7–15 days depending on the area.</li>
      <li><strong>How to handle verification visit:</strong> Be polite, answer all questions accurately, and provide documents when requested.</li>
    </ul>
  </div>
</section>

  <!-- Passport Eligibility Checker -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-3xl mx-auto">
      <h3 class="text-2xl font-bold text-blue-800 mb-6 text-center">Passport Eligibility Checker</h3>
      
      <p class="mb-6 text-gray-700 text-center">Answer a few questions to check if you are eligible for a passport and what type you can apply for.</p>
      
      <form id="eligibilityForm" class="space-y-4">
        <div>
          <label class="block font-semibold mb-2">1. Are you at least 18 years old?</label>
          <select class="w-full p-4 border rounded-lg" required>
            <option value="">Select</option>
            <option value="yes">Yes</option>
            <option value="no">No</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold mb-2">2. Do you have valid proof of address?</label>
          <select class="w-full p-4 border rounded-lg" required>
            <option value="">Select</option>
            <option value="yes">Yes</option>
            <option value="no">No</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold mb-2">3. Do you have proof of identity?</label>
          <select class="w-full p-4 border rounded-lg" required>
            <option value="">Select</option>
            <option value="yes">Yes</option>
            <option value="no">No</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold mb-2">4. Are you a government employee, diplomat, or special category?</label>
          <select class="w-full p-4 border rounded-lg" required>
            <option value="">Select</option>
            <option value="yes">Yes</option>
            <option value="no">No</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold mb-2">5. Do you have any legal disqualifications (criminal cases, banned status)?</label>
          <select class="w-full p-4 border rounded-lg" required>
            <option value="">Select</option>
            <option value="no">No</option>
            <option value="yes">Yes</option>
          </select>
        </div>
        <button type="button" onclick="checkEligibility()" class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-500 w-full">Check Eligibility</button>
      </form>

      <div id="eligibilityResult" class="mt-6 text-center text-lg font-semibold text-gray-700"></div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-blue-900 text-white text-center py-4">
    © 2025 Legal Assist | Passport Services
  </footer>
  
  <style>
  #whatsapp-button {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
  }

  #whatsapp-button img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
    cursor: pointer;
  }
</style>

<a href="https://wa.me/8897752518?text=Hello%20Legal%20Assist,%20I%20need%20help%20with%20your%20services" target="_blank" id="whatsapp-button">
  <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="Chat with us on WhatsApp">
</a>

  <script>
    // Service Type Fee Mapping
    const serviceFees = {
      "New Passport": 1500,
      "Renewal / Reissue": 1200,
      "Tatkal": 3500
    };

    const serviceType = document.getElementById("serviceType");
    const paymentAmount = document.getElementById("paymentAmount");

    serviceType.addEventListener("change", () => {
      const fee = serviceFees[serviceType.value] || 0;
      paymentAmount.value = fee;
    });

    // Form Validation Before Submission
    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
      const serviceTypeVal = document.getElementById('serviceType').value;
      const paymentAmountVal = document.getElementById('paymentAmount').value;
      
      if (!serviceTypeVal) {
        e.preventDefault();
        alert('Please select a service type');
        return false;
      }
      
      if (!paymentAmountVal || paymentAmountVal <= 0) {
        e.preventDefault();
        alert('Please select a valid service type with amount');
        return false;
      }
      
      // Show loading state
      const submitBtn = e.target.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '⏳ Processing...';
    });

    // Fees Calculator
    function calculateFees() {
      const service = document.getElementById('feesService').value;
      const pages = parseInt(document.getElementById('pages').value);
      const validity = parseInt(document.getElementById('validity').value);
      
      let baseFee = service === 'tatkal' ? 3500 : 1500;
      let pageFee = pages === 60 ? 500 : 0;
      let total = baseFee + pageFee;
      
      document.getElementById('totalFees').textContent = total;
    }

    // Slot Finder
    const timeSlots = {
      madhapur: ["09:00 AM", "10:00 AM", "11:00 AM", "02:00 PM", "03:00 PM"],
      kukatpally: ["10:00 AM", "11:30 AM", "01:00 PM", "03:00 PM", "04:00 PM"],
      secunderabad: ["09:30 AM", "11:00 AM", "01:30 PM", "02:30 PM", "04:00 PM"]
    };

    document.getElementById('pskSelect').addEventListener('change', updateTimeSlots);
    document.getElementById('slotAppointmentDate').addEventListener('change', updateTimeSlots);

    function updateTimeSlots() {
      const psk = document.getElementById('pskSelect').value;
      const timeSlotSelect = document.getElementById('slotTimeSlot');
      timeSlotSelect.innerHTML = '<option value="">Select a time slot</option>';

      if (psk && timeSlots[psk]) {
        timeSlots[psk].forEach(slot => {
          const option = document.createElement('option');
          option.value = slot;
          option.textContent = slot;
          timeSlotSelect.appendChild(option);
        });
      }
    }

    function findEarliestSlot() {
      const psk = document.getElementById('pskSelect').value;
      const timeSlotSelect = document.getElementById('slotTimeSlot');
      if(psk && timeSlots[psk]) {
        timeSlotSelect.value = timeSlots[psk][0];
        alert(`Earliest slot at ${psk.toUpperCase()}: ${timeSlots[psk][0]}`);
      } else {
        alert("Select a PSK first");
      }
    }

    function confirmAppointmentSlot() {
      const psk = document.getElementById('pskSelect').value;
      const date = document.getElementById('slotAppointmentDate').value;
      const time = document.getElementById('slotTimeSlot').value;

      if(!psk || !date || !time) {
        alert("Please select PSK, date, and time slot");
        return;
      }

      const msg = `Appointment confirmed at ${psk.toUpperCase()} on ${date} at ${time}. SMS/Email sent.`;
      const confirmationMsg = document.getElementById('confirmationMsg');
      confirmationMsg.textContent = msg;
      confirmationMsg.classList.remove('hidden');

      const gcalLink = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=Passport+Appointment+(${psk.toUpperCase()})&dates=${date.replaceAll("-","")}/${date.replaceAll("-","")}&details=Passport+appointment+at+${psk.toUpperCase()}+on+${date}+at+${time}&location=${psk}+PSK`;
      window.open(gcalLink, "_blank");
    }

    // Tatkal Calculator
    function calculateTatkal() {
      const urgentDays = parseInt(document.getElementById('urgentDays').value);
      const extraCost = parseInt(document.getElementById('extraCost').value);

      if(!urgentDays || urgentDays <= 0 || isNaN(extraCost) || extraCost < 0){
        alert("Please enter valid numbers");
        return;
      }

      const normalAvgDays = 20;
      const tatkalAvgDays = 4;

      let recommendation = "";

      if(urgentDays <= tatkalAvgDays){
        recommendation = "Tatkal is recommended. You will get your passport faster.";
      } else if(extraCost >= 2000){
        recommendation = "Tatkal is suitable if you are willing to pay the extra cost for faster processing.";
      } else {
        recommendation = "Normal service is sufficient for your needs.";
      }

      document.getElementById('tatkalResult').textContent = recommendation;
    }

    // Eligibility Checker
    function checkEligibility() {
      const form = document.getElementById('eligibilityForm');
      const values = Array.from(form.querySelectorAll('select')).map(select => select.value.toLowerCase());
      let message = "";

      if (values.includes("no")) {
        message = "You may not be fully eligible for a passport. Please review your documents and requirements.";
      } else if (values.includes("yes") && values[4] === "no") {
        message = "You are eligible to apply for a passport. Proceed with the application.";
      } else if (values[4] === "yes") {
        message = "You may face restrictions due to disqualifications. Please contact the Passport Seva office for guidance.";
      } else {
        message = "Eligibility check inconclusive. Please ensure all questions are answered correctly.";
      }

      document.getElementById('eligibilityResult').innerText = message;
    }

    // Set minimum date for appointment (today)
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('appointmentDate').setAttribute('min', today);
  </script>

</body>
</html>