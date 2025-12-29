-- ============================================
-- HotelOS Migration: Hotel Engine Tables
-- Owner-Only Super Control System
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABLE: engine_actions (Immutable Audit Trail)
-- All dangerous owner actions logged here
-- ============================================
DROP TABLE IF EXISTS `engine_actions`;
CREATE TABLE `engine_actions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    
    -- Action Details
    `action_type` VARCHAR(50) NOT NULL COMMENT 'invoice_edit, void, cash_override, staff_block',
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'invoice, booking, shift, user, settings',
    `entity_id` INT UNSIGNED NULL,
    
    -- Change Tracking
    `old_values` JSON NULL COMMENT 'State before change',
    `new_values` JSON NULL COMMENT 'State after change',
    `reason` VARCHAR(500) NOT NULL COMMENT 'Mandatory reason for action',
    
    -- Risk Classification
    `risk_level` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    `password_confirmed` BOOLEAN DEFAULT FALSE COMMENT 'Re-auth was required',
    
    -- Context
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_engine_tenant` (`tenant_id`),
    INDEX `idx_engine_user` (`user_id`),
    INDEX `idx_engine_action` (`action_type`),
    INDEX `idx_engine_risk` (`risk_level`),
    INDEX `idx_engine_date` (`created_at`),
    
    CONSTRAINT `fk_engine_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_engine_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: invoice_snapshots (Point-in-Time Backup)
-- Preserves original invoice before modifications
-- ============================================
DROP TABLE IF EXISTS `invoice_snapshots`;
CREATE TABLE `invoice_snapshots` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `invoice_id` INT UNSIGNED NOT NULL,
    `booking_id` INT UNSIGNED NOT NULL,
    
    -- Snapshot Data
    `snapshot_data` JSON NOT NULL COMMENT 'Full invoice at moment of snapshot',
    `snapshot_reason` VARCHAR(100) NOT NULL COMMENT 'before_edit, before_void, manual',
    
    -- Context
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_snapshot_tenant` (`tenant_id`),
    INDEX `idx_snapshot_invoice` (`invoice_id`),
    INDEX `idx_snapshot_booking` (`booking_id`),
    
    CONSTRAINT `fk_snapshot_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: branding_assets (Logo, Stamp, Signature)
-- ============================================
DROP TABLE IF EXISTS `branding_assets`;
CREATE TABLE `branding_assets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    
    -- Asset Details
    `asset_type` ENUM('logo', 'stamp', 'signature') NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(100) NOT NULL,
    `mime_type` VARCHAR(50) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL COMMENT 'Bytes',
    
    -- Context
    `uploaded_by` INT UNSIGNED NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_brand_tenant_type` (`tenant_id`, `asset_type`),
    
    CONSTRAINT `fk_brand_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_brand_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SCHEMA MODIFICATIONS
-- ============================================

-- Add PIN to users (for staff quick login)
ALTER TABLE `users` 
    ADD COLUMN `pin_hash` VARCHAR(255) NULL AFTER `password_hash`,
    ADD COLUMN `pin_attempts` TINYINT UNSIGNED DEFAULT 0 AFTER `pin_hash`,
    ADD COLUMN `pin_locked_until` TIMESTAMP NULL AFTER `pin_attempts`;

-- Add system controls to tenants
ALTER TABLE `tenants`
    ADD COLUMN `data_locked_until` DATE NULL COMMENT 'No edits allowed before this date',
    ADD COLUMN `maintenance_mode` BOOLEAN DEFAULT FALSE,
    ADD COLUMN `maintenance_message` VARCHAR(255) NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SUCCESS
-- ============================================
SELECT '✅ Hotel Engine Tables Created!' AS Status;
