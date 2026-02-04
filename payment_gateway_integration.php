<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Razorpay Configuration
$razorpay_key_id = "rzp_test_RuyUcsfbG8XaIT";
$razorpay_key_secret = "fliKTmw84hX8mblSM1CyRQ0D";

// Get payment details from URL parameters
$service_type = $_GET['service'] ?? '';
$service_id = (int)($_GET['id'] ?? 0);
$amount = (float)($_GET['amount'] ?? 0);

// Validate inputs
if (empty($service_type) || $service_id <= 0 || $amount <= 0) {
    die("Invalid payment parameters");
}

// Validate service type
$valid_services = ['passport', 'meeseva', 'challan', 'criminal_lawyer', 'family_lawyer', 'corporate_lawyer'];
if (!in_array($service_type, $valid_services)) {
    die("Invalid service type");
}

// Fetch service details from respective tables
$service_details = [];
$service_name = '';
$tracking_id = '';

switch ($service_type) {
    case 'passport':
        $stmt = $conn->prepare("SELECT * FROM passport_appointments WHERE id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $service_details = $result->fetch_assoc();
            $service_name = "Passport Appointment - " . ($service_details['service_type'] ?? '');
            $tracking_id = $service_details['tracking_id'] ?? '';
        } else {
            die("Passport appointment not found");
        }
        $stmt->close();
        break;
        
    case 'meeseva':
        $stmt = $conn->prepare("SELECT * FROM meeseva_applications WHERE id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $service_details = $result->fetch_assoc();
            $service_name = "MeeSeva - " . ($service_details['service'] ?? '');
            $tracking_id = $service_details['application_number'] ?? '';
        } else {
            die("MeeSeva application not found");
        }
        $stmt->close();
        break;
        
    case 'challan':
        $stmt = $conn->prepare("SELECT * FROM check_challan WHERE id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $service_details = $result->fetch_assoc();
            $service_name = "Challan Payment - " . ($service_details['challan_no'] ?? '');
            $tracking_id = $service_details['challan_no'] ?? '';
        } else {
            die("Challan not found");
        }
        $stmt->close();
        break;
        
    case 'criminal_lawyer':
    case 'family_lawyer':
    case 'corporate_lawyer':
        $service_name = ucfirst(str_replace('_', ' ', $service_type)) . " Consultation";
        $service_details = ['first_name' => 'User', 'email' => '', 'phone' => ''];
        $tracking_id = 'CONSULT-' . time();
        break;
        
    default:
        die("Invalid service type");
}

// Generate Razorpay Order ID
$order_id = "ORD_" . strtoupper($service_type) . "_" . time() . "_" . rand(1000, 9999);

