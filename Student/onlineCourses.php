<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'Online Courses';
$pageHeading = 'Online Courses';
$pageDescription = 'Browse digital learning content and track active course progress.';
$pageCards = [
  ['label' => 'Available Courses', 'value' => 'Coming Soon', 'icon' => 'fas fa-book-open', 'color' => 'primary'],
  ['label' => 'In Progress', 'value' => 'Coming Soon', 'icon' => 'fas fa-spinner', 'color' => 'warning'],
  ['label' => 'Completed', 'value' => 'Coming Soon', 'icon' => 'fas fa-check-circle', 'color' => 'success'],
  ['label' => 'Certificates', 'value' => 'Coming Soon', 'icon' => 'fas fa-certificate', 'color' => 'info'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">Online course content can be connected here later without changing the student navigation again.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';