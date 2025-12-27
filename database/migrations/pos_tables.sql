-- ============================================
-- HotelOS - POS Tables Migration
-- Run this to add POS functionality
-- ============================================

-- TABLE: pos_items (Minibar, Laundry, Services)
CREATE TABLE IF NOT EXISTS `pos_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `category` ENUM('minibar', 'laundry', 'room_service', 'other') DEFAULT 'other',
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NULL,
    `description` VARCHAR(255) NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `gst_rate` DECIMAL(4,2) DEFAULT 18.00,
    `is_active` BOOLEAN DEFAULT TRUE,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_pos_items_tenant` (`tenant_id`),
    INDEX `idx_pos_items_category` (`category`),
    INDEX `idx_pos_items_active` (`is_active`),
    
    CONSTRAINT `fk_pos_items_tenant` FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: pos_charges (Charges linked to bookings)
CREATE TABLE IF NOT EXISTS `pos_charges` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `booking_id` INT UNSIGNED NOT NULL,
    `item_id` INT UNSIGNED NULL,
    `description` VARCHAR(255) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `gst_rate` DECIMAL(4,2) DEFAULT 18.00,
    `charged_by` INT UNSIGNED NOT NULL,
    `charged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes` VARCHAR(500) NULL,
    
    PRIMARY KEY (`id`),
    INDEX `idx_pos_charges_tenant` (`tenant_id`),
    INDEX `idx_pos_charges_booking` (`booking_id`),
    INDEX `idx_pos_charges_date` (`charged_at`),
    
    CONSTRAINT `fk_pos_charges_tenant` FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pos_charges_booking` FOREIGN KEY (`booking_id`) 
        REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pos_charges_item` FOREIGN KEY (`item_id`) 
        REFERENCES `pos_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample POS items
INSERT INTO `pos_items` (`tenant_id`, `category`, `name`, `code`, `price`, `gst_rate`) VALUES
(1, 'minibar', 'Mineral Water (1L)', 'WATER', 50.00, 18.00),
(1, 'minibar', 'Soft Drink (Can)', 'SODA', 80.00, 18.00),
(1, 'minibar', 'Chips Packet', 'CHIPS', 60.00, 18.00),
(1, 'minibar', 'Chocolate Bar', 'CHOCO', 100.00, 18.00),
(1, 'laundry', 'Shirt - Wash & Iron', 'SHIRT', 150.00, 18.00),
(1, 'laundry', 'Trouser - Wash & Iron', 'TRSR', 150.00, 18.00),
(1, 'laundry', 'Suit - Dry Clean', 'SUIT', 500.00, 18.00),
(1, 'room_service', 'Extra Pillow', 'PILLOW', 0.00, 0.00),
(1, 'room_service', 'Extra Blanket', 'BLANKET', 0.00, 0.00),
(1, 'room_service', 'Late Checkout (Per Hour)', 'LATECO', 500.00, 18.00),
(1, 'other', 'Airport Transfer', 'AIRPORT', 1500.00, 5.00),
(1, 'other', 'Sightseeing Tour', 'TOUR', 2000.00, 5.00);
