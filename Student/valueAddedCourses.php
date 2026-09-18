<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'Value Added Courses';
$pageHeading = 'Value Added Courses';
$pageDescription = 'Explore additional courses and certificate-ready learning activities.';
$pageCards = [
  ['label' => 'Recommended', 'value' => 'Coming Soon', 'icon' => 'fas fa-lightbulb', 'color' => 'warning'],
  ['label' => 'Enrolled', 'value' => 'Coming Soon', 'icon' => 'fas fa-user-graduate', 'color' => 'primary'],
  ['label' => 'Completed', 'value' => 'Coming Soon', 'icon' => 'fas fa-check', 'color' => 'success'],
  ['label' => 'Certificates', 'value' => 'Coming Soon', 'icon' => 'fas fa-award', 'color' => 'info'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">This page is reserved for add-on academic and skill-based course listings.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';