<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ================================
   1️⃣ DATABASE CONNECTION
================================ */
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ================================
   2️⃣ HANDLE DISPUTE SUBMISSION
================================ */
$success = $error = "";

if (isset($_POST['submit_dispute'])) {

    $full_name  = $_POST['full_name'] ?? '';
    $vehicle_no = $_POST['vehicle_no'] ?? '';
    $challan_no = $_POST['challan_no'] ?? '';
    $comments   = $_POST['comments'] ?? '';

    if (empty($full_name) || empty($vehicle_no) || empty($challan_no) || empty($comments)) {
        $error = "All fields are required.";
    } else {
        $sql = "INSERT INTO challan_disputes (full_name, vehicle_no, challan_no, comments)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $full_name, $vehicle_no, $challan_no, $comments);

        if ($stmt->execute()) {
            $success = "Query submitted successfully. Your dispute is under review.";
        } else {
            $error = "Error submitting dispute.";
        }
        $stmt->close();
    }
}

/* ================================
   3️⃣ FETCH CHALLAN DETAILS
================================ */
$vehicle_no = $_POST['vehicle_no'] ?? '';
$challan_no = $_POST['challan_no'] ?? '';

$result = null;

if (!empty($vehicle_no)) {
    if (!empty($challan_no)) {
        $sql = "SELECT * FROM check_challan WHERE vehicle_no=? AND challan_no=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $vehicle_no, $challan_no);
    } else {
        $sql = "SELECT * FROM check_challan WHERE vehicle_no=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $vehicle_no);
    }

    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Challan Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">

<div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-6 text-center">Challan Details</h2>

    <?php if (!empty($success)) { ?>
        <p class="text-green-600 text-center font-semibold mb-4"><?= $success ?></p>
    <?php } ?>

    <?php if (!empty($error)) { ?>
        <p class="text-red-600 text-center font-semibold mb-4"><?= $error ?></p>
    <?php } ?>

    <?php
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
    ?>
        <div class="border p-4 rounded mb-6">
            <p><strong>Vehicle No:</strong> <?= $row['vehicle_no'] ?></p>
            <p><strong>Challan No:</strong> <?= $row['challan_no'] ?></p>
            <p><strong>Offence:</strong> <?= $row['offence'] ?></p>
            <p><strong>Amount:</strong> ₹<?= $row['amount'] ?></p>

            <?php if ($row['status'] === 'Unpaid') { ?>
                <p><strong>Status:</strong>
                    <span class="text-orange-600 font-bold">Pending</span>
                </p>

                <form method="post" action="pay_challan.php" class="mt-2">
                    <input type="hidden" name="challan_no" value="<?= $row['challan_no'] ?>">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded">
                        Pay Now
                    </button>
                </form>
            <?php } else { ?>
                <p><strong>Status:</strong>
                    <span class="text-green-600 font-bold">Paid</span>
                </p>
            <?php } ?>

            <p><strong>Date:</strong> <?= $row['challan_date'] ?></p>

            <!-- ================================
                 4️⃣ DISPUTE FORM (INLINE)
            ================================= -->
            <hr class="my-4">
            <h3 class="font-bold text-lg mb-2">Raise a Query / Dispute</h3>

            <form method="post">
                <input type="hidden" name="vehicle_no" value="<?= $row['vehicle_no'] ?>">
                <input type="hidden" name="challan_no" value="<?= $row['challan_no'] ?>">

                <input type="text" name="full_name"
                       placeholder="Full Name"
                       class="w-full border p-2 mb-2 rounded"
                       required>

                <textarea name="comments"
                          placeholder="Explain your issue"
                          class="w-full border p-2 mb-2 rounded"
                          required></textarea>

                <button type="submit" name="submit_dispute"
                        class="px-4 py-2 bg-yellow-500 text-white rounded">
                    Submit Query
                </button>
            </form>
        </div>
    <?php
        }
    } else {
        echo "<p class='text-center text-red-600 font-semibold'>No challan found.</p>";
    }
    ?>

    <a href="police-complaint.php"
       class="block text-center mt-6 text-blue-700 font-semibold">
        Check Another Vehicle
    </a>

</div>
</body>
</html>
