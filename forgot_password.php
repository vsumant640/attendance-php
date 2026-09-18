<?php
require_once __DIR__ . '/config/db.php';

$errors = [];
$success = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        $identifier = cleanInput($_POST['identifier'] ?? '');

        if ($identifier === '') {
            $errors[] = 'Please enter your registered email or phone.';
        } else {
            $stmt = $conn->prepare('SELECT id, email, phone FROM users WHERE email = ? OR phone = ? LIMIT 1');
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $errors[] = 'No account found with provided email or phone.';
            } else {
                $otp = (string)random_int(100000, 999999);
                $otpHash = password_hash($otp, PASSWORD_DEFAULT);
                $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $upd = $conn->prepare('UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?');
                $upd->bind_param('ssi', $otpHash, $expiry, $user['id']);

                if ($upd->execute()) {
                    $upd->close();
                    sendOtpMessage($user['email'], $user['phone'], $otp);

                    $_SESSION['password_reset_user'] = (int)$user['id'];
                    $_SESSION['password_reset_identifier'] = $identifier;

                    setFlash('success', 'OTP sent successfully. Please verify OTP to continue.');
                    header('Location: verify_otp.php');
                    exit;
                }

                $upd->close();
                $errors[] = 'Unable to generate OTP. Please try again.';
            }
        }
    }
}

$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | Student Portal</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-3">Forgot Password</h3>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo esc($flash['type']); ?>"><?php echo esc($flash['message']); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['otp_simulation'])): ?>
                        <div class="alert alert-info">
                            <?php echo esc($_SESSION['otp_simulation']); ?>
                            <div class="mt-2">For real email/mobile delivery, configure SMTP or an SMS API.</div>
                            <?php unset($_SESSION['otp_simulation']); ?>
                        </div>
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

                    <form method="post" id="forgotForm">
                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrfToken()); ?>">
                        <div class="mb-3">
                            <label class="form-label">Registered Email or Phone</label>
                            <input type="text" class="form-control" name="identifier" value="<?php echo esc($identifier); ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="otpBtn">
                            <span class="btn-text">Send OTP</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </form>

                    <p class="mt-3 mb-0 text-center"><a href="login.php">Back to login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('forgotForm').addEventListener('submit', function() {
    const btn = document.getElementById('otpBtn');
    btn.disabled = true;
    btn.querySelector('.btn-text').textContent = 'Sending...';
    btn.querySelector('.spinner-border').classList.remove('d-none');
});
</script>
</body>
</html>
