<?php
require __DIR__ . '/razorpay-php/Razorpay.php';

use Razorpay\Api\Api;

$keyId = "rzp_test_RuyUcsfbG8XaIT";
$keySecret = "fliKTmw84hX8mblSM1CyRQ0D";

$api = new Api($keyId, $keySecret);

$amount = $_POST['amount'] * 100; // paise

$order = $api->order->create([
    'amount' => $amount,
    'currency' => 'INR',
    'receipt' => 'receipt_' . time()
]);

$order_id = $order['id'];
?>
<!DOCTYPE html>
<html>
<head>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
<script>
var options = {
    "key": "<?php echo $keyId; ?>",
    "amount": "<?php echo $amount; ?>",
    "currency": "INR",
    "name": "Legal Assist",
    "description": "Police Service Payment",
    "order_id": "<?php echo $order_id; ?>",
    "handler": function (response){
        alert("Payment Successful: " + response.razorpay_payment_id);
    }
};
var rzp = new Razorpay(options);
rzp.open();
</script>
</body>
</html>
