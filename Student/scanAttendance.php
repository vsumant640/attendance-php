<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$msgType = isset($_GET['type']) ? $_GET['type'] : 'info';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="../img/logo/attnlg.jpg" rel="icon">
  <title>Scan Attendance QR</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="../css/ruang-admin.min.css" rel="stylesheet">
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <style>
    .scanner-stage { max-width: 720px; }
    .qr-display-section {
      background: linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%);
      border: 2px dashed #667eea;
      border-radius: 16px;
      padding: 2rem;
      text-align: center;
      margin-bottom: 2rem;
    }
    #qrcode { display: flex; justify-content: center; align-items: center; margin: 1rem 0; }
    .qr-token-display {
      margin-top: 1rem;
      padding: 0.75rem;
      background: white;
      border-radius: 8px;
      font-family: 'Courier New', monospace;
      font-size: 0.85rem;
      color: #667eea;
      font-weight: 600;
      word-break: break-all;
    }
    .scanner-shell {
      border-radius: 18px;
      background: linear-gradient(135deg, #ffffff 0%, #f6f8ff 100%);
      border: 1px solid #e6ebf5;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
      overflow: hidden;
    }
    .scanner-hero {
      padding: 1rem 1.15rem 0.75rem;
      border-bottom: 1px solid #e9edf6;
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
          <div class="scanner-stage mx-auto">
            <h1 class="h3 mb-4 text-gray-800">Scan QR Attendance</h1>

            <?php if ($msg !== '') { ?>
              <div class="alert alert-<?php echo htmlspecialchars($msgType); ?>"><?php echo htmlspecialchars($msg); ?></div>
            <?php } ?>

            <div class="qr-display-section">
              <div class="font-weight-bold text-primary mb-2">Refreshing QR Code Every 35 Seconds</div>
              <div id="qrcode"></div>
              <div class="qr-token-display" id="qrTokenDisplay">Loading...</div>
            </div>

            <div class="card mb-4 scanner-shell">
              <div class="scanner-hero">
                <h5 class="mb-0">Or Use Camera to Scan</h5>
                <p class="mb-0">Enable camera access and point at any valid QR code to mark attendance.</p>
              </div>
              <div class="card-body">
                <div id="qr-reader" style="max-width:420px; margin:auto;"></div>
                <hr>
                <form method="post" action="markAttendance.php" id="manualForm">
                  <div class="form-group">
                    <label>Or paste QR token manually</label>
                    <input type="text" name="qrToken" id="qrToken" class="form-control" required placeholder="Paste QR token here">
                  </div>
                  <button type="submit" class="btn btn-primary">Mark Attendance</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php include 'Includes/footer.php'; ?>
    </div>
  </div>

  <script>
    var qrCodeInstance = null;
    function generateNewQrCode() {
      fetch('api_generateQrToken.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.token) {
            var qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = '';
            new QRCode(qrContainer, {
              text: data.token,
              width: 200,
              height: 200,
              colorDark: '#000000',
              colorLight: '#ffffff',
              correctLevel: QRCode.CorrectLevel.H
            });
            document.getElementById('qrTokenDisplay').textContent = 'Token: ' + data.token;
          } else {
            document.getElementById('qrTokenDisplay').textContent = 'Unable to generate QR token';
          }
        })
        .catch(() => {
          document.getElementById('qrTokenDisplay').textContent = 'Error generating QR token';
        });
    }

    setInterval(generateNewQrCode, 35000);
    generateNewQrCode();

    const html5QrCode = new Html5Qrcode("qr-reader");
    Html5Qrcode.getCameras().then(devices => {
      if (devices && devices.length) {
        const cameraId = devices[0].id;
        html5QrCode.start(
          cameraId,
          { fps: 10, qrbox: { width: 250, height: 250 } },
          (decodedText) => {
            document.getElementById('qrToken').value = decodedText;
            document.getElementById('manualForm').submit();
          },
          () => {}
        );
      }
    }).catch(() => {});
  </script>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="../js/ruang-admin.min.js"></script>
</body>
</html>
