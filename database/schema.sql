-- ============================================
-- HotelOS Complete Database Schema v3.0
-- Production-Ready Complete Hotel Engine
-- Database: uplfveim_hotelos
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABLE: tenants (Hotel Properties)
-- Multi-tenant core table
-- ============================================
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    
    -- Basic Info
    `name` VARCHAR(255) NOT NULL COMMENT 'Hotel Display Name',
    `legal_name` VARCHAR(255) NULL COMMENT 'Legal entity for GST invoices',
    `slug` VARCHAR(100) NOT NULL COMMENT 'URL-safe identifier',
    
    -- Indian GST Compliance (MANDATORY for billing)
    `gst_number` VARCHAR(15) NULL COMMENT 'GSTIN - 15 characters',
    `state_code` CHAR(2) NOT NULL DEFAULT '27' COMMENT 'GST State Code (27=MH)',
    `pan_number` VARCHAR(10) NULL COMMENT 'PAN for tax',
    
    -- Contact Information
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(15) NOT NULL,
    `alt_phone` VARCHAR(15) NULL,
    `website` VARCHAR(255) NULL,
    
    -- Address (Required for invoices)
    `address_line1` VARCHAR(255) NOT NULL DEFAULT 'Address',
    `address_line2` VARCHAR(255) NULL,
    `city` VARCHAR(100) NOT NULL DEFAULT 'Mumbai',
    `state` VARCHAR(100) NOT NULL DEFAULT 'Maharashtra',
    `pincode` VARCHAR(6) NOT NULL DEFAULT '400001',
    `country` VARCHAR(50) DEFAULT 'India',
    
    -- Hotel Configuration
    `timezone` VARCHAR(50) DEFAULT 'Asia/Kolkata',
    `currency` CHAR(3) DEFAULT 'INR',
    `check_in_time` TIME DEFAULT '14:00:00',
    `check_out_time` TIME DEFAULT '11:00:00',
    `date_format` VARCHAR(20) DEFAULT 'd/m/Y',
    
    -- Subscription & Status
    `plan` ENUM('trial', 'starter', 'professional', 'enterprise') DEFAULT 'trial',
    `status` ENUM('active', 'suspended', 'cancelled') DEFAULT 'active',
    `trial_ends_at` DATE NULL,
    `subscription_ends_at` DATE NULL,
    
    -- Settings (JSON for flexibility)
    `settings` JSON NULL COMMENT 'Custom hotel settings',
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tenant_uuid` (`uuid`),
    UNIQUE KEY `uk_tenant_slug` (`slug`),
    INDEX `idx_tenant_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: users (Staff & Admin Accounts)
-- RBAC with security features
-- ============================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `uuid` CHAR(36) NOT NULL,
    
    -- Authentication
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Argon2ID hash',
    `phone` VARCHAR(15) NULL,
    
    -- Profile
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `avatar_url` VARCHAR(500) NULL,
    
    -- Role-Based Access Control
    `role` ENUM('superadmin', 'owner', 'manager', 'accountant', 'reception', 'housekeeping') NOT NULL DEFAULT 'reception',
    
    -- Status & Security
    `is_active` BOOLEAN DEFAULT TRUE,
    `email_verified_at` TIMESTAMP NULL,
    `last_login_at` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `failed_attempts` TINYINT UNSIGNED DEFAULT 0,
    `locked_until` TIMESTAMP NULL,
    
    -- Password Reset
    `reset_token` VARCHAR(64) NULL,
    `reset_token_expires_at` TIMESTAMP NULL,
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_uuid` (`uuid`),
    UNIQUE KEY `uk_user_email` (`email`),
    INDEX `idx_user_tenant` (`tenant_id`),
    INDEX `idx_user_role` (`role`),
    INDEX `idx_user_active` (`is_active`),
    
    CONSTRAINT `fk_user_tenant` 
        FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: room_types (Category Master)
-- With auto GST slab calculation
-- ============================================
DROP TABLE IF EXISTS `room_types`;
CREATE TABLE `room_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    
    `name` VARCHAR(100) NOT NULL COMMENT 'e.g., Deluxe, Suite',
    `code` VARCHAR(10) NOT NULL COMMENT 'Short code: DLX, STE',
    `description` TEXT NULL,
    
    -- Pricing (INR)
    `base_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Rack rate per night',
    `extra_adult_rate` DECIMAL(10,2) DEFAULT 0.00,
    `extra_child_rate` DECIMAL(10,2) DEFAULT 0.00,
    
    -- GST Auto-Calculation: <7500 = 12% | >=7500 = 18%
    `gst_rate` DECIMAL(4,2) GENERATED ALWAYS AS (
        CASE WHEN base_rate < 7500.00 THEN 12.00 ELSE 18.00 END
    ) STORED,
    
    -- Capacity
    `base_adults` TINYINT UNSIGNED DEFAULT 2,
    `base_children` TINYINT UNSIGNED DEFAULT 0,
    `max_adults` TINYINT UNSIGNED DEFAULT 3,
    `max_children` TINYINT UNSIGNED DEFAULT 2,
    `max_occupancy` TINYINT UNSIGNED DEFAULT 4,
    
    -- Amenities (JSON array for flexibility)
    `amenities` JSON NULL COMMENT '["wifi", "ac", "tv", "minibar"]',
    `images` JSON NULL COMMENT 'Array of image URLs',
    
    -- Display
    `sort_order` SMALLINT UNSIGNED DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roomtype_tenant_code` (`tenant_id`, `code`),
    INDEX `idx_roomtype_active` (`is_active`),
    
    CONSTRAINT `fk_roomtype_tenant` 
        FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: rooms (Physical Inventory)
