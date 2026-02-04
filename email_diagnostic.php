<?php
    use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;


error_reporting(E_ALL);
ini_set('display_errors', 1);

// Step 1: Detect PHPMailer path
$possible_paths = [
    __DIR__ . '/PHPMailer-master/src',
    __DIR__ . '/PHPMailer/src',
    __DIR__ . '/vendor/phpmailer/phpmailer/src',
    'PHPMailer/src'
];

$found_path = null;
foreach ($possible_paths as $path) {
    $full_path = $path . '/PHPMailer.php';
    if (file_exists($full_path)) {
        $found_path = $path;
        break;
    }
}

// Step 2: Load PHPMailer if found
if ($found_path) {
    require_once $found_path . '/Exception.php';
    require_once $found_path . '/PHPMailer.php';
    require_once $found_path . '/SMTP.php';
    
    // Use statements MUST be at top level (outside any blocks)
}

// Now start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email System Diagnostic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        h1 { color: #333; }
        h2 { 
            color: #007bff; 
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 15px 0;
        }
        .error-box {
            background: #ffe7e7;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 15px 0;
        }
        .success-box {
            background: #e7ffe7;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        table td:first-child {
            font-weight: bold;
            width: 150px;
        }
    </style>
</head>
<body>

<h1>📧 Email System Diagnostic Tool</h1>

<!-- Test 1: PHPMailer Detection -->
<div class="section">
    <h2>Test 1: PHPMailer Detection</h2>
    <?php
    if ($found_path) {
        echo "<div class='success-box'>";
        echo "✅ <strong>PHPMailer found!</strong><br>";
        echo "Location: <code>$found_path</code>";
        echo "</div>";
        
        echo "<table>";
        echo "<tr><td>Exception.php</td><td>" . (file_exists($found_path . '/Exception.php') ? '✅ Found' : '❌ Missing') . "</td></tr>";
        echo "<tr><td>PHPMailer.php</td><td>" . (file_exists($found_path . '/PHPMailer.php') ? '✅ Found' : '❌ Missing') . "</td></tr>";
        echo "<tr><td>SMTP.php</td><td>" . (file_exists($found_path . '/SMTP.php') ? '✅ Found' : '❌ Missing') . "</td></tr>";
        echo "</table>";
    } else {
        echo "<div class='error-box'>";
        echo "❌ <strong>PHPMailer NOT found!</strong><br><br>";
        echo "Searched in:<br>";
        foreach ($possible_paths as $path) {
            echo "• <code>$path</code><br>";
        }
        echo "<br><strong>Solution:</strong><br>";
        echo "1. Download PHPMailer: <a href='https://github.com/PHPMailer/PHPMailer/archive/master.zip' target='_blank'>Download ZIP</a><br>";
        echo "2. Extract and upload to your project root<br>";
        echo "3. Ensure folder structure: <code>PHPMailer-master/src/PHPMailer.php</code>";
        echo "</div>";
        echo "</div></body></html>";
        exit;
    }
    ?>
</div>

<!-- Test 2: PHP Extensions -->
<div class="section">
    <h2>Test 2: Required PHP Extensions</h2>
    <?php
    $extensions = [
        'openssl' => 'Required for SSL/TLS encryption',
        'sockets' => 'Recommended for better SMTP support'
    ];
    
    echo "<table>";
    foreach ($extensions as $ext => $desc) {
        $loaded = extension_loaded($ext);
        $status = $loaded ? "<span class='success'>✅ Loaded</span>" : "<span class='error'>❌ Not Loaded</span>";
        echo "<tr><td>$ext</td><td>$status</td><td style='color: #666; font-size: 12px;'>$desc</td></tr>";
    }
    echo "</table>";
    
    if (!extension_loaded('openssl')) {
        echo "<div class='error-box'>";
        echo "<strong>⚠️ OpenSSL is required!</strong><br>";
        echo "Enable it in your php.ini file: <code>extension=openssl</code>";
        echo "</div>";
    }
    ?>
</div>

<!-- Test 3: SMTP Configuration -->
<div class="section">
    <h2>Test 3: SMTP Configuration</h2>
    <?php
    $smtp_config = [
        'Host' => 'smtp.gmail.com',
        'Port' => 587,
        'Username' => 'geetikapandey75@gmail.com',
        'Password' => 'llgcpuqaqjexounu', // FIXED - No spaces
        'Encryption' => 'STARTTLS'
    ];
    
    echo "<table>";
    echo "<tr><td>SMTP Host</td><td><code>{$smtp_config['Host']}</code></td></tr>";
    echo "<tr><td>SMTP Port</td><td><code>{$smtp_config['Port']}</code></td></tr>";
    echo "<tr><td>Encryption</td><td><code>{$smtp_config['Encryption']}</code></td></tr>";
    echo "<tr><td>Username</td><td><code>{$smtp_config['Username']}</code></td></tr>";
    echo "<tr><td>Password</td><td><code>" . str_repeat('*', strlen($smtp_config['Password'])) . "</code> (Length: " . strlen($smtp_config['Password']) . " chars)</td></tr>";
    echo "</table>";
    
    // Check password for spaces
    if (strpos($smtp_config['Password'], ' ') !== false) {
        echo "<div class='error-box'>";
        echo "❌ <strong>Password contains spaces!</strong> This will cause authentication to fail.<br>";
        echo "Remove all spaces from your App Password.";
        echo "</div>";
    } else {
        echo "<div class='success-box'>";
        echo "✅ Password format looks correct (no spaces detected)";
        echo "</div>";
    }
    ?>
    
    <div class="info-box">
        <strong>📝 How to get Gmail App Password:</strong><br>
        1. Enable 2-Step Verification: <a href="https://myaccount.google.com/security" target="_blank">Google Security</a><br>
        2. Generate App Password: <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a><br>
        3. Select "Mail" and your device<br>
        4. Copy the 16-character password WITHOUT spaces
    </div>
