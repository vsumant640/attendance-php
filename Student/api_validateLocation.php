<?php
/**
 * Location-Based Attendance Validation
 * Uses Geolocation API to verify student is in classroom area
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

$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];
$latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;

if (is_null($latitude) || is_null($longitude)) {
    echo json_encode(['error' => 'Location not available', 'locationAllowed' => false]);
    exit;
}

// Get classroom location
$stmt = $conn->prepare("
    SELECT id, locationName, latitude, longitude, radiusMeters FROM tblclasslocations 
    WHERE classId = ? AND classArmId = ? AND isActive = 1
    LIMIT 1
");
$stmt->bind_param('ii', $classId, $classArmId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'error' => 'Location validation not set up for your class',
        'locationAllowed' => true,
        'requiresLocation' => false
    ]);
    exit;
}

$classLocation = $result->fetch_assoc();

// Calculate distance
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

$distance = calculateDistance(
    $latitude, $longitude,
    (float)$classLocation['latitude'], (float)$classLocation['longitude']
);

$radiusMeters = (int)$classLocation['radiusMeters'];
$isWithinRadius = $distance <= $radiusMeters;

echo json_encode([
    'success' => true,
    'locationAllowed' => $isWithinRadius,
    'requiresLocation' => true,
    'classLocation' => $classLocation['locationName'],
    'distance' => round($distance, 2),
    'maxDistance' => $radiusMeters,
    'message' => $isWithinRadius ? 
        'You are within the classroom area (' . round($distance, 2) . 'm)' :
        'You are ' . round($distance, 2) . 'm away from classroom area'
]);

?>
