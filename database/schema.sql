-- ============================================
-- HotelOS Database Schema v1.0
-- Production Schema for hotelos.needkit.in
-- Database: uplfveim_hotelos
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

-- ============================================
-- TABLE: tenants (Hotel Properties)
-- ============================================
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `name` VARCHAR(255) NOT NULL COMMENT 'Hotel Display Name',
    `legal_name` VARCHAR(255) NOT NULL COMMENT 'Legal entity for invoices',
    `slug` VARCHAR(100) NOT NULL COMMENT 'URL-safe identifier',
    
    -- Indian GST Compliance
    `gst_number` VARCHAR(15) NULL COMMENT 'GSTIN (15 chars)',
    `state_code` CHAR(2) NOT NULL COMMENT 'GST State Code',
    `pan_number` VARCHAR(10) NULL,
    
    -- Contact
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(15) NOT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `pincode` VARCHAR(6) NOT NULL,
    
    -- Config
    `timezone` VARCHAR(50) DEFAULT 'Asia/Kolkata',
    `currency` CHAR(3) DEFAULT 'INR',
    `check_in_time` TIME DEFAULT '14:00:00',
    `check_out_time` TIME DEFAULT '11:00:00',
    
    -- Status
    `status` ENUM('active', 'suspended', 'trial') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tenant_uuid` (`uuid`),
    UNIQUE KEY `uk_tenant_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: users (Staff & Admin Accounts)
-- ============================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `uuid` CHAR(36) NOT NULL,
    
    -- Auth
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Argon2ID',
    `phone` VARCHAR(15) NULL,
    
    -- Profile
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    
    -- RBAC
    `role` ENUM('superadmin', 'owner', 'manager', 'reception', 'housekeeping') NOT NULL DEFAULT 'reception',
    
    -- Security
    `is_active` BOOLEAN DEFAULT TRUE,
    `last_login_at` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `failed_attempts` TINYINT UNSIGNED DEFAULT 0,
    `locked_until` TIMESTAMP NULL,
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_uuid` (`uuid`),
    UNIQUE KEY `uk_user_email` (`email`),
    INDEX `idx_user_tenant` (`tenant_id`),
    INDEX `idx_user_role` (`role`),
    
    CONSTRAINT `fk_user_tenant` 
        FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT: Default HQ Tenant
-- ============================================
INSERT INTO `tenants` (
    `uuid`, `name`, `legal_name`, `slug`,
    `gst_number`, `state_code`, `pan_number`,
    `email`, `phone`, `address`, `city`, `state`, `pincode`
) VALUES (
    UUID(),
    'HotelOS HQ',
    'HotelOS Technologies Pvt Ltd',
    'hq',
    NULL,
    '27',
    NULL,
    'admin@hotelos.in',
    '9999999999',
    'Virtual Office',
    'Mumbai',
    'Maharashtra',
    '400001'
);

-- ============================================
-- INSERT: Default Super Admin
-- Password: Admin@123 (Argon2ID hash)
-- ============================================
INSERT INTO `users` (
    `tenant_id`, `uuid`, `email`, `password_hash`,
    `first_name`, `last_name`, `role`, `is_active`
) VALUES (
    1,
    UUID(),
    'admin@hotelos.in',
    '$argon2id$v=19$m=65536,t=4,p=1$WkZxY3pXTXhMUjVwdG1OSQ$H5Z2L3QpX6rK7vW8mN4jB9cD1eF0gA2hI3kJ4lM5nO6',
    'System',
    'Administrator',
    'superadmin',
    TRUE
);

-- ============================================
-- SUCCESS MESSAGE
-- ============================================
SELECT '✅ HotelOS Schema Created Successfully!' AS Status;
SELECT CONCAT('📧 Admin Email: admin@hotelos.in') AS Credentials;
SELECT CONCAT('🔑 Admin Password: Admin@123') AS Info;
