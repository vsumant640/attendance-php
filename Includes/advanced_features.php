<?php
/**
 * Advanced Features - Dark Mode, Notifications, Messaging, PDF
 */

/**
 * 1. DARK MODE MANAGER
 */
class DarkModeManager {
    private $conn;
    private $userId;
    private $userRole;
    
    public function __construct($connection, $userId, $userRole) {
        $this->conn = $connection;
        $this->userId = $userId;
        $this->userRole = $userRole;
    }
    
    public function getDarkModePreference() {
        $stmt = $this->conn->prepare("
            SELECT darkMode FROM tbluserpreferences 
            WHERE userId = ? AND userRole = ?
        ");
        $stmt->bind_param('ss', $this->userId, $this->userRole);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return (bool)$result->fetch_assoc()['darkMode'];
        }
        
        // Create default preference
        $defaultDarkMode = 0;
        $stmt = $this->conn->prepare("
            INSERT INTO tbluserpreferences (userId, userRole, darkMode) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE darkMode = VALUES(darkMode)
        ");
        $stmt->bind_param('ssi', $this->userId, $this->userRole, $defaultDarkMode);
        $stmt->execute();
        
        return false;
    }
    
    public function toggleDarkMode() {
        $currentPreference = $this->getDarkModePreference();
        $newPreference = $currentPreference ? 0 : 1;
        
        $stmt = $this->conn->prepare("
            UPDATE tbluserpreferences 
            SET darkMode = ? 
            WHERE userId = ? AND userRole = ?
        ");
        $stmt->bind_param('iss', $newPreference, $this->userId, $this->userRole);
        
        return $stmt->execute();
    }
}

/**
 * 2. PDF REPORT GENERATOR
 */
class PDFReportGenerator {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Generate attendance report as HTML (can be printed or converted to PDF)
     */
    public function generateAttendanceHTML($admissionNo, $classId, $classArmId, $sessionTermId) {
        // Get student info
        $stmt = $this->conn->prepare("
            SELECT firstName, lastName, admissionNo FROM tblstudents 
            WHERE admissionNo = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $admissionNo);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        
        // Get attendance data
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as totalClasses,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as presentDays
            FROM tblattendancelog
            WHERE admissionNo = ? AND classId = ? AND classArmId = ? AND sessionTermId = ?
        ");
        $stmt->bind_param('siii', $admissionNo, $classId, $classArmId, $sessionTermId);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        $totalClasses = (int)$stats['totalClasses'];
        $presentDays = (int)$stats['presentDays'];
        $percentage = $totalClasses > 0 ? round(($presentDays / $totalClasses) * 100, 2) : 0;
        
        // Generate HTML
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attendance Report - ' . htmlspecialchars($student['admissionNo']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #667eea; }
        .header p { margin: 5px 0; }
        .info-section { margin: 20px 0; }
        .info-section h3 { background: #667eea; color: white; padding: 10px; margin: 0 0 10px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #eee; }
        .info-row strong { width: 40%; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .stat-box { background: #f9fafb; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea; }
        .stat-box-value { font-size: 1.8em; font-weight: bold; color: #667eea; }
        .stat-box-label { font-size: 0.9em; color: #6b7280; }
        .footer { margin-top: 40px; text-align: center; color: #999; font-size: 0.9em; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th { background: #f3f4f6; padding: 10px; text-align: left; font-weight: bold; }
        .table td { padding: 10px; border-bottom: 1px solid #eee; }
        .percent-high { color: #10b981; }
        .percent-low { color: #ef4444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Attendance Report</h1>
        <p>Generated on ' . date('F d, Y') . '</p>
    </div>

    <div class="info-section">
        <h3>Student Information</h3>
        <div class="info-row">
            <strong>Name:</strong>
            <span>' . htmlspecialchars($student['firstName'] . ' ' . $student['lastName']) . '</span>
        </div>
        <div class="info-row">
            <strong>Admission No:</strong>
            <span>' . htmlspecialchars($student['admissionNo']) . '</span>
        </div>
        <div class="info-row">
            <strong>Report Date:</strong>
            <span>' . date('F d, Y h:i A') . '</span>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-box-value">' . $totalClasses . '</div>
            <div class="stat-box-label">Total Classes</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-value">' . $presentDays . '</div>
            <div class="stat-box-label">Days Present</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-value ' . ($percentage >= 75 ? 'percent-high' : 'percent-low') . '">' . $percentage . '%</div>
            <div class="stat-box-label">Attendance Rate</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-value">' . ($totalClasses - $presentDays) . '</div>
            <div class="stat-box-label">Days Absent</div>
        </div>
    </div>

    <div class="footer">
        <p>This is an official attendance report. For discrepancies, contact the administration.</p>
    </div>
</body>
</html>';
        
        return $html;
    }
}

/**
 * 3. CHAT MESSAGING SYSTEM
 */
class MessagingSystem {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Send message
     */
    public function sendMessage($senderId, $senderRole, $receiverId, $receiverRole, $message) {
        $stmt = $this->conn->prepare("
            INSERT INTO tblmessages 
            (senderId, senderRole, receiverId, receiverRole, message, createdAt)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('sssss', $senderId, $senderRole, $receiverId, $receiverRole, $message);
        
        return $stmt->execute();
    }
    
    /**
     * Get conversation
     */
    public function getConversation($userId, $otherUserId, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT * FROM tblmessages
            WHERE (senderId = ? AND receiverId = ?) OR (senderId = ? AND receiverId = ?)
            ORDER BY createdAt DESC
            LIMIT ?
        ");
        $stmt->bind_param('ssssi', $userId, $otherUserId, $otherUserId, $userId, $limit);
        $stmt->execute();
        
        return $stmt->get_result();
    }
    
    /**
     * Mark as read
     */
    public function markAsRead($userId) {
        $stmt = $this->conn->prepare("
            UPDATE tblmessages 
            SET isRead = 1, readAt = NOW()
            WHERE receiverId = ? AND isRead = 0
        ");
        $stmt->bind_param('s', $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($userId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as unread FROM tblmessages
            WHERE receiverId = ? AND isRead = 0
        ");
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result['unread'] ?? 0;
    }
}

/**
 * 4. NOTIFICATION CENTER
 */
class NotificationCenter {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get all notifications for user
     */
    public function getNotifications($recipientRole, $recipientId, $limit = 20) {
        $stmt = $this->conn->prepare("
            SELECT * FROM tblnotifications
            WHERE recipientRole = ? AND recipientId = ?
            AND (expiresAt IS NULL OR expiresAt > NOW())
            ORDER BY createdAt DESC
            LIMIT ?
        ");
        $stmt->bind_param('ssi', $recipientRole, $recipientId, $limit);
        $stmt->execute();
        
        return $stmt->get_result();
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId) {
        $stmt = $this->conn->prepare("
            UPDATE tblnotifications 
            SET isRead = 1, readAt = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param('i', $notificationId);
        
        return $stmt->execute();
    }
}

?>
