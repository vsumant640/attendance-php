<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (isset($_SESSION['userRole']) && $_SESSION['userRole'] !== 'Administrator') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$statusMsg = '';

if (isset($_POST['upload'])) {
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $classId = $_POST['classId'] === '' ? null : (int)$_POST['classId'];
  $classArmId = $_POST['classArmId'] === '' ? null : (int)$_POST['classArmId'];

  if ($title === '' || !isset($_FILES['materialFile']) || $_FILES['materialFile']['error'] !== UPLOAD_ERR_OK) {
    $statusMsg = "<div class='alert alert-danger'>Please provide title and a valid file.</div>";
  } else {
    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
    $name = $_FILES['materialFile']['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
      $statusMsg = "<div class='alert alert-danger'>Unsupported file format.</div>";
    } else {
      $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
      $relativePath = 'uploads/materials/' . $safeName;
      $targetPath = '../' . $relativePath;

      if (move_uploaded_file($_FILES['materialFile']['tmp_name'], $targetPath)) {
        $adminId = (int)$_SESSION['userId'];
        $cls = $classId === null ? 'NULL' : "'" . $classId . "'";
        $arm = $classArmId === null ? 'NULL' : "'" . $classArmId . "'";

        $titleEsc = $conn->real_escape_string($title);
        $descEsc = $conn->real_escape_string($description);
        $pathEsc = $conn->real_escape_string($relativePath);

        $insert = "INSERT INTO tblstudymaterials(title, description, filePath, classId, classArmId, uploadedByAdminId, dateCreated)
                   VALUES('$titleEsc', '$descEsc', '$pathEsc', $cls, $arm, '$adminId', NOW())";

        if ($conn->query($insert)) {
          $statusMsg = "<div class='alert alert-success'>Study material uploaded successfully.</div>";
        } else {
          $statusMsg = "<div class='alert alert-danger'>Database save failed.</div>";
        }
      } else {
        $statusMsg = "<div class='alert alert-danger'>Could not upload file.</div>";
      }
    }
  }
}

$materials = $conn->query("SELECT m.*, c.className, ca.classArmName FROM tblstudymaterials m
LEFT JOIN tblclass c ON c.Id = m.classId
LEFT JOIN tblclassarms ca ON ca.Id = m.classArmId
ORDER BY m.Id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <?php include 'includes/title.php'; ?>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>
<body id="page-top">
  <div id="wrapper">
    <?php include 'Includes/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include 'Includes/topbar.php'; ?>

        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Study Materials</h1>
          </div>

          <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
              <h6 class="m-0 font-weight-bold text-primary">Upload Study Material</h6>
              <?php echo $statusMsg; ?>
            </div>
            <div class="card-body">
              <form method="post" enctype="multipart/form-data">
                <div class="form-group row mb-3">
                  <div class="col-xl-6">
                    <label class="form-control-label">Title</label>
                    <input type="text" class="form-control" name="title" required>
                  </div>
                  <div class="col-xl-6">
                    <label class="form-control-label">Material File</label>
                    <input type="file" class="form-control" name="materialFile" required>
                  </div>
                </div>
                <div class="form-group row mb-3">
                  <div class="col-xl-6">
                    <label class="form-control-label">Class (Optional)</label>
                    <select name="classId" class="form-control">
                      <option value="">All Classes</option>
                      <?php
                      $classes = $conn->query("SELECT * FROM tblclass ORDER BY className ASC");
                      while ($row = $classes->fetch_assoc()) {
                        echo '<option value="' . $row['Id'] . '">' . htmlspecialchars($row['className']) . '</option>';
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-xl-6">
                    <label class="form-control-label">Class Arm (Optional)</label>
                    <select name="classArmId" class="form-control">
                      <option value="">All Arms</option>
                      <?php
                      $arms = $conn->query("SELECT * FROM tblclassarms ORDER BY classArmName ASC");
                      while ($row = $arms->fetch_assoc()) {
                        echo '<option value="' . $row['Id'] . '">' . htmlspecialchars($row['classArmName']) . '</option>';
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-control-label">Description</label>
                  <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" name="upload" class="btn btn-primary">Upload</button>
              </form>
            </div>
          </div>

          <div class="card mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Uploaded Materials</h6></div>
            <div class="table-responsive p-3">
              <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                <thead class="thead-light">
                  <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Class</th>
                    <th>Arm</th>
                    <th>Uploaded On</th>
                    <th>File</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                $sn = 0;
                if ($materials && $materials->num_rows > 0) {
                  while ($mat = $materials->fetch_assoc()) {
                    $sn++;
                    echo '<tr>';
                    echo '<td>' . $sn . '</td>';
                    echo '<td>' . htmlspecialchars($mat['title']) . '</td>';
                    echo '<td>' . htmlspecialchars($mat['className'] ?: 'All') . '</td>';
                    echo '<td>' . htmlspecialchars($mat['classArmName'] ?: 'All') . '</td>';
                    echo '<td>' . htmlspecialchars($mat['dateCreated']) . '</td>';
                    echo '<td><a class="btn btn-sm btn-primary" href="../' . htmlspecialchars($mat['filePath']) . '" target="_blank">View</a></td>';
                    echo '</tr>';
                  }
                } else {
                  echo '<tr><td colspan="6">No materials uploaded yet.</td></tr>';
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
  <script src="js/ruang-admin.min.js"></script>
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
  <script>
    $(document).ready(function () { $('#dataTableHover').DataTable(); });
  </script>
</body>
</html>
