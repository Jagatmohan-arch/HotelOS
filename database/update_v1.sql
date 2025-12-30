-- HotelOS Update v1.0 (Post-Audit Fixes)
-- Run this AFTER importing schema.sql

-- 1. Shifts Table (For Cash Closure)
CREATE TABLE IF NOT EXISTS `shifts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `shift_start_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `shift_end_at` TIMESTAMP NULL,
    `opening_cash` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `closing_cash` DECIMAL(10,2) NULL,
    `system_expected_cash` DECIMAL(10,2) NULL,
    `variance_amount` DECIMAL(10,2) NULL,
    `handover_to_user_id` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `verified_by` INT UNSIGNED NULL,
    `verified_at` TIMESTAMP NULL,
    `manager_note` VARCHAR(255) NULL,
    `status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_shifts_tenant` (`tenant_id`),
    INDEX `idx_shifts_user` (`user_id`),
    INDEX `idx_shifts_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Cash Ledger Table (For Petty Cash)
CREATE TABLE IF NOT EXISTS `cash_ledger` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `shift_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('expense', 'addition') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ledger_tenant` (`tenant_id`),
    INDEX `idx_ledger_shift` (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Update Bookings Table (Time Fields)
-- Using procedure to safely add columns (if not exist) is hard in pure SQL without stored procs.
-- Assuming standard MySQL 5.7+ direct ALTER Ignore or just try-catch (user will see error if exists, which is fine)
ALTER TABLE bookings ADD COLUMN `check_in_time` TIME DEFAULT '14:00:00' AFTER `check_out_date`;
ALTER TABLE bookings ADD COLUMN `check_out_time` TIME DEFAULT '11:00:00' AFTER `check_in_time`;

-- 4. Update Transactions Table (Ledger Type for Bank/Cash separation)
ALTER TABLE transactions 
ADD COLUMN `ledger_type` ENUM('cash_drawer', 'bank', 'ota_receivable', 'credit_ledger') 
NOT NULL DEFAULT 'cash_drawer' 
AFTER `type`;

-- 5. Data Migration (Set existing UPI/Card to Bank Ledger)
UPDATE transactions SET ledger_type = 'bank' WHERE payment_mode IN ('upi', 'card', 'bank_transfer', 'cheque', 'cashfree');
UPDATE transactions SET ledger_type = 'ota_receivable' WHERE payment_mode IN ('ota_prepaid', 'online');
UPDATE transactions SET ledger_type = 'credit_ledger' WHERE payment_mode IN ('credit', 'post_bill');
