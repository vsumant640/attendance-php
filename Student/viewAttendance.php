<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$admissionNumber = $_SESSION['admissionNumber'];
$query = "SELECT
  status,
  markedBy,
  dateTimeTaken,
  classId AS className,
  classArmId AS classArmName
FROM tblattendance
WHERE admissionNo = '$admissionNumber'
ORDER BY dateTimeTaken DESC";
$rs = $conn->query($query);

$statsQ = "SELECT SUM(CASE WHEN status='1' THEN 1 ELSE 0 END) AS presentCount, COUNT(*) AS totalCount FROM tblattendance WHERE admissionNo = '$admissionNumber'";
$statsRs = $conn->query($statsQ);
$stats = $statsRs ? $statsRs->fetch_assoc() : ['presentCount' => 0, 'totalCount' => 0];
$presentCount = (int)$stats['presentCount'];
$totalCount = (int)$stats['totalCount'];
$percent = $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>My Attendance</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="../css/ruang-admin.min.css" rel="stylesheet">
</head>
<body id="page-top">
  <div id="wrapper">
    <?php include 'Includes/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include 'Includes/topbar.php'; ?>
        <div class="container-fluid" id="container-wrapper">
          <h1 class="h3 mb-4 text-gray-800">My Attendance</h1>

          <div class="alert alert-info">Attendance Percentage: <strong><?php echo $percent; ?>%</strong> (<?php echo $presentCount; ?> Present / <?php echo $totalCount; ?> Total)</div>

          <div class="card mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
            </div>
            <div class="table-responsive p-3">
              <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                <thead class="thead-light">
                  <tr>
                    <th>#</th>
                    <th>Class</th>
                    <th>Class Arm</th>
                    <th>Status</th>
                    <th>Marked By</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                $sn = 0;
                if ($rs && $rs->num_rows > 0) {
                  while ($row = $rs->fetch_assoc()) {
                    $sn++;
                    $statusLabel = $row['status'] === '1' ? 'Present' : 'Absent';
                    $statusClass = $row['status'] === '1' ? 'success' : 'danger';
                    echo '<tr>';
                    echo '<td>' . $sn . '</td>';
                    echo '<td>' . htmlspecialchars($row['className']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['classArmName']) . '</td>';
                    echo '<td><span class="badge badge-' . $statusClass . '">' . $statusLabel . '</span></td>';
                    echo '<td>' . htmlspecialchars($row['markedBy']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['dateTimeTaken']) . '</td>';
                    echo '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="6">No attendance records found.</td></tr>';
                }
                ?>
                </tbody>
              </table>
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
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
  <script>
    $(document).ready(function () { $('#dataTableHover').DataTable(); });
  </script>
</body>
</html>
