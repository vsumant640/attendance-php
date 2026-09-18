<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
    echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
    exit;
}

$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];

// Get timetable
$stmt = $conn->prepare("
    SELECT * FROM tbltimetables
    WHERE classId = ? AND classArmId = ?
    ORDER BY 
        FIELD(dayOfWeek, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
        startTime
");
$stmt->bind_param('ii', $classId, $classArmId);
$stmt->execute();
$timetableResult = $stmt->get_result();

$timetableByDay = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => [],
    'Saturday' => [],
    'Sunday' => []
];

while ($row = $timetableResult->fetch_assoc()) {
    $timetableByDay[$row['dayOfWeek']][] = $row;
}

$todayName = date('l');
$currentTime = date('H:i:s');
$todaySessions = $timetableByDay[$todayName] ?? [];
$currentSession = null;
$nextSession = null;

foreach ($todaySessions as $session) {
    if ($currentTime >= $session['startTime'] && $currentTime <= $session['endTime']) {
        $currentSession = $session;
        break;
    }

    if ($currentTime < $session['startTime'] && $nextSession === null) {
        $nextSession = $session;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="../img/logo/attnlg.jpg" rel="icon">
    <title>Class Timetable</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="../css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .timetable-container {
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .day-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .day-header.weekend {
            background: linear-gradient(135deg, #fbbf24 0%, #f97316 100%);
        }

        .day-header.today {
            box-shadow: inset 0 -4px 0 rgba(255, 255, 255, 0.4);
        }

        .session-slot {
            padding: 1rem;
            border-bottom: 1px solid #f0f2f5;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1fr;
            gap: 1rem;
            align-items: center;
        }

        .session-slot:last-child {
            border-bottom: none;
        }

        .session-slot:hover {
            background: #f9fafb;
        }

        .session-slot.current-session {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
        }

        .time-badge {
            background: #ede9fe;
            color: #5b21b6;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
        }

        .subject-name {
            font-weight: 700;
            color: #1f2937;
            font-size: 1rem;
        }

        .subject-teacher {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .room-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
        }

        .no-classes {
            padding: 2rem;
            text-align: center;
            color: #9ca3af;
        }

        .timetable-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .day-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .current-class-indicator {
            position: absolute;
            right: -2px;
            top: -2px;
            background: #10b981;
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 0 0 0 6px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .current-subject-panel {
            background: linear-gradient(120deg, #0ea5e9, #1d4ed8);
            color: white;
            border-radius: 14px;
            padding: 1.2rem;
            box-shadow: 0 6px 16px rgba(29, 78, 216, 0.25);
            margin-bottom: 1.2rem;
        }

        .current-subject-title {
            font-size: 0.95rem;
            opacity: 0.92;
            margin-bottom: 0.25rem;
        }

        .current-subject-name {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .current-subject-meta {
            font-size: 0.95rem;
            opacity: 0.95;
            margin-bottom: 0.2rem;
        }

        .next-subject-line {
            margin-top: 0.6rem;
            padding-top: 0.6rem;
            border-top: 1px solid rgba(255, 255, 255, 0.35);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .session-slot {
                grid-template-columns: 1fr;
                gap: 0.6rem;
            }
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'Includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'Includes/topbar.php'; ?>
                <div class="container-fluid" id="container-wrapper">
                    <h1 class="h3 mb-4 text-gray-800">📚 Class Timetable</h1>

                    <div class="current-subject-panel">
                        <div class="current-subject-title">Today: <?php echo htmlspecialchars($todayName); ?> | Current time: <?php echo date('h:i A'); ?></div>

                        <?php if ($currentSession): ?>
                            <div class="current-subject-name">Now Running: <?php echo htmlspecialchars($currentSession['subject']); ?></div>
                            <div class="current-subject-meta">
                                <i class="fas fa-clock mr-1"></i>
                                <?php echo date('h:i A', strtotime($currentSession['startTime'])); ?> - <?php echo date('h:i A', strtotime($currentSession['endTime'])); ?>
                            </div>
                            <div class="current-subject-meta">
                                <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($currentSession['teacher'] ?: 'TBA'); ?>
                                &nbsp; | &nbsp;
                                <i class="fas fa-door-open mr-1"></i><?php echo htmlspecialchars($currentSession['roomNo'] ?: 'TBA'); ?>
                            </div>
                        <?php else: ?>
                            <div class="current-subject-name">No subject running right now</div>
                        <?php endif; ?>

                        <?php if ($nextSession): ?>
                            <div class="next-subject-line">
                                Next Subject: <strong><?php echo htmlspecialchars($nextSession['subject']); ?></strong>
                                at <?php echo date('h:i A', strtotime($nextSession['startTime'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="timetable-grid">
                        <?php foreach ($timetableByDay as $day => $classes): ?>
                            <div class="day-card">
                                <div class="day-header <?php echo in_array($day, ['Saturday', 'Sunday']) ? 'weekend' : ''; ?> <?php echo $day === $todayName ? 'today' : ''; ?>">
                                    <i class="fas fa-calendar-day mr-2"></i><?php echo $day; ?>
                                </div>
                                
                                <?php if (empty($classes)): ?>
                                    <div class="no-classes">
                                        <i class="fas fa-ban text-muted" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                        <p>No classes scheduled</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($classes as $session): ?>
                                        <?php $isCurrentSession = ($day === $todayName && $currentTime >= $session['startTime'] && $currentTime <= $session['endTime']); ?>
                                        <div class="session-slot position-relative <?php echo $isCurrentSession ? 'current-session' : ''; ?>">
                                            <?php if ($isCurrentSession): ?>
                                                <span class="current-class-indicator">NOW</span>
                                            <?php endif; ?>
                                            <div class="time-badge">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo date('h:i A', strtotime($session['startTime'])); ?>
                                            </div>
                                            <div>
                                                <div class="subject-name"><?php echo htmlspecialchars($session['subject']); ?></div>
                                                <div class="subject-teacher">
                                                    <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($session['teacher'] ?? 'TBA'); ?>
                                                </div>
                                            </div>
                                            <div class="room-badge">
                                                <i class="fas fa-door-open mr-1"></i><?php echo htmlspecialchars($session['roomNo']); ?>
                                            </div>
                                            <div style="text-align: right; color: #9ca3af; font-size: 0.85rem;">
                                                <?php echo date('h:i A', strtotime($session['endTime'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>
            <?php include 'Includes/footer.php'; ?>
        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/ruang-admin.min.js"></script>
</body>
</html>
