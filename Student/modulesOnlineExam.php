<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'Modules / Online Exam';
$pageHeading = 'Modules / Online Exam';
$pageDescription = 'Access module-wise tests, online exam schedules, and attempt history.';
$pageCards = [
  ['label' => 'Upcoming Tests', 'value' => 'Coming Soon', 'icon' => 'fas fa-calendar-day', 'color' => 'primary'],
  ['label' => 'Mock Exams', 'value' => 'Coming Soon', 'icon' => 'fas fa-file-alt', 'color' => 'warning'],
  ['label' => 'Attempts', 'value' => 'Coming Soon', 'icon' => 'fas fa-history', 'color' => 'success'],
  ['label' => 'Results', 'value' => 'Coming Soon', 'icon' => 'fas fa-poll', 'color' => 'info'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">Online exam workflows can be connected here later. The menu item now has a real student page.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';