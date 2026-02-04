<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Razorpay Configuration
$razorpay_key_secret = "fliKTmw84hX8mblSM1CyRQ0D"; // Your Test Key Secret

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$razorpay_order_id = $data['razorpay_order_id'] ?? '';
$razorpay_payment_id = $data['razorpay_payment_id'] ?? '';
$razorpay_signature = $data['razorpay_signature'] ?? '';
$payment_id = $data['payment_id'] ?? 0;
$service_type = $data['service_type'] ?? '';
$service_id = $data['service_id'] ?? 0;

// Verify signature
$generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $razorpay_key_secret);

if ($generated_signature === $razorpay_signature) {
    // Signature is valid - update payment record
    $stmt = $conn->prepare("
        UPDATE payments 
        SET razorpay_payment_id = ?, 
            razorpay_signature = ?, 
            payment_status = 'paid',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $razorpay_payment_id, $razorpay_signature, $payment_id);
    
    if ($stmt->execute()) {
        // Update service-specific tables
        updateServiceStatus($conn, $service_type, $service_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully',
            'payment_id' => $razorpay_payment_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update payment record'
        ]);
    }
} else {
    // Signature verification failed
    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_status = 'failed',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => false,
        'message' => 'Payment signature verification failed'
    ]);
}

$conn->close();

// Function to update service-specific status
function updateServiceStatus($conn, $service_type, $service_id) {
    switch ($service_type) {
        case 'passport':
            $stmt = $conn->prepare("UPDATE passport_appointments SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            break;
            
        case 'meeseva':
            $stmt = $conn->prepare("UPDATE meeseva_applications SET payment_status = 'paid', status = 'Payment Completed' WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            break;
            
        case 'challan':
            $stmt = $conn->prepare("UPDATE check_challan SET payment_status = 'paid', status = 'Paid', payment_date = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            break;
            
        case 'criminal_lawyer':
        case 'family_lawyer':
        case 'corporate_lawyer':
            // Update lawyer consultation booking status if you have such a table
            break;
    }
}
?> payment handler.php  bext is payment gateway intergartion.php  this is payment success.php <?php
session_start();

$trackingID = $_GET['tracking_id'] ?? '';

// Clear payment session data
unset($_SESSION['payment_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Successful | Legal Assist</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 font-sans text-gray-900">

  <!-- Navigation Bar -->
  <header class="bg-blue-900 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold">Legal Assist</h1>
    </div>
  </header>

  <!-- Success Section -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl mx-auto text-center">
      
      <!-- Success Icon -->
      <div class="mb-6">
        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto">
          <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
      </div>

      <h2 class="text-3xl font-bold text-green-600 mb-4">Payment Successful! ✅</h2>
      <p class="text-gray-700 text-lg mb-6">Your passport appointment has been confirmed.</p>

      <!-- Tracking Details -->
      <div class="bg-blue-50 rounded-lg p-6 mb-6">
        <p class="text-gray-600 mb-2">Your Tracking ID:</p>
        <p class="text-2xl font-bold text-blue-800 mb-4"><?php echo htmlspecialchars($trackingID); ?></p>
        <p class="text-sm text-gray-600">Please save this tracking ID to check your application status.</p>
      </div>

      <!-- Next Steps -->
      <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-left">
        <h3 class="font-bold text-yellow-800 mb-2">📋 Next Steps:</h3>
        <ul class="list-disc list-inside space-y-1 text-gray-700 text-sm">
          <li>You will receive a confirmation email with appointment details</li>
          <li>Visit the PSK on your scheduled date and time</li>
          <li>Bring all original documents for verification</li>
          <li>Track your application status using the tracking ID above</li>
        </ul>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="passport.php" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-500 transition">
          🔍 Track Application Status
        </a>
        <a href="home_page.php" class="bg-gray-200 text-gray-800 px-8 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
          🏠 Back to Home
        </a>
      </div>

      <!-- Contact Info -->
      <div class="mt-8 pt-6 border-t border-gray-200">
        <p class="text-sm text-gray-600">
          Need help? Contact us at <a href="mailto:support@legalassist.com" class="text-blue-600 hover:underline">support@legalassist.com</a>
        </p>
      </div>

    </div>
  </section>

  <footer class="bg-blue-900 text-white text-center py-4 mt-12">
    © 2025 Legal Assist | Secure Payment Gateway
  </footer>

</body>
</html>