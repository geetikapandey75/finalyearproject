<?php
$conn = new mysqli("localhost", "root", "", "projectdb");

if ($conn->connect_error) {
    die("Database connection failed");
}

$action = $_POST['action'] ?? '';

/* ===============================
   BOOK DRIVING TEST SLOT
   =============================== */
if ($action === 'book_slot') {

    $test_date = $_POST['test_date'];
    $test_time = $_POST['test_time'];
    $test_location = $_POST['test_location'];

    $sql = "INSERT INTO driving_slots (test_date, test_time, test_location)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $test_date, $test_time, $test_location);

    if ($stmt->execute()) {
        echo "✅ Slot booked successfully!";
    } else {
        echo "❌ Failed to book slot";
    }

    $stmt->close();
}


/* ===============================
   CHECK APPLICATION STATUS
   =============================== */
elseif ($action === 'check_status') {

    $application_id = $_POST['application_id'];

    $sql = "SELECT application_status FROM applications WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "📄 Application Status: <b>" . $row['application_status'] . "</b>";
    } else {
        echo "❌ Invalid Application ID";
    }

    $stmt->close();
}

else {
    echo "Invalid request";
}

$conn->close();
?>
