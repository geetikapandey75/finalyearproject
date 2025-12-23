<?php
/**
 * Test Email Script
 * Use this to verify PHPMailer is working correctly
 */

// Include PHPMailer files using absolute path
require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

// Include your email configuration
require __DIR__ . '/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    //━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // Server Settings
    //━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    $mail->isSMTP();                                      // Send using SMTP
    $mail->Host       = SMTP_HOST;                        // SMTP server
    $mail->SMTPAuth   = true;                             // Enable authentication
    $mail->Username   = SMTP_USERNAME;                    // SMTP username
    $mail->Password   = SMTP_PASSWORD;                    // SMTP password
    $mail->SMTPSecure = SMTP_ENCRYPTION;                  // Enable TLS encryption
    $mail->Port       = SMTP_PORT;                        // TCP port
    
    // Enable verbose debug output (helpful for testing)
    $mail->SMTPDebug  = 2; // 0=off, 1=client, 2=client+server, 3=detailed
    $mail->Debugoutput = 'html';

    //━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // Email Details
    //━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    // Sender
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    
    // Recipient - ⚠️ CHANGE THIS TO YOUR TEST EMAIL
    $mail->addAddress('test@example.com', 'Test User');
    
    // Reply-to (optional)
    $mail->addReplyTo(SMTP_REPLY_TO, 'No Reply');
    
    //━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // Content
    //━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from MeeSeva System';
    
    $mail->Body = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1e3a8a; color: white; padding: 20px; text-align: center; }
            .content { background: #f9fafb; padding: 20px; margin-top: 20px; }
            .success { color: #10b981; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✅ PHPMailer Test Successful!</h1>
            </div>
            <div class="content">
                <h2>Congratulations!</h2>
                <p class="success">Your PHPMailer setup is working correctly.</p>
                <p>This test email confirms that:</p>
                <ul>
                    <li>✓ PHPMailer is installed properly</li>
                    <li>✓ SMTP credentials are correct</li>
                    <li>✓ Email delivery is functional</li>
                    <li>✓ You can now send certificate emails</li>
                </ul>
                <hr>
                <p><strong>Sent from:</strong> MeeSeva Certificate System</p>
                <p><strong>Date:</strong> ' . date('d-M-Y H:i:s') . '</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Plain text version (for email clients that don't support HTML)
    $mail->AltBody = 'PHPMailer test successful! Your email system is working correctly.';
    
    //━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // Send Email
    //━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    $mail->send();
    
    echo '<div style="background:#10b981; color:white; padding:20px; margin:20px; border-radius:10px;">';
    echo '<h2>✅ SUCCESS!</h2>';
    echo '<p>Test email has been sent successfully!</p>';
    echo '<p>Check your inbox (and spam folder) for the test email.</p>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="background:#ef4444; color:white; padding:20px; margin:20px; border-radius:10px;">';
    echo '<h2>❌ ERROR!</h2>';
    echo '<p>Email could not be sent.</p>';
    echo '<p><strong>Error:</strong> ' . $mail->ErrorInfo . '</p>';
    echo '<hr style="border-color:rgba(255,255,255,0.3);">';
    echo '<h3>Troubleshooting Steps:</h3>';
    echo '<ul>';
    echo '<li>Check if you enabled 2-Step Verification in Gmail</li>';
    echo '<li>Verify you generated an App Password (not your regular Gmail password)</li>';
    echo '<li>Make sure the App Password is copied correctly (no spaces)</li>';
    echo '<li>Check if your email credentials in email_config.php are correct</li>';
    echo '<li>Ensure your internet connection is working</li>';
    echo '</ul>';
    echo '</div>';
}
?>