<?php
// ================= DATABASE CONNECTION =================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "legal_assist";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed");
}

// ======================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ========== APPLY FOR TRADE LICENCE ==========
    if ($_POST["action"] === "apply") {

        $application_id = "TL" . date("Y") . rand(10000,99999);

        $name     = $_POST["full_name"];
        $business = $_POST["business_name"];
        $address  = $_POST["business_address"];
        $category = $_POST["business_category"];
        $phone    = $_POST["phone_number"];

        $stmt = $conn->prepare("
            INSERT INTO trade_licence_applications
            (application_id, full_name, business_name, business_address,
             business_category, phone_number, status, applied_on)
            VALUES (?, ?, ?, ?, ?, ?, 'Under Review', NOW())
        ");

        $stmt->bind_param(
            "ssssss",
            $application_id,
            $name,
            $business,
            $address,
            $category,
            $phone
        );

        $stmt->execute();

        echo "
        <h2>✅ Trade Licence Application Submitted</h2>
        <p><strong>Application ID:</strong> $application_id</p>
        <p>Save this ID to track your application.</p>
        ";
        exit;
    }

    // ========== TRACK APPLICATION STATUS ==========
    if ($_POST["action"] === "status") {

        $appId = $_POST["application_id"];
        $phone = $_POST["phone_number"];

        $stmt = $conn->prepare("
            SELECT application_id, status, applied_on
            FROM trade_licence_applications
            WHERE application_id = ? AND phone_number = ?
        ");

        $stmt->bind_param("ss", $appId, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            echo "
            <h2>📄 Application Status</h2>
            <p><strong>Application ID:</strong> {$row['application_id']}</p>
            <p><strong>Status:</strong> {$row['status']}</p>
            <p><strong>Applied On:</strong> {$row['applied_on']}</p>
            ";
        } else {
            echo "<p style='color:red;'>❌ No application found with these details.</p>";
        }
        exit;
    }
}
?>


