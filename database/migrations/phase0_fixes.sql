-- HOTELOS PHASE 0 CRITICAL FIXES
-- GENERATED: 2026-01-06
-- PURPOSE: Fix GST logic and prepare for SMS OTP

SET FOREIGN_KEY_CHECKS=0;

-- 1. CREATE TAX SLABS TABLE (If Not Exists)
CREATE TABLE IF NOT EXISTS tax_slabs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    min_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_amount DECIMAL(10,2) NOT NULL DEFAULT 999999.99,
    percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. SEED TAX SLABS (India GST Logic)
TRUNCATE TABLE tax_slabs;

INSERT INTO tax_slabs (name, min_amount, max_amount, percentage, is_active) VALUES 
('GST Standard', 0.00, 7500.00, 12.00, 1),
('GST Luxury', 7500.01, 999999.00, 18.00, 1);

-- 3. ADD OTP COLUMN TO USERS
-- (Position AFTER password_hash, which is the correct column name in your DB)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'otp');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN otp VARCHAR(6) NULL AFTER password_hash', 'SELECT "Column otp already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. ADD OTP_EXPIRES_AT COLUMN
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'otp_expires_at');
SET @sql := IF(@exist = 0, 'ALTER TABLE users ADD COLUMN otp_expires_at TIMESTAMP NULL AFTER otp', 'SELECT "Column otp_expires_at already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=1;

-- CONFIRMATION
SELECT 'Phase 0 Fixes Applied Successfully' as status;
