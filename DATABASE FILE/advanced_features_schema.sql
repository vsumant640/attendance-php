-- ===============================================
-- ADVANCED FEATURES DATABASE SCHEMA 
-- Advanced Student Portal - Production Ready
-- ===============================================

-- ========== 1. ENHANCED ATTENDANCE TABLES ==========

-- Attendance Log - Stores all attendance attempts with timestamp validation
CREATE TABLE IF NOT EXISTS `tblattendancelog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admissionNo` varchar(255) NOT NULL,
  `classId` int(11) NOT NULL,
  `classArmId` int(11) NOT NULL,
  `sessionTermId` int(11) NOT NULL,
  `qrToken` varchar(255) NOT NULL,
  `latitude` decimal(10,8),
  `longitude` decimal(11,8),
  `scannedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ipAddress` varchar(45),
  `deviceInfo` varchar(255),
  `status` enum('present','late','absent','invalid_location','expired_token') DEFAULT 'present',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admission_date` (`admissionNo`, `scannedAt`),
  KEY `idx_class_date` (`classId`, `classArmId`, `scannedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prevent duplicate attendance on same day
ALTER TABLE `tblattendancelog` ADD UNIQUE KEY `unique_attendance_per_day` 
  (`admissionNo`, `classId`, `classArmId`, `scannedAt`);

-- ========== 2. TIMETABLE TABLES ==========

-- Timetable entries
CREATE TABLE IF NOT EXISTS `tbltimetables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classId` int(11) NOT NULL,
  `classArmId` int(11) NOT NULL,
  `dayOfWeek` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `subject` varchar(100) NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL,
  `roomNo` varchar(50),
  `teacher` varchar(100),
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_class` (`classId`, `classArmId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 3. ASSIGNMENT TABLES ==========

-- Assignments
CREATE TABLE IF NOT EXISTS `tblassignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classId` int(11) NOT NULL,
  `classArmId` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext,
  `filePath` varchar(255),
  `fileName` varchar(255),
  `dueDate` datetime NOT NULL,
  `maxScore` int(11) DEFAULT 100,
  `createdBy` int(11) NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isActive` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_class` (`classId`, `classArmId`),
  KEY `idx_duedate` (`dueDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignment Submissions
CREATE TABLE IF NOT EXISTS `tblassignmentsubmissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignmentId` int(11) NOT NULL,
  `admissionNo` varchar(255) NOT NULL,
  `filePath` varchar(255) NOT NULL,
  `fileName` varchar(255) NOT NULL,
  `submittedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `score` int(11),
  `feedback` longtext,
  `gradedAt` datetime,
  `gradedBy` int(11),
  `isLate` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_assignment` (`assignmentId`),
  KEY `idx_student` (`admissionNo`),
  FOREIGN KEY (`assignmentId`) REFERENCES `tblassignments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 4. NOTIFICATION TABLES ==========

-- Notifications
CREATE TABLE IF NOT EXISTS `tblnotifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipientId` int(11),
  `recipientRole` enum('Student','Teacher','Admin') NOT NULL,
  `admissionNo` varchar(255),
  `title` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `type` enum('attendance','assignment','message','system','alert') DEFAULT 'system',
  `relatedId` int(11),
  `isRead` tinyint(1) DEFAULT 0,
  `readAt` datetime,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expiresAt` datetime,
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipientRole`, `admissionNo`, `isRead`),
  KEY `idx_created` (`createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 5. MESSAGING TABLES ==========

-- Messages (Internal Chat)
CREATE TABLE IF NOT EXISTS `tblmessages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `senderId` varchar(255) NOT NULL,
  `senderRole` enum('Student','Teacher','Admin') NOT NULL,
  `receiverId` varchar(255) NOT NULL,
  `receiverRole` enum('Student','Teacher','Admin') NOT NULL,
  `message` longtext NOT NULL,
  `filePath` varchar(255),
  `isRead` tinyint(1) DEFAULT 0,
  `readAt` datetime,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`senderId`, `receiverId`),
  KEY `idx_unread` (`receiverId`, `isRead`),
  KEY `idx_created` (`createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 6. LOCATION TRACKING TABLE ==========

-- Student Locations for geography-based attendance
CREATE TABLE IF NOT EXISTS `tblclasslocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classId` int(11) NOT NULL,
  `classArmId` int(11) NOT NULL,
  `locationName` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radiusMeters` int(11) DEFAULT 100,
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_class` (`classId`, `classArmId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 7. QR CODE VALIDATION UPDATES ==========

-- Update existing tblqrcodes table to add validation fields
ALTER TABLE `tblqrcodes` 
ADD COLUMN `isUsed` tinyint(1) DEFAULT 0 AFTER `isActive`,
ADD COLUMN `usedBy` varchar(255) AFTER `isUsed`,
ADD COLUMN `usedAt` datetime AFTER `usedBy`,
ADD KEY `idx_token` (`qrToken`),
ADD KEY `idx_expiry` (`expiresAt`);

-- ========== 8. AUDIT LOG TABLE ==========

-- Security audit log
CREATE TABLE IF NOT EXISTS `tblauditlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` varchar(255),
  `userRole` enum('Student','Teacher','Admin') NOT NULL,
  `action` varchar(255) NOT NULL,
  `entityType` varchar(100),
  `entityId` int(11),
  `oldData` longtext,
  `newData` longtext,
  `ipAddress` varchar(45),
  `userAgent` varchar(255),
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`userId`, `userRole`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 9. USER PREFERENCES TABLE ==========

-- Store user preferences (dark mode, notifications, etc.)
CREATE TABLE IF NOT EXISTS `tbluserpreferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` varchar(255) NOT NULL,
  `userRole` enum('Student','Teacher','Admin') NOT NULL,
  `darkMode` tinyint(1) DEFAULT 0,
  `emailNotifications` tinyint(1) DEFAULT 1,
  `pushNotifications` tinyint(1) DEFAULT 1,
  `language` varchar(10) DEFAULT 'en',
  `timezone` varchar(50) DEFAULT 'UTC',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_pref` (`userId`, `userRole`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== 10. ATTENDANCE PERCENTAGE CACHE TABLE ==========

-- Cache attendance calculations for performance
CREATE TABLE IF NOT EXISTS `tblattendancecache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admissionNo` varchar(255) NOT NULL,
  `classId` int(11) NOT NULL,
  `classArmId` int(11) NOT NULL,
  `sessionTermId` int(11) NOT NULL,
  `totalClasses` int(11) DEFAULT 0,
  `totalPresent` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `lastUpdated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`admissionNo`, `sessionTermId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- END OF SCHEMA
-- ===============================================
