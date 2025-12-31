-- ============================================
-- FIX 1: Create police_reports Table
-- For C-Form / Police Report Feature
-- ============================================

CREATE TABLE IF NOT EXISTS `police_reports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED NOT NULL,
  `guest_id` INT UNSIGNED NOT NULL,
  `report_number` VARCHAR(50) NULL COMMENT 'C-Form number if assigned',
  `report_date` DATE NOT NULL COMMENT 'Date of report submission',
  `arrival_date` DATE NOT NULL,
  `departure_date` DATE NOT NULL,
  `coming_from` VARCHAR(255) NULL COMMENT 'City/Place guest came from',
  `going_to` VARCHAR(255) NULL COMMENT 'Next destination',
  `purpose_of_visit` VARCHAR(255) NULL COMMENT 'Business, Tourism, etc',
  `vehicle_number` VARCHAR(50) NULL COMMENT 'If guest arrived by vehicle',
  `remarks` TEXT NULL,
  `submitted_to_police` TINYINT(1) NOT NULL DEFAULT 0,
  `submitted_at` TIMESTAMP NULL,
  `submitted_by` INT UNSIGNED NULL COMMENT 'User ID who submitted',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_tenant` (`tenant_id`),
  INDEX `idx_booking` (`booking_id`),
  INDEX `idx_guest` (`guest_id`),
  INDEX `idx_report_date` (`report_date`),
  INDEX `idx_submitted` (`submitted_to_police`),
  
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`guest_id`) REFERENCES `guests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Police C-Form reports for guest tracking (government compliance)';

-- Insert sample data for existing bookings (optional)
-- This helps populate reports for guests already checked in
INSERT IGNORE INTO `police_reports` 
  (`tenant_id`, `booking_id`, `guest_id`, `report_date`, `arrival_date`, `departure_date`, `purpose_of_visit`)
SELECT 
  b.tenant_id,
  b.id,
  b.guest_id,
  b.check_in_date,
  b.check_in_date,
  b.check_out_date,
  'Not specified'
FROM `bookings` b
WHERE b.status IN ('checked_in', 'checked_out')
  AND b.check_in_date IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `police_reports` pr WHERE pr.booking_id = b.id
  );

SELECT '✅ police_reports table created successfully' AS Status;
