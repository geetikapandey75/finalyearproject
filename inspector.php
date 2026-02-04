<?php
session_start();
$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

/* ✅ MAPPING FUNCTION */
function mapLicenseType($businessType) {
    $map = [
        'retail' => 'business',
        'manufacturing' => 'trade',
        'service' => 'business',
        'food' => 'food',
        'business' => 'business',  // Handle if already mapped
        'trade' => 'trade'         // Handle if already mapped
    ];
    return $map[strtolower($businessType)] ?? 'business';
}

$fees = ['business' => 500, 'food' => 1000, 'trade' => 750, 'driving' => 300, 'retail' => 500, 'manufacturing' => 750, 'service' => 500];

// REAL RAZORPAY CREDENTIALS
$rzp_key = "rzp_test_RuyUcsfbG8XaIT";
$rzp_secret = "fliKTmw84hX8mblSM1CyRQ0D";

function getZoneFromAddress($address) {
    $address = strtolower($address);
    if (strpos($address, 'north') !== false) return 'North';
    if (strpos($address, 'south') !== false) return 'South';
    if (strpos($address, 'east') !== false) return 'East';
    if (strpos($address, 'west') !== false) return 'West';
    if (strpos($address, 'central') !== false) return 'Central';
    return 'Central';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_application') {
        $appId = $_POST['app_id'];

        // Detect license type
        if (str_starts_with($appId, 'BL')) {
            $table = 'business_applications';
            $sql = "SELECT application_id, full_name, phone as mobile, 
                           business_name, business_address, 
                           business_type as license_type 
                    FROM $table WHERE application_id = ?";

        } elseif (str_starts_with($appId, 'FSSAI')) {
            $table = 'food_licence_applications';
            $sql = "SELECT application_id, full_name, phone as mobile, 
                           business_name, 
                           NULL as business_address, 
                           'food' as license_type
                    FROM $table WHERE application_id = ?";

        } elseif (str_starts_with($appId, 'TL')) {
            $table = 'trade_licence_applications';
            $sql = "SELECT application_id, full_name, phone_number as mobile, 
                           business_name, business_address, 
                           business_category as license_type
                    FROM $table WHERE application_id = ?";

        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Application ID']);
            exit;
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $appId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $app = $result->fetch_assoc();
            $app['zone'] = getZoneFromAddress($app['business_address'] ?? '');
            echo json_encode(['success' => true, 'data' => $app]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Application not found']);
        }
        exit;
    }

    if ($action === 'get_available_slots') {
        $license_type_raw = $_POST['license_type'];
        $license_type = mapLicenseType($license_type_raw);
        $zone = $_POST['zone'];
        $date = $_POST['date'];

        // COMPREHENSIVE DEBUG LOGGING
        error_log("=== INSPECTOR SEARCH DEBUG ===");
        error_log("Raw license type: $license_type_raw");
        error_log("Mapped license type: $license_type");
        error_log("Zone: $zone");
        error_log("Date: $date");

        // First, let's check ALL inspectors to see what we have
        $debug_stmt = $conn->prepare("SELECT id, name, zone, license_types FROM inspectors");
        $debug_stmt->execute();
        $all_inspectors = $debug_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        error_log("Total inspectors in DB: " . count($all_inspectors));
        foreach ($all_inspectors as $insp) {
            error_log("Inspector: {$insp['name']}, Zone: {$insp['zone']}, Types: {$insp['license_types']}");
        }

        // Now search with our criteria
        $stmt = $conn->prepare("
            SELECT id, name, zone, license_types FROM inspectors 
            WHERE (zone = ? OR zone = 'Central')
            AND FIND_IN_SET(?, license_types) > 0
        ");

        $stmt->bind_param("ss", $zone, $license_type);
        $stmt->execute();
        $result = $stmt->get_result();

        $ids = [];
        $inspector_details = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['id'];
            $inspector_details[] = $row;
            error_log("MATCHED Inspector: " . $row['name'] . " (ID: " . $row['id'] . ") Zone: " . $row['zone'] . " Types: " . $row['license_types']);
        }

        if (empty($ids)) {
            error_log("❌ NO INSPECTORS FOUND!");
            error_log("Search criteria: zone=$zone, license_type=$license_type");
            
            // Return detailed debug info
            echo json_encode([
                'success' => false,
                'message' => "No inspectors found",
                'debug' => [
                    'zone_searched' => $zone,
                    'license_type_searched' => $license_type,
                    'raw_license_type' => $license_type_raw,
                    'total_inspectors_in_db' => count($all_inspectors),
                    'all_inspectors' => $all_inspectors
                ]
            ]);
            exit;
        }

        error_log("Found " . count($ids) . " inspectors");

        // Get available slots
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT s.id, s.slot_time, s.inspector_id, i.name as inspector_name, i.rating 
                  FROM inspection_slots s 
                  JOIN inspectors i ON s.inspector_id = i.id 
                  WHERE s.inspector_id IN ($placeholders) 
                  AND s.slot_date = ? 
                  AND s.is_booked = 0 
                  ORDER BY s.slot_time";
        
        $stmt = $conn->prepare($query);
        $types = str_repeat('i', count($ids)) . 's';
        $params = array_merge($ids, [$date]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        error_log("Found " . count($slots) . " available slots for date $date");
        
        if (empty($slots)) {
            error_log("⚠️ No slots available for this date");
            echo json_encode([
                'success' => true, 
                'slots' => [],
                'debug' => [
                    'inspectors_found' => count($ids),
                    'inspector_ids' => $ids,
                    'date_searched' => $date
                ]
            ]);
        } else {
            echo json_encode(['success' => true, 'slots' => $slots]);
        }
        exit;
    }
    
    if ($action === 'create_razorpay_order') {
        $amount = floatval($_POST['amount']);
        
        $orderData = [
            'amount' => $amount * 100,
            'currency' => 'INR',
            'receipt' => 'insp_' . time(),
            'notes' => [
                'app_id' => $_POST['app_id'] ?? 'N/A'
            ]
        ];
        
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_USERPWD, $rzp_key . ':' . $rzp_secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            echo json_encode(['error' => curl_error($ch)]);
        } else {
            echo $response;
        }
        
        curl_close($ch);
        exit;
    }
    
    if ($action === 'confirm_booking') {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT is_booked FROM inspection_slots WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $_POST['slot_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Slot not found');
            }
            
            $row = $result->fetch_assoc();
            if ($row['is_booked']) {
                throw new Exception('Slot already booked');
            }
            
            $stmt = $conn->prepare("UPDATE inspection_slots SET is_booked = 1 WHERE id = ?");
            $stmt->bind_param("i", $_POST['slot_id']);
            $stmt->execute();
            
            $stmt = $conn->prepare("INSERT INTO inspections 
                (application_id, license_type, inspector_id, slot_id, inspection_date, 
                 inspection_time, business_address, special_instructions, payment_id, amount_paid) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssiisssssd", 
                $_POST['app_id'], 
                $_POST['license_type'], 
                $_POST['inspector_id'], 
                $_POST['slot_id'],
                $_POST['inspection_date'], 
                $_POST['inspection_time'], 
                $_POST['business_address'],
                $_POST['special_instructions'], 
                $_POST['payment_id'], 
                $_POST['amount']
            );
            $stmt->execute();
            $booking_id = $conn->insert_id;
            
            $stmt = $conn->prepare("UPDATE inspectors SET total_inspections = total_inspections + 1 WHERE id = ?");
            $stmt->bind_param("i", $_POST['inspector_id']);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'booking_id' => $booking_id]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Scheduler</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body class="bg-gray-50">

