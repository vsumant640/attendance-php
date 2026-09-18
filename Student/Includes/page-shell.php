<?php
if (!isset($pageTitle)) {
  $pageTitle = 'Student Portal';
}

if (!isset($pageHeading)) {
  $pageHeading = $pageTitle;
}

if (!isset($pageDescription)) {
  $pageDescription = '';
}

if (!isset($pageContentHtml)) {
  $pageContentHtml = '';
}

if (!isset($pageCards) || !is_array($pageCards)) {
  $pageCards = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="../css/ruang-admin.min.css" rel="stylesheet">
</head>
<body id="page-top">
  <div id="wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include __DIR__ . '/topbar.php'; ?>
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
              <h1 class="h3 mb-0 text-gray-800"><?php echo htmlspecialchars($pageHeading); ?></h1>
              <?php if ($pageDescription !== '') { ?>
                <p class="text-muted mb-0 mt-2"><?php echo htmlspecialchars($pageDescription); ?></p>
              <?php } ?>
            </div>
          </div>

          <?php if (!empty($pageCards)) { ?>
            <div class="row mb-4">
              <?php foreach ($pageCards as $card) { ?>
                <div class="col-xl-3 col-md-6 mb-4">
                  <div class="card h-100 shadow-sm">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col mr-2">
                          <div class="text-xs font-weight-bold text-uppercase mb-1"><?php echo htmlspecialchars($card['label'] ?? ''); ?></div>
                          <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($card['value'] ?? ''); ?></div>
                        </div>
                        <div class="col-auto">
                          <i class="<?php echo htmlspecialchars($card['icon'] ?? 'fas fa-circle'); ?> fa-2x text-<?php echo htmlspecialchars($card['color'] ?? 'primary'); ?>"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php } ?>
            </div>
          <?php } ?>

          <?php echo $pageContentHtml; ?>
        </div>
      </div>
      <?php include __DIR__ . '/footer.php'; ?>
    </div>
  </div>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="../js/ruang-admin.min.js"></script>
</body>
</html>