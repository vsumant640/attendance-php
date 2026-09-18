<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'Training and Placement';
$pageHeading = 'Training and Placement';
$pageDescription = 'Stay updated with placement drives, workshops, and career opportunities.';
$pageCards = [
  ['label' => 'Opportunities', 'value' => 'Coming Soon', 'icon' => 'fas fa-briefcase', 'color' => 'primary'],
  ['label' => 'Applications', 'value' => 'Coming Soon', 'icon' => 'fas fa-file-signature', 'color' => 'warning'],
  ['label' => 'Interviews', 'value' => 'Coming Soon', 'icon' => 'fas fa-comments', 'color' => 'success'],
  ['label' => 'Workshops', 'value' => 'Coming Soon', 'icon' => 'fas fa-users', 'color' => 'info'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">Training and placement updates can be connected here later with the same portal navigation.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';