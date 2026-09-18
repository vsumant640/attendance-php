<?php
require_once __DIR__ . '/config/db.php';
requireLogin();

if (($_SESSION['auth_user']['role'] ?? '') !== 'admin') {
    redirectByRole($_SESSION['auth_user']['role'] ?? 'student');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Admin Dashboard</h3>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5>Welcome, <?php echo esc($_SESSION['auth_user']['name']); ?></h5>
            <p class="mb-1">Role: <?php echo esc($_SESSION['auth_user']['role']); ?></p>
            <p class="mb-3">This is the secure admin entry point for the new auth module.</p>
            <a class="btn btn-primary" href="profile.php">Manage Profile</a>
        </div>
    </div>
</div>
</body>
</html>