<nav class="bg-gray-800 text-white p-4">
    <div class="container mx-auto flex justify-between">
        <h1 class="text-xl font-bold">🏛️ Legal Assist - Inspection Scheduler</h1>
        <a href="business.html" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-500">← Back to Home</a>
    </div>
</nav>

<div class="container mx-auto px-4 py-8 max-w-5xl">
    
    <div id="loginForm" class="bg-white shadow-lg rounded-lg p-8 mb-6">
        <h2 class="text-3xl font-bold mb-6 text-center">Schedule Your Inspection</h2>
        <p class="text-gray-600 mb-6 text-center">Enter your Application ID to begin scheduling</p>
        
        <div class="max-w-md mx-auto">
            <form onsubmit="searchApp(event)" class="space-y-6">
                <div>
                    <label class="block font-semibold mb-2">Application ID *</label>
                    <input type="text" id="appIdInput" class="w-full p-4 border-2 rounded-lg focus:border-blue-500 focus:outline-none" 
                           placeholder="e.g., BL2026-83994" required>
                    <p class="text-sm text-gray-500 mt-2">💡 Find this ID in your application confirmation page</p>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-4 rounded-lg font-bold text-lg hover:bg-blue-500 transition">
                    Continue →
                </button>
            </form>
        </div>
    </div>

    <div id="appInfo" class="bg-white shadow-lg rounded-lg p-8 mb-6 hidden">
        <h2 class="text-2xl font-bold mb-4">📋 Application Details</h2>
        <div class="grid md:grid-cols-2 gap-4 bg-blue-50 p-6 rounded">
            <div><b>Application ID:</b> <span id="dAppId"></span></div>
            <div><b>License Type:</b> <span id="dType" class="uppercase text-blue-600 font-bold"></span></div>
            <div><b>Name:</b> <span id="dName"></span></div>
            <div><b>Business:</b> <span id="dBusiness"></span></div>
            <div class="col-span-2"><b>Address:</b> <span id="dAddress"></span></div>
            <div><b>Zone:</b> <span id="dZone" class="bg-green-600 text-white px-3 py-1 rounded-full text-sm inline-block"></span></div>
        </div>
        <div class="mt-4 bg-yellow-50 p-4 rounded border-l-4 border-yellow-500">
            <b>💰 Inspection Fee: ₹<span id="dFee"></span></b>
        </div>
    </div>

    <div id="calendar" class="bg-white shadow-lg rounded-lg p-8 mb-6 hidden">
        <h2 class="text-2xl font-bold mb-6">📅 Select Inspection Date</h2>
        <div class="flex justify-between items-center mb-6">
            <button onclick="changeMonth(-1)" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500">← Previous</button>
            <h3 id="monthTitle" class="text-xl font-bold"></h3>
            <button onclick="changeMonth(1)" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-500">Next →</button>
        </div>
        <div id="calGrid" class="grid grid-cols-7 gap-2"></div>
        <div class="mt-4 flex gap-4 text-sm flex-wrap">
            <div class="flex items-center gap-2"><div class="w-4 h-4 bg-green-500 rounded"></div> Available</div>
            <div class="flex items-center gap-2"><div class="w-4 h-4 bg-gray-300 rounded"></div> Past Date</div>
            <div class="flex items-center gap-2"><div class="w-4 h-4 bg-red-500 rounded"></div> Holiday</div>
        </div>
    </div>

    <div id="slots" class="bg-white shadow-lg rounded-lg p-8 mb-6 hidden">
        <h2 class="text-2xl font-bold mb-6">⏰ Select Time Slot</h2>
        <p class="mb-4">Selected Date: <span id="selDate" class="font-bold text-blue-600"></span></p>
        <div id="slotGrid" class="grid md:grid-cols-2 gap-4"></div>
    </div>

    <div id="confirm" class="bg-white shadow-lg rounded-lg p-8 mb-6 hidden">
        <h2 class="text-2xl font-bold mb-6">✅ Confirm Booking</h2>
        <div class="bg-blue-50 p-6 rounded mb-6">
            <h3 class="font-bold mb-3">Inspection Details</h3>
            <div class="space-y-2">
                <div class="flex justify-between"><span>Date:</span><b id="cDate"></b></div>
                <div class="flex justify-between"><span>Time:</span><b id="cTime"></b></div>
                <div class="flex justify-between"><span>Inspector:</span><b id="cInsp"></b></div>
                <div class="flex justify-between"><span>Duration:</span><span>60 minutes</span></div>
            </div>
        </div>
        <div class="mb-6">
            <label class="block font-semibold mb-2">Special Instructions (Optional)</label>
            <textarea id="instructions" class="w-full p-4 border rounded" rows="3" placeholder="e.g., Use back entrance, Call 30 mins before arriving"></textarea>
        </div>
        <div class="bg-green-50 p-6 rounded mb-6 border-2 border-green-500">
            <div class="flex justify-between text-xl">
                <b>Total Amount:</b><b class="text-green-600">₹<span id="cAmt"></span></b>
            </div>
        </div>
        <div class="flex gap-4">
            <button onclick="goBack()" class="bg-gray-600 text-white px-6 py-3 rounded flex-1 hover:bg-gray-500">← Back</button>
            <button onclick="pay()" class="bg-green-600 text-white px-6 py-3 rounded flex-1 font-bold hover:bg-green-500">Proceed to Payment →</button>
        </div>
    </div>

    <div id="success" class="bg-white shadow-lg rounded-lg p-8 hidden">
        <div class="text-center">
            <div class="text-6xl mb-4">✅</div>
            <h2 class="text-3xl font-bold text-green-600 mb-4">Booking Confirmed!</h2>
            <p class="text-lg mb-6">Your inspection has been successfully scheduled</p>
            
            <div class="bg-green-50 p-6 rounded mb-6 max-w-2xl mx-auto text-left">
                <h3 class="font-bold mb-3">Booking Details</h3>
                <div class="space-y-3">
                    <div class="flex justify-between"><span>Booking ID:</span><b id="sId"></b></div>
                    <div class="flex justify-between"><span>Date:</span><b id="sDate"></b></div>
                    <div class="flex justify-between"><span>Time:</span><b id="sTime"></b></div>
                    <div class="flex justify-between"><span>Inspector:</span><b id="sInsp"></b></div>
                    <div class="flex justify-between"><span>Payment ID:</span><b id="sPay" class="text-green-600"></b></div>
                </div>
            </div>

            <div class="bg-yellow-50 p-4 rounded mb-6 border-l-4 border-yellow-500 max-w-2xl mx-auto text-left">
                <p class="font-semibold mb-2">📌 Important Reminders:</p>
                <ul class="text-sm space-y-1">
                    <li>• Inspector will contact you 1 day before</li>
                    <li>• Keep all required documents ready</li>
                    <li>• Ensure premises are accessible</li>
                    <li>• Safety equipment must be installed</li>
                </ul>
            </div>

            <button onclick="location.href='business.html'" class="bg-blue-600 text-white px-8 py-3 rounded font-semibold hover:bg-blue-500">Back to Home</button>
        </div>
    </div>

    <div id="loader" class="text-center py-12 hidden">
        <div class="inline-block animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-600"></div>
        <p class="mt-4 text-lg font-semibold">Processing...</p>
    </div>

    <div id="error" class="bg-red-50 border-2 border-red-500 rounded p-8 hidden">
        <div class="flex items-start gap-3">
            <span class="text-3xl">❌</span>
            <div>
                <h3 class="font-bold text-xl text-red-700 mb-2">Error</h3>
                <p id="errMsg" class="text-red-700 mb-4"></p>
                <button onclick="resetForm()" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-500">Try Again</button>
            </div>
        </div>
    </div>
