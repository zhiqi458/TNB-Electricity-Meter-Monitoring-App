CREATE DATABASE IF NOT EXISTS `tnb_meter_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tnb_meter_db`;

-- 1. 用户表
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `role` ENUM('admin', 'user') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 插入测试账号
INSERT INTO `users` (`username`, `role`) VALUES ('admin1', 'admin'), ('staff1', 'user');

-- 2. 电表表
CREATE TABLE IF NOT EXISTS `meters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `building_name` VARCHAR(100) NOT NULL,
  `meter_code` VARCHAR(50) NOT NULL UNIQUE,
  `daily_limit_kwh` DECIMAL(10,2) DEFAULT 80.00, -- 管理员设置的每日用量上限 (默认80kWh)
  `submission_deadline` TIME DEFAULT '18:00:00' -- 每日提交截止时间
);

-- 插入测试电表
INSERT INTO `meters` (`building_name`, `meter_code`, `daily_limit_kwh`) VALUES ('Building A - Main Meter', 'METER-A01', 80.00);

-- 3. 电表读数记录表
CREATE TABLE IF NOT EXISTS `meter_readings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `meter_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `reading_value` DECIMAL(10,2) NOT NULL,
  `daily_usage` DECIMAL(10,2) DEFAULT 0.00,
  `photo_path` VARCHAR(255) NOT NULL,
  `is_live_photo` TINYINT(1) DEFAULT 0, -- 1代表实时拍摄(V2)，0代表相册/通用上传(V1)
  `submission_date` DATE NOT NULL,
  `submission_time` TIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`meter_id`) REFERENCES `meters`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);

-- 4. 系统通知/警报日志表
CREATE TABLE IF NOT EXISTS `alerts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `meter_id` INT NOT NULL,
  `alert_type` ENUM('OVER_USAGE', 'MISSING_READING') NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL
);