<?php
$query = "SELECT * FROM tblstudents WHERE Id = " . (int)$_SESSION['userId'] . " LIMIT 1";
$rs = $conn->query($query);
$rows = $rs ? $rs->fetch_assoc() : null;
$fullName = $rows ? ($rows['firstName'] . ' ' . $rows['lastName']) : 'Student';
$admissionLabel = $rows && !empty($rows['admissionNumber']) ? $rows['admissionNumber'] : 'Student Account';
?>
<style>
  .student-module-btn {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    color: #fff;
    background: rgba(255, 255, 255, 0.15);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }

  .student-module-btn:hover,
  .student-module-btn:focus,
  .student-module-btn.active {
    background: rgba(255, 255, 255, 0.3);
    color: #fff;
    outline: none;
    text-decoration: none;
  }

  .student-modules-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.35);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    z-index: 1040;
  }

  .student-modules-overlay.is-open {
    opacity: 1;
    visibility: visible;
  }

  .student-modules-drawer {
    position: fixed;
    top: 0;
    left: 0;
    width: 340px;
    max-width: calc(100vw - 24px);
    height: 100vh;
    background: #fff;
    box-shadow: 18px 0 48px rgba(15, 23, 42, 0.24);
    transform: translateX(-102%);
    transition: transform 0.25s ease;
    z-index: 1050;
    display: flex;
    flex-direction: column;
  }

  .student-modules-drawer.is-open {
    transform: translateX(0);
  }

  .student-modules-drawer-header {
    position: relative;
    background: linear-gradient(180deg, #0d6efd 0%, #0b5ed7 100%);
    color: #fff;
    padding: 1.25rem 1rem 1rem;
  }

  .student-modules-drawer-close {
    position: absolute;
    top: 10px;
    right: 12px;
    border: 0;
    background: transparent;
    color: #fff;
    font-size: 1.4rem;
    line-height: 1;
  }

  .student-modules-avatar {
    width: 62px;
    height: 62px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.18);
    border: 2px solid rgba(255, 255, 255, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 0.75rem;
  }

  .student-modules-user {
    text-align: center;
    font-size: 0.96rem;
    line-height: 1.35;
  }

  .student-modules-search-wrap {
    padding: 0.85rem 0.9rem;
    border-bottom: 1px solid #eaecf4;
  }

  .student-modules-search {
    width: 100%;
  }

  .student-modules-list {
    flex: 1 1 auto;
    overflow-y: auto;
  }

  .student-modules-section-label {
    padding: 0.8rem 1rem 0.45rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #8b93a7;
    background: #f8fafc;
  }

  .student-module-link {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.8rem 1rem;
    color: #4a4f5b;
    border-bottom: 1px solid #eef1f6;
    text-decoration: none;
    transition: background-color 0.15s ease, color 0.15s ease;
  }

  .student-module-link:hover,
  .student-module-link:focus {
    text-decoration: none;
    background: #f5f8ff;
    color: #1d4ed8;
  }

  .student-module-link i.module-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #eef2ff;
    color: #1d4ed8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 28px;
    font-size: 0.95rem;
  }

  .student-module-link span {
    font-weight: 600;
    font-size: 0.95rem;
  }

  .student-modules-empty {
    display: none;
    padding: 1rem;
    text-align: center;
    color: #858796;
    font-size: 0.92rem;
  }

  .student-modules-list::-webkit-scrollbar {
    width: 8px;
  }

  .student-modules-list::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.35);
    border-radius: 999px;
  }

  @media (max-width: 576px) {
    .student-modules-drawer {
      width: 100vw;
      max-width: 100vw;
    }

    .student-module-btn {
      width: 36px;
      height: 36px;
    }
  }
</style>
<nav class="navbar navbar-expand navbar-light bg-gradient-primary topbar mb-4 static-top">
  <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
    <i class="fa fa-bars"></i>
  </button>

  <ul class="navbar-nav ml-auto align-items-center">
    <li class="nav-item mr-2">
      <button type="button" class="student-module-btn" id="studentModulesToggle" aria-label="Open modules">
        <i class="fas fa-th-large"></i>
      </button>
    </li>
    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <img class="img-profile rounded-circle" src="img/user-icn.png" style="max-width: 60px" alt="Profile">
        <span class="ml-2 d-none d-lg-inline text-white small"><b>Welcome <?php echo htmlspecialchars($fullName); ?></b></span>
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
        <a class="dropdown-item" href="logout.php">
          <i class="fas fa-power-off fa-fw mr-2 text-danger"></i>
          Logout
        </a>
      </div>
    </li>
  </ul>
</nav>