</div>

<script>
let app={}, month=new Date(), selDate, selSlot, slots=[];
const fees=<?=json_encode($fees)?>;
const key='<?=$rzp_key?>';

async function searchApp(e) {
    e.preventDefault();
    const id=$('#appIdInput').value.trim();
    if(!id) return;
    
    console.log('🔍 Searching for application:', id);
    show('loader');
    hide('loginForm','error');
    
    try {
        const d=await post('get_application',{app_id:id});
        console.log('📋 Application response:', d);
        
        if(d.success) {
            app=d.data;
            console.log('✅ Application found:', app);
            $('#dAppId').textContent=app.application_id;
            $('#dType').textContent=app.license_type;
            $('#dName').textContent=app.full_name;
            $('#dBusiness').textContent=app.business_name||'N/A';
            $('#dAddress').textContent=app.business_address;
            $('#dZone').textContent=app.zone;
            $('#dFee').textContent=fees[app.license_type]||500;
            genCal();
            show('appInfo','calendar');
            $('#appInfo').scrollIntoView({behavior:'smooth'});
        } else {
            console.error('❌ Application not found');
            showErr(d.message);
            show('loginForm');
        }
    } catch(err) {
        console.error('💥 Error:', err);
        showErr('Network error. Please try again.');
        show('loginForm');
    }
    hide('loader');
}

