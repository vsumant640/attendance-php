<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'Fees';
$pageHeading = 'Fees';
$pageDescription = 'Review fee status, payment history, and pending dues.';
$pageCards = [
  ['label' => 'Total Fees', 'value' => 'Coming Soon', 'icon' => 'fas fa-rupee-sign', 'color' => 'primary'],
  ['label' => 'Paid', 'value' => 'Coming Soon', 'icon' => 'fas fa-check-circle', 'color' => 'success'],
  ['label' => 'Pending', 'value' => 'Coming Soon', 'icon' => 'fas fa-exclamation-triangle', 'color' => 'warning'],
  ['label' => 'Receipts', 'value' => 'Coming Soon', 'icon' => 'fas fa-file-invoice', 'color' => 'info'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">Fee collection and receipt download can be connected here later.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';