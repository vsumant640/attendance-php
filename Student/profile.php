<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$admissionNumber = $_SESSION['admissionNumber'];
$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];

$studentQuery = "SELECT * FROM tblstudents WHERE Id = " . (int)$_SESSION['userId'] . " LIMIT 1";
$studentRs = $conn->query($studentQuery);
$student = $studentRs ? $studentRs->fetch_assoc() : null;

$summaryQuery = "SELECT
  SUM(CASE WHEN status='1' THEN 1 ELSE 0 END) AS presentDays,
  COUNT(*) AS totalDays
  FROM tblattendance
  WHERE admissionNo = '$admissionNumber'";
$summaryRs = $conn->query($summaryQuery);
$summary = $summaryRs ? $summaryRs->fetch_assoc() : ['presentDays' => 0, 'totalDays' => 0];
$presentDays = (int)$summary['presentDays'];
$totalDays = (int)$summary['totalDays'];
$attendancePercent = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;

$classQuery = "SELECT c.className, a.classArmName
FROM tblclass c
INNER JOIN tblclassarms a ON a.Id = '$classArmId'
WHERE c.Id = '$classId' LIMIT 1";
$classRs = $conn->query($classQuery);
$classRow = $classRs ? $classRs->fetch_assoc() : ['className' => '-', 'classArmName' => '-'];

$fullName = $student ? trim(($student['firstName'] ?? '') . ' ' . ($student['lastName'] ?? '') . ' ' . ($student['otherName'] ?? '')) : 'Student';
$pageTitle = 'Profile';
$pageHeading = 'My Profile';
$pageDescription = 'View your student identity, class details, and attendance snapshot.';
$pageCards = [
  ['label' => 'Class', 'value' => $classRow['className'] . ' - ' . $classRow['classArmName'], 'icon' => 'fas fa-chalkboard', 'color' => 'primary'],
  ['label' => 'Present Days', 'value' => (string)$presentDays, 'icon' => 'fas fa-calendar-check', 'color' => 'success'],
  ['label' => 'Total Days', 'value' => (string)$totalDays, 'icon' => 'fas fa-calendar', 'color' => 'warning'],
  ['label' => 'Attendance', 'value' => $attendancePercent . '%', 'icon' => 'fas fa-chart-line', 'color' => 'info'],
];

ob_start();
?>
<div class="row">
  <div class="col-lg-7 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="mb-3">Student Details</h5>
        <div class="row">
          <div class="col-sm-6 mb-3">
            <div class="text-xs font-weight-bold text-uppercase mb-1">Full Name</div>
            <div class="font-weight-bold text-gray-800"><?php echo htmlspecialchars($fullName); ?></div>
          </div>
          <div class="col-sm-6 mb-3">
            <div class="text-xs font-weight-bold text-uppercase mb-1">Admission Number</div>
            <div class="font-weight-bold text-gray-800"><?php echo htmlspecialchars($student['admissionNumber'] ?? $admissionNumber); ?></div>
          </div>
          <div class="col-sm-6 mb-3">
            <div class="text-xs font-weight-bold text-uppercase mb-1">Class</div>
            <div class="font-weight-bold text-gray-800"><?php echo htmlspecialchars($classRow['className'] . ' - ' . $classRow['classArmName']); ?></div>
          </div>
          <div class="col-sm-6 mb-3">
            <div class="text-xs font-weight-bold text-uppercase mb-1">Student ID</div>
            <div class="font-weight-bold text-gray-800"><?php echo htmlspecialchars((string)($_SESSION['userId'] ?? '')); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5 mb-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="mb-3">Quick Actions</h5>
        <a class="btn btn-primary btn-block mb-2" href="scanAttendance.php">Scan QR Attendance</a>
        <a class="btn btn-outline-primary btn-block mb-2" href="viewAttendance.php">View Attendance</a>
        <a class="btn btn-outline-secondary btn-block" href="studyMaterials.php">Study Materials</a>
      </div>
    </div>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';