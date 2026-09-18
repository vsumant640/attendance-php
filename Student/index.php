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
$studentName = isset($_SESSION['firstName']) ? ucfirst($_SESSION['firstName']) : 'Student';

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

$todayName = date('l');
$currentTime = date('H:i:s');
$todaySessions = [];
$currentSession = null;
$nextSession = null;

$timetableStmt = $conn->prepare("SELECT subject, teacher, roomNo, startTime, endTime
FROM tbltimetables
WHERE classId = ? AND classArmId = ? AND dayOfWeek = ?
ORDER BY startTime ASC");

if ($timetableStmt) {
  $timetableStmt->bind_param('iis', $classId, $classArmId, $todayName);
  $timetableStmt->execute();
  $todayResult = $timetableStmt->get_result();

  while ($row = $todayResult->fetch_assoc()) {
    $todaySessions[] = $row;
  }
  $timetableStmt->close();
}

foreach ($todaySessions as $session) {
  if ($currentTime >= $session['startTime'] && $currentTime <= $session['endTime']) {
    $currentSession = $session;
    break;
  }

  if ($currentTime < $session['startTime'] && $nextSession === null) {
    $nextSession = $session;
  }
}

// Get recent study materials
$materialsQuery = "SELECT id, title, description, uploadedOn 
FROM tblstudymaterials 
WHERE classId = '$classId' AND classArmId = '$classArmId' 
ORDER BY uploadedOn DESC LIMIT 3";
$materialsRs = $conn->query($materialsQuery);
$materials = [];
if ($materialsRs) {
  while ($row = $materialsRs->fetch_assoc()) {
    $materials[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="../img/logo/attnlg.jpg" rel="icon">
  <?php include 'Includes/title.php'; ?>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="../css/ruang-admin.min.css" rel="stylesheet">
  <style>
    /* Welcome Banner */
    .welcome-section {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 20px;
      padding: 2.5rem 2rem;
      color: white;
      margin-bottom: 2.5rem;
      box-shadow: 0 20px 50px rgba(102, 126, 234, 0.25);
      position: relative;
      overflow: hidden;
    }

    .welcome-section::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 400px;
      height: 400px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    }

    .welcome-section::after {
      content: '';
      position: absolute;
      bottom: -30%;
      left: -5%;
      width: 300px;
      height: 300px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 50%;
    }

    .welcome-content {
      position: relative;
      z-index: 1;
    }

    .welcome-heading {
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      letter-spacing: -0.5px;
    }

    .welcome-subtitle {
      font-size: 1rem;
      opacity: 0.95;
      font-weight: 500;
    }

    /* Quick Action Cards */
    .quick-action-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 1rem;
      margin-bottom: 2.5rem;
    }

    .quick-action-card {
      background: white;
      border: 2px solid #f0f2f5;
      border-radius: 16px;
      padding: 1.5rem 1.25rem;
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 140px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    .quick-action-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
      border-color: #667eea;
    }

    .quick-action-icon {
      font-size: 2.2rem;
      margin-bottom: 0.75rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .quick-action-label {
      font-size: 0.9rem;
      font-weight: 600;
      color: #2d3748;
    }

    /* Stats Cards */
    .stat-card {
      background: white;
      border: 1px solid #e6ebf3;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
      transition: all 0.3s ease;
      height: 100%;
    }

    .stat-card:hover {
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }

    .stat-label {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      color: #718096;
      letter-spacing: 0.5px;
      margin-bottom: 0.75rem;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 800;
      color: #1a202c;
      margin-bottom: 0.5rem;
    }

    .stat-icon {
      font-size: 2.5rem;
      opacity: 0.15;
      position: absolute;
      top: 1rem;
      right: 1rem;
    }

    .stat-card-wrapper {
      position: relative;
    }

    .stat-subtitle {
      font-size: 0.85rem;
      color: #a0aec0;
    }

    /* Study Materials Section */
    .materials-section {
      margin-top: 3rem;
    }

    .materials-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
    }

    .materials-header h2 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #2d3748;
      margin: 0;
    }

    .view-all-link {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      transition: color 0.3s ease;
    }

    .view-all-link:hover {
      color: #764ba2;
      text-decoration: underline;
    }

    .material-card {
      background: white;
      border: 1px solid #e6ebf3;
      border-radius: 14px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    .material-card:hover {
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
      border-color: #667eea;
    }

    .material-icon {
      font-size: 2rem;
      color: #667eea;
      margin-bottom: 0.75rem;
    }

    .material-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: #2d3748;
      margin-bottom: 0.5rem;
    }

    .material-date {
      font-size: 0.8rem;
      color: #a0aec0;
    }

    .material-description {
      font-size: 0.9rem;
      color: #4a5568;
      margin-top: 0.5rem;
      line-height: 1.5;
    }

    /* Info Banner */
    .info-banner {
      background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
      border-left: 4px solid #ec8c65;
      border-radius: 12px;
      padding: 1rem 1.5rem;
      margin-top: 2.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .info-banner-icon {
      font-size: 2rem;
      color: #c65911;
    }

    .info-banner-content {
      flex: 1;
    }

    .info-banner-title {
      font-weight: 700;
      color: #c65911;
      margin-bottom: 0.25rem;
    }

    .info-banner-text {
      font-size: 0.9rem;
      color: #a0563d;
    }

    .subject-now-card {
      background: linear-gradient(120deg, #0ea5e9, #1d4ed8);
      border-radius: 16px;
      color: #fff;
      padding: 1.25rem 1.5rem;
      margin-bottom: 1.6rem;
      box-shadow: 0 10px 28px rgba(29, 78, 216, 0.25);
    }

    .subject-now-title {
      font-size: 0.9rem;
      opacity: 0.92;
      margin-bottom: 0.4rem;
    }

    .subject-now-main {
      font-size: 1.3rem;
      font-weight: 800;
      margin-bottom: 0.45rem;
    }

    .subject-now-meta {
      font-size: 0.92rem;
      opacity: 0.95;
      margin-bottom: 0.15rem;
    }

    .subject-next {
      margin-top: 0.7rem;
      border-top: 1px solid rgba(255, 255, 255, 0.35);
      padding-top: 0.65rem;
      font-size: 0.9rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .welcome-heading {
        font-size: 1.5rem;
      }

      .quick-action-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .stat-card {
        padding: 1.2rem;
      }

      .stat-value {
        font-size: 1.75rem;
      }
    }
  </style>
</head>
<body id="page-top">
  <div id="wrapper">
    <?php include 'Includes/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include 'Includes/topbar.php'; ?>
        <div class="container-fluid" id="container-wrapper">
          
          <!-- Welcome Section -->
          <div class="welcome-section">
            <div class="welcome-content">
              <h1 class="welcome-heading">Welcome back, <?php echo htmlspecialchars($studentName); ?>! 👋</h1>
              <p class="welcome-subtitle">You're all set to manage your attendance and explore learning resources</p>
            </div>
          </div>

          <div class="subject-now-card">
            <div class="subject-now-title">Today: <?php echo htmlspecialchars($todayName); ?> | Current time: <?php echo date('h:i A'); ?></div>

            <?php if ($currentSession): ?>
              <div class="subject-now-main">Now Running: <?php echo htmlspecialchars($currentSession['subject']); ?></div>
              <div class="subject-now-meta">
                <i class="fas fa-clock mr-1"></i><?php echo date('h:i A', strtotime($currentSession['startTime'])); ?> - <?php echo date('h:i A', strtotime($currentSession['endTime'])); ?>
              </div>
              <div class="subject-now-meta">
                <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($currentSession['teacher'] ?: 'TBA'); ?>
                &nbsp; | &nbsp;
                <i class="fas fa-door-open mr-1"></i><?php echo htmlspecialchars($currentSession['roomNo'] ?: 'TBA'); ?>
              </div>
            <?php else: ?>
              <div class="subject-now-main">No subject running right now</div>
            <?php endif; ?>

            <?php if ($nextSession): ?>
              <div class="subject-next">
                Next Subject: <strong><?php echo htmlspecialchars($nextSession['subject']); ?></strong>
                at <?php echo date('h:i A', strtotime($nextSession['startTime'])); ?>
              </div>
            <?php elseif (!$currentSession): ?>
              <div class="subject-next">No more classes scheduled for today.</div>
            <?php endif; ?>
          </div>

          <!-- Quick Actions -->
          <div class="quick-action-grid">
            <a href="scanAttendance.php" class="quick-action-card">
              <div class="quick-action-icon"><i class="fas fa-qrcode"></i></div>
              <div class="quick-action-label">Scan QR</div>
            </a>
            <a href="viewAttendance.php" class="quick-action-card">
              <div class="quick-action-icon"><i class="fas fa-chart-bar"></i></div>
              <div class="quick-action-label">My Attendance</div>
            </a>
            <a href="studyMaterials.php" class="quick-action-card">
              <div class="quick-action-icon"><i class="fas fa-book"></i></div>
              <div class="quick-action-label">Study Materials</div>
            </a>
            <a href="profile.php" class="quick-action-card">
              <div class="quick-action-icon"><i class="fas fa-user-circle"></i></div>
              <div class="quick-action-label">My Profile</div>
            </a>
          </div>

          <!-- Statistics Section -->
          <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="stat-card">
                <div class="stat-card-wrapper">
                  <div class="stat-label">Class Information</div>
                  <div class="stat-value"><?php echo htmlspecialchars($classRow['className']); ?></div>
                  <div class="stat-subtitle"><?php echo htmlspecialchars($classRow['classArmName']); ?></div>
                  <div class="stat-icon"><i class="fas fa-chalkboard"></i></div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="stat-card">
                <div class="stat-card-wrapper">
                  <div class="stat-label">Days Present</div>
                  <div class="stat-value"><?php echo $presentDays; ?></div>
                  <div class="stat-subtitle">out of <?php echo $totalDays; ?> class days</div>
                  <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="stat-card">
                <div class="stat-card-wrapper">
                  <div class="stat-label">Attendance Rate</div>
                  <div class="stat-value"><?php echo $attendancePercent; ?>%</div>
                  <div class="stat-subtitle">Keep it above 75%</div>
                  <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="stat-card">
                <div class="stat-card-wrapper">
                  <div class="stat-label">Status</div>
                  <div class="stat-value" style="font-size: 1.5rem; color: #48bb78;">
                    <i class="fas fa-check-circle"></i>
                  </div>
                  <div class="stat-subtitle">Active • Everything's good</div>
                  <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Study Materials -->
          <?php if (!empty($materials)): ?>
          <div class="materials-section">
            <div class="materials-header">
              <h2>Recent Study Materials</h2>
              <a href="studyMaterials.php" class="view-all-link">View all →</a>
            </div>
            <div class="row">
              <?php foreach ($materials as $material): ?>
              <div class="col-md-6 col-lg-4">
                <div class="material-card">
                  <div class="material-icon"><i class="fas fa-file-pdf"></i></div>
                  <div class="material-title"><?php echo htmlspecialchars(substr($material['title'], 0, 40)); ?></div>
                  <div class="material-date"><?php echo date('M d, Y', strtotime($material['uploadedOn'])); ?></div>
                  <?php if ($material['description']): ?>
                  <div class="material-description"><?php echo htmlspecialchars(substr($material['description'], 0, 80)) . '...'; ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Info Banner -->
          <div class="info-banner">
            <div class="info-banner-icon"><i class="fas fa-lightbulb"></i></div>
            <div class="info-banner-content">
              <div class="info-banner-title">💡 Pro Tip</div>
              <div class="info-banner-text">Mark your attendance daily using the QR code scanner. Regular attendance is crucial for your academic success and eligibility for exams.</div>
            </div>
          </div>

        </div>
      </div>
      <?php include 'Includes/footer.php'; ?>
    </div>
  </div>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="../js/ruang-admin.min.js"></script>
</body>
</html>
