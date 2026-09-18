<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];

$query = "SELECT * FROM tblstudymaterials
WHERE classId IS NULL OR classId = '$classId' OR (classId = '$classId' AND classArmId = '$classArmId')
ORDER BY dateCreated DESC";
$rs = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>Study Materials</title>
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
          <h1 class="h3 mb-4 text-gray-800">Study Materials</h1>

          <div class="card mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Available Files</h6>
            </div>
            <div class="table-responsive p-3">
              <table class="table align-items-center table-flush table-hover">
                <thead class="thead-light">
                  <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Uploaded On</th>
                    <th>Download</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                $sn = 0;
                if ($rs && $rs->num_rows > 0) {
                  while ($row = $rs->fetch_assoc()) {
                    $sn++;
                    echo '<tr>';
                    echo '<td>' . $sn . '</td>';
                    echo '<td>' . htmlspecialchars($row['title']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['description']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['dateCreated']) . '</td>';
                    echo '<td><a class="btn btn-sm btn-primary" href="../' . htmlspecialchars($row['filePath']) . '" target="_blank">Download</a></td>';
                    echo '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="5">No study materials available yet.</td></tr>';
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
</body>
</html>
