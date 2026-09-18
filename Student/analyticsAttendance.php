<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/advanced_helpers.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
    echo "<script type=\"text/javascript\">window.location = ('../index.php')</script>";
    exit;
}

$admissionNumber = $_SESSION['admissionNumber'];
$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];
$sessionTermId = 1; // Get from active session if needed

// Get overall statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as totalClasses,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as totalPresent,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as totalLate,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as totalAbsent,
        SUM(CASE WHEN status IN ('invalid_location', 'expired_token') THEN 1 ELSE 0 END) as totalInvalid
    FROM tblattendancelog
    WHERE admissionNo = ? AND classId = ? AND classArmId = ? AND sessionTermId = ?
");
$stmt->bind_param('siii', $admissionNumber, $classId, $classArmId, $sessionTermId);
$stmt->execute();
$statsResult = $stmt->get_result()->fetch_assoc();

$totalClasses = (int)$statsResult['totalClasses'] ?? 0;
$totalPresent = (int)$statsResult['totalPresent'] ?? 0;
$totalLate = (int)$statsResult['totalLate'] ?? 0;
$totalAbsent = (int)$statsResult['totalAbsent'] ?? 0;
$totalInvalid = (int)$statsResult['totalInvalid'] ?? 0;
$attendancePercentage = $totalClasses > 0 ? round(($totalPresent / $totalClasses) * 100, 2) : 0;

