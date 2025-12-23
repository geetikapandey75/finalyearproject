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

// ==================== PROBLEM 1: CLAIM YOUR VEHICLE ====================
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

// ==================== PROBLEM 2: AUCTION BIDDING ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_bid'])) {
    $vehicleId = intval($_POST['vehicle_id']);
    $bidAmount = intval($_POST['bid_amount']);
    $bidderName = trim($_POST['bidder_name']);
    $bidderPhone = trim($_POST['bidder_phone']);
    $bidderEmail = trim($_POST['bidder_email']);

    if (empty($bidderName) || empty($bidderPhone) || empty($bidderEmail) || $bidAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // Check current highest bid
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

    // Insert bid
    $stmt = $conn->prepare("INSERT INTO auction_bids (vehicle_id, bid_amount, bidder_name, bidder_phone, bidder_email, bid_time) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $vehicleId, $bidAmount, $bidderName, $bidderPhone, $bidderEmail);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Bid placed successfully!', 'new_bid' => $bidAmount]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ==================== PROBLEM 2: USER SUBMIT VEHICLE FOR AUCTION ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vehicle_auction'])) {
    $vehicleNumber = trim($_POST['auction_vehicle_no']);
    $model = trim($_POST['auction_model']);
    $type = trim($_POST['auction_type']);
    $color = trim($_POST['auction_color']);
    $make = trim($_POST['auction_make']);
    $location = trim($_POST['auction_location']);
    $startingBid = intval($_POST['starting_bid']);
    $auctionEndDate = trim($_POST['auction_end_date']);

    if (empty($vehicleNumber) || empty($model) || empty($type) || empty($auctionEndDate)) {
        $errorMsg = "All auction fields are required.";
    } else {
        // Check if vehicle already exists
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
            } else {
                $errorMsg = "Error submitting vehicle: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

// ==================== PROBLEM 3: INSURANCE CLAIM ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_insurance'])) {
    $vehicleNo    = trim($_POST['vehicle_number']);
    $damageStatus = trim($_POST['damage_status']);

    if (empty($vehicleNo) || empty($damageStatus)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // Handle file uploads
    $uploadedFiles = [];
    $targetDir = "uploads/insurance/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    if (!empty($_FILES['damage_photos']['name'][0])) {
        foreach ($_FILES['damage_photos']['name'] as $key => $name) {
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

    // Generate unique claim ID
    $claimId = "IC-" . date("Y") . "-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    // Insert into database
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

// ==================== FETCH AUCTION VEHICLES FROM DATABASE ====================
$auctionQuery = "SELECT * FROM auction_vehicles WHERE auction_end >= CURDATE() ORDER BY id DESC";
$auctionResult = $conn->query($auctionQuery);
$dbVehicles = [];
if ($auctionResult && $auctionResult->num_rows > 0) {
    while($row = $auctionResult->fetch_assoc()){
        // Get highest bid
        $vid = $row['id'];
        $bidQuery = $conn->query("SELECT MAX(bid_amount) as highest FROM auction_bids WHERE vehicle_id = $vid");
        $bidRow = $bidQuery->fetch_assoc();
        $row['highest_bid'] = $bidRow['highest'] ?? $row['current_bid'];
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
  <title>Unclaimed Vehicles | Police Services</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body class="bg-blue-50 font-sans text-gray-900">

  <!-- Navigation Bar -->
  <header class="bg-blue-900 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold">Legal Assist</h1>
      <nav class="space-x-4">
        <a href="vehicle.php" class="hover:underline font-semibold text-yellow-300">Unclaimed Vehicles</a>
        <a href="passport.php" class="hover:underline">Passport Services</a>
        <a href="e-challan.html" class="hover:underline">E-Challan</a>
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
      <button onclick="this.parentElement.parentElement.style.display='none'" class="bg-blue-600 text-white px-6 py-2 rounded">Close</button>
    </div>
  </div>
  <?php endif; ?>

  <?php if($successMsg === "auction_submitted"): ?>
  <div id="auctionSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md text-center">
      <div class="text-6xl mb-4">✅</div>
      <h3 class="text-2xl font-bold text-green-800 mb-2">Vehicle Listed!</h3>
      <p class="text-gray-700 mb-4">Your vehicle has been successfully listed for auction. Refresh the page to see it.</p>
      <button onclick="window.location.reload()" class="bg-blue-600 text-white px-6 py-2 rounded">Refresh Page</button>
    </div>
  </div>
  <script>
    // Auto refresh after 2 seconds
    setTimeout(function() {
      window.location.reload();
    }, 2000);
  </script>
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

  <!-- Auction & Vehicle Section -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8">
      <h3 class="text-2xl font-bold mb-6 text-blue-800 text-center">Vehicle Auction</h3>

      <!-- Submit Your Vehicle Button -->
      <div class="text-center mb-6">
        <button onclick="document.getElementById('submitVehicleModal').classList.remove('hidden')" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-500">
          📤 Submit Your Vehicle for Auction
        </button>
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
              <th class="py-3 px-6 text-left">Action</th>
            </tr>
          </thead>
          <tbody class="text-gray-700">
            <!-- Hardcoded Vehicles (Your original 2) -->
            <tr class="border-t hover:bg-blue-50">
              <td class="py-3 px-6">TS09AB1234</td>
              <td class="py-3 px-6">Honda Activa</td>
              <td class="py-3 px-6">Scooter</td>
              <td class="py-3 px-6" id="bid-1">₹25,000</td>
              <td class="py-3 px-6">2025-12-25</td>
              <td class="py-3 px-6">
                <button onclick="openBidModal(1, 'TS09AB1234', 'Honda Activa', 25000)" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-500">Bid Now</button>
              </td>
            </tr>
            <tr class="border-t hover:bg-blue-50">
              <td class="py-3 px-6">AP28CD5678</td>
              <td class="py-3 px-6">Hyundai i20</td>
              <td class="py-3 px-6">Car</td>
              <td class="py-3 px-6" id="bid-2">₹75,000</td>
              <td class="py-3 px-6">2025-12-28</td>
              <td class="py-3 px-6">
                <button onclick="openBidModal(2, 'AP28CD5678', 'Hyundai i20', 75000)" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-500">Bid Now</button>
              </td>
            </tr>

            <!-- Database Vehicles -->
            <?php foreach($dbVehicles as $vehicle): ?>
            <tr class="border-t hover:bg-blue-50">
              <td class="py-3 px-6"><?php echo htmlspecialchars($vehicle['vehicle_no']); ?></td>
              <td class="py-3 px-6"><?php echo htmlspecialchars($vehicle['model']); ?></td>
              <td class="py-3 px-6"><?php echo htmlspecialchars($vehicle['type']); ?></td>
              <td class="py-3 px-6" id="bid-<?php echo $vehicle['id']; ?>">₹<?php echo number_format($vehicle['highest_bid']); ?></td>
              <td class="py-3 px-6"><?php echo date('Y-m-d', strtotime($vehicle['auction_end'])); ?></td>
              <td class="py-3 px-6">
                <button onclick="openBidModal(<?php echo $vehicle['id']; ?>, '<?php echo $vehicle['vehicle_no']; ?>', '<?php echo $vehicle['model']; ?>', <?php echo $vehicle['highest_bid']; ?>)" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-500">Bid Now</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Claim Form -->
  <section class="container mx-auto px-6 pb-12">
    <div class="bg-white rounded-xl shadow-lg p-8">
      <h3 class="text-2xl font-bold mb-6 text-blue-800 text-center">Claim Your Vehicle</h3>
      <form class="space-y-6 max-w-2xl mx-auto" method="POST" action="" enctype="multipart/form-data">
        <div>
          <label class="block font-semibold mb-2">Full Name</label>
          <input type="text" name="full_name" class="w-full p-4 border rounded-lg" placeholder="Enter your name" required>
        </div>
        <div>
          <label class="block font-semibold mb-2">Vehicle Number</label>
          <input type="text" name="vehicle_no" class="w-full p-4 border rounded-lg" placeholder="Enter vehicle number" required>
        </div>
        <div>
          <label class="block font-semibold mb-2">Contact Number</label>
          <input type="tel" name="contact_no" class="w-full p-4 border rounded-lg" placeholder="Enter your contact number" required>
        </div>
        <div>
          <label class="block font-semibold mb-2">Upload Proof of Ownership</label>
          <input type="file" name="proof" class="w-full p-4 border rounded-lg" required>
        </div>
        <button type="submit" name="submit_claim" class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-500 w-full">Submit Claim</button>
      </form>
    </div>
  </section>

  <!-- Insurance Claim Helper -->
  <section class="container mx-auto px-6 py-12">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-4xl mx-auto">
      <h3 class="text-2xl font-bold mb-6 text-blue-800 text-center">Insurance Claim Helper</h3>

      <form id="insuranceForm" class="space-y-6">
        <div>
          <label class="block font-semibold mb-2">Vehicle Number</label>
          <input type="text" id="vehicleNumber" placeholder="Enter vehicle number" class="w-full p-4 border rounded-lg" required>
        </div>

        <div>
          <label class="block font-semibold mb-2">Was the vehicle damaged when found?</label>
          <select id="damageStatus" class="w-full p-4 border rounded-lg" required>
            <option value="">Select</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold mb-2">Upload Photos of Damage</label>
          <input type="file" id="damagePhotos" multiple accept="image/*" class="w-full p-4 border rounded-lg">
        </div>

        <button type="button" onclick="submitInsuranceClaim()" class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-blue-500 w-full">Submit Insurance Claim</button>
      </form>

      <div id="insuranceSuccess" class="mt-6 p-6 bg-green-50 border-2 border-green-500 rounded-lg hidden">
        <div class="text-center mb-4">
          <div class="text-5xl mb-2">✅</div>
          <h4 class="text-xl font-bold text-green-800">Insurance Claim Submitted!</h4>
          <p class="text-gray-700 mt-2">Your claim ID: <span id="displayClaimId" class="font-mono font-bold text-blue-600"></span></p>
        </div>
        <button onclick="generatePDFReport()" class="bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-500 w-full">Download PDF Report</button>
      </div>
    </div>
  </section>

  <!-- Bid Modal -->
  <div id="bidModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md w-full">
      <h3 class="text-2xl font-bold mb-4" id="bidModalTitle"></h3>
      <p class="mb-4">Current Highest Bid: <span id="currentBid" class="font-bold text-green-600"></span></p>
      
      <form id="bidForm" class="space-y-4">
        <input type="hidden" id="bidVehicleId">
        <div>
          <label class="block font-semibold mb-2">Your Name</label>
          <input type="text" id="bidderName" class="w-full p-3 border rounded-lg" required>
        </div>
        <div>
          <label class="block font-semibold mb-2">Phone Number</label>
          <input type="tel" id="bidderPhone" class="w-full p-3 border rounded-lg" required>
        </div>
        <div>
          <label class="block font-semibold mb-2">Email</label>
          <input type="email" id="bidderEmail" class="w-full p-3 border rounded-lg" required>
        </div>
        <div>
          <label class="block font-semibold mb-2">Your Bid Amount (₹)</label>
          <input type="number" id="bidAmount" class="w-full p-3 border rounded-lg" required>
        </div>
        <div class="flex gap-4">
          <button type="button" onclick="submitBid()" class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-500">Place Bid</button>
          <button type="button" onclick="closeBidModal()" class="flex-1 bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold hover:bg-gray-400">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Submit Vehicle Modal -->
  <div id="submitVehicleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full m-4">
      <h3 class="text-2xl font-bold mb-4">Submit Your Vehicle for Auction</h3>
      
      <form id="auctionSubmitForm" method="POST" action="" enctype="multipart/form-data" class="space-y-4" onsubmit="return preventDoubleSubmit(this)">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold mb-2">Vehicle Number</label>
            <input type="text" name="auction_vehicle_no" class="w-full p-3 border rounded-lg" required>
          </div>
          <div>
            <label class="block font-semibold mb-2">Model</label>
            <input type="text" name="auction_model" class="w-full p-3 border rounded-lg" required>
          </div>
          <div>
            <label class="block font-semibold mb-2">Type</label>
            <select name="auction_type" class="w-full p-3 border rounded-lg" required>
              <option value="">Select Type</option>
              <option value="Car">Car</option>
              <option value="Bike">Bike</option>
              <option value="Scooter">Scooter</option>
              <option value="Auto">Auto</option>
              <option value="Truck">Truck</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold mb-2">Color</label>
            <input type="text" name="auction_color" class="w-full p-3 border rounded-lg" required>
          </div>
          <div>
            <label class="block font-semibold mb-2">Make/Brand</label>
            <input type="text" name="auction_make" class="w-full p-3 border rounded-lg" required>
          </div>
          <div>
            <label class="block font-semibold mb-2">Location</label>
            <input type="text" name="auction_location" class="w-full p-3 border rounded-lg" required>
          </div>
          <div>
            <label class="block font-semibold mb-2">Starting Bid (₹)</label>
            <input type="number" name="starting_bid" class="w-full p-3 border rounded-lg" required>
          </div>
          <div>
            <label class="block font-semibold mb-2">Auction End Date</label>
            <input type="date" name="auction_end_date" class="w-full p-3 border rounded-lg" required>
          </div>
        </div>
        <div class="flex gap-4">
          <button type="submit" name="submit_vehicle_auction" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-500">Submit Vehicle</button>
          <button type="button" onclick="document.getElementById('submitVehicleModal').classList.add('hidden')" class="flex-1 bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold hover:bg-gray-400">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Prevent double form submission
    let isSubmitting = false;
    function preventDoubleSubmit(form) {
      if (isSubmitting) {
        alert('Please wait... Your vehicle is being submitted.');
        return false;
      }
      isSubmitting = true;
      // Disable submit button
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
      }
      return true;
    }

    // Global variables for PDF generation
    let globalClaimId = '';
    let globalVehicleNo = '';
    let globalDamageStatus = '';
    let globalPhotoCount = 0;

    // Bid Modal Functions
    function openBidModal(vehicleId, vehicleNo, model, currentBid) {
      document.getElementById('bidModal').classList.remove('hidden');
      document.getElementById('bidModal').classList.add('flex');
      document.getElementById('bidModalTitle').textContent = vehicleNo + ' - ' + model;
      document.getElementById('currentBid').textContent = '₹' + currentBid.toLocaleString();
      document.getElementById('bidVehicleId').value = vehicleId;
      document.getElementById('bidAmount').min = currentBid + 1;
    }

    function closeBidModal() {
      document.getElementById('bidModal').classList.add('hidden');
      document.getElementById('bidModal').classList.remove('flex');
      document.getElementById('bidForm').reset();
    }

    function submitBid() {
      const vehicleId = document.getElementById('bidVehicleId').value;
      const bidAmount = document.getElementById('bidAmount').value;
      const bidderName = document.getElementById('bidderName').value;
      const bidderPhone = document.getElementById('bidderPhone').value;
      const bidderEmail = document.getElementById('bidderEmail').value;

      const formData = new FormData();
      formData.append('submit_bid', '1');
      formData.append('vehicle_id', vehicleId);
      formData.append('bid_amount', bidAmount);
      formData.append('bidder_name', bidderName);
      formData.append('bidder_phone', bidderPhone);
      formData.append('bidder_email', bidderEmail);

      fetch('vehicle.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(data.message);
          document.getElementById('bid-' + vehicleId).textContent = '₹' + parseInt(data.new_bid).toLocaleString();
          closeBidModal();
        } else {
          alert(data.message);
        }
      })
      .catch(error => {
        alert('Error: ' + error);
      });
    }

    // Insurance Claim Functions
    function submitInsuranceClaim() {
      const vehicleNo = document.getElementById('vehicleNumber').value;
      const damageStatus = document.getElementById('damageStatus').value;
      const photos = document.getElementById('damagePhotos').files;

      if (!vehicleNo || !damageStatus) {
        alert('Please fill all required fields');
        return;
      }

      const formData = new FormData();
      formData.append('submit_insurance', '1');
      formData.append('vehicle_number', vehicleNo);
      formData.append('damage_status', damageStatus);

      for (let i = 0; i < photos.length; i++) {
        formData.append('damage_photos[]', photos[i]);
      }

      fetch('vehicle.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          globalClaimId = data.claim_id;
          globalVehicleNo = vehicleNo;
          globalDamageStatus = damageStatus;
          globalPhotoCount = photos.length;

          document.getElementById('displayClaimId').textContent = data.claim_id;
          document.getElementById('insuranceSuccess').classList.remove('hidden');
          document.getElementById('insuranceForm').reset();
        } else {
          alert(data.message);
        }
      })
      .catch(error => {
        alert('Error: ' + error);
      });
    }

    // Generate Professional PDF Report
    function generatePDFReport() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      
      const pageWidth = 210;
      const pageHeight = 297;
      let y = 20;

      // ============ HEADER WITH GRADIENT BACKGROUND ============
      // Blue gradient background
      doc.setFillColor(30, 58, 138);
      doc.rect(0, 0, pageWidth, 50, 'F');
      
      // Company logo area (circle)
      doc.setFillColor(255, 255, 255);
      doc.circle(25, 25, 12, 'F');
      doc.setFillColor(59, 130, 246);
      doc.circle(25, 25, 10, 'F');
      
      // Company name
      doc.setTextColor(255, 255, 255);
      doc.setFontSize(24);
      doc.setFont(undefined, 'bold');
      doc.text('LEGAL ASSIST', 45, 25);
      
      doc.setFontSize(12);
      doc.setFont(undefined, 'normal');
      doc.text('Vehicle Insurance Division', 45, 32);
      
      // Document title on right
      doc.setFontSize(16);
      doc.setFont(undefined, 'bold');
      doc.text('INSURANCE CLAIM', pageWidth - 20, 25, { align: 'right' });
      doc.setFontSize(10);
      doc.setFont(undefined, 'normal');
      doc.text('Official Document', pageWidth - 20, 32, { align: 'right' });
      
      y = 60;

      // ============ CLAIM ID BOX ============
      doc.setFillColor(59, 130, 246);
      doc.roundedRect(20, y, pageWidth - 40, 20, 3, 3, 'F');
      
      doc.setTextColor(255, 255, 255);
      doc.setFontSize(14);
      doc.setFont(undefined, 'bold');
      doc.text('CLAIM ID: ' + globalClaimId, pageWidth / 2, y + 12, { align: 'center' });
      
      y += 30;

      // ============ CLAIM INFORMATION SECTION ============
      doc.setTextColor(30, 58, 138);
      doc.setFontSize(16);
      doc.setFont(undefined, 'bold');
      doc.text('CLAIM INFORMATION', 20, y);
      
      y += 2;
      doc.setDrawColor(59, 130, 246);
      doc.setLineWidth(0.5);
      doc.line(20, y, 80, y);
      
      y += 10;
      
      // Info boxes
      const boxHeight = 12;
      const leftCol = 20;
      const rightCol = 110;
      
      // Vehicle Number
      doc.setFillColor(249, 250, 251);
      doc.roundedRect(leftCol, y, 85, boxHeight, 2, 2, 'F');
      doc.setTextColor(75, 85, 99);
      doc.setFontSize(9);
      doc.setFont(undefined, 'normal');
      doc.text('Vehicle Number', leftCol + 3, y + 5);
      doc.setTextColor(17, 24, 39);
      doc.setFontSize(11);
      doc.setFont(undefined, 'bold');
      doc.text(globalVehicleNo, leftCol + 3, y + 10);
      
      // Claim Date
      doc.setFillColor(249, 250, 251);
      doc.roundedRect(rightCol, y, 80, boxHeight, 2, 2, 'F');
      doc.setTextColor(75, 85, 99);
      doc.setFontSize(9);
      doc.setFont(undefined, 'normal');
      doc.text('Claim Date', rightCol + 3, y + 5);
      doc.setTextColor(17, 24, 39);
      doc.setFontSize(11);
      doc.setFont(undefined, 'bold');
      doc.text(new Date().toLocaleDateString('en-GB'), rightCol + 3, y + 10);
      
      y += boxHeight + 5;
      
      // Damage Status
      doc.setFillColor(249, 250, 251);
      doc.roundedRect(leftCol, y, 85, boxHeight, 2, 2, 'F');
      doc.setTextColor(75, 85, 99);
      doc.setFontSize(9);
      doc.setFont(undefined, 'normal');
      doc.text('Damage Status', leftCol + 3, y + 5);
      doc.setTextColor(17, 24, 39);
      doc.setFontSize(11);
      doc.setFont(undefined, 'bold');
      const damageText = globalDamageStatus === 'Yes' ? 'Vehicle Damaged' : 'No Damage Reported';
      doc.text(damageText, leftCol + 3, y + 10);
      
      // Photos Submitted
      doc.setFillColor(249, 250, 251);
      doc.roundedRect(rightCol, y, 80, boxHeight, 2, 2, 'F');
      doc.setTextColor(75, 85, 99);
      doc.setFontSize(9);
      doc.setFont(undefined, 'normal');
      doc.text('Photos Submitted', rightCol + 3, y + 5);
      doc.setTextColor(17, 24, 39);
      doc.setFontSize(11);
      doc.setFont(undefined, 'bold');
      doc.text(globalPhotoCount + ' Photo(s)', rightCol + 3, y + 10);
      
      y += boxHeight + 15;

      // ============ CLAIM STATUS SECTION ============
      doc.setTextColor(30, 58, 138);
      doc.setFontSize(16);
      doc.setFont(undefined, 'bold');
      doc.text('PROCESSING STATUS', 20, y);
      
      y += 2;
      doc.setDrawColor(59, 130, 246);
      doc.line(20, y, 90, y);
      
      y += 10;
      
      // Status items
      const statuses = [
        { label: 'Claim Submitted', status: 'Completed', color: [34, 197, 94] },
        { label: 'Document Verification', status: 'In Progress', color: [234, 179, 8] },
        { label: 'Assessment Review', status: 'Pending', color: [156, 163, 175] },
        { label: 'Approval', status: 'Pending', color: [156, 163, 175] }
      ];
      
      statuses.forEach((item, index) => {
        // Status dot
        doc.setFillColor(...item.color);
        doc.circle(25, y + 3, 2.5, 'F');
        
        // Status label
        doc.setTextColor(17, 24, 39);
        doc.setFontSize(11);
        doc.setFont(undefined, 'normal');
        doc.text(item.label, 32, y + 5);
        
        // Status badge
        doc.setFillColor(...item.color);
        doc.roundedRect(pageWidth - 60, y, 40, 7, 2, 2, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(9);
        doc.setFont(undefined, 'bold');
        doc.text(item.status, pageWidth - 40, y + 5, { align: 'center' });
        
        y += 12;
        
        // Connecting line (except for last item)
        if (index < statuses.length - 1) {
          doc.setDrawColor(209, 213, 219);
          doc.setLineWidth(0.3);
          doc.line(25, y - 7, 25, y + 1);
        }
      });
      
      y += 10;

      // ============ IMPORTANT INFORMATION BOX ============
      doc.setFillColor(254, 243, 199);
      doc.roundedRect(20, y, pageWidth - 40, 50, 3, 3, 'F');
      
      doc.setDrawColor(245, 158, 11);
      doc.setLineWidth(0.5);
      doc.roundedRect(20, y, pageWidth - 40, 50, 3, 3, 'S');
      
      doc.setTextColor(146, 64, 14);
      doc.setFontSize(12);
      doc.setFont(undefined, 'bold');
      doc.text('⚠ IMPORTANT INFORMATION', 25, y + 8);
      
      doc.setTextColor(120, 53, 15);
      doc.setFontSize(9);
      doc.setFont(undefined, 'normal');
      const importantInfo = [
        '• Keep this document safe for future reference',
        '• Processing typically takes 7-14 business days',
        '• You will be contacted for any additional documents',
        '• Track your claim status using ID: ' + globalClaimId
      ];
      
      let infoY = y + 16;
      importantInfo.forEach(info => {
        doc.text(info, 25, infoY);
        infoY += 6;
      });
      
      y += 60;

      // ============ NEXT STEPS SECTION ============
      doc.setTextColor(30, 58, 138);
      doc.setFontSize(14);
      doc.setFont(undefined, 'bold');
      doc.text('NEXT STEPS', 20, y);
      
      y += 2;
      doc.setDrawColor(59, 130, 246);
      doc.line(20, y, 55, y);
      
      y += 8;
      
      const nextSteps = [
        '1. Our team will verify your submitted documents within 2-3 business days',
        '2. A vehicle assessment inspector will contact you to schedule inspection',
        '3. Once approved, claim amount will be processed within 5-7 business days',
        '4. You can track status anytime using your Claim ID on our website'
      ];
      
      doc.setTextColor(55, 65, 81);
      doc.setFontSize(9);
      doc.setFont(undefined, 'normal');
      nextSteps.forEach(step => {
        const lines = doc.splitTextToSize(step, pageWidth - 50);
        lines.forEach(line => {
          doc.text(line, 25, y);
          y += 5;
        });
        y += 2;
      });

      // ============ FOOTER ============
      y = pageHeight - 30;
      
      doc.setDrawColor(209, 213, 219);
      doc.setLineWidth(0.3);
      doc.line(20, y, pageWidth - 20, y);
      
      y += 8;
      
      doc.setTextColor(107, 114, 128);
      doc.setFontSize(8);
      doc.setFont(undefined, 'normal');
      doc.text('Legal Assist - Vehicle Insurance Division', pageWidth / 2, y, { align: 'center' });
      
      y += 5;
      doc.text('Email: claims@legalassist.com | Phone: +91-8897752518', pageWidth / 2, y, { align: 'center' });
      
      y += 5;
      doc.setFontSize(7);
      doc.text('Generated on: ' + new Date().toLocaleString('en-GB'), pageWidth / 2, y, { align: 'center' });

      // Save PDF
      doc.save('Insurance_Claim_' + globalClaimId + '.pdf');
    }
  </script>

</body>
</html>