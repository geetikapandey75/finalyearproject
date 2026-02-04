<?php
/**
 * Email Configuration File
 * Store your SMTP credentials here
 */

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls'); // or 'ssl' for port 465

// Your Gmail credentials
define('SMTP_USERNAME', 'geetikapandey75@gmail.com'); // ⚠️ CHANGE THIS
define('SMTP_PASSWORD', 'llgc puqa qjex ounu'); // ⚠️ CHANGE THIS (App Password, not regular password)

// Sender information
define('SMTP_FROM_EMAIL', 'geetikapandey75@gmail.com'); // ⚠️ CHANGE THIS
define('SMTP_FROM_NAME', 'MeeSeva Services - Legal Assist');

// Reply-to email (optional)
define('SMTP_REPLY_TO', 'noreply@legalassist.com');

/**
 * HOW TO GET APP PASSWORD:
 * 
 * 1. Go to: https://myaccount.google.com/security
 * 2. Enable "2-Step Verification" (if not enabled)
 * 3. Go to: https://myaccount.google.com/apppasswords
 * 4. Select "Mail" and "Other (Custom name)"
 * 5. Enter "MeeSeva App"
 * 6. Click "Generate"
 * 7. Copy the 16-character password (format: xxxx xxxx xxxx xxxx)
 * 8. Paste it above (remove spaces)
 */

// Debug mode (set to false in production)
define('SMTP_DEBUG', 0); // 0 = off, 1 = client, 2 = client and server

?>