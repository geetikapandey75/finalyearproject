<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost","root","", "legal_assist");
if ($conn->connect_error) {
    die("DB Connection Failed");
}

$payment_id = $_GET['payment_id'] ?? '';
$order_id   = $_GET['order_id'] ?? '';

if ($payment_id === '' || $order_id === '') {
    die("Invalid payment details");
}

$stmt = $conn->prepare(
    "UPDATE lawyer_services 
     SET payment_id = ?, payment_status = 'PAID'
     WHERE razorpay_order_id = ?"
);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ss", $payment_id, $order_id);
$stmt->execute();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.08), transparent 70%);
            top: -250px;
            left: -250px;
            animation: float 20s infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.08), transparent 70%);
            bottom: -200px;
            right: -200px;
            animation: float 15s infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -50px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }
        
        .receipt {
            background: #ffffff;
            padding: 50px 40px;
            width: 100%;
            max-width: 480px;
            border-radius: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08),
                        0 0 1px rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .glow-line {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 3px;
            background: linear-gradient(90deg, 
                transparent,
                #10b981,
                #34d399,
                #10b981,
                transparent
            );
            border-radius: 0 0 3px 3px;
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #10b981, #34d399);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: scaleIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.25),
                        inset 0 -3px 15px rgba(0, 0, 0, 0.1);
        }
        
        .success-icon::before {
            content: '';
            position: absolute;
            width: 120%;
            height: 120%;
            border-radius: 50%;
            border: 2px solid rgba(16, 185, 129, 0.2);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0;
            }
        }
        
        .success-icon svg {
            width: 55px;
            height: 55px;
            stroke: white;
            stroke-width: 4;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
            animation: checkmark 0.8s ease-out 0.4s both;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0) rotate(-180deg);
                opacity: 0;
            }
            to {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }
        
        @keyframes checkmark {
            0% {
                stroke-dasharray: 0, 100;
            }
            100% {
                stroke-dasharray: 100, 0;
            }
        }
        
        h2 {
            color: #1a1a1a;
            text-align: center;
            font-size: 32px;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 15px;
            margin-bottom: 40px;
            font-weight: 400;
        }
        
        .status-badge {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .badge {
            display: inline-block;
            padding: 8px 20px;
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 1px solid #6ee7b7;
            color: #065f46;
            border-radius: 24px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.15);
        }
        
        .details-container {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            margin-bottom: 30px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .detail-row:first-child {
            padding-top: 0;
        }
        
        .detail-label {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            color: #1a1a1a;
            font-size: 15px;
            font-weight: 600;
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .btn {
            display: block;
            text-align: center;
            padding: 16px;
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            text-decoration: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.25);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.35);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        .footer-note {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 25px;
            font-weight: 300;
        }
        
        .decorative-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
        }
        
        .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #d1d5db;
        }
        
        .dot:nth-child(2) {
            background: #10b981;
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="glow-line"></div>
    
    <div class="success-icon">
        <svg viewBox="0 0 52 52">
            <polyline points="14 27 22 35 38 19"/>
        </svg>
    </div>
    
    <h2>Payment Successful</h2>
    <p class="subtitle">Your transaction has been completed</p>
    
    <div class="status-badge">
        <span class="badge">Verified</span>
    </div>
    
    <div class="details-container">
        <div class="detail-row">
            <span class="detail-label">Payment ID</span>
            <span class="detail-value"><?php echo htmlspecialchars($payment_id); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Date & Time</span>
            <span class="detail-value" id="datetime"></span>
        </div>
    </div>
    
    <a href="home_page.php" class="btn">Return to Home</a>
    
    <div class="decorative-dots">
        <span class="dot"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>
    
    <p class="footer-note">Receipt generated securely</p>
</div>

<script>
    function updateTime() {
        const now = new Date();
        const options = { 
            day: '2-digit', 
            month: 'short', 
            year: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        };
        const formatted = now.toLocaleString('en-US', options).replace(',', '');
        document.getElementById('datetime').textContent = formatted;
    }
    
    updateTime();
    setInterval(updateTime, 1000);
</script>
</body>
</html>