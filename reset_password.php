<?php
require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['otp_verified_user'])) {
    header('Location: forgot_password.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password and confirm password do not match.';
        }

        if (empty($errors)) {
            $userId = (int)$_SESSION['otp_verified_user'];
            $newHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare('UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL WHERE id = ?');
            $stmt->bind_param('si', $newHash, $userId);

            if ($stmt->execute()) {
                $stmt->close();
                unset($_SESSION['otp_verified_user'], $_SESSION['password_reset_user'], $_SESSION['password_reset_identifier']);
                setFlash('success', 'Password reset successful. Please login.');
                header('Location: login.php');
                exit;
            }

            $stmt->close();
            $errors[] = 'Unable to reset password. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | Student Portal</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-3">Reset Password</h3>

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

                    <form method="post" id="resetForm">
                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrfToken()); ?>">

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>

                    <p class="mt-3 mb-0 text-center"><a href="login.php">Back to login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