function resetForm() {
    hide('error','appInfo','calendar','slots','confirm','success');
    show('loginForm');
    $('#appIdInput').value='';
    window.scrollTo({top:0,behavior:'smooth'});
}

function genCal() {
    const y=month.getFullYear(), m=month.getMonth();
    $('#monthTitle').textContent=month.toLocaleDateString('en-US',{month:'long',year:'numeric'});
    const first=new Date(y,m,1).getDay(), days=new Date(y,m+1,0).getDate();
    const g=$('#calGrid');
    g.innerHTML='';
    ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d=>g.innerHTML+=`<div class="text-center font-bold py-2">${d}</div>`);
    for(let i=0;i<first;i++) g.innerHTML+='<div></div>';
    const now=new Date(); now.setHours(0,0,0,0);
    for(let d=1;d<=days;d++) {
        const dt=new Date(y,m,d);
        let cls='text-center py-3 rounded border ';
        if(dt<now) cls+='bg-gray-200 text-gray-400 cursor-not-allowed';
        else if(dt.getDay()===0) cls+='bg-red-100 text-red-600 cursor-not-allowed';
        else {
            cls+='bg-green-100 hover:bg-green-200 cursor-pointer';
            g.innerHTML+=`<div class="${cls}" onclick="pickDate(new Date(${y},${m},${d}))">${d}</div>`;
            continue;
        }
        g.innerHTML+=`<div class="${cls}">${d}</div>`;
    }
}

