-- HOTELOS PHASE 0 CRITICAL FIXES
-- GENERATED: 2026-01-06
-- PURPOSE: Fix GST logic and prepare for SMS OTP

SET FOREIGN_KEY_CHECKS=0;

-- 1. SEED TAX SLABS (India GST Logic)
-- Clear existing to avoid duplicates if any
TRUNCATE TABLE tax_slabs;

-- Insert Standard Hotel GST Slabs (As of 2025-26)
-- 0 - 7500 => 12%
-- > 7500 => 18%
INSERT INTO tax_slabs (name, min_amount, max_amount, percentage, is_active) VALUES 
('GST Standard', 0.00, 7500.00, 12.00, 1),
('GST Luxury', 7500.01, 999999.00, 18.00, 1);

-- 2. ADD OTP COLUMN TO USERS
-- Required for future SMS Login/Verification
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'otp');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN otp VARCHAR(6) NULL AFTER password', 'SELECT "Column otp already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. ADD OTP_EXPIRES_AT COLUMN
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'otp_expires_at');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN otp_expires_at TIMESTAMP NULL AFTER otp', 'SELECT "Column otp_expires_at already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=1;

-- CONFIRMATION
SELECT 'Phase 0 Fixes Applied Successfully' as status;
