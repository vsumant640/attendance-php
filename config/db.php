<?php

// Central database and auth utility file for the new portal auth module.
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'attendancemsystem';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

startSecureSession();
initializeAuthSchema($conn);

function startSecureSession()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

function initializeAuthSchema(mysqli $conn)
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $createUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(25) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('student','admin') NOT NULL DEFAULT 'student',
        otp VARCHAR(255) NULL,
        otp_expiry DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_users_email (email),
        UNIQUE KEY uniq_users_phone (phone),
        KEY idx_users_role (role),
        KEY idx_users_otp_expiry (otp_expiry)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conn->query($createUsers);

    ensureColumn($conn, 'users', 'phone', "ALTER TABLE users ADD COLUMN phone VARCHAR(25) NOT NULL AFTER email");
    ensureColumn($conn, 'users', 'otp', "ALTER TABLE users ADD COLUMN otp VARCHAR(255) NULL AFTER role");
    ensureColumn($conn, 'users', 'otp_expiry', "ALTER TABLE users ADD COLUMN otp_expiry DATETIME NULL AFTER otp");
    ensureColumn($conn, 'users', 'created_at', "ALTER TABLE users ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    ensureColumn($conn, 'users', 'role', "ALTER TABLE users ADD COLUMN role ENUM('student','admin') NOT NULL DEFAULT 'student' AFTER password");

    $initialized = true;
}

function ensureColumn(mysqli $conn, string $table, string $column, string $alterSql)
{
    $tableEsc = $conn->real_escape_string($table);
    $columnEsc = $conn->real_escape_string($column);
    $check = $conn->query("SHOW COLUMNS FROM {$tableEsc} LIKE '{$columnEsc}'");

    if ($check && $check->num_rows === 0) {
        $conn->query($alterSql);
    }
}

function cleanInput(string $value): string
{
    return trim($value);
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireLogin()
{
    if (empty($_SESSION['auth_user'])) {
        header('Location: login.php');
        exit;
    }
}

function currentUserId(): int
{
    return isset($_SESSION['auth_user']['id']) ? (int)$_SESSION['auth_user']['id'] : 0;
}

function redirectByRole(string $role)
{
    if ($role === 'admin') {
        header('Location: admin_dashboard.php');
        exit;
    }

    header('Location: student_dashboard.php');
    exit;
}

function sendOtpMessage(string $email, string $phone, string $otp): bool
{
    $subject = 'Your OTP for Password Reset';
    $message = "Your OTP is {$otp}. It will expire in 10 minutes.";
    $headers = 'From: no-reply@localhost';

    // If mail is available, attempt email delivery.
    if (function_exists('mail') && @mail($email, $subject, $message, $headers)) {
        return true;
    }

    // Fallback simulation for local/XAMPP testing when SMTP or SMS API is not configured.
    $_SESSION['otp_simulation'] = "Local OTP for testing only: {$otp}. No email/SMS transport is configured yet.";
    return true;
}