-- ============================================
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `room_type_id` INT UNSIGNED NOT NULL,
    
    `room_number` VARCHAR(10) NOT NULL COMMENT 'e.g., 101, A-201',
    `floor` VARCHAR(20) NULL COMMENT 'Ground, 1st, 2nd',
    `building` VARCHAR(50) NULL COMMENT 'For multi-building properties',
    
    -- Room Status
    `status` ENUM('available', 'occupied', 'reserved', 'maintenance', 'blocked') DEFAULT 'available',
    `housekeeping_status` ENUM('clean', 'dirty', 'inspected', 'out_of_order') DEFAULT 'clean',
    
    -- Notes
    `notes` TEXT NULL COMMENT 'Internal staff notes',
    
    -- Display
    `sort_order` SMALLINT UNSIGNED DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_tenant_number` (`tenant_id`, `room_number`),
    INDEX `idx_room_status` (`status`),
    INDEX `idx_room_housekeeping` (`housekeeping_status`),
    
    CONSTRAINT `fk_room_tenant` 
        FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) 
        ON DELETE CASCADE,
    CONSTRAINT `fk_room_type` 
        FOREIGN KEY (`room_type_id`) 
        REFERENCES `room_types`(`id`) 
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: guests (Customer Database)
-- With Indian ID compliance
-- ============================================
DROP TABLE IF EXISTS `guests`;
CREATE TABLE `guests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `uuid` CHAR(36) NOT NULL,
    
    -- Basic Info
    `title` ENUM('Mr', 'Mrs', 'Ms', 'Dr', 'Prof') DEFAULT 'Mr',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(15) NOT NULL,
    `alt_phone` VARCHAR(15) NULL,
    `date_of_birth` DATE NULL,
    `gender` ENUM('male', 'female', 'other') NULL,
    `nationality` VARCHAR(50) DEFAULT 'Indian',
    
    -- Indian ID Verification (Police C-Form requirement)
    `id_type` ENUM('aadhaar', 'passport', 'driving_license', 'voter_id', 'pan') NULL,
    `id_number` VARCHAR(50) NULL,
    `id_expiry` DATE NULL COMMENT 'For passport',
    `id_document_url` VARCHAR(500) NULL COMMENT 'Scanned copy',
    
    -- Address
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `pincode` VARCHAR(10) NULL,
    `country` VARCHAR(50) DEFAULT 'India',
    
    -- Company (for corporate bookings)
    `company_name` VARCHAR(255) NULL,
    `company_gst` VARCHAR(15) NULL,
    
    -- Guest Category
    `category` ENUM('regular', 'vip', 'corporate', 'government', 'blacklisted') DEFAULT 'regular',
    `notes` TEXT NULL COMMENT 'Internal notes',
    
    -- Stats
    `total_stays` INT UNSIGNED DEFAULT 0,
    `total_spent` DECIMAL(12,2) DEFAULT 0.00,
    `last_visit_at` TIMESTAMP NULL,
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_guest_uuid` (`uuid`),
    INDEX `idx_guest_tenant` (`tenant_id`),
    INDEX `idx_guest_phone` (`phone`),
    INDEX `idx_guest_email` (`email`),
    INDEX `idx_guest_category` (`category`),
    
    CONSTRAINT `fk_guest_tenant` 
        FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: sessions (Server-side session storage)
-- ============================================
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
    `id` VARCHAR(128) NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `tenant_id` INT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `payload` TEXT NOT NULL,
    `last_activity` INT UNSIGNED NOT NULL,
    
    PRIMARY KEY (`id`),
    INDEX `idx_session_user` (`user_id`),
    INDEX `idx_session_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: audit_logs (Ghost Logs for Owners)
