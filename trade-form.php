<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "legal_assist");
if ($conn->connect_error) {
    die("DB Connection Failed");
}

/* ================= AUTO STATUS UPDATE (1 WEEK LOGIC) ================= */
$conn->query("
    UPDATE trade_licence_applications
    SET status =
        CASE
            WHEN DATEDIFF(NOW(), created_at) >= 21 THEN 'Approved'
            WHEN DATEDIFF(NOW(), created_at) >= 14 THEN 'Inspection Scheduled'
            WHEN DATEDIFF(NOW(), created_at) >= 7 THEN 'Under Review'
            ELSE 'Submitted'
        END
");

/* ================= APPLY FOR TRADE LICENCE ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['action'] === "apply") {

    $full_name = trim($_POST['full_name']);
    $business_name = trim($_POST['business_name']);
    $business_address = trim($_POST['business_address']);
    $business_category = trim($_POST['business_category']);
    $phone = trim($_POST['phone_number']);

    if (!$full_name || !$business_name || !$business_address || !$business_category || !$phone) {
        die("All fields required");
    }

    $application_id = "TL" . date("Y") . strtoupper(substr(md5(time()), 0, 6));

    $stmt = $conn->prepare("
        INSERT INTO trade_licence_applications
        (application_id, full_name, business_name, business_address, business_category, phone_number, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Submitted')
    ");

    $stmt->bind_param(
        "ssssss",
        $application_id,
        $full_name,
        $business_name,
        $business_address,
        $business_category,
        $phone
    );

    if ($stmt->execute()) {
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Application Submitted | Legal Assist</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .container {
                    max-width: 550px;
                    width: 100%;
                }
                .card {
                    background: #ffffff;
                    padding: 40px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    text-align: center;
                    animation: slideUp 0.5s ease;
                }
                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .success-icon {
                    width: 80px;
                    height: 80px;
                    background: linear-gradient(135deg, #10b981, #059669);
                    border-radius: 50%;
                    margin: 0 auto 25px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    position: relative;
                }
                .success-icon::after {
                    content: '✓';
                    color: white;
                    font-size: 48px;
                    font-weight: bold;
                }
                h1 {
                    color: #1f2937;
                    font-size: 28px;
                    margin-bottom: 15px;
                    font-weight: 700;
                }
                .subtitle {
                    color: #6b7280;
                    margin-bottom: 30px;
                    font-size: 16px;
                    line-height: 1.6;
                }
                .info-section {
                    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
                    border-left: 4px solid #10b981;
                    padding: 25px;
                    border-radius: 12px;
                    margin: 25px 0;
                    text-align: left;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 12px 0;
                    border-bottom: 1px solid #d1fae5;
                }
                .info-row:last-child {
                    border-bottom: none;
                }
                .info-label {
                    color: #6b7280;
                    font-size: 14px;
                    font-weight: 600;
                }
                .info-value {
                    color: #1f2937;
                    font-weight: 700;
                    font-size: 16px;
                }
                .application-id {
                    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
                    padding: 18px;
                    border-radius: 12px;
                    margin: 25px 0;
                    border: 2px dashed #3b82f6;
                }
                .application-id .label {
                    color: #1e40af;
                    font-size: 13px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 8px;
                }
                .application-id .value {
                    color: #1e3a8a;
                    font-size: 24px;
                    font-weight: 800;
                    font-family: 'Courier New', monospace;
                    letter-spacing: 1px;
                }
                .notice {
                    background: #fef3c7;
                    border-left: 4px solid #f59e0b;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 25px 0;
                    text-align: left;
                    font-size: 14px;
                    color: #92400e;
                    line-height: 1.6;
                }
                .notice strong {
                    display: block;
                    margin-bottom: 5px;
                    color: #78350f;
                }
                .btn-home {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    padding: 14px 40px;
                    border-radius: 10px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 16px;
                    margin-top: 20px;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                }
                .btn-home:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='card'>
                    <div class='success-icon'></div>
                    
                    <h1>Application Submitted Successfully</h1>
                    <p class='subtitle'>Your trade licence application has been received and is being processed.</p>

                    <div class='application-id'>
                        <div class='label'>Your Application ID</div>
                        <div class='value'>$application_id</div>
                    </div>

                    <div class='info-section'>
                        <div class='info-row'>
                            <span class='info-label'>Current Status</span>
                            <span class='info-value'>Submitted</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Business Name</span>
                            <span class='info-value'>$business_name</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>Category</span>
                            <span class='info-value'>$business_category</span>
                        </div>
                    </div>

                    <div class='notice'>
                        <strong>Important Information</strong>
                        Please save your Application ID for future reference. You can track your application status using this ID and your registered phone number.
                    </div>

                    <a href='trade-licence.html' class='btn-home'>Return to Dashboard</a>
                </div>
            </div>
        </body>
        </html>
        ";
    } else {
        echo "Error submitting application";
    }
    exit;
}

/* ================= TRACK APPLICATION STATUS ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['action'] === "status") {

    $app_id = trim($_POST['application_id']);
    $phone = trim($_POST['phone_number']);

    $stmt = $conn->prepare("
        SELECT * FROM trade_licence_applications
        WHERE application_id = ? AND phone_number = ?
    ");

    $stmt->bind_param("ss", $app_id, $phone);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Application Not Found | Legal Assist</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    background: linear-gradient(135deg, #f87171 0%, #dc2626 100%);
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .card {
                    background: white;
                    padding: 40px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    text-align: center;
                    max-width: 500px;
                }
                .error-icon {
                    width: 80px;
                    height: 80px;
                    background: linear-gradient(135deg, #ef4444, #dc2626);
                    border-radius: 50%;
                    margin: 0 auto 25px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 48px;
                    color: white;
                }
                h1 {
                    color: #1f2937;
                    font-size: 28px;
                    margin-bottom: 15px;
                }
                p {
                    color: #6b7280;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                .btn-back {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    padding: 14px 40px;
                    border-radius: 10px;
                    text-decoration: none;
                    font-weight: 600;
                    transition: all 0.3s ease;
                }
                .btn-back:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
                }
            </style>
        </head>
        <body>
            <div class='card'>
                <div class='error-icon'>✕</div>
                <h1>Application Not Found</h1>
                <p>No application found with the provided Application ID and phone number. Please verify your details and try again.</p>
                <a href='trade-licence.html' class='btn-back'>Go Back</a>
            </div>
        </body>
        </html>
        ";
        exit;
    }

    $row = $res->fetch_assoc();

    $progress = match ($row['status']) {
        "Submitted" => 25,
        "Under Review" => 50,
        "Inspection Scheduled" => 75,
        "Approved" => 100,
        default => 0
    };

    $status_color = match ($row['status']) {
        "Submitted" => "#3b82f6",
        "Under Review" => "#f59e0b",
        "Inspection Scheduled" => "#8b5cf6",
        "Approved" => "#10b981",
        default => "#6b7280"
    };

    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Application Status | Legal Assist</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                min-height: 100vh;
                padding: 40px 20px;
            }
            .container {
                max-width: 750px;
                margin: 0 auto;
            }
            .card {
                background: #ffffff;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: slideUp 0.5s ease;
            }
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .header {
                text-align: center;
                margin-bottom: 35px;
                padding-bottom: 25px;
                border-bottom: 2px solid #f3f4f6;
            }
            h1 {
                color: #1f2937;
                font-size: 32px;
                margin-bottom: 10px;
                font-weight: 700;
            }
            .app-id {
                color: #6b7280;
                font-size: 16px;
                font-family: 'Courier New', monospace;
                font-weight: 600;
            }
            .status-badge {
                display: inline-block;
                background: $status_color;
                color: white;
                padding: 10px 24px;
                border-radius: 25px;
                font-weight: 700;
                font-size: 16px;
                margin: 20px 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .progress-section {
                margin: 35px 0;
            }
            .progress-label {
                font-size: 14px;
                font-weight: 600;
                color: #6b7280;
                margin-bottom: 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .progress-bar-container {
                background: #e5e7eb;
                border-radius: 15px;
                height: 24px;
                overflow: hidden;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .progress-bar {
                height: 100%;
                width: {$progress}%;
                background: linear-gradient(90deg, $status_color, " . ($progress == 100 ? "#059669" : $status_color) . ");
                border-radius: 15px;
                transition: width 1s ease;
                position: relative;
                overflow: hidden;
            }
            .progress-bar::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
                animation: shimmer 2s infinite;
            }
            @keyframes shimmer {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }
            .progress-text {
                text-align: center;
                margin-top: 10px;
                font-weight: 700;
                color: $status_color;
                font-size: 18px;
            }
            .info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .info-item {
                background: linear-gradient(135deg, #f9fafb, #f3f4f6);
                padding: 20px;
                border-radius: 12px;
                border-left: 4px solid $status_color;
            }
            .info-label {
                color: #6b7280;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 8px;
            }
            .info-value {
                color: #1f2937;
                font-weight: 700;
                font-size: 18px;
            }
            .remarks-section {
                background: linear-gradient(135deg, #fffbeb, #fef3c7);
                border-left: 4px solid #f59e0b;
                padding: 20px;
                border-radius: 12px;
                margin: 25px 0;
            }
            .remarks-label {
                color: #92400e;
                font-weight: 700;
                margin-bottom: 8px;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .remarks-text {
                color: #78350f;
                line-height: 1.6;
                font-size: 15px;
            }
            .timeline {
                margin: 35px 0;
                padding: 25px;
                background: #f9fafb;
                border-radius: 12px;
            }
            .timeline-title {
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 20px;
                font-size: 18px;
            }
            .timeline-item {
                display: flex;
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px solid #e5e7eb;
            }
            .timeline-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .timeline-dot {
                width: 16px;
                height: 16px;
                border-radius: 50%;
                margin-right: 15px;
                margin-top: 3px;
                flex-shrink: 0;
            }
            .timeline-dot.active {
                background: $status_color;
                box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            }
            .timeline-dot.inactive {
                background: #d1d5db;
            }
            .timeline-content {
                flex: 1;
            }
            .timeline-status {
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 3px;
            }
            .timeline-desc {
                font-size: 13px;
                color: #6b7280;
            }
            .btn-back {
                display: inline-block;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 14px 40px;
                border-radius: 10px;
                text-decoration: none;
                font-weight: 600;
                font-size: 16px;
                margin-top: 20px;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            }
            .btn-back:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='card'>
                <div class='header'>
                    <h1>Trade Licence Application</h1>
                    <div class='app-id'>Application ID: {$row['application_id']}</div>
                    <div class='status-badge'>{$row['status']}</div>
                </div>

                <div class='progress-section'>
                    <div class='progress-label'>Application Progress</div>
                    <div class='progress-bar-container'>
                        <div class='progress-bar'></div>
                    </div>
                    <div class='progress-text'>{$progress}% Complete</div>
                </div>

                <div class='info-grid'>
                    <div class='info-item'>
                        <div class='info-label'>Business Name</div>
                        <div class='info-value'>{$row['business_name']}</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Category</div>
                        <div class='info-value'>{$row['business_category']}</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Applicant Name</div>
                        <div class='info-value'>{$row['full_name']}</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Phone Number</div>
                        <div class='info-value'>{$row['phone_number']}</div>
                    </div>
                </div>

                <div class='timeline'>
                    <div class='timeline-title'>Application Timeline</div>
                    
                    <div class='timeline-item'>
                        <div class='timeline-dot active'></div>
                        <div class='timeline-content'>
                            <div class='timeline-status'>Submitted</div>
                            <div class='timeline-desc'>Application received and logged</div>
                        </div>
                    </div>
                    
                    <div class='timeline-item'>
                        <div class='timeline-dot " . ($progress >= 50 ? "active" : "inactive") . "'></div>
                        <div class='timeline-content'>
                            <div class='timeline-status'>Under Review</div>
                            <div class='timeline-desc'>Documents verification in progress</div>
                        </div>
                    </div>
                    
                    <div class='timeline-item'>
                        <div class='timeline-dot " . ($progress >= 75 ? "active" : "inactive") . "'></div>
                        <div class='timeline-content'>
                            <div class='timeline-status'>Inspection Scheduled</div>
                            <div class='timeline-desc'>Site inspection and compliance check</div>
                        </div>
                    </div>
                    
                    <div class='timeline-item'>
                        <div class='timeline-dot " . ($progress == 100 ? "active" : "inactive") . "'></div>
                        <div class='timeline-content'>
                            <div class='timeline-status'>Approved</div>
                            <div class='timeline-desc'>Licence issued successfully</div>
                        </div>
                    </div>
                </div>

                <div class='remarks-section'>
                    <div class='remarks-label'>Official Remarks</div>
                    <div class='remarks-text'>{$row['remarks']}</div>
                </div>

                <div style='text-align: center;'>
                    <a href='trade.html' class='btn-back'>Return to Dashboard</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    exit;
}
?>