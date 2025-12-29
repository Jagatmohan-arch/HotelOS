-- ============================================
-- HotelOS Migration: Refund Requests Table
-- 2-Person Approval Workflow for Refunds
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABLE: refund_requests
-- Staff initiates, Manager approves/rejects
-- ============================================
DROP TABLE IF EXISTS `refund_requests`;
CREATE TABLE `refund_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    
    -- Linked Entities
    `booking_id` INT UNSIGNED NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL COMMENT 'Snapshot of invoice number',
    
    -- Request Details
    `requested_amount` DECIMAL(10,2) NOT NULL,
    `max_refundable` DECIMAL(10,2) NOT NULL COMMENT 'Snapshot of paid_amount at request time',
    `reason_code` ENUM('service_complaint', 'early_checkout', 'booking_cancelled', 'overcharge', 'other') NOT NULL,
    `reason_text` VARCHAR(500) NULL COMMENT 'Additional explanation',
    
    -- Requester (Staff)
    `requested_by` INT UNSIGNED NOT NULL,
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Approval Status
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    
    -- Approver (Manager)
    `approved_by` INT UNSIGNED NULL,
    `approved_at` TIMESTAMP NULL,
    `rejection_note` VARCHAR(255) NULL,
    
    -- Credit Note (Generated on approval)
    `credit_note_number` VARCHAR(50) NULL COMMENT 'CN-YYMMDD-NNN',
    `transaction_id` INT UNSIGNED NULL COMMENT 'Link to transactions table on approval',
    
    -- Meta
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_refund_tenant` (`tenant_id`),
    INDEX `idx_refund_booking` (`booking_id`),
    INDEX `idx_refund_status` (`status`),
    INDEX `idx_refund_requested_by` (`requested_by`),
    
    CONSTRAINT `fk_refund_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_refund_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_refund_requester` FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_refund_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SUCCESS: Refund Requests Table Created
-- ============================================
SELECT '✅ Refund Requests Table - 2-Person Approval Workflow!' AS Status;
