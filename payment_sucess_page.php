<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$payment_id = (int)($_GET['payment_id'] ?? 0);

// Validate payment_id
if ($payment_id <= 0) {
    die("Invalid payment ID");
}

// Fetch payment details
$stmt = $conn->prepare("
    SELECT p.*, 
    CASE 
        WHEN p.service_type = 'passport' THEN pa.tracking_id
        WHEN p.service_type = 'meeseva' THEN ma.application_number
        WHEN p.service_type = 'challan' THEN cc.challan_no
        ELSE NULL
    END as reference_number
    FROM payments p
    LEFT JOIN passport_appointments pa ON p.service_type = 'passport' AND p.service_record_id = pa.id
    LEFT JOIN meeseva_applications ma ON p.service_type = 'meeseva' AND p.service_record_id = ma.id
    LEFT JOIN check_challan cc ON p.service_type = 'challan' AND p.service_record_id = cc.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo '<div class="container mx-auto px-6 py-12 text-center">
        <div class="bg-red-100 border-2 border-red-400 rounded-lg p-8 max-w-md mx-auto">
            <div class="text-6xl mb-4">❌</div>
            <h2 class="text-2xl font-bold text-red-800 mb-2">Payment Not Found</h2>
            <p class="text-red-600 mb-4">The payment record could not be found.</p>
            <a href="home_page.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                Return to Home
            </a>
        </div>
    </div>';
    exit();
}

// Check if payment was actually successful
if ($payment['payment_status'] !== 'paid') {
    echo '<div class="container mx-auto px-6 py-12 text-center">
        <div class="bg-orange-100 border-2 border-orange-400 rounded-lg p-8 max-w-md mx-auto">
            <div class="text-6xl mb-4">⚠️</div>
            <h2 class="text-2xl font-bold text-orange-800 mb-2">Payment Not Completed</h2>
            <p class="text-orange-600 mb-4">This payment has not been completed yet. Status: ' . htmlspecialchars($payment['payment_status']) . '</p>
            <a href="home_page.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                Return to Home
            </a>
        </div>
    </div>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Legal Assist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">

    <!-- Header -->
    <header class="bg-blue-900 text-white p-4 shadow-lg">
        <div class="container mx-auto">
            <h1 class="text-2xl font-bold">Legal Assist</h1>
        </div>
    </header>

    <!-- Success Message -->
    <div class="container mx-auto px-6 py-12">
        <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- Success Header -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 p-8 text-white text-center">
                <div class="text-7xl mb-4 animate-bounce">✅</div>
                <h2 class="text-4xl font-bold mb-2">Payment Successful!</h2>
                <p class="text-green-100 text-lg">Your transaction has been completed successfully</p>
            </div>

            <!-- Payment Details -->
            <div class="p-8">
                <!-- Transaction Info -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 mb-6">
                    <h3 class="text-2xl font-bold text-blue-900 mb-4 text-center">Transaction Details</h3>
                    
                    <div class="space-y-4">
                        <?php if (!empty($payment['razorpay_payment_id'])): ?>
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Payment ID:</span>
                            <span class="text-gray-900 font-mono text-sm"><?php echo htmlspecialchars($payment['razorpay_payment_id']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Order ID:</span>
                            <span class="text-gray-900 font-mono"><?php echo htmlspecialchars($payment['razorpay_order_id']); ?></span>
                        </div>
                        
                        <?php if (!empty($payment['reference_number'])): ?>
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">
                                <?php 
                                    echo $payment['service_type'] == 'passport' ? 'Tracking ID:' : 
                                         ($payment['service_type'] == 'meeseva' ? 'Application No:' : 'Challan No:');
                                ?>
                            </span>
                            <span class="text-blue-600 font-mono font-bold"><?php echo htmlspecialchars($payment['reference_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Service:</span>
                            <span class="text-gray-900 font-bold"><?php echo ucfirst(str_replace('_', ' ', $payment['service_type'])); ?></span>
                        </div>
                        
                        <div class="flex justify-between border-b pb-3">
                            <span class="text-gray-600 font-semibold">Amount Paid:</span>
                            <span class="text-green-600 font-bold text-xl">₹<?php echo number_format($payment['amount'], 2); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600 font-semibold">Payment Date:</span>
                            <span class="text-gray-900"><?php echo date('d M Y, h:i A', strtotime($payment['updated_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <button onclick="downloadReceipt()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        📥 Download Receipt
                    </button>
                    <button onclick="window.print()" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition">
                        🖨️ Print Receipt
                    </button>
                </div>

                <!-- Important Notice -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded mb-6">
                    <p class="text-sm text-yellow-800">
                        <strong>📧 Important:</strong> A payment confirmation email has been sent to your registered email address. 
                        Please save this receipt for your records.
                    </p>
                </div>

                <!-- Next Steps -->
                <div class="bg-blue-50 rounded-xl p-6 mb-6">
                    <h4 class="font-bold text-blue-900 mb-3 text-lg">🎯 What's Next?</h4>
                    <ul class="space-y-2 text-gray-700">
                        <?php if ($payment['service_type'] == 'passport'): ?>
                            <li>✓ Your appointment has been confirmed</li>
                            <li>✓ Visit the PSK center on your scheduled date</li>
                            <li>✓ Bring original documents and this receipt</li>
                            <li>✓ Track your application status using the tracking ID</li>
                        <?php elseif ($payment['service_type'] == 'meeseva'): ?>
                            <li>✓ Your MeeSeva application has been submitted</li>
                            <li>✓ Visit the center on your appointment date</li>
                            <li>✓ Bring original documents and this receipt</li>
                            <li>✓ Check your application status anytime</li>
                        <?php elseif ($payment['service_type'] == 'challan'): ?>
                            <li>✓ Your challan payment has been processed</li>
                            <li>✓ The payment will be updated in the system within 24 hours</li>
                            <li>✓ Keep this receipt as proof of payment</li>
                            <li>✓ You can verify payment status after 24 hours</li>
                        <?php else: ?>
                            <li>✓ Your consultation has been booked</li>
                            <li>✓ Our team will contact you shortly</li>
                            <li>✓ Keep your payment receipt handy</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Return Button -->
                <div class="text-center">
                    <a href="home_page.php" class="inline-block bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-10 py-4 rounded-xl font-bold text-lg hover:from-blue-700 hover:to-indigo-700 transition transform hover:scale-105">
                        🏠 Return to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-blue-900 text-white text-center py-6 mt-12">
        <p>© 2025 Legal Assist | Secure Payment Gateway</p>
    </footer>

    <script>
        function downloadReceipt() {
            window.print();
        }

        // Prevent back button after successful payment
        if (window.history && window.history.pushState) {
            window.history.pushState('forward', null, window.location.href);
            window.onpopstate = function() {
                window.history.pushState('forward', null, window.location.href);
                alert('Please use the "Return to Home" button to navigate.');
            };
        }

        // Show confetti animation (optional)
        setTimeout(() => {
            console.log('Payment successful! 🎉');
        }, 500);
    </script>
    
    <!-- Print-specific styles -->
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .max-w-3xl, .max-w-3xl * {
                visibility: visible;
            }
            .max-w-3xl {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            header, footer, button, .no-print {
                display: none !important;
            }
            @page {
                margin: 1cm;
            }
        }
    </style>
</body>
</html>

<?php $conn->close(); ?>