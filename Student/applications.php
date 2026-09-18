<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'Applications';
$pageHeading = 'Applications';
$pageDescription = 'Submit and track student requests from a single place.';
$pageCards = [
  ['label' => 'Submitted', 'value' => 'Coming Soon', 'icon' => 'fas fa-paper-plane', 'color' => 'primary'],
  ['label' => 'Under Review', 'value' => 'Coming Soon', 'icon' => 'fas fa-search', 'color' => 'warning'],
  ['label' => 'Approved', 'value' => 'Coming Soon', 'icon' => 'fas fa-thumbs-up', 'color' => 'success'],
  ['label' => 'Rejected', 'value' => 'Coming Soon', 'icon' => 'fas fa-times-circle', 'color' => 'danger'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">Application workflows can be connected here later, without changing the navigation structure.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';