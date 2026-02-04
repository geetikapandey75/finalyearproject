<?php
session_start();

// Get payment data from POST or SESSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['payment_data'] = [
        'service_type' => $_POST['service_type'] ?? '',
        'service_record_id' => $_POST['service_record_id'] ?? '',
        'amount' => $_POST['amount'] ?? 0,
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'tracking_id' => 'MS' . str_pad($_POST['service_record_id'] ?? '', 10, '0', STR_PAD_LEFT)
    ];
}

// Check if payment data exists in session
if (!isset($_SESSION['payment_data'])) {
  // After successful payment
header("Location: payment_success.php?tracking_id=" . $tracking_id . "&service_type=" . $service_type);
    exit();
}

$paymentData = $_SESSION['payment_data'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Gateway | Legal Assist</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body class="bg-blue-50 font-sans text-gray-900">

  <!-- Navigation Bar -->
  <header class="bg-blue-900 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold">Legal Assist - Payment</h1>
    </div>
  </header>

  <!-- Payment Section -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl mx-auto">
      <h2 class="text-3xl font-bold text-blue-800 mb-6 text-center">Complete Your Payment</h2>
      
      <!-- Payment Details -->
      <div class="bg-blue-50 rounded-lg p-6 mb-6">
        <h3 class="text-xl font-semibold text-blue-700 mb-4">Booking Summary</h3>
        <div class="space-y-2 text-gray-700">
          <div class="flex justify-between">
            <span>Service:</span>
            <span class="font-semibold">MeeSeva Service</span>
          </div>
          <div class="flex justify-between">
            <span>Applicant Name:</span>
            <span class="font-semibold"><?php echo htmlspecialchars($paymentData['name']); ?></span>
          </div>
          <div class="flex justify-between">
            <span>Email:</span>
            <span class="font-semibold"><?php echo htmlspecialchars($paymentData['email']); ?></span>
          </div>
          <div class="flex justify-between">
            <span>Application ID:</span>
            <span class="font-semibold text-green-600"><?php echo htmlspecialchars($paymentData['tracking_id']); ?></span>
          </div>
          <div class="border-t-2 border-blue-200 pt-2 mt-2 flex justify-between text-xl">
            <span class="font-bold">Total Amount:</span>
            <span class="font-bold text-green-600">₹<?php echo number_format($paymentData['amount'], 2); ?></span>
          </div>
        </div>
      </div>

      <!-- Payment Button -->
      <button 
        id="rzp-button" 
        class="w-full bg-green-600 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-green-500 transition shadow-lg">
        💳 Pay ₹<?php echo number_format($paymentData['amount'], 2); ?> Now
      </button>

      <p class="text-center text-gray-600 text-sm mt-4">
        🔒 Secure payment powered by Razorpay
      </p>

      <div class="mt-6 text-center">
        <a href="meeseva.html" class="text-blue-600 hover:underline">← Back to MeeSeva Services</a>
      </div>
    </div>
  </section>

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 text-center">
      <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mx-auto mb-4"></div>
      <p class="text-gray-700 font-semibold">Processing Payment...</p>
    </div>
  </div>

  <script>
    // Create Razorpay order on page load
    document.addEventListener('DOMContentLoaded', function() {
      const rzpButton = document.getElementById('rzp-button');
      let orderData = null;

      // Fetch order from server
      fetch('api/create_order.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          service_type: '<?php echo $paymentData['service_type']; ?>',
          service_record_id: '<?php echo $paymentData['service_record_id']; ?>',
          amount: '<?php echo $paymentData['amount']; ?>'
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          orderData = data;
          
          // Setup Razorpay button
          rzpButton.onclick = function(e) {
            e.preventDefault();
            
            const options = {
              key: orderData.key,
              amount: orderData.amount * 100,
              currency: orderData.currency,
              name: orderData.name,
              description: orderData.description,
              order_id: orderData.order_id,
              prefill: {
                name: '<?php echo htmlspecialchars($paymentData['name']); ?>',
                email: '<?php echo htmlspecialchars($paymentData['email']); ?>',
                contact: '<?php echo htmlspecialchars($paymentData['phone']); ?>'
              },
              theme: {
                color: '#1e40af'
              },
              handler: function(response) {
                // Show loading
                document.getElementById('loading-overlay').classList.remove('hidden');
                
                // Verify payment
                fetch('api/verify_payment.php', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                  },
                  body: new URLSearchParams({
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature: response.razorpay_signature
                  })
                })
                .then(res => res.json())
                .then(result => {
                  document.getElementById('loading-overlay').classList.add('hidden');
                  
                  if (result.success) {
                    // Redirect to success page
                    window.location.href = 'payment_success.php?tracking_id=<?php echo $paymentData['tracking_id']; ?>';
                  } else {
                    alert('Payment verification failed: ' + result.error);
                  }
                })
                .catch(error => {
                  document.getElementById('loading-overlay').classList.add('hidden');
                  alert('Error verifying payment: ' + error);
                });
              },
              modal: {
                ondismiss: function() {
                  alert('Payment cancelled. You can try again.');
                }
              }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
          };
          
        } else {
          alert('Failed to create payment order: ' + data.error);
          rzpButton.disabled = true;
          rzpButton.textContent = 'Payment Error - Contact Support';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to initialize payment: ' + error);
        rzpButton.disabled = true;
      });
    });
  </script>

</body>
</html>