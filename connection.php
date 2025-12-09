<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Testing Database Connection</h2>";

// Database credentials
$servername = "localhost:3306";
$username = "root";
$password = "";
$dbname = "project";

echo "<h3>Step 1: Testing MySQL Connection...</h3>";

// Test connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo "❌ <strong>Connection Failed!</strong><br>";
    echo "Error: " . $conn->connect_error . "<br>";
    die();
} else {
    echo "✅ <strong>Connected to MySQL successfully!</strong><br>";
    echo "Database: <strong>$dbname</strong><br><br>";
}

echo "<h3>Step 2: Checking 'users' table...</h3>";

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");

if ($table_check->num_rows > 0) {
    echo "✅ <strong>Table 'users' exists!</strong><br><br>";
} else {
    echo "❌ <strong>Table 'users' does NOT exist!</strong><br>";
    echo "Please create the table first.<br>";
    $conn->close();
    die();
}

echo "<h3>Step 3: Checking table structure...</h3>";

// Get table structure
$structure = $conn->query("DESCRIBE users");

if ($structure) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ Could not retrieve table structure<br>";
}

echo "<h3>Step 4: Counting existing users...</h3>";

// Count users
$count_result = $conn->query("SELECT COUNT(*) as total FROM users");
$count = $count_result->fetch_assoc();

echo "📊 Total users in database: <strong>" . $count['total'] . "</strong><br><br>";

if ($count['total'] > 0) {
    echo "<h3>Step 5: Sample user data (first 3 users)...</h3>";
    
    $users = $conn->query("SELECT user_id, full_name, email, role, created_at FROM users LIMIT 3");
    
    if ($users->num_rows > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Full Name</th><th>Email</th><th>Role</th><th>Created At</th>";
        echo "</tr>";
        
        while ($user = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";
            echo "<td>" . $user['full_name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
}

echo "<h3>✅ All tests completed!</h3>";
echo "<p><strong>Result:</strong> Your PHP and Database connection is working perfectly!</p>";
echo "<p>Now you can test your signup form at: <a href='home.html'>home.html</a></p>";

$conn->close();
?>