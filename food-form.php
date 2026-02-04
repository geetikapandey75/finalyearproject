<?php
// DATABASE CONNECTION
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) die("Database connection failed");

// APPLY FOR LICENCE
if ($_POST['action'] === 'apply') {
    $full_name = $_POST['full_name'];
    $business_name = $_POST['business_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone_number'];
    $application_id = "FSSAI" . date("Y") . rand(10000, 99999);

    $stmt = $conn->prepare("INSERT INTO food_licence_applications (application_id, full_name, business_name, email, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $application_id, $full_name, $business_name, $email, $phone);

    if ($stmt->execute()) {
        renderSuccessPage($application_id, $full_name, $business_name, $email, $phone);
    } else {
        renderErrorPage();
    }
    exit;
}

// TRACK STATUS - Auto-progressing based on 7-day intervals
if ($_POST['action'] === 'status') {
    $application_id = $_POST['application_id'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("SELECT * FROM food_licence_applications WHERE application_id = ? OR phone = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("ss", $application_id, $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        renderStatusPage($row);
    } else {
        renderNotFoundPage();
    }
    exit;
}

// SUCCESS PAGE FUNCTION
function renderSuccessPage($app_id, $name, $business, $email, $phone) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Successful | Legal Assist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes checkmark { 0% { stroke-dashoffset: 100; } 100% { stroke-dashoffset: 0; } }
        @keyframes scaleIn { from { transform: scale(0); } to { transform: scale(1); } }
        .animate-slide-up { animation: slideUp 0.6s ease-out; }
        .animate-scale { animation: scaleIn 0.5s ease-out; }
        .checkmark { stroke-dasharray: 100; animation: checkmark 0.8s ease-out 0.3s forwards; }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white rounded-3xl shadow-2xl p-10 animate-slide-up">
        <div class="flex justify-center mb-6">
            <div class="w-24 h-24 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center animate-scale shadow-xl">
                <svg class="w-16 h-16" viewBox="0 0 52 52">
                    <circle cx="26" cy="26" r="24" fill="none" stroke="white" stroke-width="3"/>
                    <path class="checkmark" fill="none" stroke="white" stroke-width="4" stroke-linecap="round" d="M14 27 l8 8 16-16"/>
                </svg>
            </div>
        </div>

        <h2 class="text-4xl font-extrabold text-center mb-3 bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
            Application Submitted Successfully!
        </h2>
        <p class="text-center text-gray-600 mb-8 text-lg">Your food licence application has been received and is now being processed.</p>

        <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-2xl p-8 mb-8 text-white shadow-xl">
            <p class="text-green-100 text-sm font-semibold mb-2 uppercase tracking-wide">Your Application ID</p>
            <div class="flex items-center justify-between bg-white bg-opacity-20 backdrop-blur-sm rounded-xl p-4 border border-white border-opacity-30">
                <span class="text-3xl font-bold tracking-wider"><?php echo $app_id; ?></span>
                <button onclick="navigator.clipboard.writeText('<?php echo $app_id; ?>').then(() => { this.innerText='Copied!'; setTimeout(() => this.innerText='Copy', 2000); })" 
                        class="bg-white text-green-600 px-4 py-2 rounded-lg font-semibold hover:bg-green-50 transition-all duration-300 active:scale-95">
                    Copy
                </button>
            </div>
            <p class="text-green-100 text-sm mt-3">⚠️ Please save this ID to track your application status</p>
        </div>

        <div class="bg-gray-50 rounded-2xl p-6 mb-8 border-2 border-gray-200">
            <h3 class="font-bold text-lg mb-4 text-gray-800">Application Details</h3>
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Applicant Name:</span>
                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($name); ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Business Name:</span>
                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($business); ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Email:</span>
                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($email); ?></span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-600">Phone:</span>
                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($phone); ?></span>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 border-2 border-blue-200 rounded-2xl p-6 mb-8">
            <h3 class="font-bold text-lg mb-3 text-blue-800">📋 What Happens Next?</h3>
            <ol class="space-y-2 text-gray-700">
                <li class="flex items-start gap-3">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">1</span>
                    <span>Application review (Days 0-7)</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">2</span>
                    <span>Document verification (Days 7-14)</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">3</span>
                    <span>Final inspection (Days 14-21)</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">4</span>
                    <span>Licence issued (Day 28)</span>
                </li>
            </ol>
        </div>

        <div class="flex flex-col sm:flex-row gap-4">
            <a href="food.html" class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 py-4 rounded-xl font-bold text-center hover:from-green-500 hover:to-emerald-500 transition-all duration-300 shadow-lg hover:shadow-xl">
                ← Back to Home
            </a>
            <a href="food.html#track-status" class="flex-1 bg-blue-600 text-white px-6 py-4 rounded-xl font-bold text-center hover:bg-blue-500 transition-all duration-300 shadow-lg hover:shadow-xl">
                Track Status →
            </a>
        </div>
    </div>
</body>
</html>
<?php
}

// STATUS PAGE FUNCTION - Auto-progresses every 7 days
function renderStatusPage($row) {
    $created_date = new DateTime($row['created_at']);
    $current_date = new DateTime();
    $days_passed = $current_date->diff($created_date)->days;
    
    // Auto-progressing stages (7-day intervals)
    $stages = [
        ['name' => 'Application Submitted', 'days' => 0, 'color' => 'from-blue-500 to-indigo-500', 'icon' => '', 'desc' => 'Your application has been received and queued for review'],
        ['name' => 'Under Review', 'days' => 7, 'color' => 'from-yellow-500 to-orange-500', 'icon' => '', 'desc' => 'Our team is reviewing your application and documents'],
        ['name' => 'Document Verification', 'days' => 14, 'color' => 'from-purple-500 to-pink-500', 'icon' => '', 'desc' => 'Documents are being verified for authenticity'],
        ['name' => 'Final Inspection', 'days' => 21, 'color' => 'from-indigo-500 to-blue-600', 'icon' => '', 'desc' => 'Final inspection and quality checks in progress'],
        ['name' => 'Approved - Licence Issued', 'days' => 28, 'color' => 'from-green-500 to-emerald-600', 'icon' => '✅', 'desc' => 'Congratulations! Your food licence has been approved and issued']
    ];
    
    $current_idx = 0;
    for ($i = count($stages) - 1; $i >= 0; $i--) {
        if ($days_passed >= $stages[$i]['days']) {
            $current_idx = $i;
            break;
        }
    }
    
    $current = $stages[$current_idx];
    $next_days = ($current_idx < count($stages) - 1) ? $stages[$current_idx + 1]['days'] - $days_passed : 0;
    $progress = round(($current_idx / (count($stages) - 1)) * 100);
    $completion_date = clone $created_date;
    $completion_date->modify('+28 days');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status | Legal Assist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .animate-pulse-slow { animation: pulse 2s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl p-10 animate-fade-in">
        
        <div class="text-center mb-8">
            <div class="inline-block w-24 h-24 bg-gradient-to-br <?php echo $current['color']; ?> rounded-full flex items-center justify-center text-5xl mb-4 shadow-xl">
                <?php echo $current['icon']; ?>
            </div>
            <h2 class="text-4xl font-extrabold mb-2 bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                Application Status Tracker
            </h2>
            <p class="text-gray-600">Real-time progress of your food licence application</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gradient-to-r from-gray-800 to-gray-700 rounded-2xl p-6 text-white">
                <p class="text-gray-300 text-sm mb-2">Application ID</p>
                <p class="text-2xl font-bold tracking-wider"><?php echo htmlspecialchars($row['application_id']); ?></p>
            </div>
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-6 text-white">
                <p class="text-blue-100 text-sm mb-2">Days Since Applied</p>
                <p class="text-2xl font-bold"><?php echo $days_passed; ?> Days</p>
            </div>
        </div>

        <div class="bg-gradient-to-br <?php echo $current['color']; ?> rounded-2xl p-8 mb-8 text-white shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex-1">
                    <p class="text-white text-opacity-90 text-sm mb-1 uppercase tracking-wide">Current Status</p>
                    <p class="text-4xl font-bold mb-2"><?php echo $current['name']; ?></p>
                    <p class="text-white text-opacity-90"><?php echo $current['desc']; ?></p>
                </div>
                <div class="text-7xl ml-4 animate-pulse-slow"><?php echo $current['icon']; ?></div>
            </div>
            <?php if ($next_days > 0): ?>
            <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-xl p-4 border border-white border-opacity-30 mt-4">
                <p class="text-sm text-white text-opacity-90 mb-2">Next Update In</p>
                <p class="text-2xl font-bold"><?php echo $next_days; ?> Days</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-gray-50 rounded-2xl p-6 mb-8">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-bold text-lg text-gray-800">Overall Progress</h3>
                <span class="text-2xl font-bold text-blue-600"><?php echo $progress; ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-4 rounded-full shadow-lg transition-all duration-1000" style="width: <?php echo $progress; ?>%"></div>
            </div>
        </div>

        <div class="bg-gray-50 rounded-2xl p-6 mb-8">
            <h3 class="font-bold text-xl mb-6 text-gray-800">Application Timeline</h3>
            <div class="space-y-6">
                <?php foreach ($stages as $idx => $stage): 
                    $is_done = $idx <= $current_idx;
                    $is_current = $idx === $current_idx;
                    $stage_date = clone $created_date;
                    $stage_date->modify('+' . $stage['days'] . ' days');
                ?>
                <div class="flex items-start gap-4 relative <?php echo $is_done ? 'opacity-100' : 'opacity-40'; ?>">
                    <?php if ($idx < count($stages) - 1): ?>
                    <div class="absolute left-5 top-12 w-0.5 h-full <?php echo $is_done ? 'bg-green-500' : 'bg-gray-300'; ?>"></div>
                    <?php endif; ?>
                    
                    <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 text-white font-bold shadow-lg
                                <?php echo $is_done ? 'bg-gradient-to-br from-green-500 to-emerald-600' : 'bg-gray-400'; ?>
                                <?php echo $is_current ? 'ring-4 ring-blue-300 animate-pulse-slow' : ''; ?>">
                        <?php echo $is_done ? '✓' : ($idx + 1); ?>
                    </div>
                    
                    <div class="flex-1 <?php echo $is_current ? 'bg-blue-50 border-2 border-blue-300' : 'bg-white border border-gray-200'; ?> rounded-xl p-4 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-bold text-lg text-gray-800 mb-1"><?php echo $stage['name']; ?></p>
                                <p class="text-sm text-gray-600 mb-2"><?php echo $stage['desc']; ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php if ($is_done && !$is_current): ?>
                                        ✓ Completed on <?php echo $stage_date->format('M d, Y'); ?>
                                    <?php elseif ($is_current): ?>
                                        🔄 In Progress (Day <?php echo $days_passed; ?>)
                                    <?php else: ?>
                                        Expected: <?php echo $stage_date->format('M d, Y'); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="text-3xl ml-4"><?php echo $stage['icon']; ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-2xl p-6 mb-8 border-2 border-gray-200">
            <h3 class="font-bold text-lg mb-4 text-gray-800">Applicant Information</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Full Name</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['full_name']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Business Name</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['business_name']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Application Date</p>
                    <p class="font-semibold text-gray-800"><?php echo $created_date->format('F d, Y h:i A'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Expected Completion</p>
                    <p class="font-semibold text-gray-800"><?php echo $completion_date->format('F d, Y'); ?></p>
                </div>
            </div>
        </div>

        <?php if ($current_idx < count($stages) - 1): ?>
        <div class="bg-yellow-50 border-2 border-yellow-200 rounded-2xl p-6 mb-8">
            <h4 class="font-bold text-yellow-800 mb-2">ℹ️ What to Expect Next</h4>
            <p class="text-yellow-800">Your application will automatically progress to the next stage in <?php echo $next_days; ?> days. You'll receive an email notification when the status changes.</p>
        </div>
        <?php else: ?>
        <div class="bg-green-50 border-2 border-green-200 rounded-2xl p-6 mb-8">
            <h4 class="font-bold text-green-800 mb-2 text-xl">🎉 Congratulations!</h4>
            <p class="text-green-800">Your food licence has been approved and issued! Check your email for the digital copy and collection instructions.</p>
        </div>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row gap-4">
            <a href="food.html" class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4 rounded-xl font-bold text-center hover:from-blue-500 hover:to-indigo-500 transition-all duration-300 shadow-lg hover:shadow-xl">
                ← Back to Home
            </a>
            <button onclick="window.print()" class="flex-1 bg-gray-700 text-white px-6 py-4 rounded-xl font-bold hover:bg-gray-600 transition-all duration-300 shadow-lg hover:shadow-xl">
                Print Status
            </button>
        </div>
    </div>
</body>
</html>
<?php
}

// ERROR PAGE FUNCTION
function renderErrorPage() {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Error | Legal Assist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg w-full bg-white rounded-3xl shadow-2xl p-10 text-center">
        <div class="w-20 h-20 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-red-600 mb-4">Something Went Wrong</h2>
        <p class="text-gray-600 mb-8">We couldn't process your application. Please try again or contact support.</p>
        <a href="food.html" class="inline-block bg-red-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-red-500 transition-all">Try Again</a>
    </div>
</body>
</html>
<?php
}

// NOT FOUND PAGE FUNCTION
function renderNotFoundPage() {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Not Found | Legal Assist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg w-full bg-white rounded-3xl shadow-2xl p-10 text-center">
        <div class="w-24 h-24 bg-gradient-to-br from-red-500 to-orange-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl">
            <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-gray-800 mb-3">Application Not Found</h2>
        <p class="text-gray-600 mb-8">We couldn't find an application with the provided details. Please check your Application ID and phone number.</p>
        
        <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-6 mb-8 text-left">
            <h4 class="font-bold text-red-800 mb-3">⚠️ Common Issues:</h4>
            <ul class="space-y-2 text-sm text-red-800">
                <li>• Application ID format: FSSAI2025XXXXX</li>
                <li>• Phone number must match registration</li>
                <li>• Wait 24 hours if just submitted</li>
            </ul>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="food.html#track-status" class="flex-1 bg-gradient-to-r from-red-600 to-orange-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-red-500 hover:to-orange-500 transition-all shadow-lg text-center">
                Try Again
            </a>
            <a href="food.html" class="flex-1 bg-gray-700 text-white px-6 py-3 rounded-xl font-semibold hover:bg-gray-600 transition-all shadow-lg text-center">
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
<?php
}
?>