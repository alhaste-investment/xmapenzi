-- ============================================================
-- Xmapenzi — MySQL schema (run once on a fresh database)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `videos` (
  `id` CHAR(36) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `category` VARCHAR(100) NOT NULL DEFAULT 'general',
  `thumbnail_url` VARCHAR(500) NULL,
  `video_url` VARCHAR(500) NOT NULL,
  `duration` INT NULL,
  `views` INT NOT NULL DEFAULT 0,
  `is_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `price_tzs` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `statuses` (
  `id` CHAR(36) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `whatsapp` VARCHAR(20) NULL,
  `photo_url` VARCHAR(500) NULL,
  `subtitle` VARCHAR(150) NULL,
  `description` TEXT NULL,
  `call_price_tzs` INT NOT NULL DEFAULT 1000,
  `chat_price_tzs` INT NOT NULL DEFAULT 500,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` VARCHAR(64) NOT NULL UNIQUE,
  `msisdn` VARCHAR(20) NOT NULL,
  `item_type` ENUM('video','status_call','status_chat') NOT NULL,
  `item_id` CHAR(36) NOT NULL,
  `amount_tzs` INT NOT NULL,
  `status` ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `selcom_reference` VARCHAR(100) NULL,
  `selcom_resultcode` VARCHAR(20) NULL,
  `selcom_message` VARCHAR(255) NULL,
  `unlock_token` VARCHAR(64) NULL,
  `paid_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_item` (`item_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `key_name` VARCHAR(64) NOT NULL,
  `value` TEXT NULL,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings
INSERT INTO `settings` (`key_name`, `value`) VALUES
  ('selcom_base_url', 'https://apigw.selcommobile.com'),
  ('selcom_api_key', ''),
  ('selcom_api_secret', ''),
  ('selcom_vendor', ''),
  ('selcom_webhook_token', '')
  ,('payment_provider','selcom'),
  ('grebo_base_url','https://grebo.tesloty.com'),
  ('grebo_api_key',''),
  ('grebo_webhook_secret','')
ON DUPLICATE KEY UPDATE `value` = `value`;

CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `msisdn` VARCHAR(20) NULL,
  `amount_tzs` INT NOT NULL,
  `admin` VARCHAR(64) NULL,
  `note` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: username=admin password=admin123 (BADILISHA mara baada ya ku-install)
INSERT INTO `admins` (`username`, `password_hash`) VALUES
  ('admin', '$2y$10$abcdefghijklmnopqrstuuQGYV3oRDqvDl9p1KJ6h.5Q1lP1Vh4Aq')
ON DUPLICATE KEY UPDATE `username` = `username`;
-- NOTE: hash hapo juu ni placeholder; install.php itaweka admin halisi.

-- Sample data
INSERT INTO `videos` (`id`, `title`, `description`, `category`, `thumbnail_url`, `video_url`, `is_paid`, `price_tzs`) VALUES
  (UUID(), 'Karibu Xmapenzi', 'Video ya ukaribisho', 'general', '', '', 0, 0)
ON DUPLICATE KEY UPDATE `title` = `title`;

SET FOREIGN_KEY_CHECKS = 1;