-- Track all changes for accountability
-- ============================================
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    
    `action` VARCHAR(50) NOT NULL COMMENT 'create, update, delete, login, logout',
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'booking, invoice, room, user',
    `entity_id` INT UNSIGNED NULL,
    
    `old_values` JSON NULL COMMENT 'Before change',
    `new_values` JSON NULL COMMENT 'After change',
    `description` VARCHAR(500) NULL,
    
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_audit_tenant` (`tenant_id`),
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: settings (Key-Value Store)
-- Flexible configuration storage
-- ============================================
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    `type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_setting_tenant_key` (`tenant_id`, `key`),
    
    CONSTRAINT `fk_setting_tenant` 
        FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: bookings (The Core Business Engine)
-- Tracks Reservations, Check-in, Check-out
-- ============================================
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `uuid` CHAR(36) NOT NULL,
    `booking_number` VARCHAR(20) NOT NULL COMMENT 'Unique ID: BK-240101-001',
    
    -- Guest & Room Assignment
    `guest_id` INT UNSIGNED NOT NULL,
    `room_id` INT UNSIGNED NULL COMMENT 'Null if unassigned/future booking',
    `room_type_id` INT UNSIGNED NOT NULL COMMENT 'Requested room type',
    
    -- Dates & Times
    `check_in_date` DATE NOT NULL,
    `check_out_date` DATE NOT NULL,
    `check_in_time` TIME DEFAULT '14:00:00',
    `check_out_time` TIME DEFAULT '11:00:00',
    `actual_check_in` DATETIME NULL COMMENT 'When guest actually checked in',
    `actual_check_out` DATETIME NULL COMMENT 'When guest actually checked out',
    `nights` SMALLINT UNSIGNED GENERATED ALWAYS AS (DATEDIFF(check_out_date, check_in_date)) STORED,
    
    -- Occupancy
    `adults` TINYINT UNSIGNED DEFAULT 1,
    `children` TINYINT UNSIGNED DEFAULT 0,
    `extra_beds` TINYINT UNSIGNED DEFAULT 0,
    
    -- Pricing (Snapshot at booking time)
    `rate_per_night` DECIMAL(10,2) NOT NULL COMMENT 'Agreed rate',
    `room_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `extra_charges` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Minibar, laundry, etc',
    `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
    `discount_reason` VARCHAR(100) NULL,
    
    -- Tax (GST) - Calculated at checkout
    `taxable_amount` DECIMAL(12,2) DEFAULT 0.00,
    `gst_rate` DECIMAL(4,2) DEFAULT 0.00 COMMENT '12 or 18',
    `cgst_amount` DECIMAL(10,2) DEFAULT 0.00,
    `sgst_amount` DECIMAL(10,2) DEFAULT 0.00,
    `igst_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'For inter-state guests',
    `tax_total` DECIMAL(10,2) DEFAULT 0.00,
    
    -- Final Amounts
    `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
    `balance_amount` DECIMAL(12,2) GENERATED ALWAYS AS (grand_total - paid_amount) STORED,
    `payment_status` ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
    
    -- Booking Status
    `status` ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',
    `source` ENUM('walk_in', 'phone', 'website', 'booking_com', 'agoda', 'goibibo', 'makemytrip', 'other') DEFAULT 'walk_in',
    `source_reference` VARCHAR(100) NULL COMMENT 'OTA booking ID',
    
    -- Special Requests
    `special_requests` TEXT NULL,
    `internal_notes` TEXT NULL COMMENT 'Staff notes',
    
    -- Tracking
    `created_by` INT UNSIGNED NULL COMMENT 'User who created',
    `confirmed_by` INT UNSIGNED NULL,
    `cancelled_by` INT UNSIGNED NULL,
    `cancellation_reason` VARCHAR(255) NULL,
    `cancelled_at` TIMESTAMP NULL,
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_booking_uuid` (`uuid`),
    UNIQUE KEY `uk_booking_number` (`tenant_id`, `booking_number`),
    INDEX `idx_booking_tenant` (`tenant_id`),
    INDEX `idx_booking_guest` (`guest_id`),
    INDEX `idx_booking_room` (`room_id`),
    INDEX `idx_booking_dates` (`check_in_date`, `check_out_date`),
    INDEX `idx_booking_status` (`status`),
    INDEX `idx_booking_payment` (`payment_status`),
    
    CONSTRAINT `fk_booking_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_booking_guest` FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_booking_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_booking_roomtype` FOREIGN KEY (`room_type_id`) REFERENCES `room_types`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: transactions (Money Trail)
-- Records every rupee IN or OUT
-- ============================================
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `uuid` CHAR(36) NOT NULL,
    `transaction_number` VARCHAR(20) NOT NULL COMMENT 'TXN-240101-001',
    
    -- Link to Booking (optional for other transactions)
    `booking_id` INT UNSIGNED NULL,
    `invoice_id` INT UNSIGNED NULL,
    
    -- Amount
    `amount` DECIMAL(12,2) NOT NULL,
    `type` ENUM('credit', 'debit') NOT NULL COMMENT 'Credit=Income, Debit=Refund/Expense',
    `category` ENUM('room_payment', 'advance', 'security_deposit', 'extra_charges', 'refund', 'adjustment') DEFAULT 'room_payment',
    
    -- Payment Method
    `payment_mode` ENUM('cash', 'card', 'upi', 'bank_transfer', 'cheque', 'wallet', 'online') NOT NULL,
    `reference_number` VARCHAR(100) NULL COMMENT 'UPI Ref / Card Auth / Cheque No',
    `bank_name` VARCHAR(100) NULL,
    
    -- Tracking
    `collected_by` INT UNSIGNED NOT NULL COMMENT 'Staff User ID',
    `collected_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `receipt_number` VARCHAR(50) NULL,
    `notes` VARCHAR(500) NULL,
    
    -- Reconciliation
    `is_reconciled` BOOLEAN DEFAULT FALSE,
    `reconciled_at` TIMESTAMP NULL,
    `reconciled_by` INT UNSIGNED NULL,
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_txn_uuid` (`uuid`),
    UNIQUE KEY `uk_txn_number` (`tenant_id`, `transaction_number`),
    INDEX `idx_txn_tenant` (`tenant_id`),
    INDEX `idx_txn_booking` (`booking_id`),
    INDEX `idx_txn_date` (`collected_at`),
    INDEX `idx_txn_type` (`type`),
    INDEX `idx_txn_mode` (`payment_mode`),
    
    CONSTRAINT `fk_txn_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_txn_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_txn_collector` FOREIGN KEY (`collected_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: invoices (GST Tax Invoices)
