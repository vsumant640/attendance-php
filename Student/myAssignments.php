<?php
/**
 * Student Assignments - Submission Interface
 */
include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/advanced_helpers.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
    echo "<script>window.location = '../index.php'</script>";
    exit;
}

Logger::setConnection($conn);
NotificationHelper::setConnection($conn);

$admissionNo = $_SESSION['admissionNumber'];
$classId = (int)$_SESSION['classId'];
$classArmId = (int)$_SESSION['classArmId'];

$message = '';
$msgType = '';

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    $assignmentId = (int)$_POST['assignmentId'];
    
    // Check if file uploaded
    if (!isset($_FILES['submissionFile']) || $_FILES['submissionFile']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please select a file to upload';
        $msgType = 'danger';
    } else {
        // Validate file
        $file = $_FILES['submissionFile'];
        $fileSize = $file['size'];
        $fileName = basename($file['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExts = ['pdf', 'doc', 'docx', 'txt', 'xlsx', 'pptx', 'jpg', 'jpeg', 'png'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($fileExt, $allowedExts)) {
            $message = 'File type not allowed. Allowed: ' . implode(', ', $allowedExts);
            $msgType = 'danger';
        } elseif ($fileSize > $maxFileSize) {
            $message = 'File size exceeds 10MB limit';
            $msgType = 'danger';
        } else {
            // Create upload directory if not exists
            $uploadDir = '../uploads/assignments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $uniqueFileName = $admissionNo . '_' . $assignmentId . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . $uniqueFileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Check for duplicate submission
                $stmt = $conn->prepare("
                    SELECT id FROM tblassignmentsubmissions 
                    WHERE assignmentId = ? AND admissionNo = ?
                    LIMIT 1
                ");
                $stmt->bind_param('is', $assignmentId, $admissionNo);
                $stmt->execute();
                $existingSubmission = $stmt->get_result();
                
                if ($existingSubmission->num_rows > 0) {
                    // Update existing submission
                    $submissionId = $existingSubmission->fetch_assoc()['id'];
                    $updateStmt = $conn->prepare("
                        UPDATE tblassignmentsubmissions 
                        SET filePath = ?, fileName = ?, submittedAt = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->bind_param('ssi', $filePath, $uniqueFileName, $submissionId);
                    $updateStmt->execute();
                    
                    Logger::audit($admissionNo, 'Student', 'assignment_resubmitted', 'tblassignmentsubmissions', $submissionId);
                    $message = 'Assignment resubmitted successfully!';
                    $msgType = 'success';
                } else {
                    // Create new submission
                    $stmt = $conn->prepare("
                        INSERT INTO tblassignmentsubmissions 
                        (assignmentId, admissionNo, filePath, fileName, submittedAt)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->bind_param('isss', $assignmentId, $admissionNo, $filePath, $uniqueFileName);
                    
                    if ($stmt->execute()) {
                        Logger::audit($admissionNo, 'Student', 'assignment_submitted', 'tblassignmentsubmissions', $conn->insert_id);
                        NotificationHelper::create('Student', null, $admissionNo, 'Assignment Submitted', 
                            'Your assignment has been submitted successfully', 'assignment');
                        
                        $message = 'Assignment submitted successfully!';
                        $msgType = 'success';
                    } else {
                        $message = 'Error submitting assignment';
                        $msgType = 'danger';
                    }
                }
            } else {
                $message = 'Error uploading file. Please try again.';
                $msgType = 'danger';
            }
        }
    }
}

// Get active assignments
$stmt = $conn->prepare("
    SELECT a.*, 
        (SELECT id FROM tblassignmentsubmissions WHERE assignmentId = a.id AND admissionNo = ? LIMIT 1) as submitted
    FROM tblassignments a
    WHERE a.classId = ? AND a.classArmId = ? AND a.isActive = 1
    ORDER BY a.dueDate ASC
");
$stmt->bind_param('sii', $admissionNo, $classId, $classArmId);
$stmt->execute();
$assignmentsResult = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="../img/logo/attnlg.jpg" rel="icon">
    <title>My Assignments</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="../css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .assignment-card {
            background: white;
            border: 1px solid #e6ebf3;
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .assignment-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .assignment-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .assignment-subject {
            font-size: 0.9rem;
            color: #667eea;
            font-weight: 600;
        }

        .due-date-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .due-date-badge.upcoming {
            background: #dbeafe;
            color: #1e40af;
        }

        .due-date-badge.overdue {
            background: #fee2e2;
            color: #7f1d1d;
        }

        .due-date-badge.completed {
            background: #ecfdf5;
            color: #065f46;
        }

        .submission-status {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .submission-status.submitted {
            background: #ecfdf5;
            color: #065f46;
        }

        .submission-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .assignment-description {
            color: #6b7280;
            line-height: 1.6;
            margin: 1rem 0;
        }

        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%);
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .upload-area:hover {
            border-color: #764ba2;
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
        }

        .upload-area i {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .upload-area-text {
            color: #667eea;
            font-weight: 600;
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
                    <h1 class="h3 mb-4 text-gray-800">📝 My Assignments</h1>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $msgType; ?>" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php while ($assignment = $assignmentsResult->fetch_assoc()): ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <div>
                                <div class="assignment-subject"><?php echo htmlspecialchars($assignment['subject']); ?></div>
                                <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                            </div>
                            <?php 
                                $dueDate = strtotime($assignment['dueDate']);
                                $now = time();
                                if ($now > $dueDate) {
                                    $badgeClass = 'overdue';
                                    $badgeText = 'Overdue';
                                } else {
                                    $badgeClass = 'upcoming';
                                    $badgeText = 'Due ' . date('M d', $dueDate);
                                }
                            ?>
                            <span class="due-date-badge <?php echo $badgeClass; ?>">
                                <i class="fas fa-calendar"></i>
                                <?php echo $badgeText; ?>
                            </span>
                        </div>

                        <?php if (!empty($assignment['description'])): ?>
                        <div class="assignment-description">
                            <?php echo htmlspecialchars($assignment['description']); ?>
                        </div>
                        <?php endif; ?>

                        <div>
                            <?php if ($assignment['submitted']): ?>
                            <span class="submission-status submitted">
                                <i class="fas fa-check-circle"></i> Submitted
                            </span>
                            <?php else: ?>
                            <span class="submission-status pending">
                                <i class="fas fa-clock"></i> Pending Submission
                            </span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="mt-3">
                            <input type="hidden" name="action" value="submit">
                            <input type="hidden" name="assignmentId" value="<?php echo $assignment['id']; ?>">
                            
                            <label for="file_<?php echo $assignment['id']; ?>" class="upload-area">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div class="upload-area-text">Click to upload your assignment</div>
                                <small style="color: #6b7280;">or drag and drop (Max 10MB)</small>
                                <input type="file" id="file_<?php echo $assignment['id']; ?>" name="submissionFile" 
                                    style="display: none;" onchange="this.form.submit();" required>
                            </label>
                        </form>
                    </div>
                    <?php endwhile; ?>

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
?>
