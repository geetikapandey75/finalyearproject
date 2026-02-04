<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    $_SESSION['error'] = "Please login to access this service.";
    header("Location: home_page.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Database connection
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$success_message = "";
$error_message = "";
$case_details = null;

// Handle Missing Person Report Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_missing'])) {
    $full_name = $_POST['full_name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $last_seen_date = $_POST['last_seen_date'];
    $last_seen_location = $_POST['last_seen_location'];
    $description = $_POST['description'] ?? '';
    $contact = $_POST['contact'];
    
    // Upload image
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $photoName = time() . "_" . basename($_FILES['photo']['name']);
    move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName);
    
    // Generate unique Case ID
    $year = date("Y");
    do {
        $random_number = rand(1000, 9999);
        $case_id = "MP-$year-$random_number";
        $check = $conn->query("SELECT id FROM missing_persons WHERE case_id='$case_id'");
    } while ($check->num_rows > 0);
    
    // Insert into database with user_id
    $stmt = $conn->prepare(
      "INSERT INTO missing_persons
      (user_id, case_id, full_name, age, gender, last_seen_date, last_seen_location, description, photo, contact)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
      "isssisssss",
      $user_id, $case_id, $full_name, $age, $gender, $last_seen_date,
      $last_seen_location, $description, $photoName, $contact
    );
    
    if ($stmt->execute()) {
        $success_message = "Report Submitted Successfully! Your Case ID is: <strong>$case_id</strong>. Keep this ID safe to track your case status.";
    } else {
        $error_message = "Error submitting report: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Case Tracking
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['track_case'])) {
    $case_id = trim($_POST['case_id']);
    
    // Fetch case details ONLY if it belongs to the logged-in user
    $stmt = $conn->prepare("SELECT full_name, status_update, last_seen_date, last_seen_location FROM missing_persons WHERE case_id=? AND user_id=?");
    $stmt->bind_param("si", $case_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $case_details = $result->fetch_assoc();
    } else {
        $error_message = "No case found with this ID, or you don't have permission to view it.";
    }
    $stmt->close();
}

