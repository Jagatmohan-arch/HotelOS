-- Phase F2: Cash Counter Ledger
-- Tracks petty cash expenses and additions during a shift

DROP TABLE IF EXISTS `cash_ledger`;
CREATE TABLE `cash_ledger` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `shift_id` INT UNSIGNED NOT NULL COMMENT 'Link to the active shift',
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Staff who made the entry',
    
    -- Transaction
    `type` ENUM('expense', 'addition') NOT NULL COMMENT 'Expense = money out, Addition = money in (not from bookings)',
    `amount` DECIMAL(10,2) NOT NULL,
    `category` VARCHAR(50) NOT NULL COMMENT 'e.g., Petty Cash, Snacks, Taxi, Maintenance',
    `description` VARCHAR(255) NULL,
    
    -- Meta
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_ledger_tenant` (`tenant_id`),
    INDEX `idx_ledger_shift` (`shift_id`),
    INDEX `idx_ledger_date` (`created_at`),
    
    CONSTRAINT `fk_ledger_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ledger_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ledger_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
