-- Tax Exemption Migration
-- Adds tax_exempt flag to bookings table for GST exemption
-- Use cases: Government guests, Exports, Diplomatic, etc.
-- Author: HotelOS Engine
-- Date: 2025-12-29

-- Add tax_exempt column to bookings
ALTER TABLE `bookings` 
ADD COLUMN `tax_exempt` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Tax exemption flag (1=exempt, 0=normal)' 
AFTER `discount_amount`;

-- Add tax_exempt_reason column for compliance
ALTER TABLE `bookings` 
ADD COLUMN `tax_exempt_reason` VARCHAR(100) NULL 
COMMENT 'Reason for tax exemption' 
AFTER `tax_exempt`;

-- Index for reporting
ALTER TABLE `bookings` 
ADD INDEX `idx_bookings_tax_exempt` (`tax_exempt`);
