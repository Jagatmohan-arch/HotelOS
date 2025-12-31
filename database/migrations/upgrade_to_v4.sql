-- ============================================
-- HotelOS Master Migration Script
-- For upgrading existing v3.0 databases to v4.0
-- Run this on live/existing databases
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. ADD pin COLUMN TO users TABLE
-- ============================================
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `pin` CHAR(4) NULL COMMENT '4-digit PIN for staff quick login' 
AFTER `locked_until`;

-- ============================================
-- 2. ADD ledger_type COLUMN TO transactions TABLE
-- ============================================
ALTER TABLE `transactions`
ADD COLUMN IF NOT EXISTS `ledger_type` ENUM('cash_drawer', 'bank', 'ota_receivable', 'credit_ledger') 
DEFAULT 'cash_drawer' COMMENT 'Which ledger this transaction affects'
AFTER `category`;

-- ============================================
-- 3. EXPAND payment_mode ENUM in transactions
-- ============================================
ALTER TABLE `transactions`
MODIFY COLUMN `payment_mode` ENUM('cash', 'card', 'upi', 'bank_transfer', 'cheque', 'wallet', 'online', 'cashfree', 'ota_prepaid', 'credit', 'post_bill') NOT NULL;

-- ============================================
-- 4. CREATE refund_requests TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `refund_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `booking_id` INT UNSIGNED NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL,
    `requested_amount` DECIMAL(10,2) NOT NULL,
    `max_refundable` DECIMAL(10,2) NOT NULL,
    `reason_code` ENUM('service_complaint', 'early_checkout', 'booking_cancelled', 'overcharge', 'other') NOT NULL,
    `reason_text` VARCHAR(500) NULL,
    `requested_by` INT UNSIGNED NOT NULL,
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `approved_by` INT UNSIGNED NULL,
    `approved_at` TIMESTAMP NULL,
    `rejection_note` VARCHAR(255) NULL,
    `credit_note_number` VARCHAR(50) NULL,
    `transaction_id` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_refund_tenant` (`tenant_id`),
    INDEX `idx_refund_status` (`status`),
    CONSTRAINT `fk_refund_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_refund_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. CREATE room_move_history TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `room_move_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `booking_id` INT UNSIGNED NOT NULL,
    `from_room_id` INT UNSIGNED NOT NULL,
    `to_room_id` INT UNSIGNED NOT NULL,
    `from_room_number` VARCHAR(20) NOT NULL,
    `to_room_number` VARCHAR(20) NOT NULL,
    `reason` ENUM('maintenance', 'upgrade', 'downgrade', 'guest_request', 'housekeeping', 'other') NOT NULL,
    `notes` VARCHAR(500) NULL,
    `rate_action` ENUM('keep_original', 'use_new_rate', 'custom') NOT NULL DEFAULT 'keep_original',
    `old_rate` DECIMAL(10,2) NOT NULL,
    `new_rate` DECIMAL(10,2) NOT NULL,
    `moved_by` INT UNSIGNED NOT NULL,
    `moved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_move_tenant` (`tenant_id`),
    INDEX `idx_move_booking` (`booking_id`),
    CONSTRAINT `fk_move_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. CREATE engine_actions TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `engine_actions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `reason` VARCHAR(500) NOT NULL,
    `risk_level` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    `password_confirmed` BOOLEAN DEFAULT FALSE,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_engine_tenant` (`tenant_id`),
    INDEX `idx_engine_action` (`action_type`),
    CONSTRAINT `fk_engine_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. CREATE police_reports TABLE
-- ============================================
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
    CONSTRAINT `fk_police_report_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. CREATE subscription_plans TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) UNIQUE NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `price_monthly` DECIMAL(10,2) NOT NULL,
    `price_yearly` DECIMAL(10,2) NULL,
    `max_rooms` INT NULL,
    `max_users` INT NULL,
    `features` JSON NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. CREATE subscription_transactions TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `subscription_transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `plan_id` INT UNSIGNED NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'INR',
    `payment_gateway` VARCHAR(50) DEFAULT 'cashfree',
    `gateway_transaction_id` VARCHAR(255) NULL,
    `gateway_order_id` VARCHAR(255) NULL,
    `status` ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
    `type` ENUM('trial', 'subscription', 'upgrade', 'renewal', 'downgrade') NOT NULL,
    `billing_period` ENUM('monthly', 'yearly') NULL,
    `invoice_url` VARCHAR(500) NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sub_txn_tenant` (`tenant_id`),
    INDEX `idx_sub_txn_status` (`status`),
    CONSTRAINT `fk_sub_txn_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. CREATE invoice_snapshots TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `invoice_snapshots` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `invoice_id` INT UNSIGNED NULL,
    `booking_id` INT UNSIGNED NOT NULL,
    `snapshot_reason` VARCHAR(255) NOT NULL,
    `invoice_data` JSON NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_snapshot_tenant` (`tenant_id`),
    INDEX `idx_snapshot_booking` (`booking_id`),
    CONSTRAINT `fk_snapshot_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. SEED subscription_plans (if empty)
-- ============================================
INSERT IGNORE INTO `subscription_plans` (`slug`, `name`, `display_name`, `price_monthly`, `price_yearly`, `max_rooms`, `max_users`, `features`, `sort_order`) VALUES
('starter', 'Starter', 'Starter Plan', 999.00, 9990.00, 10, 5, '{"email_notifications": true, "basic_reports": true, "police_reports": true, "pdf_invoices": true}', 1),
('professional', 'Professional', 'Professional Plan', 2499.00, 24990.00, 30, 15, '{"email_notifications": true, "basic_reports": true, "police_reports": true, "pdf_invoices": true, "sms_notifications": true, "advanced_reports": true, "api_access": true}', 2),
('enterprise', 'Enterprise', 'Enterprise Plan', 4999.00, 49990.00, NULL, NULL, '{"email_notifications": true, "basic_reports": true, "police_reports": true, "pdf_invoices": true, "sms_notifications": true, "advanced_reports": true, "api_access": true, "whatsapp": true, "priority_support": true}', 3);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- VERIFICATION: Check all tables exist
-- ============================================
SELECT 'Migration Complete! Run this to verify:' AS Status;
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME;
