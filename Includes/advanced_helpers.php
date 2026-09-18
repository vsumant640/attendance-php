<?php
/**
 * Database Helper Class
 * Provides safe database operations with prepared statements
 */

class DatabaseHelper {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Execute SELECT query and return results
     */
    public function select($table, $columns = '*', $where = '', $params = [], $limit = '') {
        $query = "SELECT {$columns} FROM `{$table}`";
        if (!empty($where)) {
            $query .= " WHERE {$where}";
        }
        if (!empty($limit)) {
            $query .= " LIMIT {$limit}";
        }
        
        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $this->bindParams($stmt, $params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Insert data safely
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        $query = "INSERT INTO `{$table}` (" . implode(',', $columns) . ") VALUES ({$placeholders})";
        
        $stmt = $this->conn->prepare($query);
        $types = $this->getParamTypes(array_values($data));
        $stmt->bind_param($types, ...array_values($data));
        
        return $stmt->execute();
    }
    
    /**
     * Update data safely
     */
    public function update($table, $data, $where, $params = []) {
        $setClauses = [];
        foreach ($data as $col => $val) {
            $setClauses[] = "`{$col}` = ?";
        }
        $setString = implode(',', $setClauses);
        
        $query = "UPDATE `{$table}` SET {$setString} WHERE {$where}";
        
        $stmt = $this->conn->prepare($query);
        $allParams = array_merge(array_values($data), $params);
        $types = $this->getParamTypes($allParams);
        $stmt->bind_param($types, ...$allParams);
        
        return $stmt->execute();
    }
    
    /**
     * Delete data safely
     */
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $types = $this->getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Get parameter types for bind_param
     */
    private function getParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
    
    /**
     * Bind parameters to statement
     */
    private function bindParams(&$stmt, $params) {
        $types = $this->getParamTypes($params);
        $stmt->bind_param($types, ...$params);
    }
}

/**
 * Security Helper Class
 */
class SecurityHelper {
    /**
     * Sanitize input
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Prevent SQL injection validation
     */
    public static function validateInput($input, $type = 'string') {
        switch ($type) {
            case 'integer':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
            case 'string':
            default:
                return self::sanitizeInput($input);
        }
    }
}

/**
 * Logger Helper Class
 */
class Logger {
    private static $conn;
    
    public static function setConnection($connection) {
        self::$conn = $connection;
    }
    
    /**
     * Log audit trail
     */
    public static function audit($userId, $userRole, $action, $entityType = null, $entityId = null, $oldData = null, $newData = null) {
        if (!self::$conn) return false;
        
        $ipAddress = SecurityHelper::getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $oldDataJson = $oldData ? json_encode($oldData) : null;
        $newDataJson = $newData ? json_encode($newData) : null;
        
        $stmt = self::$conn->prepare("INSERT INTO tblauditlog 
            (userId, userRole, action, entityType, entityId, oldData, newData, ipAddress, userAgent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param('ssssiisss', $userId, $userRole, $action, $entityType, $entityId, $oldDataJson, $newDataJson, $ipAddress, $userAgent);
        
        return $stmt->execute();
    }
    
    /**
     * Log error
     */
    public static function error($message, $context = []) {
        $logPath = __DIR__ . '/../logs/error.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[{$timestamp}] {$message} {$contextStr}\n";
        
        file_put_contents($logPath, $logMessage, FILE_APPEND);
    }
    
    /**
     * Log info
     */
    public static function info($message) {
        $logPath = __DIR__ . '/../logs/info.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        file_put_contents($logPath, $logMessage, FILE_APPEND);
    }
}

/**
 * Notification Helper Class
 */
class NotificationHelper {
    private static $conn;
    
    public static function setConnection($connection) {
        self::$conn = $connection;
    }
    
    /**
     * Create notification
     */
    public static function create($recipientRole, $recipientId, $admissionNo, $title, $message, $type = 'system', $relatedId = null) {
        if (!self::$conn) return false;
        
        $stmt = self::$conn->prepare("INSERT INTO tblnotifications 
            (recipientRole, recipientId, admissionNo, title, message, type, relatedId) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param('ssisssi', $recipientRole, $recipientId, $admissionNo, $title, $message, $type, $relatedId);
        
        return $stmt->execute();
    }
    
    /**
     * Mark as read
     */
    public static function markAsRead($notificationId) {
        if (!self::$conn) return false;
        
        $stmt = self::$conn->prepare("UPDATE tblnotifications SET isRead = 1, readAt = NOW() WHERE id = ?");
        $stmt->bind_param('i', $notificationId);
        
        return $stmt->execute();
    }
    
    /**
     * Get unread count
     */
    public static function getUnreadCount($recipientRole, $recipientId) {
        if (!self::$conn) return 0;
        
        $stmt = self::$conn->prepare("SELECT COUNT(*) as count FROM tblnotifications 
            WHERE recipientRole = ? AND recipientId = ? AND isRead = 0");
        $stmt->bind_param('ss', $recipientRole, $recipientId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] ?? 0;
    }
}

?>