// Insert payment record
$amount_in_paise = $amount * 100;
$stmt = $conn->prepare("
    INSERT INTO payments 
    (service_type, service_record_id, razorpay_order_id, amount, currency, payment_status) 
    VALUES (?, ?, ?, ?, 'INR', 'created')
");
$stmt->bind_param("sisd", $service_type, $service_id, $order_id, $amount);
$stmt->execute();
$payment_id = $conn->insert_id;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway - Legal Assist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

    <!-- Header -->
    <header class="bg-blue-900 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Legal Assist - Payment</h1>
            <a href="home_page.php" class="bg-white text-blue-900 px-4 py-2 rounded-lg hover:bg-gray-100 transition">
                ← Back to Home
            </a>
        </div>
    </header>

    <!-- Payment Section -->
    <div class="container mx-auto px-6 py-12">
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- Payment Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold mb-2">Secure Payment</h2>
                        <p class="text-blue-100">Complete your payment securely</p>
                    </div>
                    <div class="text-5xl">
                        🔒
                    </div>
                </div>
            </div>

            <!-- Service Details -->
            <div class="p-8">
                <div class="bg-blue-50 rounded-xl p-6 mb-6">
                    <h3 class="text-xl font-bold text-blue-900 mb-4">Payment Details</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Service:</span>
                            <span class="text-gray-900 font-bold"><?php echo htmlspecialchars($service_name); ?></span>
                        </div>
                        
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Order ID:</span>
                            <span class="text-gray-900 font-mono"><?php echo $order_id; ?></span>
                        </div>
                        
                        <?php if ($service_type == 'passport' && !empty($tracking_id)): ?>
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Tracking ID:</span>
                            <span class="text-gray-900 font-mono"><?php echo $tracking_id; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($service_type == 'meeseva' && !empty($tracking_id)): ?>
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Application No:</span>
                            <span class="text-gray-900 font-mono"><?php echo $tracking_id; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($service_type == 'challan'): ?>
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Challan No:</span>
                            <span class="text-gray-900 font-mono"><?php echo $service_details['challan_no'] ?? ''; ?></span>
                        </div>
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Vehicle No:</span>
                            <span class="text-gray-900 font-bold"><?php echo $service_details['vehicle_no'] ?? ''; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between pt-3">
                            <span class="text-gray-600 font-semibold text-lg">Amount to Pay:</span>
                            <span class="text-green-600 font-bold text-2xl">₹<?php echo number_format($amount, 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="mb-6">
                    <h4 class="font-bold text-gray-800 mb-3">💳 Available Payment Methods:</h4>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-2xl mb-1">💳</div>
                            <p class="text-xs text-gray-700">Credit/Debit Card</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-2xl mb-1">🏦</div>
                            <p class="text-xs text-gray-700">Net Banking</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-2xl mb-1">📱</div>
                            <p class="text-xs text-gray-700">UPI</p>
                        </div>
                    </div>
                </div>

                <!-- Pay Now Button -->
                <button 
                    onclick="initiatePayment()" 
                    class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white text-xl font-bold py-4 rounded-xl hover:from-green-600 hover:to-green-700 transition duration-300 shadow-lg transform hover:scale-105">
                    💰 Pay Now - ₹<?php echo number_format($amount, 2); ?>
                </button>

                <!-- Security Notice -->
                <div class="mt-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                    <p class="text-sm text-green-800">
                        <strong>🔐 Secure Payment:</strong> Your payment is processed through Razorpay's encrypted payment gateway. 
                        We do not store your card details.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-blue-900 text-white text-center py-6 mt-12">
        <p>© 2025 Legal Assist | Powered by Razorpay</p>
    </footer>

    <script>
        // Store service type and tracking ID for later use
        const serviceType = '<?php echo $service_type; ?>';
        const trackingId = '<?php echo addslashes($tracking_id); ?>';
        
        function initiatePayment() {
            var options = {
                "key": "<?php echo $razorpay_key_id; ?>",
                "amount": <?php echo $amount_in_paise; ?>,
                "currency": "INR",
                "name": "Legal Assist",
                "description": "<?php echo htmlspecialchars($service_name); ?>",
                "order_id": "<?php echo $order_id; ?>",
                "handler": function (response) {
                    verifyPayment(response);
                },
                "prefill": {
                    "name": "<?php echo $service_details['first_name'] ?? $service_details['full_name'] ?? 'User'; ?>",
                    "email": "<?php echo $service_details['email'] ?? ''; ?>",
                    "contact": "<?php echo $service_details['phone'] ?? $service_details['contact_number'] ?? ''; ?>"
                },
                "theme": {
                    "color": "#1e40af"
                },
                "modal": {
                    "ondismiss": function() {
                        alert("Payment cancelled. You can try again.");
                    }
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();
        }

        function verifyPayment(response) {
    console.log('🔐 Verifying payment signature...');
    
    const verificationData = {
        razorpay_order_id: response.razorpay_order_id,
        razorpay_payment_id: response.razorpay_payment_id,
        razorpay_signature: response.razorpay_signature,
        payment_id: PAYMENT_ID,
        service_type: SERVICE_TYPE,  // ← MUST BE HERE
        service_id: SERVICE_ID
    };
    
    fetch('verify_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(verificationData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // ✅ THIS IS THE CRITICAL LINE
            const redirectUrl = 'payment_success.php?service_type=' + encodeURIComponent(SERVICE_TYPE) + '&tracking_id=' + encodeURIComponent(TRACKING_ID);
            
            console.log('🔄 Redirecting to:', redirectUrl);
            alert('Payment Successful! Redirecting to ' + SERVICE_TYPE + ' success page.');
            
            window.location.href = redirectUrl;
        }
    });
}
    </script>
</body>
</html>

<?php $conn->close(); ?>