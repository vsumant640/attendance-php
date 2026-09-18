<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'e-Grievance';
$pageHeading = 'e-Grievance';
$pageDescription = 'Raise support issues and monitor complaint resolution progress.';
$pageCards = [
  ['label' => 'Open Tickets', 'value' => 'Coming Soon', 'icon' => 'fas fa-ticket-alt', 'color' => 'primary'],
  ['label' => 'In Progress', 'value' => 'Coming Soon', 'icon' => 'fas fa-spinner', 'color' => 'warning'],
  ['label' => 'Resolved', 'value' => 'Coming Soon', 'icon' => 'fas fa-check', 'color' => 'success'],
  ['label' => 'Escalated', 'value' => 'Coming Soon', 'icon' => 'fas fa-exclamation-circle', 'color' => 'danger'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">Support and grievance handling can be connected here later for students.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';