// Get daily attendance data for last 30 days
$stmt = $conn->prepare("
    SELECT 
        DATE(scannedAt) as attendanceDate,
        COUNT(*) as classCount,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as presentCount
    FROM tblattendancelog
    WHERE admissionNo = ? AND classId = ? AND classArmId = ? AND sessionTermId = ?
    AND scannedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(scannedAt)
    ORDER BY attendanceDate DESC
");
$stmt->bind_param('siii', $admissionNumber, $classId, $classArmId, $sessionTermId);
$stmt->execute();
$dailyResult = $stmt->get_result();

$dailyData = [];
while ($row = $dailyResult->fetch_assoc()) {
    $dailyData[] = [
        'date' => date('M d', strtotime($row['attendanceDate'])),
        'dateRaw' => $row['attendanceDate'],
        'classes' => (int)$row['classCount'],
        'present' => (int)$row['presentCount']
    ];
}

// Get monthly attendance data for last 6 months
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(scannedAt, '%Y-%m') as monthYear,
        DATE_FORMAT(scannedAt, '%b %Y') as monthDisplay,
        COUNT(*) as totalClasses,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as presentClasses
    FROM tblattendancelog
    WHERE admissionNo = ? AND classId = ? AND classArmId = ? AND sessionTermId = ?
    AND scannedAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(scannedAt, '%Y-%m')
    ORDER BY monthYear DESC
");
$stmt->bind_param('siii', $admissionNumber, $classId, $classArmId, $sessionTermId);
$stmt->execute();
$monthlyResult = $stmt->get_result();

$monthlyData = [];
while ($row = $monthlyResult->fetch_assoc()) {
    $percentage = (int)$row['totalClasses'] > 0 ? round(($row['presentClasses'] / $row['totalClasses']) * 100, 2) : 0;
    $monthlyData[] = [
        'month' => $row['monthDisplay'],
        'total' => (int)$row['totalClasses'],
        'present' => (int)$row['presentClasses'],
        'percentage' => $percentage
    ];
}

// Get recent attendance records
$stmt = $conn->prepare("
    SELECT 
        scannedAt,
        status,
        deviceInfo
    FROM tblattendancelog
    WHERE admissionNo = ? AND classId = ? AND classArmId = ? AND sessionTermId = ?
    ORDER BY scannedAt DESC
    LIMIT 10
");
$stmt->bind_param('siii', $admissionNumber, $classId, $classArmId, $sessionTermId);
$stmt->execute();
$recentResult = $stmt->get_result();

$recentRecords = [];
while ($row = $recentResult->fetch_assoc()) {
    $recentRecords[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="../img/logo/attnlg.jpg" rel="icon">
    <title>Attendance Analytics</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="../css/ruang-admin.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .analytics-card {
            background: white;
            border: 1px solid #e6ebf3;
            border-radius: 14px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            margin-bottom: 1.5rem;
        }

        .stat-box {
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .stat-box.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .stat-box.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .stat-box.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .stat-box.info {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.85rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 2rem;
        }

        .progress-ring {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .progress-value {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .attendance-status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-present {
            background: #ecfdf5;
            color: #065f46;
        }

        .status-late {
            background: #fef3c7;
            color: #92400e;
        }

        .status-absent {
            background: #fee2e2;
            color: #7f1d1d;
        }

        .status-invalid {
            background: #ede9fe;
            color: #5b21b6;
        }

        .recent-record-row {
            padding: 1rem 0;
            border-bottom: 1px solid #f0f2f5;
        }

        .recent-record-row:last-child {
            border-bottom: none;
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
                    <h1 class="h3 mb-4 text-gray-800">📊 Attendance Analytics</h1>

                    <!-- Overview Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-box success">
                                <div class="stat-value"><?php echo $totalPresent; ?></div>
                                <div class="stat-label">Days Present</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-box warning">
                                <div class="stat-value"><?php echo $totalLate; ?></div>
                                <div class="stat-label">Late Arrivals</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-box danger">
                                <div class="stat-value"><?php echo $totalAbsent; ?></div>
                                <div class="stat-label">Days Absent</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-box info">
                                <div class="stat-value"><?php echo $attendancePercentage; ?>%</div>
                                <div class="stat-label">Attendance Rate</div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="analytics-card">
                                <h5 class="mb-4">Daily Attendance (Last 30 Days)</h5>
                                <div class="chart-container">
                                    <canvas id="dailyChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="analytics-card">
                                <h5 class="mb-4">Monthly Attendance Percentage</h5>
                                <div class="chart-container">
                                    <canvas id="monthlyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Distribution -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="analytics-card">
                                <h5 class="mb-4">Attendance Distribution</h5>
                                <div class="chart-container">
                                    <canvas id="distributionChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="analytics-card">
                                <h5 class="mb-4">Overall Summary</h5>
                                <div style="padding: 2rem;">
                                    <div class="recent-record-row">
                                        <div class="d-flex justify-content-between">
                                            <span>Total Classes</span>
                                            <strong><?php echo $totalClasses; ?></strong>
                                        </div>
                                    </div>
                                    <div class="recent-record-row">
                                        <div class="d-flex justify-content-between">
                                            <span>Classes Attended</span>
                                            <strong><?php echo $totalPresent; ?> (<?php echo round(($totalPresent / max($totalClasses, 1)) * 100, 2); ?>%)</strong>
                                        </div>
                                    </div>
                                    <div class="recent-record-row">
                                        <div class="d-flex justify-content-between">
                                            <span>Late Arrivals</span>
                                            <strong><?php echo $totalLate; ?> (<?php echo round(($totalLate / max($totalClasses, 1)) * 100, 2); ?>%)</strong>
                                        </div>
                                    </div>
                                    <div class="recent-record-row">
                                        <div class="d-flex justify-content-between">
                                            <span>Classes Missed</span>
                                            <strong><?php echo $totalAbsent; ?> (<?php echo round(($totalAbsent / max($totalClasses, 1)) * 100, 2); ?>%)</strong>
                                        </div>
                                    </div>
                                    <div class="recent-record-row">
                                        <div class="d-flex justify-content-between">
                                            <span>Invalid Records</span>
                                            <strong><?php echo $totalInvalid; ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Records -->
                    <div class="analytics-card">
                        <h5 class="mb-4">Recent Attendance Records</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Device</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRecords as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($record['scannedAt'])); ?></td>
                                        <td>
                                            <span class="attendance-status-badge status-<?php echo str_replace('_', '-', $record['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><?php echo substr($record['deviceInfo'], 0, 50); ?>...</small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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

    <script>
        // Daily Attendance Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyData = <?php echo json_encode($dailyData); ?>;
        const dailyLabels = dailyData.map(d => d.date);
        const dailyPresent = dailyData.map(d => d.present);
        const dailyTotal = dailyData.map(d => d.classes);

        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: dailyLabels,
                datasets: [
                    {
                        label: 'Present',
                        data: dailyPresent,
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    },
                    {
                        label: 'Total Classes',
                        data: dailyTotal,
                        backgroundColor: '#e5e7eb',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Attendance Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthlyData); ?>;
        const monthlyLabels = monthlyData.map(d => d.month);
        const monthlyPercentages = monthlyData.map(d => d.percentage);

        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Attendance %',
                    data: monthlyPercentages,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        // Distribution Chart
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [<?php echo $totalPresent; ?>, <?php echo $totalLate; ?>, <?php echo $totalAbsent; ?>],
                    backgroundColor: [
                        '#10b981',
                        '#f59e0b',
                        '#ef4444'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>

?>
