-- Authentication and user management schema upgrade
USE `attendancemsystem`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student','admin') NOT NULL DEFAULT 'student',
  `otp` VARCHAR(255) DEFAULT NULL,
  `otp_expiry` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  UNIQUE KEY `uniq_users_phone` (`phone`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_otp_expiry` (`otp_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- For environments where users table already exists and needs extra columns.
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `phone` VARCHAR(25) NOT NULL AFTER `email`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `otp` VARCHAR(255) DEFAULT NULL AFTER `role`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `otp_expiry` DATETIME DEFAULT NULL AFTER `otp`;
