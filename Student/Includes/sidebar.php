<?php include_once '../Includes/branding.php'; ?>
<ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
  <a class="sidebar-brand d-flex align-items-center bg-gradient-primary justify-content-center" href="index.php">
    <div class="sidebar-brand-icon">
      <img src="img/logo/<?php echo BRAND_LOGO_FILE; ?>" alt="<?php echo BRAND_SHORT_NAME; ?>">
    </div>
    <div class="sidebar-brand-text mx-3"><?php echo BRAND_SHORT_NAME; ?></div>
  </a>
  <hr class="sidebar-divider my-0">

  <li class="nav-item active">
    <a class="nav-link" href="index.php">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <li class="nav-item">
    <a class="nav-link" href="scanAttendance.php">
      <i class="fas fa-qrcode"></i>
      <span>Scan QR Attendance</span>
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="viewAttendance.php">
      <i class="fas fa-calendar-check"></i>
      <span>My Attendance</span>
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="studyMaterials.php">
      <i class="fas fa-book"></i>
      <span>Study Materials</span>
    </a>
  </li>

  <hr class="sidebar-divider">
</ul>