<div class="student-modules-overlay" id="studentModulesOverlay" aria-hidden="true"></div>
<aside class="student-modules-drawer" id="studentModulesDrawer" aria-hidden="true" aria-label="Student modules">
  <div class="student-modules-drawer-header">
    <button type="button" class="student-modules-drawer-close" id="studentModulesClose" aria-label="Close modules">&times;</button>
    <div class="student-modules-avatar">
      <i class="fas fa-user"></i>
    </div>
    <div class="student-modules-user">
      <div class="font-weight-bold"><?php echo htmlspecialchars($fullName); ?></div>
      <div class="small">Admission No: <?php echo htmlspecialchars($admissionLabel); ?></div>
    </div>
  </div>

  <div class="student-modules-search-wrap">
    <input type="text" id="studentModuleSearch" class="form-control form-control-sm student-modules-search" placeholder="Search by module name" autocomplete="off">
  </div>

  <div class="student-modules-list">
    <div class="student-modules-empty" id="studentModulesEmpty">No modules found for your search.</div>

    <div class="student-modules-section-label">Core</div>
    <a class="student-module-link student-module-item" href="index.php" data-module-title="Home Dashboard">
      <i class="fas fa-home module-icon"></i>
      <span>Home</span>
    </a>
    <a class="student-module-link student-module-item" href="leaderboard.php" data-module-title="Leaderboard">
      <i class="fas fa-trophy module-icon"></i>
      <span>Leaderboard</span>
    </a>
    <a class="student-module-link student-module-item" href="onlineCourses.php" data-module-title="Online Courses">
      <i class="fas fa-book-open module-icon"></i>
      <span>Online Courses</span>
    </a>
    <a class="student-module-link student-module-item" href="valueAddedCourses.php" data-module-title="Value Added Courses">
      <i class="fas fa-layer-group module-icon"></i>
      <span>Value Added Courses</span>
    </a>
    <a class="student-module-link student-module-item" href="modulesOnlineExam.php" data-module-title="Modules Online Exam">
      <i class="fas fa-folder-open module-icon"></i>
      <span>Modules / Online Exam</span>
    </a>
    <a class="student-module-link student-module-item" href="academicPlanning.php" data-module-title="Academic Planning">
      <i class="fas fa-calendar-alt module-icon"></i>
      <span>Academic Planning</span>
    </a>

    <div class="student-modules-section-label">Student Services</div>
    <a class="student-module-link student-module-item" href="profile.php" data-module-title="Profile">
      <i class="fas fa-user module-icon"></i>
      <span>Profile</span>
    </a>
    <a class="student-module-link student-module-item" href="inventory.php" data-module-title="Inventory">
      <i class="fas fa-box module-icon"></i>
      <span>Inventory</span>
    </a>
    <a class="student-module-link student-module-item" href="applications.php" data-module-title="Applications">
      <i class="fas fa-file-alt module-icon"></i>
      <span>Applications</span>
    </a>
    <a class="student-module-link student-module-item" href="fees.php" data-module-title="Fees">
      <i class="fas fa-rupee-sign module-icon"></i>
      <span>Fees</span>
    </a>
    <a class="student-module-link student-module-item" href="eGrievance.php" data-module-title="e Grievance">
      <i class="fas fa-headset module-icon"></i>
      <span>e-Grievance</span>
    </a>
    <a class="student-module-link student-module-item" href="trainingAndPlacement.php" data-module-title="Training and Placement">
      <i class="fas fa-briefcase module-icon"></i>
      <span>Training and Placement</span>
    </a>

    <div class="student-modules-section-label">Attendance</div>
    <a class="student-module-link student-module-item" href="scanAttendance.php" data-module-title="Scan QR Attendance">
      <i class="fas fa-qrcode module-icon"></i>
      <span>Scan QR Attendance</span>
    </a>
    <a class="student-module-link student-module-item" href="viewAttendance.php" data-module-title="My Attendance">
      <i class="fas fa-calendar-check module-icon"></i>
      <span>My Attendance</span>
    </a>
    <a class="student-module-link student-module-item" href="studyMaterials.php" data-module-title="Study Materials">
      <i class="fas fa-book module-icon"></i>
      <span>Study Materials</span>
    </a>
    <a class="student-module-link student-module-item" href="logout.php" data-module-title="Logout Sign Out">
      <i class="fas fa-power-off module-icon"></i>
      <span>Sign Out</span>
    </a>
  </div>
</aside>

<script>
  (function () {
    var toggle = document.getElementById('studentModulesToggle');
    var overlay = document.getElementById('studentModulesOverlay');
    var drawer = document.getElementById('studentModulesDrawer');
    var closeButton = document.getElementById('studentModulesClose');
    var searchInput = document.getElementById('studentModuleSearch');
    var moduleItems = document.querySelectorAll('.student-module-item');
    var emptyState = document.getElementById('studentModulesEmpty');

    if (!toggle || !overlay || !drawer || !closeButton || !searchInput || !moduleItems.length || !emptyState) {
      return;
    }

    function filterModules() {
      var keyword = (searchInput.value || '').toLowerCase().trim();
      var visibleCount = 0;

      moduleItems.forEach(function (item) {
        var title = (item.getAttribute('data-module-title') || '').toLowerCase();
        var showItem = !keyword || title.indexOf(keyword) !== -1;
        item.style.display = showItem ? 'flex' : 'none';
        if (showItem) {
          visibleCount++;
        }
      });

      emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    function openDrawer() {
      overlay.classList.add('is-open');
      drawer.classList.add('is-open');
      toggle.classList.add('active');
      overlay.setAttribute('aria-hidden', 'false');
      drawer.setAttribute('aria-hidden', 'false');
      window.setTimeout(function () {
        searchInput.focus();
      }, 50);
    }

    function closeDrawer() {
      overlay.classList.remove('is-open');
      drawer.classList.remove('is-open');
      toggle.classList.remove('active');
      overlay.setAttribute('aria-hidden', 'true');
      drawer.setAttribute('aria-hidden', 'true');
      searchInput.value = '';
      filterModules();
    }

    toggle.addEventListener('click', function () {
      if (drawer.classList.contains('is-open')) {
        closeDrawer();
      } else {
        openDrawer();
      }
    });

    overlay.addEventListener('click', closeDrawer);
    closeButton.addEventListener('click', closeDrawer);
    searchInput.addEventListener('input', filterModules);

    moduleItems.forEach(function (item) {
      item.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeDrawer();
      }
    });
  })();
</script>
