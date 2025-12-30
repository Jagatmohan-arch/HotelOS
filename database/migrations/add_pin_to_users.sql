-- ============================================
-- HotelOS Migration: Add PIN Authentication for Staff
-- Adds pin_hash column to users table for 4-digit PIN login
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- Add pin_hash column to users table
ALTER TABLE `users` 
ADD COLUMN `pin_hash` VARCHAR(255) NULL COMMENT 'Hashed 4-digit PIN for staff login' 
AFTER `password_hash`;

-- Add index for PIN lookups (performance)
ALTER TABLE `users` 
ADD INDEX `idx_user_pin` (`pin_hash`);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SUCCESS: PIN column added to users table
-- ============================================
SELECT '✅ Staff PIN authentication column added to users table' AS Status;
