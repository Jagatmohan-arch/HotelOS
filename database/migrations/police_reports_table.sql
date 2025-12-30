-- ==========================================
-- Missing Table: police_reports
-- For Indian C-Form compliance tracking
-- ==========================================

CREATE TABLE IF NOT EXISTS `police_reports` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `report_date` DATE NOT NULL,
    `status` ENUM('pending', 'submitted') DEFAULT 'pending',
    `submitted_at` TIMESTAMP NULL,
    `submitted_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_police_report_date` (`tenant_id`, `report_date`),
    INDEX `idx_police_status` (`status`),
    INDEX `idx_police_date` (`report_date`),
    
    CONSTRAINT `fk_police_report_tenant` 
        FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✅ police_reports table created' AS Status;
