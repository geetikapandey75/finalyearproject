<?php
// ================= DATABASE CONFIG =================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "legal_assist";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed");
}

// ===================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ================= APPLY FORM =================
    if ($_POST["action"] === "apply") {

        // Generate Application ID
        $appId = "BL" . date("Y") . rand(10000, 99999);

        $name     = $_POST["full_name"];
        $email    = $_POST["email"];
        $phone    = $_POST["phone"];
        $bname    = $_POST["business_name"];
        $btype    = $_POST["business_type"];
        $bsize    = $_POST["business_size"];
        $address  = $_POST["business_address"];
        $desc     = $_POST["description"];
        $gst      = $_POST["gst"] ?? "";
        $pan      = $_POST["pan"];

        // Save uploaded documents
        $uploadDir = "uploads/$appId/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES["documents"]["name"] ?? [] as $key => $nameFile) {
            move_uploaded_file(
                $_FILES["documents"]["tmp_name"][$key],
                $uploadDir . basename($nameFile)
            );
        }

        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO business_applications 
            (application_id, name, email, phone, business_name, business_type, business_size,
             address, description, gst, pan, status, submitted_on)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Under Review', NOW())
        ");

        $stmt->bind_param(
            "sssssssssss",
            $appId, $name, $email, $phone, $bname, $btype,
            $bsize, $address, $desc, $gst, $pan
        );

        $stmt->execute();

        echo "
        <h2>✅ Application Submitted Successfully</h2>
        <p><strong>Your Application ID:</strong> $appId</p>
        <p>Please save this ID to track your application.</p>
        ";
        exit;
    }

    // ================= STATUS CHECK =================
    if ($_POST["action"] === "status") {

        $appId  = $_POST["appId"];
        $mobile = $_POST["mobile"];

        $stmt = $conn->prepare("
            SELECT application_id, status, submitted_on 
            FROM business_applications 
            WHERE application_id = ? AND phone = ?
        ");

        $stmt->bind_param("ss", $appId, $mobile);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            echo "
            <h2>📄 Application Status</h2>
            <p><strong>ID:</strong> {$row['application_id']}</p>
            <p><strong>Status:</strong> {$row['status']}</p>
            <p><strong>Submitted On:</strong> {$row['submitted_on']}</p>
            ";
        } else {
            echo "<p style='color:red;'>❌ No application found. Please check details.</p>";
        }
        exit;
    }
}
?>
