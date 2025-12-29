-- ============================================
-- HotelOS Migration: Room Move History Table
-- Historical tracking for room changes during stay
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABLE: room_move_history
-- Tracks every room change during a booking
-- ============================================
DROP TABLE IF EXISTS `room_move_history`;
CREATE TABLE `room_move_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `booking_id` INT UNSIGNED NOT NULL,
    
    -- Room Change Details
    `from_room_id` INT UNSIGNED NOT NULL,
    `to_room_id` INT UNSIGNED NOT NULL,
    `from_room_number` VARCHAR(20) NOT NULL COMMENT 'Snapshot',
    `to_room_number` VARCHAR(20) NOT NULL COMMENT 'Snapshot',
    
    -- Reason and Notes
    `reason` ENUM('maintenance', 'upgrade', 'downgrade', 'guest_request', 'housekeeping', 'other') NOT NULL,
    `notes` VARCHAR(500) NULL,
    
    -- Rate Handling
    `rate_action` ENUM('keep_original', 'use_new_rate', 'custom') NOT NULL DEFAULT 'keep_original',
    `old_rate` DECIMAL(10,2) NOT NULL,
    `new_rate` DECIMAL(10,2) NOT NULL,
    
    -- Who and When
    `moved_by` INT UNSIGNED NOT NULL,
    `moved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_move_tenant` (`tenant_id`),
    INDEX `idx_move_booking` (`booking_id`),
    INDEX `idx_move_from` (`from_room_id`),
    INDEX `idx_move_to` (`to_room_id`),
    
    CONSTRAINT `fk_move_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_move_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_move_from` FOREIGN KEY (`from_room_id`) REFERENCES `rooms`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_move_to` FOREIGN KEY (`to_room_id`) REFERENCES `rooms`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_move_user` FOREIGN KEY (`moved_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SUCCESS: Room Move History Table Created
-- ============================================
SELECT '✅ Room Move History Table - Historical Tracking!' AS Status;
