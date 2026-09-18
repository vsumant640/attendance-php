<?php
include 'Includes/dbcon.php';
include 'Includes/auth.php';
include 'Includes/branding.php';
session_start();

$loginError = '';
$selectedRole = '';

function studentHasEmailColumn(mysqli $conn): bool {
  static $checked = false;
  static $hasEmail = false;

  if ($checked) {
    return $hasEmail;
  }

  $res = $conn->query("SHOW COLUMNS FROM tblstudents LIKE 'emailAddress'");
  $hasEmail = ($res && $res->num_rows > 0);
  $checked = true;

  return $hasEmail;
}

if (isset($_POST['login'])) {
  $userType = $_POST['userType'] ?? '';
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $selectedRole = $userType;

  if ($userType === 'Administrator') {
    $stmt = $conn->prepare('SELECT * FROM tbladmin WHERE emailAddress = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($rows && verifyAndUpgradePassword($conn, 'tbladmin', (int)$rows['Id'], $rows['password'], $password)) {
      $_SESSION['userId'] = $rows['Id'];
      $_SESSION['userRole'] = 'Administrator';
      $_SESSION['firstName'] = $rows['firstName'];
      $_SESSION['lastName'] = $rows['lastName'];
      $_SESSION['emailAddress'] = $rows['emailAddress'];
      header('Location: Admin/index.php');
      exit;
    }
  } elseif ($userType === 'ClassTeacher') {
    $stmt = $conn->prepare('SELECT * FROM tblclassteacher WHERE emailAddress = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($rows && verifyAndUpgradePassword($conn, 'tblclassteacher', (int)$rows['Id'], $rows['password'], $password)) {
      $_SESSION['userId'] = $rows['Id'];
      $_SESSION['userRole'] = 'ClassTeacher';
      $_SESSION['firstName'] = $rows['firstName'];
      $_SESSION['lastName'] = $rows['lastName'];
      $_SESSION['emailAddress'] = $rows['emailAddress'];
      $_SESSION['classId'] = $rows['classId'];
      $_SESSION['classArmId'] = $rows['classArmId'];
      header('Location: ClassTeacher/index.php');
      exit;
    }
  } elseif ($userType === 'Student') {
    $normalizedUsername = strtoupper(trim($username));
    $hasEmailColumn = studentHasEmailColumn($conn);

    if ($hasEmailColumn) {
      $stmt = $conn->prepare('SELECT * FROM tblstudents WHERE UPPER(TRIM(admissionNumber)) = ? OR LOWER(TRIM(emailAddress)) = LOWER(TRIM(?)) LIMIT 1');
      $stmt->bind_param('ss', $normalizedUsername, $username);
    } else {
      $stmt = $conn->prepare('SELECT * FROM tblstudents WHERE UPPER(TRIM(admissionNumber)) = ? LIMIT 1');
      $stmt->bind_param('s', $normalizedUsername);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $isValidStudentLogin = false;
    if ($rows) {
      $isValidStudentLogin = verifyAndUpgradePassword($conn, 'tblstudents', (int)$rows['Id'], $rows['password'], $password);

      if (!$isValidStudentLogin && isLikelyTruncatedBcryptHash((string)$rows['password']) && $password === $rows['admissionNumber']) {
        $isValidStudentLogin = forceUpgradePasswordHash($conn, 'tblstudents', (int)$rows['Id'], $password);
      }

      if (!$isValidStudentLogin && (string)$rows['password'] === '12345' && $password === $rows['admissionNumber']) {
        $isValidStudentLogin = forceUpgradePasswordHash($conn, 'tblstudents', (int)$rows['Id'], $password);
      }
    }

    if ($rows && $isValidStudentLogin) {
      $_SESSION['userId'] = $rows['Id'];
      $_SESSION['userRole'] = 'Student';
      $_SESSION['firstName'] = $rows['firstName'];
      $_SESSION['lastName'] = $rows['lastName'];
      $_SESSION['admissionNumber'] = $rows['admissionNumber'];
      $_SESSION['classId'] = $rows['classId'];
      $_SESSION['classArmId'] = $rows['classArmId'];
      header('Location: Student/index.php');
      exit;
    }

    if (!$rows) {
      $loginError = 'Student account not found. Use Admission Number or Student Email, then try again.';
    }
  }

  if ($loginError === '') {
    $loginError = 'Invalid Username or Password!';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Student Portal with QR Attendance, assignments, reports, and secure login.">
  <meta name="author" content="<?php echo BRAND_COLLEGE_NAME; ?>">
  <link href="img/logo/<?php echo BRAND_LOGO_FILE; ?>" rel="icon">
  <title><?php echo BRAND_SHORT_NAME; ?> | <?php echo BRAND_SYSTEM_NAME; ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <style>
    :root {
      --navy: #0b2942;
      --blue: #1f4b7a;
      --orange: #f17c1f;
      --ink: #16202a;
      --muted: #60738a;
      --card-shadow: 0 18px 45px rgba(6, 22, 39, .14);
    }

    * { box-sizing: border-box; }

    html { scroll-behavior: smooth; }

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      color: var(--ink);
      background:
        radial-gradient(circle at top left, rgba(30, 87, 153, .16), transparent 30%),
        radial-gradient(circle at bottom right, rgba(241, 124, 31, .10), transparent 30%),
        linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
      overflow-x: hidden;
    }

    .top-strip {
      background: linear-gradient(90deg, var(--navy), var(--blue));
      color: #fff;
      position: sticky;
      top: 0;
      z-index: 1050;
      box-shadow: 0 10px 25px rgba(6, 22, 39, .12);
      font-size: .92rem;
    }

    .top-strip .wrap {
      max-width: 1180px;
      margin: auto;
      padding: .55rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .top-strip a { color: #fff; text-decoration: none; }

    .social a {
      display: inline-flex;
      width: 30px;
      height: 30px;
      align-items: center;
      justify-content: center;
      margin-left: .35rem;
      border-radius: 50%;
      background: rgba(255, 255, 255, .12);
      transition: transform .2s ease, background .2s ease;
    }

    .social a:hover {
      transform: translateY(-1px);
      background: rgba(255, 255, 255, .22);
    }

    .nav-shell {
      background: rgba(255, 255, 255, .95);
      backdrop-filter: blur(12px);
      box-shadow: 0 10px 28px rgba(6, 22, 39, .08);
      border-bottom: 1px solid rgba(16, 48, 76, .06);
      position: sticky;
      top: 46px;
      z-index: 1040;
    }

    .nav-shell .navbar {
      max-width: 1180px;
      margin: auto;
      padding: .7rem 1rem;
    }

    .navbar-brand {
      font-weight: 800;
      color: var(--blue) !important;
      letter-spacing: .02em;
    }

    .navbar-brand img { height: 40px; width: auto; margin-right: .5rem; }

    .project-by {
      font-size: .78rem;
      font-weight: 600;
      letter-spacing: .02em;
      padding: .3rem .55rem;
      border-radius: 999px;
      color: #0f3556;
      background: rgba(31, 75, 122, .08);
      border: 1px solid rgba(31, 75, 122, .15);
      margin-left: .55rem;
      white-space: nowrap;
    }

    .navbar .nav-link {
      color: #274867 !important;
      font-weight: 600;
      border-radius: 999px;
      padding: .55rem .9rem !important;
      margin: 0 .15rem;
      transition: background .2s ease, color .2s ease, transform .2s ease;
    }

    .navbar .nav-link:hover,
    .navbar .nav-link.active {
      color: #fff !important;
      background: linear-gradient(100deg, var(--blue), #2f77b6);
      box-shadow: 0 8px 18px rgba(31, 75, 122, .18);
      transform: translateY(-1px);
    }

    .main-wrap {
      max-width: 1180px;
      margin: 0 auto;
      padding: 1.5rem 1rem 3.8rem;
    }

    .hero-section {
      background: linear-gradient(135deg, rgba(11, 41, 66, .96), rgba(31, 75, 122, .92));
      border-radius: 30px;
      box-shadow: 0 28px 70px rgba(6, 22, 39, .20);
      overflow: hidden;
      position: relative;
      margin-bottom: 1.35rem;
      animation: riseIn .75s ease both;
    }

    .hero-section::after {
      content: '';
      position: absolute;
      inset: auto -80px -80px auto;
      width: 240px;
      height: 240px;
      border-radius: 50%;
      background: rgba(241, 124, 31, .10);
      filter: blur(10px);
      pointer-events: none;
    }

    .hero-inner {
      position: relative;
      padding: 2.6rem;
      color: #fff;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: .45rem .85rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .16);
      font-size: .88rem;
      font-weight: 600;
    }

    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2.2rem, 5vw, 4.2rem);
      line-height: 1.06;
      margin: 1rem 0 .85rem;
    }

    .hero-subtitle {
      max-width: 700px;
      color: rgba(255, 255, 255, .9);
      font-size: 1.08rem;
      line-height: 1.7;
      margin-bottom: 1.4rem;
    }

    .hero-actions { display: flex; flex-wrap: wrap; gap: .8rem; }

    .btn-hero {
      border-radius: 999px;
      padding: .8rem 1.25rem;
      font-weight: 700;
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .btn-hero:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(0, 0, 0, .18); }

    .hero-stats {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: .8rem;
      margin-top: 1.1rem;
    }

    .hero-stat {
      background: rgba(255, 255, 255, .1);
      border: 1px solid rgba(255, 255, 255, .12);
      border-radius: 18px;
      padding: 1rem;
      backdrop-filter: blur(8px);
    }

    .hero-stat strong { display: block; font-size: 1.4rem; margin-bottom: .2rem; }
    .hero-stat span { color: rgba(255, 255, 255, .84); font-size: .92rem; }

    .section-pad { padding-top: 2rem; }
    .panel-card,
    .login-form-card,
    .login-quick,
    .contact-card {
      background: rgba(255, 255, 255, .97);
      border-radius: 28px;
      box-shadow: 0 20px 50px rgba(6, 22, 39, .12);
      border: 1px solid rgba(16, 48, 76, .07);
      overflow: hidden;
      animation: riseIn .75s ease both;
    }

    .panel-card { padding: 1.9rem; }

    .section-heading {
      font-family: 'Playfair Display', serif;
      color: #0f3556;
      font-size: clamp(1.7rem, 3vw, 2.4rem);
      margin-bottom: .5rem;
    }

    .section-subtitle { color: var(--muted); margin-bottom: 1.4rem; max-width: 760px; }

    .feature-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 1rem;
    }

    .feature-card {
      background: linear-gradient(180deg, #fff, #f7fbff);
      border-radius: 20px;
      padding: 1.45rem 1.25rem;
      border: 1px solid rgba(16, 48, 76, .08);
      box-shadow: 0 12px 28px rgba(8, 26, 42, .07);
      transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
      height: 100%;
      animation: riseIn .7s ease both;
    }

    .feature-card:hover { transform: translateY(-8px); box-shadow: 0 20px 34px rgba(8, 26, 42, .14); }

    .feature-icon {
      width: 54px;
      height: 54px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      background: linear-gradient(135deg, var(--blue), #49a0da);
      margin-bottom: 1rem;
      font-size: 1.2rem;
      box-shadow: 0 10px 18px rgba(31, 75, 122, .18);
    }

    .feature-card h5 { font-size: 1.02rem; font-weight: 700; color: #12395c; margin-bottom: .45rem; }
    .feature-card p { color: var(--muted); font-size: .95rem; line-height: 1.7; margin: 0; }

    .about-grid {
      display: grid;
      grid-template-columns: .95fr 1.05fr;
      gap: 1.2rem;
      align-items: center;
    }

    .about-image {
      min-height: 320px;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 20px 45px rgba(6, 22, 39, .12);
      background: linear-gradient(135deg, #dfeefa, #ffffff);
    }

    .about-image img, .img-fluid-cover { width: 100%; height: 100%; object-fit: cover; display: block; }

    .about-copy { padding: .5rem 0; }

    .about-bullet { display: flex; gap: .75rem; margin-bottom: .9rem; align-items: flex-start; }
    .about-bullet i { color: var(--orange); margin-top: .2rem; }

    .login-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      align-items: start;
    }

    .login-quick {
      background: linear-gradient(135deg, rgba(31, 75, 122, .95), rgba(11, 41, 66, .95));
      color: #fff;
      padding: 1.55rem;
    }

    .login-quick h3,
    .login-form-card h3,
    .contact-card h3 { font-family: 'Playfair Display', serif; margin-bottom: .5rem; }

    .login-button {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .6rem;
      padding: .95rem 1rem;
      border-radius: 14px;
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      transition: transform .2s ease;
    }

    .login-button:hover { transform: translateY(-2px); text-decoration: none; color: #fff; }
    .login-button.student { background: linear-gradient(100deg, #2d7bd1, #4aa0e6); }
    .login-button.teacher { background: linear-gradient(100deg, #0f8b8d, #1f4b7a); }
    .login-button.admin { background: linear-gradient(100deg, #f17c1f, #e9620c); }

    .login-form-card { padding: 1.55rem; }
    .form-label { font-weight: 600; color: #23405e; }
    .form-control, .custom-select { border-radius: 12px; border-color: #cfdceb; min-height: 48px; }
    .form-control:focus, .custom-select:focus { box-shadow: 0 0 0 .2rem rgba(31, 75, 122, .12); border-color: #4291d2; }

    .btn-brand {
      background: linear-gradient(100deg, var(--blue), #2c79bb);
      color: #fff;
      border-radius: 12px;
      padding: .85rem 1.1rem;
      font-weight: 700;
      box-shadow: 0 12px 22px rgba(31, 75, 122, .18);
      border: 0;
    }

    .btn-brand:hover { color: #fff; transform: translateY(-1px); }

    .contact-card { padding: 1.55rem; }

    .developer-card {
      background: linear-gradient(145deg, #ffffff, #f4f9ff);
      border-radius: 26px;
      border: 1px solid rgba(16, 48, 76, .08);
      box-shadow: 0 20px 42px rgba(6, 22, 39, .12);
      padding: 2rem 1.6rem;
      text-align: center;
      animation: riseIn .7s ease both;
    }

    .developer-title {
      font-family: 'Playfair Display', serif;
      color: #113a60;
      font-size: clamp(1.5rem, 2.5vw, 2rem);
      margin-bottom: .35rem;
    }

    .developer-name {
      font-size: 1.35rem;
      color: #0d3557;
      font-weight: 700;
      margin-bottom: .15rem;
    }

    .developer-role {
      font-size: .93rem;
      color: #4f6a84;
      font-weight: 600;
      margin-bottom: .9rem;
    }

    .developer-desc {
      max-width: 900px;
      margin: 0 auto 1rem;
      color: #5f778d;
      line-height: 1.75;
    }

    .developer-contact {
      display: flex;
      flex-wrap: wrap;
      gap: .65rem;
      justify-content: center;
      margin-bottom: .95rem;
    }

    .developer-pill {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
      padding: .5rem .8rem;
      border-radius: 999px;
      background: #f0f7ff;
      border: 1px solid #d8e8f8;
      color: #1d4b79;
      font-weight: 600;
      font-size: .9rem;
      text-decoration: none;
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .developer-pill:hover {
      text-decoration: none;
      color: #1d4b79;
      transform: translateY(-2px);
      box-shadow: 0 8px 16px rgba(31, 75, 122, .16);
    }

    .btn-developer {
      border: 0;
      border-radius: 999px;
      padding: .72rem 1.15rem;
      font-weight: 700;
      color: #fff;
      background: linear-gradient(100deg, var(--orange), #e9620c);
      box-shadow: 0 12px 22px rgba(241, 124, 31, .24);
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .developer-actions {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: .65rem;
      flex-wrap: wrap;
    }

    .btn-developer.secondary {
      background: linear-gradient(100deg, var(--blue), #2c79bb);
      box-shadow: 0 12px 22px rgba(31, 75, 122, .24);
    }

    .btn-developer:hover {
      color: #fff;
      text-decoration: none;
      transform: translateY(-2px);
      box-shadow: 0 14px 24px rgba(241, 124, 31, .30);
    }

    .footer {
      margin-top: 2.2rem;
      background: linear-gradient(135deg, var(--navy), #0f3a5f);
      color: rgba(255, 255, 255, .9);
      padding: 2.2rem 0 1rem;
    }

    .footer h5 { color: #fff; font-weight: 700; margin-bottom: 1rem; }
    .footer a { color: rgba(255, 255, 255, .88); text-decoration: none; }
    .footer a:hover { color: #fff; text-decoration: underline; }
    .footer .social a {
      display: inline-flex;
      width: 36px;
      height: 36px;
      align-items: center;
      justify-content: center;
      margin-right: .35rem;
      border-radius: 50%;
      background: rgba(255, 255, 255, .1);
    }

    .footer-bottom {
      margin-top: 1.5rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255, 255, 255, .12);
      color: rgba(255, 255, 255, .72);
      font-size: .92rem;
    }

    .floating-pill {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
      padding: .4rem .8rem;
      border-radius: 999px;
      background: rgba(241, 124, 31, .12);
      color: #b85b09;
      font-weight: 600;
      margin-bottom: .8rem;
    }

    .panel-card .section-subtitle,
    .contact-card .section-subtitle {
      margin-bottom: 1.6rem;
    }

    .feature-grid .feature-card:nth-child(1) { animation-delay: .05s; }
    .feature-grid .feature-card:nth-child(2) { animation-delay: .12s; }
    .feature-grid .feature-card:nth-child(3) { animation-delay: .19s; }
    .feature-grid .feature-card:nth-child(4) { animation-delay: .26s; }

    .login-grid > div:first-child { animation-delay: .08s; }
    .login-grid > div:last-child { animation-delay: .16s; }

    @keyframes riseIn {
      from {
        opacity: 0;
        transform: translateY(18px) scale(.99);
      }

      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    @media (max-width: 992px) {
      .feature-grid,
      .about-grid,
      .login-grid,
      .hero-stats { grid-template-columns: 1fr; }
      .nav-shell { top: 44px; }
    }

    @media (max-width: 768px) {
      .feature-grid { grid-template-columns: 1fr; }
      .hero-inner, .panel-card, .login-quick, .login-form-card, .contact-card { padding: 1.1rem; }
      .top-strip .wrap { justify-content: center; text-align: center; }
      .navbar-nav { padding-top: .6rem; }
      .project-by { margin-left: .35rem; margin-top: .2rem; }
    }
  </style>
</head>
<body>
  <div class="top-strip">
    <div class="wrap">
      <div><i class="fas fa-phone-alt mr-2"></i>Questions? <?php echo BRAND_SUPPORT_PHONE; ?></div>
      <div class="social">
        <a href="mailto:<?php echo BRAND_SUPPORT_EMAIL; ?>" aria-label="Email"><i class="fas fa-envelope"></i></a>
        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
  </div>

  <div class="nav-shell sticky-top">
    <nav class="navbar navbar-expand-lg navbar-light">
      <a class="navbar-brand d-flex align-items-center" href="#home">
        <img src="img/logo/<?php echo BRAND_LOGO_FILE; ?>" alt="<?php echo BRAND_SHORT_NAME; ?>">
        <span><?php echo BRAND_SHORT_NAME; ?> Portal</span>
        <!-- <span class="project-by"></span> -->


      </a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav ml-auto align-items-lg-center">
          <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
          <li class="nav-item"><a class="nav-link" href="#login">Student Login</a></li>
          <li class="nav-item"><a class="nav-link" href="#login" data-role="ClassTeacher">Teacher Login</a></li>
          <li class="nav-item"><a class="nav-link" href="#login" data-role="Administrator">Admin Login</a></li>
          <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
        </ul>
      </div>
    </nav>
  </div>

  <main class="main-wrap" id="home">
    <section class="hero-section">
      <div class="hero-inner row no-gutters align-items-center">
        <div class="col-lg-7 pr-lg-4">
          <div class="hero-badge"><i class="fas fa-graduation-cap"></i> <?php echo BRAND_SHORT_NAME; ?> Digital Campus Suite</div>
          <h1 class="hero-title">Welcome to Student Portal</h1>
          <p class="hero-subtitle">Manage Attendance, Assignments & Academic Records Easily. A clean, secure portal for students, teachers, and administrators built for smooth campus operations.</p>
          <div class="hero-actions">
            <a href="#login" class="btn btn-light btn-hero"><i class="fas fa-arrow-right mr-2"></i>Get Started</a>
            <a href="#features" class="btn btn-outline-light btn-hero">Explore Features</a>
          </div>
          <div class="hero-stats">
            <div class="hero-stat"><strong>QR</strong><span>Fast attendance marking</span></div>
            <div class="hero-stat"><strong>24/7</strong><span>Portal access anytime</span></div>
            <div class="hero-stat"><strong>Secure</strong><span>Role-based login control</span></div>
          </div>
        </div>
        <div class="col-lg-5 mt-4 mt-lg-0">
          <div class="login-form-card" style="background: rgba(255,255,255,.97); border-radius: 22px;">
            <div class="floating-pill"><i class="fas fa-shield-alt"></i> Secure Student Portal</div>
            <div class="row text-dark">
              <div class="col-12 mb-3">
                <p class="mb-0 text-muted">Use the portal to view classes, mark attendance, upload assignments, and check academic progress from any device.</p>
              </div>
              <div class="col-6 mb-2"><div class="p-3 rounded" style="background:#f8fbff; border:1px solid #d9e7f5;"><strong class="d-block text-primary">Attendance</strong><span class="text-muted small">QR and reports</span></div></div>
              <div class="col-6 mb-2"><div class="p-3 rounded" style="background:#f8fbff; border:1px solid #d9e7f5;"><strong class="d-block text-primary">Assignments</strong><span class="text-muted small">Upload and track</span></div></div>
              <div class="col-6"><div class="p-3 rounded" style="background:#f8fbff; border:1px solid #d9e7f5;"><strong class="d-block text-primary">Login</strong><span class="text-muted small">Secure access</span></div></div>
              <div class="col-6"><div class="p-3 rounded" style="background:#f8fbff; border:1px solid #d9e7f5;"><strong class="d-block text-primary">Support</strong><span class="text-muted small">Help desk ready</span></div></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section-pad" id="features">
      <div class="panel-card">
        <h2 class="section-heading">Portal Features</h2>
        <p class="section-subtitle">Everything your campus needs in one place, designed with a modern interface, smooth interaction, and responsive layouts for desktop and mobile.</p>
        <div class="feature-grid">
          <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-qrcode"></i></div>
            <h5>QR Attendance</h5>
            <p>Mark attendance quickly with QR-based verification and location-aware controls.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon" style="background: linear-gradient(135deg, #f17c1f, #e9620c);"><i class="fas fa-cloud-upload-alt"></i></div>
            <h5>Assignment Upload</h5>
            <p>Submit assignments securely with clear deadlines, file validation, and tracking.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon" style="background: linear-gradient(135deg, #2b79bb, #1f4b7a);"><i class="fas fa-chart-line"></i></div>
            <h5>Attendance Reports</h5>
            <p>View detailed reports and analytics to monitor presence, trends, and performance.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon" style="background: linear-gradient(135deg, #0f8b8d, #1f4b7a);"><i class="fas fa-lock"></i></div>
            <h5>Secure Login</h5>
            <p>Role-based access for students, teachers, and admins with protected sessions.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section-pad" id="about">
      <div class="panel-card">
        <div class="about-grid">
          <div class="about-image">
            <img src="assets/images/header.png" alt="About the portal" class="img-fluid-cover" onerror="this.onerror=null;this.src='img/logo/<?php echo BRAND_LOGO_FILE; ?>';">
          </div>
          <div class="about-copy">
            <div class="floating-pill"><i class="fas fa-university"></i> About the Portal</div>
            <h2 class="section-heading mb-3">A professional campus experience for every user</h2>
            <p class="section-subtitle">This student portal centralizes attendance, assignments, academic records, and communication into a single modern interface. It is built to help students stay organized, help teachers manage classes efficiently, and help administrators monitor everything with ease.</p>
            <div class="about-bullet"><i class="fas fa-check-circle"></i><div>Simple navigation with clear calls to action and mobile-friendly design.</div></div>
            <div class="about-bullet"><i class="fas fa-check-circle"></i><div>Consistent blue, white, and orange branding that matches college identity.</div></div>
            <div class="about-bullet"><i class="fas fa-check-circle"></i><div>Secure login flow with polished UI and smooth hover interactions.</div></div>
          </div>
        </div>
      </div>
    </section>

    <section class="section-pad" id="login">
      <div class="login-grid">
        <div class="login-quick">
          <h3>Portal Login</h3>
          <p class="mb-3" style="color: rgba(255,255,255,.82);">Choose your role and sign in to continue.</p>
          <div class="d-grid gap-3">
            <a href="#login" class="login-button student" data-role="Student"><i class="fas fa-user-graduate"></i> Student Login</a>
            <a href="#login" class="login-button teacher" data-role="ClassTeacher"><i class="fas fa-chalkboard-teacher"></i> Teacher Login</a>
            <a href="#login" class="login-button admin" data-role="Administrator"><i class="fas fa-user-shield"></i> Admin Login</a>
          </div>
          <div class="mt-4 p-3 rounded" style="background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);">
            <strong>Login tip:</strong> Students use Admission Number. Teachers and Admin use their registered email and password.
          </div>
        </div>

        <div class="login-form-card">
          <h3>Sign In</h3>
          <p class="text-muted mb-3">Secure access for students and administrators.</p>

          <?php if ($loginError !== '') { ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($loginError); ?></div>
          <?php } ?>

          <form method="post" action="" id="login-panel">
            <div class="form-group">
              <label for="userType" class="form-label">User Role</label>
              <select required name="userType" id="userType" class="custom-select">
                <option value="">Select User Role</option>
                <option value="Administrator" <?php echo $selectedRole === 'Administrator' ? 'selected' : ''; ?>>Administrator</option>
                <option value="ClassTeacher" <?php echo $selectedRole === 'ClassTeacher' ? 'selected' : ''; ?>>Class Teacher</option>
                <option value="Student" <?php echo $selectedRole === 'Student' ? 'selected' : ''; ?>>Student</option>
              </select>
            </div>

            <div class="form-group">
              <label for="username" class="form-label">Username</label>
              <input type="text" class="form-control" required name="username" id="username" placeholder="Email or Admission Number">
            </div>

            <div class="form-group">
              <label for="password" class="form-label">Password</label>
              <input type="password" name="password" required class="form-control" id="password" placeholder="Enter Password">
            </div>

            <button type="submit" class="btn btn-brand btn-block" name="login">Secure Login</button>
          </form>

          <div class="mt-3 small text-muted">
            Student login uses Admission Number as username. New students usually use their Admission Number as password.
          </div>
        </div>
      </div>
    </section>

    <section class="section-pad" id="contact">
      <div class="contact-card">
        <div class="row align-items-center">
          <div class="col-md-6 mb-3 mb-md-0">
            <h3 class="section-heading mb-2">Contact & Support</h3>
            <p class="section-subtitle mb-0">Need help with login, attendance, or portal access? Reach out to the support team using the details below.</p>
          </div>
          <div class="col-md-6">
            <div class="row">
              <div class="col-sm-6 mb-3 mb-sm-0">
                <div class="p-3 rounded" style="background:#f8fbff; border:1px solid #d9e7f5;">
                  <div class="text-primary font-weight-bold mb-1"><i class="fas fa-phone-alt mr-2"></i>Phone</div>
                  <div class="text-muted"><?php echo BRAND_SUPPORT_PHONE; ?></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="p-3 rounded" style="background:#f8fbff; border:1px solid #d9e7f5;">
                  <div class="text-primary font-weight-bold mb-1"><i class="fas fa-envelope mr-2"></i>Email</div>
                  <div class="text-muted"><?php echo BRAND_SUPPORT_EMAIL; ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section-pad" id="developer">
      <div class="developer-card">
        <h3 class="developer-title">Developed By</h3>
        <div class="developer-name">Sumant</div>
        <div class="developer-role">Full Stack Developer (Student Project)</div>
        <p class="developer-desc">This project is a professional Student Portal system built using HTML, CSS, JavaScript, PHP, and MySQL with advanced features like QR-based attendance and secure authentication.</p>

        <div class="developer-contact">
          <a class="developer-pill" href="tel:9554785804"><i class="fas fa-phone-alt"></i> 9554785804</a>
          <a class="developer-pill" href="mailto:vsumant640@gmail.com"><i class="fas fa-envelope"></i> vsumant640@gmail.com</a>
        </div>

        <div class="developer-actions">
          <a class="btn-developer" href="mailto:vsumant640@gmail.com?subject=Student%20Portal%20Project%20Query">Contact Developer</a>
          <a class="btn-developer secondary" href="assets/docs/Sumant-Resume.txt" download>Download Resume</a>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 mb-4">
          <h5>About Project</h5>
          <p class="mb-3">Professional Student Portal with role-based dashboard, attendance tracking, assignment flow, and secure authentication for modern campus ERP operations.</p>
          <div class="social">
            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6 mb-4">
          <h5>Quick Links</h5>
          <div class="d-flex flex-column">
            <a href="#home" class="mb-2">Home</a>
            <a href="#login" class="mb-2">Login</a>
            <a href="#contact">Contact</a>
          </div>
        </div>
        <div class="col-lg-3 col-6 mb-4">
          <h5>Contact Info</h5>
          <div class="mb-2"><i class="fas fa-user mr-2"></i>Sumant</div>
          <div class="mb-2"><i class="fas fa-phone-alt mr-2"></i>9554785804</div>
          <div class="mb-2"><i class="fas fa-envelope mr-2"></i><a href="mailto:vsumant640@gmail.com">vsumant640@gmail.com</a></div>
        </div>
        <div class="col-lg-2 col-6 mb-4">
          <h5>Social</h5>
          <div class="social">
            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
          </div>
        </div>
      </div>
      <div class="footer-bottom text-center">
        &copy; 2026 Student Portal | Developed by Sumant
      </div>
    </div>
  </footer>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script>
    document.querySelectorAll('.nav-link[href^="#"], .login-button[data-role]').forEach(function (item) {
      item.addEventListener('click', function () {
        var role = item.getAttribute('data-role');
        if (role) {
          var roleSelect = document.getElementById('userType');
          if (roleSelect) {
            roleSelect.value = role;
          }
        }
      });
    });

    (function () {
      var sections = ['home', 'about', 'login', 'contact'];
      var navLinks = document.querySelectorAll('.navbar .nav-link');

      function setActiveLink() {
        var scrollPosition = window.scrollY + 180;
        var activeId = 'home';

        sections.forEach(function (sectionId) {
          var section = document.getElementById(sectionId);
          if (section && section.offsetTop <= scrollPosition) {
            activeId = sectionId;
          }
        });

        navLinks.forEach(function (link) {
          var href = link.getAttribute('href');
          link.classList.toggle('active', href === '#' + activeId);
        });
      }

      window.addEventListener('scroll', setActiveLink, { passive: true });
      setActiveLink();
    })();
  </script>
</body>
</html>