function changeMonth(d) {
    month.setMonth(month.getMonth()+d);
    const now=new Date();
    if(month<new Date(now.getFullYear(),now.getMonth(),1)) month=new Date(now.getFullYear(),now.getMonth(),1);
    genCal();
}

async function pickDate(d) {
    selDate=d;
    const dateStr = d.toISOString().split('T')[0];
    $('#selDate').textContent=d.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
    
    console.log('📅 Date selected:', dateStr);
    console.log('📦 Sending data:', {
        license_type: app.license_type,
        zone: app.zone,
        date: dateStr
    });
    
    show('loader');
    hide('slots');
    
    try {
        const r=await post('get_available_slots',{
            license_type:app.license_type,
            zone:app.zone,
            date:dateStr
        });
        
        console.log('🎯 Slots response:', r);
        
        if(r.success) {
            slots=r.slots;
            console.log('✅ Found', slots.length, 'slots');
            
            const g=$('#slotGrid');
            if(slots.length===0) {
                console.warn('⚠️ No slots available');
                if(r.debug) {
                    console.log('🐛 Debug info:', r.debug);
                }
                g.innerHTML='<div class="col-span-2 text-center bg-yellow-50 p-8 rounded border-2 border-yellow-300"><p class="text-lg font-semibold text-yellow-800 mb-2">❌ No slots available for this date</p><p class="text-sm text-yellow-600">Please try another date or contact support</p></div>';
            } else {
                g.innerHTML='';
                slots.forEach(s => {
                    console.log('👤 Slot:', s);
                    g.innerHTML+=`
                    <div class="bg-blue-50 p-4 rounded border-2 border-blue-200 hover:border-blue-500 cursor-pointer transition" onclick="pickSlot(${s.id})">
                        <div class="flex justify-between items-start mb-2">
                            <b class="text-lg">${fmt(s.slot_time)}</b>
                            <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs">Available</span>
                        </div>
                        <div class="text-sm text-gray-600">
                            <p>👤 Inspector: ${s.inspector_name}</p>
                            <p>⭐ Rating: ${s.rating}/5.0</p>
                            <p>⏱️ Duration: 60 mins</p>
                        </div>
                    </div>`;
                });
            }
            show('slots');
            $('#slots').scrollIntoView({behavior:'smooth'});
        } else {
            console.error('❌ Error:', r.message);
            if(r.debug) {
                console.log('🐛 Debug info:', r.debug);
                let debugMsg = r.message + '\n\nDEBUG INFO:\n';
                debugMsg += 'Zone: ' + r.debug.zone_searched + '\n';
                debugMsg += 'License Type: ' + r.debug.license_type_searched + '\n';
                debugMsg += 'Inspectors in DB: ' + r.debug.total_inspectors_in_db;
                showErr(debugMsg);
            } else {
                showErr(r.message || 'No slots available');
            }
        }
    } catch(err) {
        console.error('💥 Error loading slots:', err);
        showErr('Error loading slots. Please try again.');
    }
    hide('loader');
}