</div>

<!-- Test 4: Send Test Email -->
<div class="section">
    <h2>Test 4: Send Test Email</h2>
    
    <?php
    if (isset($_POST['send_test'])) {
        $test_email = filter_var($_POST['test_email'], FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            echo "<div class='error-box'>❌ Invalid email address</div>";
        } else {
            echo "<h3>Attempting to send email to: <strong>$test_email</strong></h3>";
            
            $mail = new PHPMailer(true);
            
            try {
                // Enable verbose debug output
                ob_start();
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) {
                    echo htmlspecialchars($str) . "<br>";
                };
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = $smtp_config['Host'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_config['Username'];
                $mail->Password = $smtp_config['Password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $smtp_config['Port'];
                $mail->Timeout = 30;
                
                // Recipients
                $mail->setFrom($smtp_config['Username'], 'MeeSeva Diagnostic Test');
                $mail->addAddress($test_email);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'MeeSeva Email System Test - ' . date('Y-m-d H:i:s');
                $mail->Body = '
                    <h1 style="color: #28a745;">✅ Success!</h1>
                    <p>Your MeeSeva email system is working correctly.</p>
                    <p><strong>Test sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>
                    <hr>
                    <p style="color: #666; font-size: 12px;">This is an automated test email from your MeeSeva application.</p>
                ';
                $mail->AltBody = 'Success! Your MeeSeva email system is working correctly. Test sent at: ' . date('Y-m-d H:i:s');
                
                $mail->send();
                $debug_output = ob_get_clean();
                
                echo "<div class='success-box'>";
                echo "<h3>✅ Email Sent Successfully!</h3>";
                echo "Recipient: <strong>$test_email</strong><br>";
                echo "Time: <strong>" . date('Y-m-d H:i:s') . "</strong><br>";
                echo "</div>";
                
                echo "<h4>Debug Output:</h4>";
                echo "<pre>$debug_output</pre>";
                
                echo "<div class='info-box'>";
                echo "<strong>✓ Next Steps:</strong><br>";
                echo "1. Check the recipient's inbox (and spam folder)<br>";
                echo "2. If email received, your system is ready!<br>";
                echo "3. Replace your main PHP file with the fixed version<br>";
                echo "4. Test OTP sending from your website";
                echo "</div>";
                
            } catch (Exception $e) {
                $debug_output = ob_get_clean();
                
                echo "<div class='error-box'>";
                echo "<h3>❌ Failed to Send Email</h3>";
                echo "<strong>Error:</strong> " . htmlspecialchars($mail->ErrorInfo) . "<br>";
                echo "<strong>Exception:</strong> " . htmlspecialchars($e->getMessage());
                echo "</div>";
                
                if ($debug_output) {
                    echo "<h4>Debug Output:</h4>";
                    echo "<pre>$debug_output</pre>";
                }
                
                echo "<div class='error-box'>";
                echo "<h4>🔧 Common Solutions:</h4>";
                echo "<ul>";
                echo "<li><strong>SMTP Authentication Failed:</strong> Regenerate your Gmail App Password</li>";
                echo "<li><strong>Connection Timeout:</strong> Check if your server allows outbound SMTP (port 587)</li>";
                echo "<li><strong>Certificate Errors:</strong> Update OpenSSL or try port 465 with SSL</li>";
                echo "<li><strong>Account Locked:</strong> Check Google Account security notifications</li>";
                echo "</ul>";
                
                echo "<h4>📞 Contact Hosting Provider if:</h4>";
                echo "<ul>";
                echo "<li>Error mentions 'Connection refused' or 'Could not connect'</li>";
                echo "<li>Firewall or port blocking suspected</li>";
                echo "<li>All credentials are correct but still failing</li>";
                echo "</ul>";
                echo "</div>";
            }
        }
    } else {
        // Show form
        ?>
        <form method="POST" action="">
            <label for="test_email"><strong>Enter recipient email address:</strong></label>
            <input type="email" 
                   id="test_email" 
                   name="test_email" 
                   value="<?php echo htmlspecialchars($smtp_config['Username']); ?>" 
                   required 
                   placeholder="recipient@example.com">
            <button type="submit" name="send_test">📨 Send Test Email</button>
        </form>
        
        <div class="info-box" style="margin-top: 20px;">
            <strong>💡 Tip:</strong> Send the test to yourself first to verify everything works!
        </div>
        <?php
    }
    ?>
</div>

<!-- Summary -->
<div class="section">
    <h2>📋 Summary & Next Steps</h2>
    
    <div class="info-box">
        <strong>If all tests passed:</strong>
        <ol>
            <li>✅ PHPMailer is installed correctly</li>
            <li>✅ PHP extensions are loaded</li>
            <li>✅ SMTP configuration is valid</li>
            <li>✅ Test email was delivered</li>
        </ol>
        
        <strong>You're ready to go!</strong> Replace your <code>meeseva_appointment_system.php</code> with the fixed version.
    </div>
    
    <div class="warning" style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 15px;">
        <strong>⚠️ Important Reminders:</strong><br>
        • Keep your App Password secure (never commit to Git)<br>
        • Monitor your email quota (Gmail: 500 emails/day for free accounts)<br>
        • Test thoroughly before going live<br>
        • Set up email error logging for production
    </div>
</div>

<div style="text-align: center; color: #666; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
    <p>MeeSeva Email Diagnostic Tool v1.0 | <?php echo date('Y'); ?></p>
</div>

</body>
</html>