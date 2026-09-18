<?php
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['auth_user'])) {
    redirectByRole($_SESSION['auth_user']['role']);
}

$flash = getFlash();
$error = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request token. Please refresh and try again.';
    } else {
        $identifier = cleanInput($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $error = 'Please fill in both login fields.';
        } else {
            $stmt = $conn->prepare('SELECT id, name, email, phone, password, role FROM users WHERE email = ? OR phone = ? LIMIT 1');
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['auth_user'] = [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role' => $user['role']
                ];

                redirectByRole($user['role']);
            } else {
                $error = 'Invalid login credentials.';
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
    <title>Login | Student Portal</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-3">Sign In</h3>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo esc($flash['type']); ?>"><?php echo esc($flash['message']); ?></div>
                    <?php endif; ?>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?php echo esc($error); ?></div>
                    <?php endif; ?>

                    <form method="post" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrfToken()); ?>">

                        <div class="mb-3">
                            <label class="form-label">Email or Phone</label>
                            <input type="text" class="form-control" name="identifier" value="<?php echo esc($identifier); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                            <span class="btn-text">Login</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </form>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="register.php">Create account</a>
                        <a href="forgot_password.php">Forgot password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.querySelector('.btn-text').textContent = 'Signing in...';
    btn.querySelector('.spinner-border').classList.remove('d-none');
});
</script>
</body>
</html>
