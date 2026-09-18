<?php
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['auth_user'])) {
    redirectByRole($_SESSION['auth_user']['role']);
}

$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    }

    $name = cleanInput($_POST['name'] ?? '');
    $email = strtolower(cleanInput($_POST['email'] ?? ''));
    $phone = cleanInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || strlen($name) < 3) {
        $errors[] = 'Name must be at least 3 characters long.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = 'Phone number must contain 10 to 15 digits.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirm password do not match.';
    }

    if (empty($errors)) {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if ($exists) {
            $errors[] = 'This email is already registered.';
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student';

        $insert = $conn->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)');
        $insert->bind_param('sssss', $name, $email, $phone, $passwordHash, $role);

        if ($insert->execute()) {
            $insert->close();
            setFlash('success', 'Registration successful. Please login with your credentials.');
            header('Location: login.php');
            exit;
        }

        $insert->close();
        $errors[] = 'Unable to register right now. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Student Portal</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-3">Create Student Account</h3>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo esc($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="registerForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo esc(csrfToken()); ?>">

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo esc($name); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo esc($email); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo esc($phone); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="registerBtn">
                            <span class="btn-text">Sign Up</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </form>

                    <p class="mt-3 mb-0 text-center">Already have an account? <a href="login.php">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const phone = this.querySelector('input[name="phone"]').value.trim();
    const password = this.querySelector('input[name="password"]').value;
    const confirm = this.querySelector('input[name="confirm_password"]').value;

    if (!/^\d{10,15}$/.test(phone)) {
        e.preventDefault();
        alert('Phone number must contain 10 to 15 digits.');
        return;
    }

    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long.');
        return;
    }

    if (password !== confirm) {
        e.preventDefault();
        alert('Password and confirm password do not match.');
        return;
    }

    const btn = document.getElementById('registerBtn');
    btn.disabled = true;
    btn.querySelector('.btn-text').textContent = 'Creating...';
    btn.querySelector('.spinner-border').classList.remove('d-none');
});
</script>
</body>
</html>
