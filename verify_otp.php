<?php
require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['password_reset_user'])) {
    header('Location: forgot_password.php');
    exit;
}

$flash = getFlash();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        $otp = cleanInput($_POST['otp'] ?? '');

        if (!preg_match('/^\d{6}$/', $otp)) {
            $errors[] = 'OTP must be 6 digits.';
        } else {
            $userId = (int)$_SESSION['password_reset_user'];
            $stmt = $conn->prepare('SELECT otp, otp_expiry FROM users WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$data || empty($data['otp']) || empty($data['otp_expiry'])) {
                $errors[] = 'OTP not found. Please request a new OTP.';
            } else if (strtotime($data['otp_expiry']) < time()) {
                $errors[] = 'OTP has expired. Please request a new OTP.';
            } else if (!password_verify($otp, $data['otp'])) {
                $errors[] = 'Invalid OTP.';
            } else {
                $_SESSION['otp_verified_user'] = $userId;
                session_regenerate_id(true);
                header('Location: reset_password.php');
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify OTP | Student Portal</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-3">Verify OTP</h3>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo esc($flash['type']); ?>"><?php echo esc($flash['message']); ?></div>
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

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrfToken()); ?>">

                        <div class="mb-3">
                            <label class="form-label">Enter 6-digit OTP</label>
                            <input type="text" class="form-control" name="otp" maxlength="6" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
                    </form>

                    <p class="mt-3 mb-0 text-center"><a href="forgot_password.php">Resend OTP</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
