-- ==========================================
-- Migration Tracking Table
-- Keeps record of all applied database migrations
-- ==========================================

CREATE TABLE IF NOT EXISTS `migrations_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration_file` VARCHAR(255) NOT NULL COMMENT 'Filename of migration (e.g., shifts_table.sql)',
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `applied_by` VARCHAR(100) DEFAULT 'migrate.php' COMMENT 'Who/what applied this',
    `checksum` VARCHAR(64) NULL COMMENT 'SHA256 of file content to detect changes',
    `execution_time_ms` INT UNSIGNED NULL COMMENT 'How long it took to run',
    `status` ENUM('success', 'failed') DEFAULT 'success',
    `error_message` TEXT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_migration_file` (`migration_file`),
    INDEX `idx_applied_at` (`applied_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✅ migrations_log table created for tracking' AS Status;
