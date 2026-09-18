<?php
require_once __DIR__ . '/config/db.php';
requireLogin();

$userId = currentUserId();
$errors = [];
$success = '';

$stmt = $conn->prepare('SELECT id, name, email, phone, role, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $name = cleanInput($_POST['name'] ?? '');
            $phone = cleanInput($_POST['phone'] ?? '');

            if ($name === '' || strlen($name) < 3) {
                $errors[] = 'Name must be at least 3 characters long.';
            }

            if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
                $errors[] = 'Phone number must contain 10 to 15 digits.';
            }

            if (empty($errors)) {
                $checkPhone = $conn->prepare('SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1');
                $checkPhone->bind_param('si', $phone, $userId);
                $checkPhone->execute();
                $phoneExists = $checkPhone->get_result()->fetch_assoc();
                $checkPhone->close();

                if ($phoneExists) {
                    $errors[] = 'Phone number already in use.';
                }
            }

            if (empty($errors)) {
                $update = $conn->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?');
                $update->bind_param('ssi', $name, $phone, $userId);
                $update->execute();
                $update->close();

                $_SESSION['auth_user']['name'] = $name;
                $_SESSION['auth_user']['phone'] = $phone;
                $success = 'Profile updated successfully.';
            }
        }

        if ($action === 'update_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $passwordStmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
            $passwordStmt->bind_param('i', $userId);
            $passwordStmt->execute();
            $passwordData = $passwordStmt->get_result()->fetch_assoc();
            $passwordStmt->close();

            if (!$passwordData || !password_verify($currentPassword, $passwordData['password'])) {
                $errors[] = 'Current password is incorrect.';
            }

            if (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters long.';
            }

            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirm password do not match.';
            }

            if (empty($errors)) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updPass = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $updPass->bind_param('si', $newHash, $userId);
                $updPass->execute();
                $updPass->close();
                $success = 'Password updated successfully.';
            }
        }
    }

    $stmt = $conn->prepare('SELECT id, name, email, phone, role, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile | Student Portal</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">My Profile</h3>
        <div>
            <a href="<?php echo $user['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo esc($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo esc($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Profile Details</h5>
                    <p class="text-muted mb-3">Role: <?php echo esc($user['role']); ?> | Joined: <?php echo esc($user['created_at']); ?></p>

                    <form method="post" id="profileForm">
                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrfToken()); ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo esc($user['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo esc($user['email']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo esc($user['phone']); ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Change Password</h5>
                    <form method="post" id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrfToken()); ?>">
                        <input type="hidden" name="action" value="update_password">

                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-warning">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
