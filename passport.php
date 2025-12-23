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

// Display success message after payment
$showSuccessMessage = false;
if (isset($_GET['payment_success']) && $_GET['payment_success'] == '1' && isset($_GET['tracking_id'])) {
    $showSuccessMessage = true;
    $successTrackingID = $_GET['tracking_id'];
    
    // Get applicant details
    $stmt = $conn->prepare("SELECT first_name, service_type, appointment_date, psk_location FROM passport_appointments WHERE tracking_id = ?");
    $stmt->bind_param("s", $successTrackingID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $successData = $result->fetch_assoc();
    }
    $stmt->close();
}

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
                
                // FIXED: Redirect with correct parameter name that payment_gateway.php expects
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

  <!-- Success Message After Payment -->
  <?php if($showSuccessMessage && isset($successData)): ?>
  <div class="container mx-auto px-6 py-4">
    <div class="bg-green-50 border-2 border-green-400 p-6 rounded-lg shadow-xl">
      <div class="text-center">
        <div class="text-6xl mb-4">✅</div>
        <h3 class="text-3xl font-bold text-green-800 mb-4">Payment Successful!</h3>
        <p class="text-green-700 text-lg mb-6">Your passport appointment has been confirmed successfully.</p>
        
        <div class="bg-white p-6 rounded-xl shadow-lg max-w-2xl mx-auto mb-6">
          <div class="grid grid-cols-2 gap-4 text-left">
            <div>
              <p class="text-sm text-gray-600 mb-1">Tracking ID:</p>
              <p class="text-2xl font-bold text-blue-600 font-mono"><?php echo htmlspecialchars($successTrackingID); ?></p>
            </div>
            <div>
              <p class="text-sm text-gray-600 mb-1">Applicant Name:</p>
              <p class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($successData['first_name']); ?></p>
            </div>
            <div>
              <p class="text-sm text-gray-600 mb-1">Service Type:</p>
              <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($successData['service_type']); ?></p>
            </div>
            <div>
              <p class="text-sm text-gray-600 mb-1">PSK Location:</p>
              <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($successData['psk_location']); ?></p>
            </div>
            <div class="col-span-2">
              <p class="text-sm text-gray-600 mb-1">Appointment Date:</p>
              <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($successData['appointment_date']); ?></p>
            </div>
          </div>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded mb-4">
          <p class="text-sm text-yellow-800">
            <strong>⚠️ IMPORTANT:</strong> Please save your tracking ID: <span class="font-mono font-bold"><?php echo htmlspecialchars($successTrackingID); ?></span>
          </p>
          <p class="text-sm text-yellow-800 mt-2">You'll need this to track your passport application status.</p>
        </div>

        <div class="flex gap-4 justify-center">
          <button onclick="copyTrackingID('<?php echo htmlspecialchars($successTrackingID); ?>')" 
                  class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-500">
            📋 Copy Tracking ID
          </button>
          <a href="#status-tracker" 
             class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-500 inline-block">
            🔍 Track Status Now
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

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
  <section id="status-tracker" class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-4xl mx-auto">
      <h3 class="text-2xl font-bold text-blue-800 mb-6 text-center">🔍 Live Application Status Tracker</h3>
      <p class="text-center text-gray-600 mb-6">Enter your tracking ID to check real-time status of your passport application</p>
      
      <form method="POST" action="passport.php#status-tracker" class="mb-6">
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
    // Copy Tracking ID Function
    function copyTrackingID(trackingID) {
      navigator.clipboard.writeText(trackingID).then(function() {
        alert('Tracking ID copied to clipboard: ' + trackingID);
      }, function(err) {
        alert('Failed to copy tracking ID');
      });
    }

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

    // Set minimum date for appointment (today)
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('appointmentDate').setAttribute('min', today);
  </script>

</body>
</html>