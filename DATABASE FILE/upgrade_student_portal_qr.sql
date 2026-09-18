-- Student Portal + QR Attendance upgrade script
-- Run this script on your existing attendancemsystem database.

USE `attendancemsystem`;

-- 1) Strengthen password column lengths for hash storage
ALTER TABLE `tbladmin` MODIFY `password` VARCHAR(255) NOT NULL;
ALTER TABLE `tblclassteacher` MODIFY `password` VARCHAR(255) NOT NULL;
ALTER TABLE `tblstudents` MODIFY `password` VARCHAR(255) NOT NULL;

-- 2) Add student contact fields for portal profile usage
ALTER TABLE `tblstudents`
  ADD COLUMN IF NOT EXISTS `emailAddress` VARCHAR(150) NULL AFTER `admissionNumber`;

ALTER TABLE `tblstudents`
  ADD COLUMN IF NOT EXISTS `phoneNo` VARCHAR(30) NULL AFTER `emailAddress`;

-- 3) Add QR code master table
CREATE TABLE IF NOT EXISTS `tblqrcodes` (
  `Id` INT(11) NOT NULL AUTO_INCREMENT,
  `qrToken` VARCHAR(120) NOT NULL,
  `classId` INT(11) NOT NULL,
  `classArmId` INT(11) NOT NULL,
  `sessionTermId` INT(11) NOT NULL,
  `createdByTeacherId` INT(11) NOT NULL,
  `attendanceDate` DATE NOT NULL,
  `expiresAt` DATETIME NOT NULL,
  `isActive` TINYINT(1) NOT NULL DEFAULT 1,
  `dateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uniq_qr_token` (`qrToken`),
  KEY `idx_qr_class` (`classId`, `classArmId`),
  KEY `idx_qr_attendance_date` (`attendanceDate`),
  KEY `idx_qr_expiry` (`expiresAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Extend attendance table for QR tracking
ALTER TABLE `tblattendance`
  ADD COLUMN IF NOT EXISTS `qrCodeId` INT(11) NULL AFTER `status`;

ALTER TABLE `tblattendance`
  ADD COLUMN IF NOT EXISTS `markedBy` VARCHAR(20) NOT NULL DEFAULT 'manual' AFTER `qrCodeId`;

ALTER TABLE `tblattendance`
  ADD INDEX IF NOT EXISTS `idx_attendance_date_class` (`dateTimeTaken`, `classId`, `classArmId`);

ALTER TABLE `tblattendance`
  ADD INDEX IF NOT EXISTS `idx_attendance_admission` (`admissionNo`);

-- 5) Optional study material module table
CREATE TABLE IF NOT EXISTS `tblstudymaterials` (
  `Id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `filePath` VARCHAR(255) NOT NULL,
  `classId` INT(11) NULL,
  `classArmId` INT(11) NULL,
  `uploadedByAdminId` INT(11) NOT NULL,
  `dateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `idx_material_class` (`classId`, `classArmId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Helpful note:
-- Existing MD5/plain passwords remain valid until users log in and are re-hashed by updated PHP code.

-- 7) Lecture-level QR attendance tables for the real-time student scanner
CREATE TABLE IF NOT EXISTS `tbllecture_qrcodes` (
  `Id` INT(11) NOT NULL AUTO_INCREMENT,
  `subjectName` VARCHAR(120) NOT NULL,
  `lectureTitle` VARCHAR(180) NULL,
  `lectureDate` DATE NOT NULL,
  `lectureTime` TIME NOT NULL,
  `lectureToken` VARCHAR(80) NOT NULL,
  `qrPayload` LONGTEXT NOT NULL,
  `expiresAt` DATETIME NOT NULL,
  `classId` INT(11) NULL,
  `classArmId` INT(11) NULL,
  `createdByAdminId` INT(11) NOT NULL,
  `latitude` DECIMAL(10,7) NULL,
  `longitude` DECIMAL(10,7) NULL,
  `radiusMeters` INT(11) NULL,
  `isActive` TINYINT(1) NOT NULL DEFAULT 1,
  `dateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uniq_lecture_token` (`lectureToken`),
  KEY `idx_lecture_subject_date` (`subjectName`, `lectureDate`),
  KEY `idx_lecture_class` (`classId`, `classArmId`),
  KEY `idx_lecture_expires` (`expiresAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbllecture_attendance` (
  `Id` INT(11) NOT NULL AUTO_INCREMENT,
  `studentId` INT(11) NOT NULL,
  `admissionNumber` VARCHAR(50) NOT NULL,
  `subjectName` VARCHAR(120) NOT NULL,
  `lectureDate` DATE NOT NULL,
  `lectureTime` TIME NOT NULL,
  `lectureQrId` INT(11) NOT NULL,
  `lectureToken` VARCHAR(80) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Present',
  `scannedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `markedBy` VARCHAR(20) NOT NULL DEFAULT 'qr',
  `latitude` DECIMAL(10,7) NULL,
  `longitude` DECIMAL(10,7) NULL,
  `ipAddress` VARCHAR(45) NULL,
  `deviceInfo` VARCHAR(255) NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uniq_student_lecture` (`studentId`, `lectureQrId`),
  KEY `idx_student_subject_date` (`studentId`, `subjectName`, `lectureDate`),
  KEY `idx_lecture_token_attendance` (`lectureToken`),
  KEY `idx_attendance_scanned_at` (`scannedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
