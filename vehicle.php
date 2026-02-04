<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$dbname = "legal_assist";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$successMsg = "";
$errorMsg = "";

// ==================== CLAIM YOUR VEHICLE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_claim'])) {
    $fullName   = trim($_POST['full_name']);
    $vehicleNo  = trim($_POST['vehicle_no']);
    $contactNo  = trim($_POST['contact_no']);

    if (empty($fullName) || empty($vehicleNo) || empty($contactNo) || empty($_FILES['proof']['name'])) {
        $errorMsg = "All fields are required for vehicle claim.";
    } else {
        $targetDir = "uploads/claims/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["proof"]["name"]);
        $targetFile = $targetDir . $fileName;

        $allowedTypes = ['jpg','jpeg','png','pdf'];
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        if (!in_array($fileType, $allowedTypes)) {
            $errorMsg = "Only JPG, PNG, and PDF files are allowed.";
        } elseif (!move_uploaded_file($_FILES["proof"]["tmp_name"], $targetFile)) {
            $errorMsg = "Error uploading file.";
        } else {
            $stmt = $conn->prepare("INSERT INTO claims (full_name, vehicle_no, contact_no, proof_file, submitted_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $fullName, $vehicleNo, $contactNo, $fileName);

            if ($stmt->execute()) {
                $successMsg = "claim_submitted";
            } else {
                $errorMsg = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// ==================== AUCTION BIDDING ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_bid'])) {
    header('Content-Type: application/json');
    ob_clean();
    error_reporting(0);
    ini_set('display_errors', 0);

    $vehicleId = intval($_POST['vehicle_id']);
    $bidAmount = intval($_POST['bid_amount']);
    $bidderName = trim($_POST['bidder_name']);
    $bidderPhone = trim($_POST['bidder_phone']);
    $bidderEmail = trim($_POST['bidder_email']);

    if (empty($bidderName) || empty($bidderPhone) || empty($bidderEmail) || $bidAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    $checkStmt = $conn->prepare("SELECT MAX(bid_amount) as highest FROM auction_bids WHERE vehicle_id = ?");
    $checkStmt->bind_param("i", $vehicleId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $currentHighest = $row['highest'] ?? 0;
    $checkStmt->close();

    if ($bidAmount <= $currentHighest) {
        echo json_encode(['success' => false, 'message' => "Your bid must be higher than ₹" . number_format($currentHighest)]);
        exit;
    }

    $year = date("Y");
    do {
        $random_number = rand(1000, 9999);
        $bid_id = "BID-$year-$random_number";
        $check = $conn->query("SELECT id FROM auction_bids WHERE bid_id = '$bid_id'");
    } while ($check->num_rows > 0);

    $stmt = $conn->prepare("INSERT INTO auction_bids (bid_id, vehicle_id, bid_amount, bidder_name, bidder_phone, bidder_email, bid_time) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("siisss", $bid_id, $vehicleId, $bidAmount, $bidderName, $bidderPhone, $bidderEmail);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Bid placed successfully!',
            'bid_id'  => $bid_id,
            'new_bid' => $bidAmount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ==================== TRACK BID STATUS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_bid'])) {
    header('Content-Type: application/json');
    ob_clean();

    $trackBidId = trim($_POST['track_bid_id']);

    if (empty($trackBidId)) {
        echo json_encode(['success' => false, 'message' => 'Bid ID is required.']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT ab.bid_id, ab.bid_amount, ab.bidder_name, ab.bidder_phone, ab.bidder_email, ab.bid_time,
               av.vehicle_no, av.model, av.auction_end,
               (SELECT MAX(bid_amount) FROM auction_bids WHERE vehicle_id = ab.vehicle_id) as highest_bid
        FROM auction_bids ab
        JOIN auction_vehicles av ON ab.vehicle_id = av.id
        WHERE ab.bid_id = ?
    ");
    $stmt->bind_param("s", $trackBidId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $bid = $result->fetch_assoc();
        $status = ($bid['bid_amount'] == $bid['highest_bid']) ? 'Leading' : 'Outbid';

        echo json_encode([
            'success' => true,
            'bid' => [
                'bid_id' => $bid['bid_id'],
                'bid_amount' => $bid['bid_amount'],
                'highest_bid' => $bid['highest_bid'],
                'auction_end' => $bid['auction_end'],
                'status' => $status,
                'vehicle_no' => $bid['vehicle_no'],
                'model' => $bid['model']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bid not found.']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ==================== USER SUBMIT VEHICLE FOR AUCTION ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vehicle_auction'])) {
    $vehicleNumber = trim($_POST['auction_vehicle_no']);
    $model = trim($_POST['auction_model']);
    $type = trim($_POST['auction_type']);
    $color = trim($_POST['auction_color']);
    $make = trim($_POST['auction_make']);
    $location = trim($_POST['auction_location']);
    $startingBid = intval($_POST['starting_bid']);
    $auctionEndDate = trim($_POST['auction_end_date']);

    if (empty($vehicleNumber) || empty($model) || empty($type) || empty($auctionEndDate) || $startingBid <= 0) {
        $errorMsg = "All auction fields are required.";
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM auction_vehicles WHERE vehicle_no = ?");
        $checkStmt->bind_param("s", $vehicleNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $errorMsg = "This vehicle number is already listed for auction.";
            $checkStmt->close();
        } else {
            $checkStmt->close();
            
            $stmt = $conn->prepare("INSERT INTO auction_vehicles (vehicle_no, model, type, color, make, location, current_bid, auction_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssis", $vehicleNumber, $model, $type, $color, $make, $location, $startingBid, $auctionEndDate);

            if ($stmt->execute()) {
                $successMsg = "auction_submitted";
                header("Location: vehicle.php?success=auction");
                exit;
            } else {
                $errorMsg = "Error submitting vehicle: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// ==================== INSURANCE CLAIM ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_insurance'])) {
    header('Content-Type: application/json');
    ob_clean();

    $vehicleNo    = trim($_POST['vehicle_number']);
    $damageStatus = trim($_POST['damage_status']);

    if (empty($vehicleNo) || empty($damageStatus)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    $uploadedFiles = [];
    $targetDir = "uploads/insurance/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    if (!empty($_FILES['damage_photos']['name'][0])) {
        foreach ($_FILES['damage_photos']['name'] as $key => $name) {
            if ($_FILES['damage_photos']['error'][$key] !== UPLOAD_ERR_OK) continue;

            $tmpName = $_FILES['damage_photos']['tmp_name'][$key];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png'];
            if (!in_array($ext, $allowed)) continue;

            $newName = time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($tmpName, $targetDir . $newName)) {
                $uploadedFiles[] = $newName;
            }
        }
    }

    $photos = implode(',', $uploadedFiles);

    $claimId = "IC-" . date("Y") . "-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    $stmt = $conn->prepare("INSERT INTO insurance_claims (claim_id, vehicle_no, damage_status, photo_files, status, submitted_at) VALUES (?, ?, ?, ?, 'Submitted', NOW())");
    $stmt->bind_param("ssss", $claimId, $vehicleNo, $damageStatus, $photos);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Insurance claim submitted successfully.', 'claim_id' => $claimId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ==================== CHECK INSURANCE CLAIM STATUS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_claim_status'])) {
    header('Content-Type: application/json');
    ob_clean();

    $claimId = trim($_POST['claim_id']);

    if (empty($claimId)) {
        echo json_encode(['success' => false, 'message' => 'Claim ID is required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT claim_id, vehicle_no, damage_status, photo_files, status, submitted_at FROM insurance_claims WHERE claim_id = ?");
    $stmt->bind_param("s", $claimId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $claim = $result->fetch_assoc();
        $photoCount = empty($claim['photo_files']) ? 0 : count(explode(',', $claim['photo_files']));
        
        echo json_encode([
            'success' => true,
            'claim_id' => $claim['claim_id'],
            'vehicle_no' => $claim['vehicle_no'],
            'damage_status' => $claim['damage_status'],
            'photo_count' => $photoCount,
            'status' => $claim['status'],
            'submitted_at' => date('d M Y, h:i A', strtotime($claim['submitted_at']))
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Claim not found.']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

if (isset($_GET['success']) && $_GET['success'] === 'auction') {
    $successMsg = "auction_submitted";
}

// ==================== FETCH AUCTION VEHICLES ====================
$auctionQuery = "SELECT * FROM auction_vehicles WHERE auction_end >= CURDATE() ORDER BY id DESC";
$auctionResult = $conn->query($auctionQuery);
$dbVehicles = [];
if ($auctionResult && $auctionResult->num_rows > 0) {
    while($row = $auctionResult->fetch_assoc()){
        $vid = intval($row['id']);
        $bidQuery = $conn->prepare("SELECT MAX(bid_amount) as highest FROM auction_bids WHERE vehicle_id = ?");
        $bidQuery->bind_param("i", $vid);
        $bidQuery->execute();
        $bidResult = $bidQuery->get_result();
        $bidRow = $bidResult->fetch_assoc();
        $row['highest_bid'] = $bidRow['highest'] ?? $row['current_bid'];
        $bidQuery->close();
        $dbVehicles[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Unclaimed Vehicles | Legal Assist</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-blue-50 font-sans text-gray-900">

  <!-- Navigation Bar -->
  <header class="bg-blue-900 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold">Legal Assist</h1>
      <nav class="space-x-4">
        <a href="vehicle.php" class="hover:underline font-semibold text-yellow-300">Unclaimed Vehicles</a>
        <a href="passport.php" class="hover:underline">Passport Services</a>
        <a href="e-challan.php" class="hover:underline">E-Challan</a>
      </nav>
    </div>
  </header>

  <!-- Success/Error Messages -->
  <?php if($successMsg === "claim_submitted"): ?>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md text-center">
      <div class="text-6xl mb-4">✅</div>
      <h3 class="text-2xl font-bold text-green-800 mb-2">Claim Submitted!</h3>
      <p class="text-gray-700 mb-4">Your vehicle claim has been successfully submitted. We will contact you soon.</p>
      <button onclick="window.location.href='vehicle.php'" class="bg-blue-600 text-white px-6 py-2 rounded">Close</button>
    </div>
  </div>
  <?php endif; ?>

  <?php if($successMsg === "auction_submitted"): ?>
  <div id="auctionSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md text-center">
      <div class="text-6xl mb-4">✅</div>
      <h3 class="text-2xl font-bold text-green-800 mb-2">Vehicle Listed!</h3>
      <p class="text-gray-700 mb-4">Your vehicle has been successfully listed for auction.</p>
      <button onclick="window.location.href='vehicle.php'" class="bg-blue-600 text-white px-6 py-2 rounded">Close</button>
    </div>
  </div>
  <?php endif; ?>

  <?php if($errorMsg): ?>
  <div class="container mx-auto px-6 py-4">
    <div class="bg-red-50 border-2 border-red-400 p-4 rounded-lg text-center">
      <p class="text-red-800 font-semibold">❌ <?php echo htmlspecialchars($errorMsg); ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Hero Section -->
  <section class="text-center py-16 bg-gradient-to-r from-blue-800 to-blue-600 text-white">
    <h2 class="text-4xl font-bold mb-4">Unclaimed Vehicles</h2>
    <p class="mb-6 text-lg max-w-2xl mx-auto">View details of vehicles found and reported as unclaimed by the police department. If you identify your vehicle, you can initiate the claim process online.</p>
  </section>

  <!-- 1. VEHICLE AUCTION SECTION (FIRST) -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8">
      <h3 class="text-2xl font-bold mb-6 text-blue-800 text-center">Vehicle Auction</h3>

      <div class="text-center mb-6">
        <button onclick="document.getElementById('submitVehicleModal').classList.remove('hidden')" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-500">
          📤 Submit Your Vehicle for Auction
        </button>
      </div>

      <!-- Track Bid -->
      <div class="max-w-md mx-auto mb-8">
        <h4 class="text-xl font-semibold mb-4 text-center">Track Your Bid Status</h4>
        <form id="trackBidForm" class="flex gap-2">
          <input type="text" name="track_bid_id" placeholder="Enter BID ID (e.g. BID-2025-1234)" required class="flex-1 border p-3 rounded">
          <button type="submit" class="bg-teal-600 text-white px-6 py-3 rounded hover:bg-teal-700">Track</button>
        </form>
        <div id="trackBidResponse" class="mt-4"></div>
      </div>

      <!-- Auction Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-200">
          <thead class="bg-blue-100">
            <tr>
              <th class="py-3 px-6 text-left">Vehicle Number</th>
              <th class="py-3 px-6 text-left">Model</th>
              <th class="py-3 px-6 text-left">Type</th>
              <th class="py-3 px-6 text-left">Current Bid</th>
              <th class="py-3 px-6 text-left">Auction Ends</th>
              <th class="py-3 px-6 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($dbVehicles)): ?>
              <tr>
                <td colspan="6" class="py-8 text-center text-gray-500">No active auctions at the moment.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($dbVehicles as $vehicle): ?>
                <tr class="border-t hover:bg-gray-50">
                  <td class="py-4 px-6 font-semibold"><?php echo htmlspecialchars($vehicle['vehicle_no']); ?></td>
                  <td class="py-4 px-6"><?php echo htmlspecialchars($vehicle['model'] . ' ' . $vehicle['make']); ?></td>
                  <td class="py-4 px-6"><?php echo htmlspecialchars($vehicle['type']); ?></td>
                  <td class="py-4 px-6 font-bold text-green-700">₹<?php echo number_format($vehicle['highest_bid']); ?></td>
                  <td class="py-4 px-6"><?php echo date('d M Y', strtotime($vehicle['auction_end'])); ?></td>
                  <td class="py-4 px-6">
                    <button onclick="openBidModal(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars(addslashes($vehicle['vehicle_no'])); ?>', <?php echo $vehicle['highest_bid']; ?>)" 
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                      Place Bid
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- 2. CLAIM YOUR VEHICLE SECTION -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8">
      <h3 class="text-2xl font-bold mb-6 text-blue-800 text-center">Claim Your Vehicle</h3>
      <form action="" method="POST" enctype="multipart/form-data" class="max-w-2xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <input type="text" name="full_name" placeholder="Full Name" required class="border p-3 rounded">
          <input type="text" name="vehicle_no" placeholder="Vehicle Number" required class="border p-3 rounded">
          <input type="text" name="contact_no" placeholder="Contact Number" required class="border p-3 rounded">
          <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required class="border p-3 rounded">
        </div>
        <div class="text-center mt-8">
          <button type="submit" name="submit_claim" class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700">
            Submit Claim
          </button>
        </div>
      </form>
    </div>
  </section>

  <!-- 3. INSURANCE CLAIM + STATUS (SIDE BY SIDE) -->
  <section class="container mx-auto px-6 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

      <!-- Insurance Claim Form -->
      <div class="bg-white rounded-xl shadow-lg p-8">
        <h3 class="text-2xl font-bold mb-6 text-blue-800 text-center">Insurance Claim</h3>
        <form id="insuranceForm">
          <input type="text" name="vehicle_number" placeholder="Vehicle Number" required class="w-full border p-3 rounded mb-4">
          <textarea name="damage_status" placeholder="Describe Damage Status" required class="w-full border p-3 rounded mb-4 h-32"></textarea>
          <input type="file" name="damage_photos[]" multiple accept="image/*" class="w-full border p-3 rounded mb-6">
          <button type="submit" class="w-full bg-purple-600 text-white py-3 rounded-lg font-semibold hover:bg-purple-700">
            Submit Insurance Claim
          </button>
        </form>
        <div id="insuranceResponse" class="mt-4 text-center font-bold"></div>
      </div>

      <!-- Check Insurance Claim Status -->
      <div class="bg-white rounded-xl shadow-lg p-8">
        <h3 class="text-2xl font-bold mb-6 text-blue-800 text-center">Check Insurance Claim Status</h3>
        <form id="trackInsuranceForm">
          <input type="text" name="claim_id" placeholder="Enter Claim ID (e.g. IC-2025-ABCDEF)" required class="w-full border p-3 rounded mb-6">
          <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700">
            Check Status
          </button>
        </form>
        <div id="trackInsuranceResponse" class="mt-6"></div>
      </div>

    </div>
  </section>

  <!-- Submit Vehicle Modal -->
  <div id="submitVehicleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full">
      <h3 class="text-2xl font-bold mb-6 text-center">Submit Vehicle for Auction</h3>
      <form action="" method="POST">
        <div class="grid grid-cols-2 gap-4">
          <input type="text" name="auction_vehicle_no" placeholder="Vehicle Number" required class="border p-3 rounded">
          <input type="text" name="auction_model" placeholder="Model" required class="border p-3 rounded">
          <input type="text" name="auction_type" placeholder="Type" required class="border p-3 rounded">
          <input type="text" name="auction_color" placeholder="Color" required class="border p-3 rounded">
          <input type="text" name="auction_make" placeholder="Make" required class="border p-3 rounded">
          <input type="text" name="auction_location" placeholder="Location" required class="border p-3 rounded">
          <input type="number" name="starting_bid" placeholder="Starting Bid (₹)" required class="border p-3 rounded">
          <input type="date" name="auction_end_date" required class="border p-3 rounded">
        </div>
        <div class="text-center mt-6">
          <button type="button" onclick="document.getElementById('submitVehicleModal').classList.add('hidden')" class="bg-gray-500 text-white px-6 py-3 rounded mr-4">Cancel</button>
          <button type="submit" name="submit_vehicle_auction" class="bg-green-600 text-white px-8 py-3 rounded">Submit</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bid Modal -->
  <div id="bidModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md w-full">
      <h3 class="text-2xl font-bold mb-6 text-center">Place Your Bid</h3>
      <form id="bidForm">
        <input type="hidden" id="vehicle_id" name="vehicle_id">
        <p class="mb-4">Vehicle: <span id="modal_vehicle_no" class="font-bold"></span></p>
        <p class="mb-4">Current Highest: ₹<span id="modal_current_bid" class="font-bold"></span></p>
        
        <input type="number" name="bid_amount" id="bid_amount" placeholder="Your Bid Amount" required class="w-full border p-3 rounded mb-4">
        <input type="text" name="bidder_name" placeholder="Name" required class="w-full border p-3 rounded mb-4">
        <input type="tel" name="bidder_phone" placeholder="Phone" required class="w-full border p-3 rounded mb-4">
        <input type="email" name="bidder_email" placeholder="Email" required class="w-full border p-3 rounded mb-4">
        
        <div id="bidResponse" class="mt-4 text-center font-bold"></div>
        
        <div class="text-center mt-6">
          <button type="button" onclick="closeBidModal()" class="bg-gray-500 text-white px-6 py-3 rounded mr-4">Cancel</button>
          <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded">Submit Bid</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openBidModal(id, vehicleNo, currentBid) {
      document.getElementById('vehicle_id').value = id;
      document.getElementById('modal_vehicle_no').textContent = vehicleNo;
      document.getElementById('modal_current_bid').textContent = new Intl.NumberFormat().format(currentBid);
      document.getElementById('bid_amount').min = currentBid + 1;
      document.getElementById('bid_amount').value = currentBid + 1000;
      document.getElementById('bidModal').classList.remove('hidden');
    }

    function closeBidModal() {
      document.getElementById('bidModal').classList.add('hidden');
      document.getElementById('bidForm').reset();
      document.getElementById('bidResponse').innerHTML = '';
    }

    $('#bidForm').submit(function(e) {
      e.preventDefault();
      $.post('', $(this).serialize() + '&submit_bid=1', function(response) {
        if (response.success) {
          $('#bidResponse').html('<span class="text-green-600">Bid placed! Your Unique BID ID: <strong>' + response.bid_id + '</strong></span>');
          setTimeout(() => location.reload(), 2500);
        } else {
          $('#bidResponse').html('<span class="text-red-600">' + response.message + '</span>');
        }
      }, 'json');
    });

    $('#trackBidForm').submit(function(e) {
      e.preventDefault();
      $.post('', $(this).serialize() + '&track_bid=1', function(response) {
        if (response.success) {
          const b = response.bid;
          $('#trackBidResponse').html(`
            <div class="bg-green-50 border border-green-400 p-4 rounded text-center">
              <p class="font-bold text-xl">${b.vehicle_no} - ${b.model}</p>
              <p>Your Bid: ₹${new Intl.NumberFormat().format(b.bid_amount)}</p>
              <p>Highest: ₹${new Intl.NumberFormat().format(b.highest_bid)}</p>
              <p class="font-bold text-2xl mt-2 ${b.status === 'Leading' ? 'text-green-600' : 'text-red-600'}">${b.status}</p>
            </div>
          `);
        } else {
          $('#trackBidResponse').html('<p class="text-red-600 font-bold">' + response.message + '</p>');
        }
      }, 'json');
    });

    $('#insuranceForm').submit(function(e) {
      e.preventDefault();
      let formData = new FormData(this);
      formData.append('submit_insurance', '1');
      $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          if (response.success) {
            $('#insuranceResponse').html('<p class="text-green-600 font-bold">Submitted! Claim ID: <strong>' + response.claim_id + '</strong></p>');
          } else {
            $('#insuranceResponse').html('<p class="text-red-600 font-bold">' + response.message + '</p>');
          }
        }
      });
    });

    $('#trackInsuranceForm').submit(function(e) {
      e.preventDefault();
      $.post('', $(this).serialize() + '&check_claim_status=1', function(response) {
        if (response.success) {
          $('#trackInsuranceResponse').html(`
            <div class="bg-blue-50 border border-blue-400 p-6 rounded">
              <p class="font-bold text-xl">Claim ID: ${response.claim_id}</p>
              <p>Vehicle: ${response.vehicle_no}</p>
              <p>Status: <strong class="text-blue-700">${response.status}</strong></p>
              <p>Photos: ${response.photo_count}</p>
              <p class="text-sm text-gray-600 mt-4">Submitted: ${response.submitted_at}</p>
            </div>
          `);
        } else {
          $('#trackInsuranceResponse').html('<p class="text-red-600 font-bold">' + response.message + '</p>');
        }
      }, 'json');
    });
    import React, { useState } from 'react';
import { Upload, FileText, CheckCircle, Circle, Download, Trash2, Edit3, X } from 'lucide-react';

const DocumentManagement = () => {
  const [activeTab, setActiveTab] = useState('wallet');
  const [documents, setDocuments] = useState([
    { id: 1, name: 'Vehicle Registration.pdf', uploadDate: '2024-12-20', size: '2.3 MB', signed: true },
    { id: 2, name: 'Insurance Policy.pdf', uploadDate: '2024-12-21', size: '1.8 MB', signed: true },
  ]);
  const [dragActive, setDragActive] = useState(false);
  const [signature, setSignature] = useState('');
  const [signatureData, setSignatureData] = useState(null);
  const [steps, setSteps] = useState([
    { id: 1, title: 'Upload Vehicle Documents', completed: true },
    { id: 2, title: 'Upload Insurance Documents', completed: true },
    { id: 3, title: 'Identity Verification', completed: false },
    { id: 4, title: 'Sign Claim Forms', completed: false },
    { id: 5, title: 'Submit for Review', completed: false },
  ]);

  const progress = (steps.filter(s => s.completed).length / steps.length) * 100;

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4">
      <div className="max-w-6xl mx-auto">
        <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
          {/* Header */}
          <div className="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white">
            <h1 className="text-3xl font-bold mb-2">Document Management Center</h1>
            <p className="text-blue-100">Manage your claim documents and track progress</p>
          </div>

          {/* Tabs */}
          <div className="flex border-b border-gray-200">
            <button
              onClick={() => setActiveTab('wallet')}
              className={`flex-1 py-4 px-6 font-semibold transition-colors ${
                activeTab === 'wallet'
                  ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
                  : 'text-gray-600 hover:bg-gray-50'
              }`}
            >
              <FileText className="inline mr-2" size={20} />
              Digital Wallet
            </button>
            <button
              onClick={() => setActiveTab('progress')}
              className={`flex-1 py-4 px-6 font-semibold transition-colors ${
                activeTab === 'progress'
                  ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
                  : 'text-gray-600 hover:bg-gray-50'
              }`}
            >
              <CheckCircle className="inline mr-2" size={20} />
              Progress Tracker
            </button>
            <button
              onClick={() => setActiveTab('upload')}
              className={`flex-1 py-4 px-6 font-semibold transition-colors ${
                activeTab === 'upload'
                  ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
                  : 'text-gray-600 hover:bg-gray-50'
              }`}
            >
              <Upload className="inline mr-2" size={20} />
              Upload Documents
            </button>
            <button
              onClick={() => setActiveTab('signature')}
              className={`flex-1 py-4 px-6 font-semibold transition-colors ${
                activeTab === 'signature'
                  ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
                  : 'text-gray-600 hover:bg-gray-50'
              }`}
            >
              <Edit3 className="inline mr-2" size={20} />
              E-Signature
            </button>
          </div>

          <div className="p-6">
            {/* Digital Wallet Tab */}
            {activeTab === 'wallet' && (
              <div>
                <h2 className="text-2xl font-bold mb-4 text-gray-800">Your Documents</h2>
                <div className="grid gap-4">
                  {documents.map((doc) => (
                    <div key={doc.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                      <div className="flex items-center space-x-4 flex-1">
                        <div className="bg-blue-100 p-3 rounded-lg">
                          <FileText className="text-blue-600" size={24} />
                        </div>
                        <div className="flex-1">
                          <h3 className="font-semibold text-gray-800">{doc.name}</h3>
                          <p className="text-sm text-gray-500">
                            Uploaded: {doc.uploadDate} • Size: {doc.size}
                          </p>
                        </div>
                        {doc.signed && (
                          <span className="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                            Signed
                          </span>
                        )}
                      </div>
                      <div className="flex space-x-2">
                        <button className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                          <Download size={20} />
                        </button>
                        <button 
                          onClick={() => setDocuments(documents.filter(doc => doc.id !== doc.id))}
                          className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        >
                          <Trash2 size={20} />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Progress Tracker Tab */}
            {activeTab === 'progress' && (
              <div>
                <h2 className="text-2xl font-bold mb-4 text-gray-800">Claim Progress</h2>
                
                {/* Progress Bar */}
                <div className="mb-8 bg-gray-200 rounded-full h-4 overflow-hidden">
                  <div 
                    className="bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full transition-all duration-500"
                    style={{ width: `${progress}%` }}
                  ></div>
                </div>
                <p className="text-center text-gray-600 mb-8 font-semibold">{progress.toFixed(0)}% Complete</p>

                {/* Steps */}
                <div className="space-y-4">
                  {steps.map((step, index) => (
                    <div key={step.id} className="flex items-start space-x-4">
                      <div className="flex flex-col items-center">
                        <button
                          onClick={() => setSteps(steps.map(s => s.id === step.id ? {...s, completed: !s.completed} : s))}
                          className={`w-10 h-10 rounded-full flex items-center justify-center ${
                            step.completed ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'
                          }`}
                        >
                          {step.completed ? <CheckCircle size={24} /> : <Circle size={24} />}
                        </button>
                        {index < steps.length - 1 && (
                          <div className={`w-1 h-12 ${step.completed ? 'bg-green-500' : 'bg-gray-200'}`}></div>
                        )}
                      </div>
                      <div className="flex-1 pb-8">
                        <h3 className={`font-semibold text-lg ${step.completed ? 'text-gray-800' : 'text-gray-500'}`}>
                          {step.title}
                        </h3>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Upload Tab */}
            {activeTab === 'upload' && (
              <div>
                <h2 className="text-2xl font-bold mb-4 text-gray-800">Upload Documents</h2>
                <div className="border-4 border-dashed border-gray-300 rounded-xl p-12 text-center bg-gray-50">
                  <Upload className="mx-auto text-gray-400 mb-4" size={64} />
                  <h3 className="text-xl font-semibold text-gray-700 mb-2">Upload Your Files</h3>
                  <label className="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold cursor-pointer hover:bg-blue-700 inline-block mt-4">
                    Choose Files
                    <input
                      type="file"
                      multiple
                      onChange={(e) => e.target.files && handleFiles(e.target.files)}
                      className="hidden"
                    />
                  </label>
                  <p className="text-sm text-gray-400 mt-4">PDF, DOC, JPG, PNG</p>
                </div>
              </div>
            )}

            {/* E-Signature Tab */}
            {activeTab === 'signature' && (
              <div>
                <h2 className="text-2xl font-bold mb-4 text-gray-800">Electronic Signature</h2>
                <div className="bg-gray-50 rounded-xl p-8 border-2 border-gray-200">
                  <p className="text-gray-700 mb-6">
                    Sign below to confirm all information is accurate.
                  </p>
                  
                  {!signatureData ? (
                    <div>
                      <input
                        type="text"
                        value={signature}
                        onChange={(e) => setSignature(e.target.value)}
                        placeholder="Type your full name"
                        className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg mb-4 text-lg"
                      />
                      <div className="bg-white border-2 border-gray-300 rounded-lg p-8 mb-4 min-h-32 flex items-center justify-center">
                        {signature ? (
                          <div className="text-4xl font-bold text-blue-600 italic" style={{ fontFamily: 'cursive' }}>
                            {signature}
                          </div>
                        ) : (
                          <p className="text-gray-400">Your signature will appear here</p>
                        )}
                      </div>
                      <button
                        onClick={() => {
                          if(signature.trim()) {
                            setSignatureData(signature);
                            setSteps(steps.map(s => s.id === 4 ? {...s, completed: true} : s));
                          }
                        }}
                        disabled={!signature.trim()}
                        className="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:bg-gray-300"
                      >
                        Sign Document
                      </button>
                    </div>
                  ) : (
                    <div>
                      <div className="bg-white border-2 border-green-500 rounded-lg p-8 mb-4">
                        <div className="flex items-center justify-between mb-4">
                          <span className="text-green-600 font-semibold flex items-center">
                            <CheckCircle className="mr-2" size={20} />
                            Document Signed
                          </span>
                          <button
                            onClick={() => {
                              setSignature('');
                              setSignatureData(null);
                            }}
                            className="text-red-600 hover:bg-red-50 p-2 rounded-lg"
                          >
                            <X size={20} />
                          </button>
                        </div>
                        <div className="text-4xl font-bold text-blue-600 italic mb-4" style={{ fontFamily: 'cursive' }}>
                          {signatureData}
                        </div>
                        <p className="text-sm text-gray-600">
                          Signed on: {new Date().toLocaleString()}
                        </p>
                      </div>
                      <button className="w-full bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700">
                        Download Signed Document
                      </button>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default DocumentManagement;
  </script>
</body>
</html>