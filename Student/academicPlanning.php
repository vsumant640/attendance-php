<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'Academic Planning';
$pageHeading = 'Academic Planning';
$pageDescription = 'Review learning milestones, upcoming tasks, and study plans.';
$pageCards = [
  ['label' => 'Upcoming Classes', 'value' => 'Coming Soon', 'icon' => 'fas fa-chalkboard-teacher', 'color' => 'primary'],
  ['label' => 'Assignments', 'value' => 'Coming Soon', 'icon' => 'fas fa-clipboard-list', 'color' => 'warning'],
  ['label' => 'Goals', 'value' => 'Coming Soon', 'icon' => 'fas fa-bullseye', 'color' => 'success'],
  ['label' => 'Deadlines', 'value' => 'Coming Soon', 'icon' => 'fas fa-clock', 'color' => 'danger'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">This page is ready for study plans, schedules, and milestone tracking for students.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';