function pickSlot(id) {
    selSlot=slots.find(s=>s.id===id);
    console.log('🎯 Selected slot:', selSlot);
    $('#cDate').textContent=selDate.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
    $('#cTime').textContent=fmt(selSlot.slot_time);
    $('#cInsp').textContent=selSlot.inspector_name;
    $('#cAmt').textContent=fees[app.license_type]||500;
    hide('slots');
    show('confirm');
    $('#confirm').scrollIntoView({behavior:'smooth'});
}

function goBack() {
    hide('confirm');
    show('slots');
    $('#slots').scrollIntoView({behavior:'smooth'});
}

async function pay() {
    const amt = fees[app.license_type] || 500;
    
    console.log('💳 Initiating payment for ₹', amt);
    show('loader');
    hide('confirm');
    
    try {
        const orderResponse = await post('create_razorpay_order', {
            amount: amt,
            app_id: app.application_id
        });
        
        console.log('📦 Order Response:', orderResponse);
        
        if (orderResponse.error) {
            throw new Error(orderResponse.error.description || 'Payment initialization failed');
        }
        
        if (!orderResponse.id) {
            throw new Error('Invalid order response from payment gateway');
        }
        
        hide('loader');
        
        const options = {
            key: key,
            amount: orderResponse.amount,
            currency: orderResponse.currency,
            name: 'Legal Assist',
            description: `${app.license_type.toUpperCase()} License Inspection`,
            order_id: orderResponse.id,
            handler: function (response) {
                console.log('✅ Payment Success:', response);
                book(response.razorpay_payment_id, amt);
            },
            prefill: {
                name: app.full_name,
                contact: app.mobile,
                email: app.email || ''
            },
            theme: {
                color: '#2563eb'
            },
            modal: {
                ondismiss: function() {
                    console.log('❌ Payment cancelled');
                    hide('loader');
                    show('confirm');
                }
            }
        };
        
        const rzp = new Razorpay(options);
        
        rzp.on('payment.failed', function (response) {
            console.error('❌ Payment Failed:', response.error);
            hide('loader');
            show('confirm');
            alert('Payment failed: ' + response.error.description);
        });
        
        rzp.open();
        
    } catch(err) {
        console.error('💥 Payment Error:', err);
        hide('loader');
        show('confirm');
        alert('Error: ' + err.message);
    }
}

async function book(paymentId, amount) {
    console.log('📝 Confirming booking...');
    show('loader');
    
    try {
        const r = await post('confirm_booking', {
            app_id: app.application_id,
            slot_id: selSlot.id,
            inspector_id: selSlot.inspector_id,
            inspection_date: selDate.toISOString().split('T')[0],
            inspection_time: selSlot.slot_time,
            license_type: app.license_type,
            business_address: app.business_address,
            special_instructions: $('#instructions').value || '',
            payment_id: paymentId,
            amount: amount
        });
        
        console.log('📋 Booking response:', r);
        
        if (r.success) {
            console.log('✅ Booking confirmed!');
            $('#sId').textContent = '#' + r.booking_id;
            $('#sDate').textContent = selDate.toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
            $('#sTime').textContent = fmt(selSlot.slot_time);
            $('#sInsp').textContent = selSlot.inspector_name;
            $('#sPay').textContent = paymentId;
            
            hide('loader', 'confirm');
            show('success');
            $('#success').scrollIntoView({behavior:'smooth'});
        } else {
            throw new Error(r.message || 'Booking failed');
        }
    } catch(err) {
        console.error('💥 Booking Error:', err);
        hide('loader');
        showErr('Booking failed: ' + err.message);
    }
}

function fmt(t) {
    const[h,m]=t.split(':');
    const hr=parseInt(h);
    return `${hr>12?hr-12:hr===0?12:hr}:${m} ${hr>=12?'PM':'AM'}`;
}

async function post(a,d) {
    const f=new FormData();
    f.append('action',a);
    for(let k in d) f.append(k,d[k]);
    const res=await fetch('',{method:'POST',body:f});
    return await res.json();
}

const $=s=>document.querySelector(s);
const show=(...ids)=>ids.forEach(i=>$('#'+i)?.classList.remove('hidden'));
const hide=(...ids)=>ids.forEach(i=>$('#'+i)?.classList.add('hidden'));
const showErr=m=>{$('#errMsg').textContent=m;show('error');hide('loader')};
</script>
</body>
</html>