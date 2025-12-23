<?php
// ================= DB CONNECTION =================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "legal_assist";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed");
}

// ===============================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ================= APPLY FOOD LICENCE =================
    if ($_POST["action"] === "apply") {

        $application_id = "FSSAI" . date("Y") . rand(10000,99999);

        $name     = $_POST["full_name"];
        $business = $_POST["business_name"];
        $email    = $_POST["email"];
        $phone    = $_POST["phone_number"];

        $stmt = $conn->prepare("
            INSERT INTO food_licence_applications
            (application_id, full_name, business_name, email, phone, status, applied_on)
            VALUES (?, ?, ?, ?, ?, 'Under Review', NOW())
        ");

        $stmt->bind_param(
            "sssss",
            $application_id,
            $name,
            $business,
            $email,
            $phone
        );

        $stmt->execute();

        echo "
        <h2>✅ Application Submitted Successfully</h2>
        <p><strong>Your Application ID:</strong> $application_id</p>
        <p>Please save this ID to track your application.</p>
        ";
        exit;
    }

    // ================= TRACK STATUS =================
    if ($_POST["action"] === "status") {

        $appId = $_POST["application_id"];
        $phone = $_POST["phone"];

        $stmt = $conn->prepare("
            SELECT application_id, status, applied_on
            FROM food_licence_applications
            WHERE application_id = ? AND phone = ?
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
            echo "<p style='color:red;'>❌ Application not found. Please check details.</p>";
        }
        exit;
    }
}
?>
