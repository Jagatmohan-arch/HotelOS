-- Phase F1: Staff Shift & Handover System
-- Tracks staff sessions and cash responsibility

DROP TABLE IF EXISTS `shifts`;
CREATE TABLE `shifts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Staff member owning this shift',
    
    -- Timings
    `shift_start_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `shift_end_at` TIMESTAMP NULL,
    
    -- Cash Handling (The Core Value)
    `opening_cash` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `closing_cash` DECIMAL(10,2) NULL,
    `system_expected_cash` DECIMAL(10,2) NULL COMMENT 'Snapshot of theoretical cash',
    `variance_amount` DECIMAL(10,2) NULL COMMENT 'closing - expected',
    
    -- Handover
    `handover_to_user_id` INT UNSIGNED NULL COMMENT 'Next staff member',
    `notes` TEXT NULL COMMENT 'Shift summary / handover notes',
    
    -- Status
    `status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    
    -- Meta
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_shifts_tenant` (`tenant_id`),
    INDEX `idx_shifts_user` (`user_id`),
    INDEX `idx_shifts_status` (`status`),
    INDEX `idx_shifts_date` (`shift_start_at`),
    
    CONSTRAINT `fk_shifts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_shifts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_shifts_handover` FOREIGN KEY (`handover_to_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
