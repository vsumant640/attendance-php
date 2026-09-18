<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'ClassTeacher') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];
$teacherId = (int)$_SESSION['userId'];
$statusMsg = '';
$generatedToken = '';

$termQ = $conn->query("SELECT Id FROM tblsessionterm WHERE isActive='1' LIMIT 1");
$termRow = $termQ ? $termQ->fetch_assoc() : null;
$sessionTermId = $termRow ? (int)$termRow['Id'] : 0;

if (isset($_POST['generate']) && $sessionTermId > 0) {
  $validMinutes = isset($_POST['validMinutes']) ? (int)$_POST['validMinutes'] : 15;
  if ($validMinutes < 1 || $validMinutes > 180) {
    $validMinutes = 15;
  }

  $today = date('Y-m-d');
  $token = strtoupper(bin2hex(random_bytes(8))) . '-' . $classId . '-' . $classArmId;
  $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $validMinutes . ' minutes'));

  $stmt = $conn->prepare("INSERT INTO tblqrcodes(qrToken, classId, classArmId, sessionTermId, createdByTeacherId, attendanceDate, expiresAt, isActive, dateCreated)
  VALUES(?, ?, ?, ?, ?, ?, ?, 1, NOW())");
  $stmt->bind_param('siiiiss', $token, $classId, $classArmId, $sessionTermId, $teacherId, $today, $expiresAt);

  if ($stmt->execute()) {
    $generatedToken = $token;
    $statusMsg = "<div class='alert alert-success'>QR token generated successfully. Students can now scan it.</div>";
  } else {
    $statusMsg = "<div class='alert alert-danger'>Unable to generate QR token.</div>";
  }
  $stmt->close();
}

$qrList = $conn->query("SELECT * FROM tblqrcodes
WHERE classId = '$classId' AND classArmId = '$classArmId'
ORDER BY Id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>Generate Attendance QR</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body id="page-top">
  <div id="wrapper">
    <?php include 'Includes/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include 'Includes/topbar.php'; ?>

        <div class="container-fluid" id="container-wrapper">
          <h1 class="h3 mb-4 text-gray-800">Generate QR Attendance</h1>

          <?php echo $statusMsg; ?>

          <?php if ($sessionTermId === 0) { ?>
            <div class="alert alert-warning">No active session/term found. Ask admin to activate a session term first.</div>
          <?php } ?>

          <div class="card mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Create New QR</h6></div>
            <div class="card-body">
              <form method="post">
                <div class="form-group" style="max-width:280px;">
                  <label>Valid For (Minutes)</label>
                  <input type="number" name="validMinutes" class="form-control" min="1" max="180" value="15" required>
                </div>
                <button type="submit" name="generate" class="btn btn-primary">Generate QR</button>
              </form>
            </div>
          </div>

          <?php if ($generatedToken !== '') { ?>
          <div class="card mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success">Latest Generated QR</h6></div>
            <div class="card-body">
              <p><strong>Token:</strong> <?php echo htmlspecialchars($generatedToken); ?></p>
              <div id="qrcode"></div>
            </div>
          </div>
          <?php } ?>

          <div class="card mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Recent QR Codes</h6></div>
            <div class="table-responsive p-3">
              <table class="table align-items-center table-flush table-hover">
                <thead class="thead-light">
                  <tr>
                    <th>#</th>
                    <th>Token</th>
                    <th>Date</th>
                    <th>Expires At</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                $sn = 0;
                if ($qrList && $qrList->num_rows > 0) {
                  while ($row = $qrList->fetch_assoc()) {
                    $sn++;
                    $expired = strtotime($row['expiresAt']) < time();
                    $label = $expired ? 'Expired' : 'Active';
                    echo '<tr>';
                    echo '<td>' . $sn . '</td>';
                    echo '<td>' . htmlspecialchars($row['qrToken']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['attendanceDate']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['expiresAt']) . '</td>';
                    echo '<td>' . $label . '</td>';
                    echo '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="5">No QR codes generated yet.</td></tr>';
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

  <?php if ($generatedToken !== '') { ?>
  <script>
    new QRCode(document.getElementById('qrcode'), {
      text: <?php echo json_encode($generatedToken); ?>,
      width: 220,
      height: 220
    });
  </script>
  <?php } ?>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
</body>
</html>
