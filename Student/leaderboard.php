<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
  echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
  exit;
}

$pageTitle = 'Leaderboard';
$pageHeading = 'Leaderboard';
$pageDescription = 'Track academic standing, rankings, and achievement progress.';
$pageCards = [
  ['label' => 'Current Rank', 'value' => 'Coming Soon', 'icon' => 'fas fa-trophy', 'color' => 'warning'],
  ['label' => 'Points Earned', 'value' => 'Coming Soon', 'icon' => 'fas fa-star', 'color' => 'primary'],
  ['label' => 'Badges', 'value' => 'Coming Soon', 'icon' => 'fas fa-medal', 'color' => 'success'],
  ['label' => 'Streak', 'value' => 'Coming Soon', 'icon' => 'fas fa-fire', 'color' => 'danger'],
];

ob_start();
?>
<div class="card shadow-sm">
  <div class="card-body">
    <p class="mb-0 text-gray-700">The leaderboard module is not connected yet. This page is ready for future ranking data.</p>
  </div>
</div>
<?php
$pageContentHtml = ob_get_clean();
include 'Includes/page-shell.php';