// Handle Volunteer Registration
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['join_volunteer'])) {
    $volunteer_name = trim($_POST['volunteer_name']);
    $location = trim($_POST['location']);
    $phone_number = trim($_POST['phone_number']);
    $alert_radius = $_POST['alert_radius'];
    
    // Validation
    if (empty($volunteer_name) || empty($location) || empty($phone_number)) {
        $error_message = "All volunteer fields are required!";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone_number)) {
        $error_message = "Please enter a valid 10-digit phone number!";
    } else {
        // Check if user already registered as volunteer
        $check_sql = "SELECT id FROM volunteers_network WHERE user_id = ? AND status = 'active'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "You are already registered as a volunteer!";
        } else {
            // Insert volunteer registration
            $sql = "INSERT INTO volunteers_network (user_id, full_name, location, phone_number, alert_radius) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $user_id, $volunteer_name, $location, $phone_number, $alert_radius);
            
            if ($stmt->execute()) {
                // SUCCESS - Redirect to WhatsApp Group
                // REPLACE THIS URL WITH YOUR ACTUAL WHATSAPP GROUP INVITE LINK
                $whatsapp_group_url = "https://chat.whatsapp.com/YOUR_GROUP_INVITE_CODE_HERE";
                
                // Alternative: If you want to use WhatsApp API to send a message first
                // $whatsapp_message = urlencode("Hi, I just registered as a volunteer for the Missing Person Search Network!");
                // $whatsapp_group_url = "https://wa.me/8897752519?text=" . $whatsapp_message;
                
                $stmt->close();
                $check_stmt->close();
                $conn->close();
                
                // JavaScript redirect with delay
                echo '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Redirecting to WhatsApp Group</title>
                    <script src="https://cdn.tailwindcss.com"></script>
                </head>
                <body class="bg-gray-100 flex items-center justify-center min-h-screen">
                    <div class="bg-white p-8 rounded-lg shadow-xl max-w-md text-center">
                        <div class="mb-6">
                            <svg class="w-20 h-20 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">Registration Successful!</h2>
                        <p class="text-gray-600 mb-6">You have been registered as a volunteer. Redirecting you to our WhatsApp group...</p>
                        <div class="flex items-center justify-center space-x-2">
                            <div class="animate-bounce bg-green-500 rounded-full w-3 h-3"></div>
                            <div class="animate-bounce bg-green-500 rounded-full w-3 h-3" style="animation-delay: 0.2s"></div>
                            <div class="animate-bounce bg-green-500 rounded-full w-3 h-3" style="animation-delay: 0.4s"></div>
                        </div>
                        <p class="text-sm text-gray-500 mt-6">If not redirected, <a href="' . $whatsapp_group_url . '" class="text-blue-600 font-semibold hover:underline">click here</a></p>
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = "' . $whatsapp_group_url . '";
                        }, 3000);
                    </script>
                </body>
                </html>';
                exit();
            } else {
                $error_message = "Error registering as volunteer: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Missing Person Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="m-0 font-sans">

  <!-- Success/Error Messages -->
  <?php if ($success_message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 p-4 rounded-lg max-w-3xl mx-auto mt-6">
      <strong>Success!</strong> <?php echo $success_message; ?>
    </div>
  <?php endif; ?>

  <?php if ($error_message): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded-lg max-w-3xl mx-auto mt-6">
      <strong>Error!</strong> <?php echo $error_message; ?>
    </div>
  <?php endif; ?>

  <!-- HERO HEADER -->
  <header class="bg-[#10367d] h-[320px] flex items-center justify-center text-center text-white">
    <div class="w-full h-full p-5 flex flex-col justify-center items-center">
      <h1 class="text-4xl font-bold mb-2">Missing Person Portal</h1>
      <p class="text-lg mb-5">Help us reunite families and loved ones.</p>
      <p class="text-sm mb-5">Logged in as: <strong><?php echo htmlspecialchars($user_name); ?></strong></p>

      <div class="flex gap-4">
        <a href="#missing-form" 
           class="bg-[#ffcc00] text-[#1a237e] font-bold px-6 py-2 rounded-full hover:bg-[#ffb300] transition">
           Report Missing Person
        </a>

        <a href="#emergency" 
           class="bg-red-600 text-white font-bold px-6 py-2 rounded-full hover:bg-red-700 transition">
           ⚠ Emergency Help
        </a>
      </div>
    </div>
  </header>

  <!-- FORM SECTION -->
  <section id="missing-form" class="p-8 max-w-3xl mx-auto">
    <h2 class="text-3xl font-bold mb-6 text-[#10367d]">Report a Missing Person</h2>

    <form action="missing.php" method="POST" enctype="multipart/form-data"
          class="space-y-4 bg-gray-100 p-6 rounded-lg shadow">

      <div>
        <label class="font-semibold">Full Name</label>
        <input type="text" name="full_name" required class="w-full p-2 border rounded">
      </div>

      <div>
        <label class="font-semibold">Age</label>
        <input type="number" name="age" required class="w-full p-2 border rounded">
      </div>

      <div>
        <label class="font-semibold">Gender</label>
        <select name="gender" required class="w-full p-2 border rounded">
          <option value="">Select</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>
      </div>

      <div>
        <label class="font-semibold">Date Last Seen</label>
        <input type="date" name="last_seen_date" required class="w-full p-2 border rounded">
      </div>

      <div>
        <label class="font-semibold">Last Seen Location</label>
        <input type="text" name="last_seen_location" required class="w-full p-2 border rounded">
      </div>

      <div>
        <label class="font-semibold">Description / Identifying Marks</label>
        <textarea name="description" rows="4" class="w-full p-2 border rounded"></textarea>
      </div>

      <div>
        <label class="font-semibold">Upload Recent Photo</label>
        <input type="file" name="photo" required class="w-full">
      </div>

      <div>
        <label class="font-semibold">Your Contact Number</label>
        <input type="tel" name="contact" required class="w-full p-2 border rounded">
      </div>

      <button type="submit" name="submit_missing"
              class="bg-[#10367d] text-white w-full py-2 rounded font-bold hover:bg-blue-900 transition">
        Submit Report
      </button>

    </form>
  </section>

  <!-- LIVE STATUS TRACKING -->
  <section class="p-8 bg-blue-50">
    <div class="max-w-3xl mx-auto">
      <h2 class="text-3xl font-bold text-blue-900 mb-4">Track Your Case</h2>

      <form class="space-y-4 bg-gray-100 p-6 rounded-lg shadow" method="POST" action="missing.php">
        <label class="font-semibold" for="case_id">Enter Your Case ID</label>
        <input type="text" id="case_id" name="case_id" class="w-full p-2 border rounded" placeholder="MP-2025-1234" required>

        <button type="submit" name="track_case" class="bg-[#10367d] text-white w-full py-2 rounded font-bold hover:bg-blue-900 transition">
          Track Status
        </button>
      </form>

      <!-- Case Details Result -->
      <?php if ($case_details): ?>
        <div class="bg-white p-5 rounded-lg shadow mt-4 border">
          <h3 class="font-bold text-lg mb-2 text-[#10367d]">Case Details</h3>
          <p><strong>Name:</strong> <?php echo htmlspecialchars($case_details['full_name']); ?></p>
          <p><strong>Status:</strong> <?php echo htmlspecialchars($case_details['status_update']); ?></p>
          <p><strong>Last Seen Date:</strong> <?php echo htmlspecialchars($case_details['last_seen_date']); ?></p>
          <p><strong>Last Seen Location:</strong> <?php echo htmlspecialchars($case_details['last_seen_location']); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- MAP-BASED SIGHTINGS -->
  <section class="p-8 max-w-5xl mx-auto">
    <h2 class="text-3xl font-bold text-[#10367d] mb-3">Map-Based Sightings</h2>
    <p class="mb-3 text-gray-700">View real-time sightings reported by the public.</p>
    <div id="sightingsMap" class="w-full h-80 rounded shadow border"></div>
  </section>

  <!-- HEATMAP -->
  <section class="p-8 max-w-5xl mx-auto">
    <h2 class="text-3xl font-bold text-[#10367d] mb-3">Heatmap of Missing Cases</h2>
    <p class="mb-3 text-gray-700">Hotspots of missing persons and safe/unsafe zones.</p>
    <div id="heatmap" class="w-full h-80 rounded shadow border"></div>
  </section>

  <!-- VOLUNTEER NETWORK -->
  <section id="volunteer" class="p-8 max-w-4xl mx-auto">
    <h2 class="text-3xl font-bold text-[#10367d] mb-4">Volunteer Search Network</h2>
    <p class="text-gray-700 mb-4">
      Join the community and get notified when a missing person is reported near your area.
    </p>

    <form action="missing.php" method="POST" class="bg-gray-100 p-6 rounded-lg shadow space-y-4">
      <div>
        <label class="font-semibold">Full Name</label>
        <input type="text" name="volunteer_name" required class="w-full p-2 border rounded">
      </div>

      <div>
        <label class="font-semibold">Your Location</label>
        <input type="text" name="location" placeholder="City / Pincode" required class="w-full p-2 border rounded">
      </div>

      <div>
        <label class="font-semibold">Phone Number</label>
        <input type="tel" name="phone_number" pattern="[0-9]{10}" maxlength="10" required class="w-full p-2 border rounded" placeholder="10-digit number">
      </div>

      <div>
        <label class="font-semibold">Receive Alerts Within</label>
        <select name="alert_radius" required class="w-full p-2 border rounded">
          <option value="1 km">1 km</option>
          <option value="5 km">5 km</option>
          <option value="10 km">10 km</option>
          <option value="20 km">20 km</option>
        </select>
      </div>

      <button type="submit" name="join_volunteer" class="bg-[#10367d] text-white w-full py-2 rounded font-bold hover:bg-blue-900 transition">
        Join Volunteer Network
      </button>
    </form>
  </section>

  <!-- COMMUNITY FORUMS -->
  <section class="p-8 max-w-5xl mx-auto">
    <h2 class="text-3xl font-bold text-[#10367d] mb-4">Community Forums</h2>
    <p class="text-gray-700 mb-4">Connect with families, volunteers, and support groups.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white p-5 rounded-lg shadow border">
        <h3 class="font-bold text-xl mb-2">Families of Missing Individuals</h3>
        <p class="mb-3">Share updates, photos, tips, and receive support from the community.</p>
        <a class="text-blue-700 font-semibold hover:underline" href="#">Enter Forum →</a>
      </div>

      <div class="bg-white p-5 rounded-lg shadow border">
        <h3 class="font-bold text-xl mb-2">Volunteer Discussions</h3>
        <p class="mb-3">Coordinate searches, share sightings, and organize group efforts.</p>
        <a class="text-blue-700 font-semibold hover:underline" href="#">Enter Forum →</a>
      </div>
    </div>
  </section>

  <!-- SOCIAL MEDIA SHARE -->
  <section class="p-8 max-w-4xl mx-auto">
    <h2 class="text-3xl font-bold text-[#10367d] mb-4">Share Missing Person Poster</h2>
    <p class="text-gray-700 mb-4">Help spread awareness by sharing with one click.</p>

    <div class="flex flex-wrap gap-4">
      <a href="https://wa.me/?text=Please%20help%20find%20this%20missing%20person%20-%20[LINK]" 
         class="bg-green-500 text-white px-4 py-2 rounded-full hover:bg-green-600">
         WhatsApp
      </a>

      <a href="#" class="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700">
        Facebook
      </a>

      <a href="#" class="bg-[#E4405F] text-white px-4 py-2 rounded-full hover:bg-pink-600">
        Instagram
      </a>

      <a href="#" class="bg-sky-500 text-white px-4 py-2 rounded-full hover:bg-sky-600">
        Twitter
      </a>
    </div>
  </section>

  <!-- WHAT TO DO FIRST -->
  <section class="p-8 bg-gray-100">
    <div class="max-w-4xl mx-auto">
      <h2 class="text-3xl font-bold text-[#10367d] mb-4">What To Do First</h2>
      <p class="text-gray-700 mb-4">If someone you know goes missing, follow these essential steps:</p>

      <ol class="list-decimal pl-6 space-y-3 text-gray-800">
        <li>Call 112 immediately and report the missing person.</li>
        <li>Gather a recent photo, description, and last known location.</li>
        <li>Notify close friends, neighbors, and relatives.</li>
        <li>Submit a missing report on this portal.</li>
        <li>Share the poster on WhatsApp and social media.</li>
        <li>Check CCTV in nearby areas if possible.</li>
        <li>Join search teams and track live updates from volunteers.</li>
      </ol>
    </div>
  </section>

  <!-- SAFETY TIPS -->
  <section class="p-8 bg-gray-100">
    <div class="max-w-4xl mx-auto">
      <h2 class="text-3xl font-bold text-[#10367d] mb-4">Safety Alerts & Tips</h2>

      <ul class="list-disc pl-6 space-y-2 text-gray-800">
        <li>Share your live location with trusted contacts.</li>
        <li>Avoid isolated areas at night.</li>
        <li>Use emergency SOS on your phone.</li>
        <li>Teach children how to identify safe adults.</li>
        <li>Report suspicious activity immediately.</li>
      </ul>
    </div>
  </section>

  <!-- SUCCESS STORIES -->
  <section class="p-8 max-w-5xl mx-auto">
    <h2 class="text-3xl font-bold text-[#10367d] mb-4">Success Stories</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="p-5 bg-white rounded-lg shadow border">
        <h3 class="font-bold text-xl mb-2">Reunited After 3 Weeks</h3>
        <p>A 14-year-old girl from Hyderabad was safely found with the help of public reports.</p>
      </div>

      <div class="p-5 bg-white rounded-lg shadow border">
        <h3 class="font-bold text-xl mb-2">Community Search Saved a Life</h3>
        <p>A missing senior citizen was located quickly due to quick public response.</p>
      </div>
    </div>
  </section>

  <!-- Floating WhatsApp Icon -->
  <a href="https://wa.me/8897752519" target="_blank"
     class="fixed bottom-5 right-5 bg-green-500 hover:bg-green-600 text-white p-4 rounded-full shadow-lg flex items-center justify-center transition-all duration-300">
     <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-7 h-7" viewBox="0 0 16 16">
       <path d="M13.601 2.326A7.854 7.854 0 0 0 8.002 0C3.584 0 .01 3.584.01 8.002c0 1.41.367 2.79 1.064 4.01L0 16l4.114-1.058a7.953 7.953 0 0 0 3.888 1h.004c4.417 0 8.002-3.584 8.002-8.002a7.95 7.95 0 0 0-2.407-5.614zM8.006 14.53h-.003a6.52 6.52 0 0 1-3.324-.91l-.238-.14-2.44.628.65-2.377-.156-.245a6.53 6.53 0 1 1 5.51 3.045zm3.625-4.93c-.198-.099-1.174-.578-1.355-.644-.182-.066-.315-.099-.446.099-.132.198-.513.644-.63.776-.116.132-.232.149-.43.05-.198-.1-.837-.308-1.594-.983-.589-.525-.985-1.175-1.102-1.373-.116-.198-.013-.304.087-.403.089-.089.198-.232.297-.347.099-.116.132-.198.198-.33.066-.132.033-.248-.017-.347-.05-.099-.446-1.075-.611-1.47-.161-.386-.325-.33-.446-.335l-.38-.007c-.132 0-.347.05-.528.248-.182.198-.695.679-.695 1.654 0 .975.712 1.915.811 2.049.099.132 1.402 2.137 3.396 2.997.475.205.845.326 1.133.417.476.151.91.13 1.253.079.383-.057 1.174-.48 1.34-.944.165-.464.165-.863.116-.944-.05-.082-.182-.132-.38-.231z"/>
     </svg>
  </a>

  <!-- EMERGENCY -->
  <section id="emergency" class="p-8 text-center bg-red-50">
    <h2 class="text-3xl font-bold text-red-700 mb-4">Emergency Assistance</h2>
    <p class="mb-4">If someone is in immediate danger, click below:</p>
    <a href="tel:112" class="bg-red-600 text-white font-bold px-8 py-3 rounded-full hover:bg-red-700 transition">
      Call 112 (Emergency)
    </a>
  </section>

  <!-- JavaScript -->
  <script>
    // Map: Sightings
    const sightingsMap = L.map('sightingsMap').setView([17.3850, 78.4867], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(sightingsMap);

    const sightings = [
      { lat: 17.387, lng: 78.491, text: "Sighting near Charminar" },
      { lat: 17.448, lng: 78.392, text: "Reported near Kukatpally" },
      { lat: 17.406, lng: 78.477, text: "Seen near Secunderabad" }
    ];

    sightings.forEach(s => {
      L.marker([s.lat, s.lng]).addTo(sightingsMap).bindPopup(s.text);
    });

    // Heatmap
    const heatMap = L.map('heatmap').setView([17.3850, 78.4867], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(heatMap);

    const hotspots = [[17.39, 78.48], [17.42, 78.45], [17.36, 78.50]];
    hotspots.forEach(point => {
      L.circle(point, {
        radius: 1500,
        color: 'red',
        fillColor: '#f03',
        fillOpacity: 0.4
      }).addTo(heatMap);
    });
  </script>

</body>
</html>