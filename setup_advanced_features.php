<?php
/**
 * INSTALLATION SCRIPT - Run this once to set up advanced features
 * Location: Root directory
 * Rename to: setup_advanced_features.php
 */

echo "== ADVANCED STUDENT PORTAL - SETUP SCRIPT ==\n\n";

// 1. Check directory permissions
$directories = [
    'uploads/',
    'uploads/assignments/',
    'uploads/materials/',
    'logs/'
];

echo "[1/4] Checking directories...\n";
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✓ Created $dir\n";
    } elseif (!is_writable($dir)) {
        chmod($dir, 0755);
        echo "✓ Fixed permissions: $dir\n";
    } else {
        echo "✓ $dir exists and is writable\n";
    }
}

// 2. Check database connection
echo "\n[2/4] Testing database connection...\n";
try {
    $conn = new mysqli("localhost", "root", "", "attendancemsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "✓ Database connected successfully\n";
} catch (Exception $e) {
    die("✗ Database error: " . $e->getMessage());
}

// 3. Check required tables
echo "\n[3/4] Checking database tables...\n";
$requiredTables = [
    'tblattendancelog',
    'tbltimetables',
    'tblassignments',
    'tblassignmentsubmissions',
    'tblnotifications',
    'tblmessages',
    'tblclasslocations',
    'tblauditlog',
    'tbluserpreferences'
];

$missingTables = [];
foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ $table exists\n";
    } else {
        echo "✗ $table missing\n";
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "\n⚠  IMPORTANT: Run this SQL to create missing tables:\n";
    echo "   File: /DATABASE FILE/advanced_features_schema.sql\n";
    echo "   Import in phpMyAdmin or run via MySQL CLI\n";
}

// 4. Check file permissions
echo "\n[4/4] Checking file permissions...\n";
$files = [
    'Includes/advanced_helpers.php',
    'Includes/advanced_features.php',
    'Includes/dark_mode_component.php',
    'Student/analyticsAttendance.php',
    'Student/api_generateQrToken.php',
    'Student/api_markAttendanceWithValidation.php',
    'Admin/manageTimetables.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

echo "\n=== SETUP COMPLETE ===\n";
echo "\nNEXT STEPS:\n";
echo "1. Import SQL schema: DATABASE FILE/advanced_features_schema.sql\n";
echo "2. Test QR scanning: Student > Scan Attendance\n";
echo "3. View analytics: Student > Attendance Analytics\n";
echo "4. Set up timetables: Admin > Manage Timetables\n";
echo "5. Enable dark mode: Click moon icon in topbar\n";
echo "\nFor detailed documentation, see: ADVANCED_FEATURES_README.md\n";

?>
