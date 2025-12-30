-- ==========================================
-- Missing Table: invoice_snapshots
-- For Owner engine invoice modification tracking
-- ==========================================

CREATE TABLE IF NOT EXISTS `invoice_snapshots` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `invoice_id` INT UNSIGNED NULL COMMENT 'NULL if invoice not yet created at snapshot time',
    `booking_id` INT UNSIGNED NOT NULL,
    `snapshot_data` JSON NOT NULL COMMENT 'Complete booking/invoice state before modification',
    `snapshot_reason` VARCHAR(255) NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_snapshot_invoice` (`invoice_id`),
    INDEX `idx_snapshot_booking` (`booking_id`),
    INDEX `idx_snapshot_date` (`created_at`),
    
    CONSTRAINT `fk_snapshot_tenant` 
        FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) 
        ON DELETE CASCADE,
    CONSTRAINT `fk_snapshot_booking` 
        FOREIGN KEY (`booking_id`) 
        REFERENCES `bookings`(`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✅ invoice_snapshots table created' AS Status;
