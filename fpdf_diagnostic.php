<?php
/**
 * FPDF Diagnostic Tool
 * This will check your FPDF installation and identify the exact problem
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>FPDF Diagnostic</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 900px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box { 
            background: white; 
            padding: 20px; 
            margin: 15px 0; 
            border-radius: 8px;
            border-left: 4px solid #ccc;
        }
        .success { border-left-color: #10b981; }
        .error { border-left-color: #ef4444; }
        .warning { border-left-color: #f59e0b; }
        .info { border-left-color: #3b82f6; }
        h1 { color: #1e3a8a; }
        h2 { color: #374151; margin-top: 0; }
        pre { 
            background: #f9fafb; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto;
            font-size: 12px;
        }
        .icon-success { color: #10b981; font-size: 24px; }
        .icon-error { color: #ef4444; font-size: 24px; }
        .icon-warning { color: #f59e0b; font-size: 24px; }
        .badge { 
            display: inline-block; 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 12px; 
            font-weight: bold;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <h1>🔍 FPDF Diagnostic Tool</h1>
    <p style='color: #6b7280;'>Checking your FPDF installation...</p>
";

// ========================================
// TEST 1: Check if FPDF file exists
// ========================================
echo "<div class='test-box'>";
echo "<h2>Test 1: FPDF Main File</h2>";

$fpdf_path = __DIR__ . '/fpdf/fpdf.php';
$fpdf_exists = file_exists($fpdf_path);

if ($fpdf_exists) {
    echo "<span class='icon-success'>✅</span> <span class='badge badge-success'>FOUND</span><br>";
    echo "<strong>Location:</strong> <code>$fpdf_path</code><br>";
    echo "<strong>Size:</strong> " . number_format(filesize($fpdf_path)) . " bytes<br>";
    
    // Check FPDF version
    $fpdf_content = file_get_contents($fpdf_path);
    if (preg_match("/define\('FPDF_VERSION','([^']+)'\)/", $fpdf_content, $matches)) {
        echo "<strong>Version:</strong> " . $matches[1] . "<br>";
    }
} else {
    echo "<span class='icon-error'>❌</span> <span class='badge badge-error'>NOT FOUND</span><br>";
    echo "<strong>Expected location:</strong> <code>$fpdf_path</code><br>";
    echo "<strong>Problem:</strong> FPDF library is missing!<br>";
}
echo "</div>";

// ========================================
// TEST 2: Check font directory
// ========================================
echo "<div class='test-box'>";
echo "<h2>Test 2: Font Directory</h2>";

$font_dir = __DIR__ . '/fpdf/font';
$font_dir_exists = is_dir($font_dir);

if ($font_dir_exists) {
    echo "<span class='icon-success'>✅</span> <span class='badge badge-success'>FOUND</span><br>";
    echo "<strong>Location:</strong> <code>$font_dir</code><br>";
    
    // List all font files
    $font_files = glob($font_dir . '/*.php');
    echo "<strong>Font files found:</strong> " . count($font_files) . "<br>";
    
    if (count($font_files) > 0) {
        echo "<pre>";
        foreach ($font_files as $font) {
            echo basename($font) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<span class='icon-warning'>⚠️</span> <span class='badge badge-warning'>EMPTY</span> Font directory exists but contains no font files!<br>";
    }
} else {
    echo "<span class='icon-error'>❌</span> <span class='badge badge-error'>NOT FOUND</span><br>";
    echo "<strong>Expected location:</strong> <code>$font_dir</code><br>";
    echo "<strong>Problem:</strong> Font directory is missing!<br>";
}
echo "</div>";

// ========================================
// TEST 3: Check specific required fonts
// ========================================
echo "<div class='test-box'>";
echo "<h2>Test 3: Required Font Files</h2>";

$required_fonts = [
    'courier.php',
    'courierb.php',
    'courierbi.php',
    'courieri.php',
    'helvetica.php',
    'helveticab.php',
    'helveticabi.php',
    'helveticai.php',
    'times.php',
    'timesb.php',
    'timesbi.php',
    'timesi.php',
];

$missing_fonts = [];
$found_fonts = [];

foreach ($required_fonts as $font) {
    $font_path = $font_dir . '/' . $font;
    if (file_exists($font_path)) {
        $found_fonts[] = $font;
    } else {
        $missing_fonts[] = $font;
    }
}

if (count($missing_fonts) == 0) {
    echo "<span class='icon-success'>✅</span> <span class='badge badge-success'>ALL FOUND</span><br>";
    echo "All " . count($required_fonts) . " core font files are present.<br>";
} else {
    echo "<span class='icon-error'>❌</span> <span class='badge badge-error'>MISSING FONTS</span><br>";
    echo "<strong>Missing " . count($missing_fonts) . " font file(s):</strong><br>";
    echo "<pre>";
    foreach ($missing_fonts as $font) {
        echo "❌ $font\n";
    }
    echo "</pre>";
    
    if (count($found_fonts) > 0) {
        echo "<strong>Found fonts:</strong><br>";
        echo "<pre>";
        foreach ($found_fonts as $font) {
            echo "✅ $font\n";
        }
        echo "</pre>";
    }
}
echo "</div>";

// ========================================
// TEST 4: Try to load FPDF
// ========================================
echo "<div class='test-box'>";
echo "<h2>Test 4: Load FPDF Library</h2>";

if ($fpdf_exists) {
    try {
        require_once($fpdf_path);
        echo "<span class='icon-success'>✅</span> <span class='badge badge-success'>SUCCESS</span><br>";
        echo "FPDF library loaded successfully!<br>";
        
        // Check if FPDF class exists
        if (class_exists('FPDF')) {
            echo "FPDF class is available.<br>";
        } else {
            echo "<span class='icon-error'>❌</span> FPDF class not found after loading file!<br>";
        }
    } catch (Exception $e) {
        echo "<span class='icon-error'>❌</span> <span class='badge badge-error'>FAILED</span><br>";
        echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    }
} else {
    echo "<span class='icon-warning'>⚠️</span> <span class='badge badge-warning'>SKIPPED</span> (FPDF file not found)<br>";
}
echo "</div>";

// ========================================
// TEST 5: Try to create a simple PDF
// ========================================
echo "<div class='test-box'>";
echo "<h2>Test 5: Create Test PDF</h2>";

if ($fpdf_exists && class_exists('FPDF')) {
    try {
        $pdf = new FPDF();
        echo "<span class='icon-success'>✅</span> PDF object created successfully!<br>";
        
        // Try to add a page
        $pdf->AddPage();
        echo "<span class='icon-success'>✅</span> Page added successfully!<br>";
        
        // Try to set font WITHOUT bold (should always work)
        try {
            $pdf->SetFont('Arial', '', 12);
            echo "<span class='icon-success'>✅</span> Font 'Arial' (regular) works!<br>";
        } catch (Exception $e) {
            echo "<span class='icon-error'>❌</span> Font 'Arial' (regular) failed: " . $e->getMessage() . "<br>";
        }
        
        // Try to set font WITH bold
        try {
            $pdf->SetFont('Arial', 'B', 12);
            echo "<span class='icon-success'>✅</span> Font 'Arial' (bold) works!<br>";
        } catch (Exception $e) {
            echo "<span class='icon-error'>❌</span> Font 'Arial' (bold) failed: " . $e->getMessage() . "<br>";
            echo "<strong>This is your problem!</strong><br>";
        }
        
    } catch (Exception $e) {
        echo "<span class='icon-error'>❌</span> <span class='badge badge-error'>FAILED</span><br>";
        echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<span class='icon-warning'>⚠️</span> <span class='badge badge-warning'>SKIPPED</span> (Previous tests failed)<br>";
}
echo "</div>";

// ========================================
// TEST 6: Check directory structure
// ========================================
echo "<div class='test-box info'>";
echo "<h2>📁 Current Directory Structure</h2>";

function listDirectory($dir, $prefix = '') {
    $items = @scandir($dir);
    if (!$items) return;
    
    $output = '';
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $output .= $prefix . "📁 " . $item . "/\n";
            if ($item == 'fpdf' || $item == 'font') {
                $output .= listDirectory($path, $prefix . "  ");
            }
        } else {
            $size = filesize($path);
            $output .= $prefix . "📄 " . $item . " (" . number_format($size) . " bytes)\n";
        }
    }
    return $output;
}

echo "<pre>";
echo listDirectory(__DIR__);
echo "</pre>";
echo "</div>";

// ========================================
// RECOMMENDATIONS
// ========================================
echo "<div class='test-box " . (count($missing_fonts) > 0 ? "error" : "success") . "'>";
echo "<h2>🎯 Diagnosis & Recommendations</h2>";

if (!$fpdf_exists) {
    echo "<h3>❌ CRITICAL: FPDF Library Not Found</h3>";
    echo "<p><strong>Solution:</strong></p>";
    echo "<ol>
        <li>Download FPDF from: <a href='http://www.fpdf.org/en/download.php' target='_blank'>http://www.fpdf.org/en/download.php</a></li>
        <li>Extract the ZIP file</li>
        <li>Copy the 'fpdf' folder to: <code>" . __DIR__ . "/</code></li>
        <li>Make sure the structure is: <code>project/fpdf/fpdf.php</code></li>
    </ol>";
} elseif (!$font_dir_exists || count($missing_fonts) > 0) {
    echo "<h3>❌ Problem: Missing Font Files</h3>";
    echo "<p>Your FPDF installation is incomplete. The font files are missing.</p>";
    echo "<p><strong>Solution:</strong></p>";
    echo "<ol>
        <li>Download complete FPDF package from: <a href='http://www.fpdf.org/en/download.php' target='_blank'>http://www.fpdf.org/en/download.php</a></li>
        <li>Extract and replace your entire 'fpdf' folder</li>
        <li>Make sure the 'font' subfolder contains all .php font files</li>
    </ol>";
} else {
    echo "<h3>✅ All Tests Passed!</h3>";
    echo "<p>Your FPDF installation appears to be complete and working correctly.</p>";
    echo "<p>If you're still having issues with your certificate generator:</p>";
    echo "<ol>
        <li>Make sure you're using the correct require path</li>
        <li>Try using: <code>require __DIR__ . '/fpdf/fpdf.php';</code></li>
        <li>Clear your browser cache and try again</li>
    </ol>";
}

echo "</div>";

echo "
    <div style='text-align: center; margin-top: 30px; color: #6b7280;'>
        <p>Generated: " . date('Y-m-d H:i:s') . "</p>
    </div>
</body>
</html>";
?>