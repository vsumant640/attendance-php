<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow Student role
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];

// Get active session/term
$termQ = $conn->query("SELECT Id FROM tblsessionterm WHERE isActive='1' LIMIT 1");
$termRow = $termQ ? $termQ->fetch_assoc() : null;
$sessionTermId = $termRow ? (int)$termRow['Id'] : 0;

if ($sessionTermId === 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'No active session term. Please activate a session in Admin > Manage Session & Term.']);
  exit;
}

// Generate new QR token
$today = date('Y-m-d');
$token = strtoupper(bin2hex(random_bytes(8))) . '-' . $classId . '-' . $classArmId;
$validMinutes = 5; // Token valid for 5 minutes
$expiresAt = date('Y-m-d H:i:s', strtotime('+' . $validMinutes . ' minutes'));

$stmt = $conn->prepare("INSERT INTO tblqrcodes(qrToken, classId, classArmId, sessionTermId, createdByTeacherId, attendanceDate, expiresAt, isActive, dateCreated)
VALUES(?, ?, ?, ?, ?, ?, ?, 1, NOW())");

// Use 0 for createdByTeacherId since it's auto-generated
$createdByTeacherId = 0;
$stmt->bind_param('siiiiss', $token, $classId, $classArmId, $sessionTermId, $createdByTeacherId, $today, $expiresAt);

if ($stmt->execute()) {
  $stmt->close();
  echo json_encode(['token' => $token, 'success' => true]);
} else {
  $stmt->close();
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Failed to generate token']);
}
?>
