<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'legal_assist');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Database connection failed.");
    }
    return $conn;
}

session_start();

// Check if showing success page
if(isset($_GET['success']) && $_GET['success'] === '1' && isset($_SESSION['app_data'])) {
    displaySuccessPage();
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if($action === 'status') {
    header('Content-Type: application/json');
}

switch($action) {
    case 'apply':
        handleApplication();
        break;
    case 'status':
        checkStatus();
        break;
}

function handleApplication() {
    $conn = getDBConnection();
    
    // Generate Application ID
    $appId = 'BL' . date('Y') . '-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
    $expectedDate = date('Y-m-d', strtotime('+15 weekdays'));
    
    $stmt = $conn->prepare("INSERT INTO business_applications (application_id, full_name, email, phone, 
                           business_name, business_type, business_size, business_address, 
                           pan_number, expected_completion) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssssssssss", 
        $appId,
        $_POST['full_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['business_name'],
        $_POST['business_type'],
        $_POST['business_size'],
        $_POST['business_address'],
        $_POST['pan_number'],
        $expectedDate
    );
    
    if($stmt->execute()) {
        $_SESSION['app_data'] = [
            'application_id' => $appId,
            'applicant_name' => $_POST['full_name'],
            'applicant_email' => $_POST['email'],
            'business_name' => $_POST['business_name'],
            'phone' => $_POST['phone'],
            'expected_date' => date('d M Y', strtotime($expectedDate)),
            'submit_date' => date('d M Y')
        ];
        
        header("Location: business-form.php?success=1");
        exit();
    } else {
        die("Error: Failed to submit application.");
    }
}

function checkStatus() {
    $conn = getDBConnection();
    
    $appId = $_POST['application_id'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    
    if(empty($appId) || empty($mobile)) {
        echo json_encode(['success' => false, 'message' => 'Application ID and mobile required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT application_id, full_name, status, 
                           DATE_FORMAT(submit_date, '%d %b %Y') as submit_date,
                           DATE_FORMAT(expected_completion, '%d %b %Y') as expected_date
                           FROM business_applications 
                           WHERE application_id = ? AND phone LIKE ?");
    
    $phonePattern = '%' . substr($mobile, -4);
    $stmt->bind_param("ss", $appId, $phonePattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Application not found']);
    }
}

function displaySuccessPage() {
    $data = $_SESSION['app_data'];
    unset($_SESSION['app_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Application Submitted | Legal Assist</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<nav class="bg-gray-800 text-white p-4">
  <div class="container mx-auto">
    <h1 class="text-xl font-bold">Legal Assist</h1>
  </div>
</nav>

<div class="container mx-auto px-4 py-16">
  <div class="max-w-3xl mx-auto">
    
    <div class="bg-white shadow-2xl rounded-lg overflow-hidden">
      
      <!-- Success Header -->
      <div class="bg-gradient-to-r from-green-600 to-green-500 text-white p-8 text-center">
        <div class="text-6xl mb-4">✅</div>
        <h1 class="text-3xl font-bold mb-2">Application Submitted Successfully!</h1>
        <p class="text-green-100">Your business license application is being processed</p>
      </div>
      
      <!-- Application Details Card -->
      <div class="p-8">
        
        <!-- Application ID Highlight -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-lg mb-6 text-center">
          <p class="text-sm uppercase tracking-wide mb-2">Your Application ID</p>
          <div class="flex items-center justify-center gap-4">
            <p class="text-4xl font-bold tracking-wider" id="appIdDisplay"><?php echo htmlspecialchars($data['application_id']); ?></p>
            <button onclick="copyAppId()" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
              Copy
            </button>
          </div>
          <p class="text-blue-100 text-sm mt-3">⚠️ Save this ID to track your application</p>
        </div>

        <!-- Details Grid -->
        <div class="bg-gray-50 rounded-lg p-6 mb-6">
          <h2 class="font-bold text-xl mb-4">📋 Application Details</h2>
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <p class="text-gray-600 text-sm">Applicant Name</p>
              <p class="font-semibold"><?php echo htmlspecialchars($data['applicant_name']); ?></p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Business Name</p>
              <p class="font-semibold"><?php echo htmlspecialchars($data['business_name']); ?></p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Email</p>
              <p class="font-semibold"><?php echo htmlspecialchars($data['applicant_email']); ?></p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Phone</p>
              <p class="font-semibold"><?php echo htmlspecialchars($data['phone']); ?></p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Submitted On</p>
              <p class="font-semibold"><?php echo htmlspecialchars($data['submit_date']); ?></p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Expected Completion</p>
              <p class="font-semibold text-green-600"><?php echo htmlspecialchars($data['expected_date']); ?></p>
            </div>
          </div>
        </div>

       

        <!-- Actions -->
        <div class="grid md:grid-cols-2 gap-4 mb-6">
          <a href="business.html#statusCheck" class="bg-blue-600 text-white px-6 py-4 rounded-lg font-semibold text-center hover:bg-blue-500 transition">
            🔍 Track Status
          </a>
          <a href="business.html" class="bg-gray-600 text-white px-6 py-4 rounded-lg font-semibold text-center hover:bg-gray-500 transition">
            🏠 Back to Home
          </a>
        </div>

        <div class="text-center">
          <button onclick="window.print()" class="text-blue-600 hover:text-blue-800 font-semibold">
            🖨️ Print This Page
          </button>
        </div>
      </div>
    </div>

    <!-- Support -->
    <div class="mt-8 bg-white shadow rounded-lg p-6">
      <h3 class="font-bold text-lg mb-3">Need Help?</h3>
      <div class="grid md:grid-cols-3 gap-4 text-center">
        <div>
          <div class="text-2xl mb-2">📧</div>
          <p class="text-sm font-semibold">Email</p>
          <p class="text-sm text-blue-600">support@legalassist.com</p>
        </div>
        <div>
          <div class="text-2xl mb-2">📞</div>
          <p class="text-sm font-semibold">Phone</p>
          <p class="text-sm text-blue-600">1800-123-4567</p>
        </div>
        <div>
          <div class="text-2xl mb-2">⏰</div>
          <p class="text-sm font-semibold">Hours</p>
          <p class="text-sm text-gray-600">Mon-Fri: 9 AM - 6 PM</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function copyAppId() {
  const appId = document.getElementById('appIdDisplay').textContent;
  navigator.clipboard.writeText(appId).then(() => {
    alert('Application ID copied: ' + appId);
  });
}
</script>

<style>
@media print {
  nav, button, a { display: none; }
}
</style>

</body>
</html>
<?php
}
?>