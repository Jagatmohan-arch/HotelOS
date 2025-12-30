-- ============================================
-- EMERGENCY FIX: Run ALL Missing Migrations
-- Execute this on live database immediately
-- ============================================

-- Migration 1: Refund Requests Table (CRITICAL)
CREATE TABLE IF NOT EXISTS `refund_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED NOT NULL,
  `requested_by` INT UNSIGNED NOT NULL,
  `requested_amount` DECIMAL(10,2) NOT NULL,
  `reason_code` VARCHAR(50) NOT NULL,
  `reason_notes` TEXT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `approved_by` INT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `rejected_by` INT UNSIGNED NULL,
  `rejection_note` TEXT NULL,
  `credit_note_number` VARCHAR(50) NULL,
  `transaction_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_booking` (`booking_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`),
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`),
  FOREIGN KEY (`rejected_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 2: Add PIN to Users Table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `pin_hash` VARCHAR(255) NULL COMMENT 'Hashed 4-digit PIN for staff login' 
AFTER `password_hash`;

-- Migration 3: Engine Actions Table
CREATE TABLE IF NOT EXISTS `engine_actions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `action_type` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT UNSIGNED NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `reason` TEXT NOT NULL,
  `risk_level` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
  `password_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_action_type` (`action_type`),
  INDEX `idx_risk_level` (`risk_level`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 4: Invoice Snapshots Table
CREATE TABLE IF NOT EXISTS `invoice_snapshots` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `invoice_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED NOT NULL,
  `snapshot_data` JSON NOT NULL,
  `snapshot_reason` VARCHAR(255) NOT NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_invoice` (`invoice_id`),
  INDEX `idx_booking` (`booking_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 5: Branding Assets Table
CREATE TABLE IF NOT EXISTS `branding_assets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `asset_type` ENUM('logo', 'stamp', 'signature') NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_type` (`asset_type`),
  INDEX `idx_active` (`is_active`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 6: Subscription System
ALTER TABLE `tenants` 
ADD COLUMN IF NOT EXISTS `plan_started_at` TIMESTAMP NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `trial_ends_at` TIMESTAMP NULL AFTER `plan_started_at`,
ADD COLUMN IF NOT EXISTS `subscription_id` VARCHAR(100) NULL AFTER `trial_ends_at`,
ADD COLUMN IF NOT EXISTS `next_billing_date` DATE NULL AFTER `subscription_id`,
ADD COLUMN IF NOT EXISTS `billing_status` ENUM('active', 'past_due', 'cancelled', 'trial') DEFAULT 'trial' AFTER `next_billing_date`;

CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `monthly_price` DECIMAL(10,2) NOT NULL,
  `yearly_price` DECIMAL(10,2) NULL,
  `features` JSON NOT NULL,
  `max_rooms` INT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subscription_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `gateway_order_id` VARCHAR(100) NULL,
  `gateway_payment_id` VARCHAR(100) NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'INR',
  `status` ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) NULL,
  `billing_period_start` DATE NOT NULL,
  `billing_period_end` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Subscription Plans
INSERT IGNORE INTO `subscription_plans` (`slug`, `name`, `description`, `monthly_price`, `yearly_price`, `features`, `max_rooms`, `display_order`) VALUES
('starter', 'Starter', 'Perfect for small properties', 999.00, 9990.00, '["Up to 25 rooms", "Basic reports", "Email support", "Mobile app"]', 25, 1),
('professional', 'Professional', 'For growing hotels', 2499.00, 24990.00, '["Up to 100 rooms", "Advanced reports", "Priority support", "OTA integrations", "Custom branding"]', 100, 2),
('enterprise', 'Enterprise', 'Unlimited everything', 4999.00, 49990.00, '["Unlimited rooms", "Custom reports", "24/7 phone support", "White-label", "Dedicated account manager"]', NULL, 3);

-- Migration 7: Rate Limiting Table
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `action` VARCHAR(50) NOT NULL DEFAULT 'login',
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_agent` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_ip_action` (`ip_address`, `action`),
  INDEX `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SUCCESS MESSAGE
-- ============================================
SELECT '✅ ALL MIGRATIONS APPLIED SUCCESSFULLY!' AS Status;
SELECT 'Live site should now work without errors' AS Message;
