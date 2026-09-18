<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

if (!isset($_POST['qrToken']) || trim($_POST['qrToken']) === '') {
  header('Location: scanAttendance.php?type=danger&msg=Invalid QR token');
  exit;
}

$token = trim($_POST['qrToken']);
$studentAdmission = $_SESSION['admissionNumber'];
$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];
$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT * FROM tblqrcodes WHERE qrToken = ? AND isActive = 1 LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$qrResult = $stmt->get_result();
$qrRow = $qrResult->fetch_assoc();
$stmt->close();

if (!$qrRow) {
  header('Location: scanAttendance.php?type=danger&msg=QR code not found or inactive');
  exit;
}

if (strtotime($qrRow['expiresAt']) < time()) {
  header('Location: scanAttendance.php?type=danger&msg=QR code expired');
  exit;
}

if ((int)$qrRow['classId'] !== $classId || (int)$qrRow['classArmId'] !== $classArmId) {
  header('Location: scanAttendance.php?type=danger&msg=This QR is not for your class');
  exit;
}

if ($qrRow['attendanceDate'] !== $today) {
  header('Location: scanAttendance.php?type=danger&msg=This QR is not valid for today');
  exit;
}

$existingStmt = $conn->prepare("SELECT Id, status FROM tblattendance WHERE admissionNo = ? AND classId = ? AND classArmId = ? AND dateTimeTaken = ? LIMIT 1");
$existingStmt->bind_param('siis', $studentAdmission, $classId, $classArmId, $today);
$existingStmt->execute();
$existingRs = $existingStmt->get_result();
$existing = $existingRs->fetch_assoc();
$existingStmt->close();

if ($existing) {
  if ($existing['status'] === '1') {
    header('Location: scanAttendance.php?type=info&msg=Attendance already marked as present');
    exit;
  }

  $updateStmt = $conn->prepare("UPDATE tblattendance SET status = '1', qrCodeId = ?, markedBy = 'qr' WHERE Id = ?");
  $qrId = (int)$qrRow['Id'];
  $attendanceId = (int)$existing['Id'];
  $updateStmt->bind_param('ii', $qrId, $attendanceId);
  $ok = $updateStmt->execute();
  $updateStmt->close();

  if ($ok) {
    header('Location: scanAttendance.php?type=success&msg=Attendance marked successfully');
  } else {
    header('Location: scanAttendance.php?type=danger&msg=Unable to mark attendance');
  }
  exit;
}

$insertStmt = $conn->prepare("INSERT INTO tblattendance(admissionNo, classId, classArmId, sessionTermId, status, qrCodeId, markedBy, dateTimeTaken) VALUES(?, ?, ?, ?, '1', ?, 'qr', ?)");
$sessionTermId = (int)$qrRow['sessionTermId'];
$qrId = (int)$qrRow['Id'];
$insertStmt->bind_param('siiiis', $studentAdmission, $classId, $classArmId, $sessionTermId, $qrId, $today);
$ok = $insertStmt->execute();
$insertStmt->close();

if ($ok) {
  header('Location: scanAttendance.php?type=success&msg=Attendance marked successfully');
} else {
  header('Location: scanAttendance.php?type=danger&msg=Unable to save attendance');
}
exit;
