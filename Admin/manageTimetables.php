<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/advanced_helpers.php';

// Only admin can manage timetables
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Administrator') {
    header('location: ../index.php');
    exit;
}

Logger::setConnection($conn);

$action = isset($_GET['action']) ? $_GET['action'] : 'manage';
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$msgType = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'info';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $classId = (int)$_POST['classId'];
            $classArmId = (int)$_POST['classArmId'];
            $dayOfWeek = SecurityHelper::sanitizeInput($_POST['dayOfWeek']);
            $subject = SecurityHelper::sanitizeInput($_POST['subject']);
            $startTime = $_POST['startTime'];
            $endTime = $_POST['endTime'];
            $roomNo = SecurityHelper::sanitizeInput($_POST['roomNo']);
            $teacher = SecurityHelper::sanitizeInput($_POST['teacher'] ?? '');

            $stmt = $conn->prepare("INSERT INTO tbltimetables 
                (classId, classArmId, dayOfWeek, subject, startTime, endTime, roomNo, teacher) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iisssss', $classId, $classArmId, $dayOfWeek, $subject, $startTime, $endTime, $roomNo, $teacher);

            if ($stmt->execute()) {
                Logger::audit($_SESSION['userId'], 'Administrator', 'timetable_created', 'tbltimetables', $conn->insert_id);
                header('location: manageTimetables.php?message=Timetable+added+successfully&type=success');
                exit;
            } else {
                $message = 'Error adding timetable';
                $msgType = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update') {
            $id = (int)$_POST['id'];
            $roomNo = SecurityHelper::sanitizeInput($_POST['roomNo']);
            $teacher = SecurityHelper::sanitizeInput($_POST['teacher']);

            $stmt = $conn->prepare("UPDATE tbltimetables SET roomNo = ?, teacher = ? WHERE id = ?");
            $stmt->bind_param('ssi', $roomNo, $teacher, $id);

            if ($stmt->execute()) {
                Logger::audit($_SESSION['userId'], 'Administrator', 'timetable_updated', 'tbltimetables', $id);
                header('location: manageTimetables.php?message=Timetable+updated+successfully&type=success');
                exit;
            } else {
                $message = 'Error updating timetable';
                $msgType = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];

            $stmt = $conn->prepare("DELETE FROM tbltimetables WHERE id = ?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                Logger::audit($_SESSION['userId'], 'Administrator', 'timetable_deleted', 'tbltimetables', $id);
                header('location: manageTimetables.php?message=Timetable+deleted+successfully&type=success');
                exit;
            } else {
                $message = 'Error deleting timetable';
                $msgType = 'danger';
            }
            $stmt->close();
        }
    }
}

// Get classes for dropdown
$classesResult = $conn->query("SELECT DISTINCT c.Id, c.className, a.Id as armId, a.classArmName 
    FROM tblclass c 
    LEFT JOIN tblclassarms a ON c.Id = a.classId 
    ORDER BY c.className, a.classArmName");

$classes = [];
while ($row = $classesResult->fetch_assoc()) {
    if (!isset($classes[$row['Id']])) {
        $classes[$row['Id']] = ['name' => $row['className'], 'arms' => []];
    }
    if ($row['armId']) {
        $classes[$row['Id']]['arms'][$row['armId']] = $row['classArmName'];
    }
}

// Get all timetables
$timetableResult = $conn->query("
    SELECT t.*, c.className, ca.classArmName 
    FROM tbltimetables t
    JOIN tblclass c ON t.classId = c.Id
    JOIN tblclassarms ca ON t.classArmId = ca.Id
    ORDER BY c.className, ca.classArmName, 
        FIELD(t.dayOfWeek, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
        t.startTime
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="../img/logo/attnlg.jpg" rel="icon">
    <title>Manage Timetables</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="../css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .timetable-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-section {
            background: linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%);
            border: 2px dashed #667eea;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-section h5 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .table-entry {
            padding: 1rem;
            border-bottom: 1px solid #f0f2f5;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        .table-entry:last-child {
            border-bottom: none;
        }

        .entry-label {
            font-weight: 600;
            color: #667eea;
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 6px;
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
                    <h1 class="h3 mb-4 text-gray-800">📚 Manage Class Timetables</h1>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $msgType; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <!-- Add New Timetable -->
                    <div class="form-section">
                        <h5><i class="fas fa-plus-circle mr-2"></i>Add New Timetable Entry</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="row">
                                <div class="col-md-2 mb-3">
                                    <label>Class</label>
                                    <select name="classId" id="classId" class="form-control" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $classId => $classData): ?>
                                            <option value="<?php echo $classId; ?>"><?php echo htmlspecialchars($classData['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label>Arm</label>
                                    <select name="classArmId" id="classArmId" class="form-control" required>
                                        <option value="">Select Arm</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label>Day</label>
                                    <select name="dayOfWeek" class="form-control" required>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                        <option value="Sunday">Sunday</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label>Subject</label>
                                    <input type="text" name="subject" class="form-control" required>
                                </div>
                                <div class="col-md-1 mb-3">
                                    <label>Start</label>
                                    <input type="time" name="startTime" class="form-control" required>
                                </div>
                                <div class="col-md-1 mb-3">
                                    <label>End</label>
                                    <input type="time" name="endTime" class="form-control" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label>Room No</label>
                                            <input type="text" name="roomNo" class="form-control" placeholder="e.g., A101">
                                        </div>
                                        <div class="col-md-4">
                                            <label>Teacher</label>
                                            <input type="text" name="teacher" class="form-control" placeholder="Teacher name">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-save mr-2"></i>Add Entry
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Timetable List -->
                    <div class="timetable-card">
                        <h5 class="mb-4"><i class="fas fa-list mr-2"></i>All Timetable Entries</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Class</th>
                                        <th>Day</th>
                                        <th>Subject</th>
                                        <th>Time</th>
                                        <th>Room</th>
                                        <th>Teacher</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $timetableResult->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['className']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['classArmName']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary"><?php echo htmlspecialchars($row['dayOfWeek']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                        <td>
                                            <?php echo date('h:i A', strtotime($row['startTime'])); ?> - 
                                            <?php echo date('h:i A', strtotime($row['endTime'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['roomNo'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['teacher'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-action" data-toggle="modal" data-target="#editModal" 
                                                onclick="editTimetable(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this entry?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger btn-action">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
            <?php include 'Includes/footer.php'; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Timetable Entry</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editId">
                        <div class="form-group">
                            <label>Room No</label>
                            <input type="text" name="roomNo" id="editRoomNo" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Teacher</label>
                            <input type="text" name="teacher" id="editTeacher" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/ruang-admin.min.js"></script>

    <script>
        const classArms = <?php echo json_encode($classes); ?>;

        document.getElementById('classId').addEventListener('change', function() {
            const classId = this.value;
            const armSelect = document.getElementById('classArmId');
            armSelect.innerHTML = '<option value="">Select Arm</option>';
            
            if (classId && classArms[classId]) {
                Object.entries(classArms[classId].arms).forEach(([armId, armName]) => {
                    const option = document.createElement('option');
                    option.value = armId;
                    option.textContent = armName;
                    armSelect.appendChild(option);
                });
            }
        });

        function editTimetable(data) {
            document.getElementById('editId').value = data.id;
            document.getElementById('editRoomNo').value = data.roomNo || '';
            document.getElementById('editTeacher').value = data.teacher || '';
        }
    </script>
</body>
</html>
?>
