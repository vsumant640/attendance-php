<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'Inventory';
$pageHeading = 'Inventory';
$pageDescription = 'Track issued resources, device allocations, and return status.';
$pageCards = [
  ['label' => 'Issued Items', 'value' => 'Coming Soon', 'icon' => 'fas fa-box', 'color' => 'primary'],
  ['label' => 'Due Returns', 'value' => 'Coming Soon', 'icon' => 'fas fa-undo', 'color' => 'warning'],
  ['label' => 'Requests', 'value' => 'Coming Soon', 'icon' => 'fas fa-inbox', 'color' => 'success'],
  ['label' => 'Status', 'value' => 'Coming Soon', 'icon' => 'fas fa-clipboard-check', 'color' => 'info'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">Student inventory records can be connected here when the backend is ready.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';