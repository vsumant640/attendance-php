<?php
/**
 * Enhanced QR Attendance with Validation
 * - Prevents duplicate marking
 * - Validates QR expiry
 * - Records timestamp and location
 */

include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/advanced_helpers.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$admissionNumber = $_SESSION['admissionNumber'];
$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];
$qrToken = isset($_POST['qrToken']) ? SecurityHelper::sanitizeInput($_POST['qrToken']) : '';
$latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;

// Logger and Notification setup
Logger::setConnection($conn);
NotificationHelper::setConnection($conn);

if (empty($qrToken)) {
    echo json_encode(['error' => 'QR token is required']);
    exit;
}

// Get current datetime
$now = new DateTime('now', new DateTimeZone('UTC'));
$timestamp = $now->format('Y-m-d H:i:s');
$todayDate = $now->format('Y-m-d');

// ========== VALIDATION STEP 1: Check for duplicate attendance ==========
$stmt = $conn->prepare("
    SELECT id FROM tblattendancelog 
    WHERE admissionNo = ? AND classId = ? AND classArmId = ? 
    AND DATE(scannedAt) = DATE(?)
    LIMIT 1
");
$stmt->bind_param('siss', $admissionNumber, $classId, $classArmId, $timestamp);
$stmt->execute();
$duplicateResult = $stmt->get_result();

if ($duplicateResult->num_rows > 0) {
    Logger::audit($admissionNumber, 'Student', 'attendance_duplicate_attempt', 'attendance', null, null, ['qrToken' => $qrToken]);
    echo json_encode([
        'error' => 'You have already marked attendance today',
        'alreadyMarked' => true
    ]);
    exit;
}

// ========== VALIDATION STEP 2: Verify QR token ==========
$stmt = $conn->prepare("
    SELECT id, expiresAt, classId, classArmId FROM tblqrcodes 
    WHERE qrToken = ? AND isActive = 1
    LIMIT 1
");
$stmt->bind_param('s', $qrToken);
$stmt->execute();
$qrResult = $stmt->get_result();

if ($qrResult->num_rows === 0) {
    Logger::audit($admissionNumber, 'Student', 'attendance_invalid_token', 'attendance', null, null, ['qrToken' => $qrToken]);
    echo json_encode(['error' => 'Invalid QR token']);
    exit;
}

$qrRecord = $qrResult->fetch_assoc();

// Check if token expired
$expiryTime = new DateTime($qrRecord['expiresAt'], new DateTimeZone('UTC'));
if ($now > $expiryTime) {
    Logger::audit($admissionNumber, 'Student', 'attendance_expired_token', 'attendance', $qrRecord['id'], null, ['expiresAt' => $qrRecord['expiresAt']]);
    echo json_encode(['error' => 'QR code has expired']);
    exit;
}

// Verify class matches
if ((int)$qrRecord['classId'] !== $classId || (int)$qrRecord['classArmId'] !== $classArmId) {
    Logger::audit($admissionNumber, 'Student', 'attendance_class_mismatch', 'attendance', $qrRecord['id']);
    echo json_encode(['error' => 'QR code is for a different class']);
    exit;
}

// ========== VALIDATION STEP 3: Location-based validation ==========
$locationStatus = 'present';
$locationError = null;

if (!is_null($latitude) && !is_null($longitude)) {
    // Get classroom location
    $stmt = $conn->prepare("
        SELECT id, latitude, longitude, radiusMeters FROM tblclasslocations 
        WHERE classId = ? AND classArmId = ? AND isActive = 1
        LIMIT 1
    ");
    $stmt->bind_param('ii', $classId, $classArmId);
    $stmt->execute();
    $locResult = $stmt->get_result();
    
    if ($locResult->num_rows > 0) {
        $classLocation = $locResult->fetch_assoc();
        $distance = calculateDistance(
            $latitude, $longitude,
            (float)$classLocation['latitude'], (float)$classLocation['longitude']
        );
        
        if ($distance > (int)$classLocation['radiusMeters']) {
            $locationStatus = 'invalid_location';
            $locationError = 'You are outside the classroom area. Distance: ' . round($distance, 2) . 'm';
            Logger::audit($admissionNumber, 'Student', 'attendance_location_invalid', 'attendance', $qrRecord['id'], null, [
                'studentLocation' => ['lat' => $latitude, 'lon' => $longitude],
                'classLocation' => ['lat' => $classLocation['latitude'], 'lon' => $classLocation['longitude']],
                'distance' => $distance
            ]);
        }
    }
}

// ========== RECORD ATTENDANCE ==========
$ipAddress = SecurityHelper::getClientIP();
$deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$stmt = $conn->prepare("
    INSERT INTO tblattendancelog 
    (admissionNo, classId, classArmId, sessionTermId, qrToken, latitude, longitude, scannedAt, ipAddress, deviceInfo, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$sessionTermId = 1; // Get from session if needed
$stmt->bind_param('siissddssss', $admissionNumber, $classId, $classArmId, $sessionTermId, $qrToken, 
                  $latitude, $longitude, $timestamp, $ipAddress, $deviceInfo, $locationStatus);

if (!$stmt->execute()) {
    Logger::error('Failed to record attendance', ['admissionNo' => $admissionNumber, 'qrToken' => $qrToken]);
    echo json_encode(['error' => 'Failed to record attendance']);
    exit;
}

// Mark QR as used
$stmt = $conn->prepare("UPDATE tblqrcodes SET isUsed = 1, usedBy = ?, usedAt = ? WHERE id = ?");
$stmt->bind_param('ssi', $admissionNumber, $timestamp, $qrRecord['id']);
$stmt->execute();

// Log successful attendance
Logger::audit($admissionNumber, 'Student', 'attendance_marked', 'tblattendancelog', $conn->insert_id, null, ['status' => $locationStatus]);

// Create notification
NotificationHelper::create('Student', null, $admissionNumber, 'Attendance Marked', 
    'Your attendance has been recorded successfully at ' . $now->format('h:i A'), 'attendance');

// Response
$response = [
    'success' => true,
    'message' => $locationStatus === 'present' ? 'Attendance marked successfully!' : 'Attendance marked but location is outside classroom area',
    'status' => $locationStatus,
    'timestamp' => $timestamp
];

if ($locationError) {
    $response['locationWarning'] = $locationError;
}

echo json_encode($response);

// ========== HELPER FUNCTION: Calculate distance between two coordinates ==========
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Earth radius in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c; // Distance in meters
}

?>
