<?php
/**
 * Quick Test Script - Run this to verify everything is connected
 * Access: http://localhost/your-folder/test_connection.php
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Connection Test - MeeSeva</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .test { padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #ccc; }
        .success { background: #d1fae5; border-color: #10b981; color: #065f46; }
        .error { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
        .info { background: #dbeafe; border-color: #3b82f6; color: #1e40af; }
        pre { background: #f3f4f6; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 MeeSeva System Test</h1>
        <p>This page will test your database connection and configuration.</p>
        <hr>

        <?php
        // Test 1: Database Connection
        echo "<h2>Test 1: Database Connection</h2>";
        $conn = @new mysqli("localhost", "root", "", "legal_assist");
        
        if ($conn->connect_error) {
            echo "<div class='test error'>";
            echo "<strong>❌ FAILED:</strong> " . $conn->connect_error;
            echo "<br><br><strong>Solution:</strong> Make sure MySQL is running in XAMPP/WAMP";
            echo "</div>";
            die();
        } else {
            echo "<div class='test success'>";
            echo "<strong>✅ SUCCESS:</strong> Connected to database 'legal_assist'";
            echo "</div>";
        }

        // Test 2: Check Tables
        echo "<h2>Test 2: Database Tables</h2>";
        $tables = ['meeseva_applications', 'meeseva_documents'];
        $allTablesExist = true;
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "<div class='test success'>✅ Table '$table' exists</div>";
            } else {
                echo "<div class='test error'>❌ Table '$table' NOT FOUND</div>";
                $allTablesExist = false;
            }
        }

        if (!$allTablesExist) {
            echo "<div class='test info'>";
            echo "<strong>📝 Action Required:</strong> Run database_setup.sql in phpMyAdmin";
            echo "</div>";
        }

        // Test 3: Insert Test Record
        echo "<h2>Test 3: Test Data Insertion</h2>";
        
        $test_app_number = 'MSTEST' . time();
        $sql = "INSERT INTO meeseva_applications 
                (application_number, full_name, email, contact_number, service, center_name, appointment_date, appointment_time, status) 
                VALUES 
                ('$test_app_number', 'Test User', 'test@example.com', '9999999999', 'Test Service', 'Test Center', '2025-12-25', '10:00 AM', 'Test')";
        
        if ($conn->query($sql)) {
            echo "<div class='test success'>";
            echo "✅ Successfully inserted test record<br>";
            echo "<strong>Application Number:</strong> $test_app_number";
            echo "</div>";
            
            // Clean up test record
            $conn->query("DELETE FROM meeseva_applications WHERE application_number='$test_app_number'");
            echo "<div class='test info'>🧹 Test record cleaned up</div>";
        } else {
            echo "<div class='test error'>";
            echo "❌ Failed to insert: " . $conn->error;
            echo "</div>";
        }

        // Test 4: Check Recent Applications
        echo "<h2>Test 4: Recent Applications</h2>";
        $result = $conn->query("SELECT * FROM meeseva_applications ORDER BY created_at DESC LIMIT 5");
        
        if ($result->num_rows > 0) {
            echo "<div class='test success'>";
            echo "✅ Found {$result->num_rows} application(s) in database:<br><br>";
            echo "<table style='width:100%; border-collapse: collapse;'>";
            echo "<tr style='background:#f3f4f6; font-weight:bold;'>";
            echo "<td style='padding:8px; border:1px solid #ddd;'>App Number</td>";
            echo "<td style='padding:8px; border:1px solid #ddd;'>Name</td>";
            echo "<td style='padding:8px; border:1px solid #ddd;'>Service</td>";
            echo "<td style='padding:8px; border:1px solid #ddd;'>Status</td>";
            echo "<td style='padding:8px; border:1px solid #ddd;'>Date</td>";
            echo "</tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td style='padding:8px; border:1px solid #ddd;'>{$row['application_number']}</td>";
                echo "<td style='padding:8px; border:1px solid #ddd;'>{$row['full_name']}</td>";
                echo "<td style='padding:8px; border:1px solid #ddd;'>{$row['service']}</td>";
                echo "<td style='padding:8px; border:1px solid #ddd;'>{$row['status']}</td>";
                echo "<td style='padding:8px; border:1px solid #ddd;'>{$row['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        } else {
            echo "<div class='test info'>";
            echo "ℹ️ No applications found yet. Submit one through the form to test!";
            echo "</div>";
        }

        // Test 5: PHP Configuration
        echo "<h2>Test 5: PHP Configuration</h2>";
        
        $checks = [
            ['MySQLi Extension', extension_loaded('mysqli')],
            ['Session Support', session_status() != PHP_SESSION_DISABLED],
            ['File Uploads', ini_get('file_uploads')],
            ['Max Upload Size', ini_get('upload_max_filesize')],
            ['Max Post Size', ini_get('post_max_size')]
        ];
        
        foreach ($checks as $check) {
            if (is_bool($check[1])) {
                if ($check[1]) {
                    echo "<div class='test success'>✅ {$check[0]}: Enabled</div>";
                } else {
                    echo "<div class='test error'>❌ {$check[0]}: Disabled</div>";
                }
            } else {
                echo "<div class='test success'>✅ {$check[0]}: {$check[1]}</div>";
            }
        }

        // Test 6: File Checks
        echo "<h2>Test 6: Required Files</h2>";
        
        $files = [
            'meeseva.html' => 'Main page',
            'meeseva.php' => 'Form handler',
            'meeseva_appointment_system.php' => 'OTP system',
            'generate_appointment_certificate.php' => 'PDF generator',
            'fpdf/fpdf.php' => 'FPDF library',
            'PHPMailer/PHPMailer.php' => 'PHPMailer library'
        ];
        
        foreach ($files as $file => $desc) {
            if (file_exists($file)) {
                echo "<div class='test success'>✅ $desc ($file)</div>";
            } else {
                echo "<div class='test error'>❌ Missing: $desc ($file)</div>";
            }
        }

        $conn->close();
        ?>

        <hr>
        <h2>🎯 Next Steps</h2>
        <div class="test info">
            <ol>
                <li>If all tests passed, open <strong>meeseva.html</strong> and test the form</li>
                <li>Fill in your details and submit</li>
                <li>You should see a confirmation page with an Application Number</li>
                <li>Use that number to test the Certificate Download feature</li>
                <li>Check your email for OTP (make sure SMTP is configured)</li>
            </ol>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <button onclick="location.href='meeseva.html'">Open MeeSeva Form</button>
            <button onclick="location.href='system_check.php'">Run Full System Check</button>
            <button onclick="location.reload()">Refresh Tests</button>
        </div>
    </div>
</body>
</html>