-- Immutable legal documents
-- ============================================
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `uuid` CHAR(36) NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL COMMENT 'INV/2024-25/0001',
    
    -- Booking Link
    `booking_id` INT UNSIGNED NOT NULL,
    
    -- Invoice Type
    `type` ENUM('tax_invoice', 'proforma', 'credit_note', 'receipt') DEFAULT 'tax_invoice',
    `invoice_date` DATE NOT NULL,
    `due_date` DATE NULL,
    
    -- Hotel Info Snapshot (for GST compliance)
    `hotel_name` VARCHAR(255) NOT NULL,
    `hotel_gstin` VARCHAR(15) NULL,
    `hotel_address` TEXT NOT NULL,
    `hotel_state_code` CHAR(2) NOT NULL,
    
    -- Guest Info Snapshot
    `guest_name` VARCHAR(200) NOT NULL,
    `guest_address` TEXT NULL,
    `guest_gstin` VARCHAR(15) NULL COMMENT 'For B2B invoices',
    `guest_state_code` CHAR(2) NULL,
    `guest_phone` VARCHAR(15) NULL,
    `guest_email` VARCHAR(255) NULL,
    
    -- Stay Details
    `room_number` VARCHAR(10) NOT NULL,
    `room_type` VARCHAR(100) NOT NULL,
    `check_in` DATE NOT NULL,
    `check_out` DATE NOT NULL,
    `nights` SMALLINT NOT NULL,
    
    -- Amounts
    `subtotal` DECIMAL(12,2) NOT NULL COMMENT 'Before tax',
    `discount` DECIMAL(10,2) DEFAULT 0.00,
    `taxable_amount` DECIMAL(12,2) NOT NULL,
    
    -- GST Breakdown
    `gst_rate` DECIMAL(4,2) NOT NULL,
    `cgst_rate` DECIMAL(4,2) DEFAULT 0.00,
    `cgst_amount` DECIMAL(10,2) DEFAULT 0.00,
    `sgst_rate` DECIMAL(4,2) DEFAULT 0.00,
    `sgst_amount` DECIMAL(10,2) DEFAULT 0.00,
    `igst_rate` DECIMAL(4,2) DEFAULT 0.00,
    `igst_amount` DECIMAL(10,2) DEFAULT 0.00,
    `total_tax` DECIMAL(10,2) NOT NULL,
    
    -- Final
    `grand_total` DECIMAL(12,2) NOT NULL,
    `amount_in_words` VARCHAR(255) NULL,
    
    -- Payment Status
    `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
    `balance` DECIMAL(12,2) GENERATED ALWAYS AS (grand_total - paid_amount) STORED,
    `status` ENUM('draft', 'issued', 'paid', 'partially_paid', 'cancelled', 'void') DEFAULT 'draft',
    
    -- Document
    `pdf_url` VARCHAR(500) NULL,
    `notes` TEXT NULL,
    `terms` TEXT NULL,
    
    -- Tracking
    `created_by` INT UNSIGNED NULL,
    `cancelled_by` INT UNSIGNED NULL,
    `cancelled_at` TIMESTAMP NULL,
    `cancellation_reason` VARCHAR(255) NULL,
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_inv_uuid` (`uuid`),
    UNIQUE KEY `uk_inv_number` (`tenant_id`, `invoice_number`),
    INDEX `idx_inv_tenant` (`tenant_id`),
    INDEX `idx_inv_booking` (`booking_id`),
    INDEX `idx_inv_date` (`invoice_date`),
    INDEX `idx_inv_status` (`status`),
    
    CONSTRAINT `fk_inv_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: invoice_items (Line Items)
-- Detailed breakdown of charges
-- ============================================
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` INT UNSIGNED NOT NULL,
    
    `description` VARCHAR(255) NOT NULL,
    `hsn_sac` VARCHAR(10) NULL COMMENT 'HSN/SAC code for GST',
    `quantity` DECIMAL(10,2) DEFAULT 1.00,
    `unit` VARCHAR(20) DEFAULT 'Nights',
    `rate` DECIMAL(10,2) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    
    `sort_order` SMALLINT DEFAULT 0,
    
    PRIMARY KEY (`id`),
    INDEX `idx_item_invoice` (`invoice_id`),
    
    CONSTRAINT `fk_item_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Enable Foreign Keys
-- ============================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SEED DATA: Default HQ Tenant + Super Admin
-- ============================================
INSERT INTO `tenants` (
    `uuid`, `name`, `legal_name`, `slug`,
    `state_code`, `email`, `phone`,
    `address_line1`, `city`, `state`, `pincode`,
    `status`, `plan`
) VALUES (
    UUID(),
    'HotelOS HQ',
    'HotelOS Technologies Pvt Ltd',
    'hq',
    '27',
    'admin@hotelos.in',
    '9999999999',
    'Virtual Office',
    'Mumbai',
    'Maharashtra',
    '400001',
    'active',
    'enterprise'
);

-- Admin user with password: Admin@123
-- Hash generated using: password_hash('Admin@123', PASSWORD_BCRYPT)
INSERT INTO `users` (
    `tenant_id`, `uuid`, `email`, `password_hash`,
    `first_name`, `last_name`, `role`, `is_active`, `email_verified_at`
) VALUES (
    1,
    UUID(),
    'admin@hotelos.in',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'System',
    'Administrator',
    'superadmin',
    TRUE,
    NOW()
);

-- ============================================
-- SUCCESS: Schema v3.0 Complete!
-- ============================================
SELECT '✅ HotelOS Database v3.0 - Complete Hotel Engine!' AS Status;
SELECT '📊 Tables: tenants, users, room_types, rooms, guests, bookings, transactions, invoices, invoice_items, sessions, audit_logs, settings' AS Tables;
SELECT '💼 Features: Multi-tenant, GST Billing, Booking Engine, Payment Tracking, Audit Logs' AS Features;
SELECT '👤 Admin: admin@hotelos.in / Admin@123' AS Credentials;
