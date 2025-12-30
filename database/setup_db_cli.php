<?php
// Database Setup / Migration Script (CLI Only)
// Usage: php database/setup_db_cli.php

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/app.php';

use HotelOS\Core\Database;

echo "Starting Database Setup...\n";

try {
    $db = Database::getInstance();
    
    // 1. Create Shifts Table
    echo "Checking 'shifts' table... ";
    $db->execute("
        CREATE TABLE IF NOT EXISTS `shifts` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `tenant_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `shift_start_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `shift_end_at` TIMESTAMP NULL,
            `opening_cash` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `closing_cash` DECIMAL(10,2) NULL,
            `system_expected_cash` DECIMAL(10,2) NULL,
            `variance_amount` DECIMAL(10,2) NULL,
            `handover_to_user_id` INT UNSIGNED NULL,
            `notes` TEXT NULL,
            `verified_by` INT UNSIGNED NULL,
            `verified_at` TIMESTAMP NULL,
            `manager_note` VARCHAR(255) NULL,
            `status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_shifts_tenant` (`tenant_id`),
            INDEX `idx_shifts_user` (`user_id`),
            INDEX `idx_shifts_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ Done.\n";
    
    // 2. Create Cash Ledger Table
    echo "Checking 'cash_ledger' table... ";
    $db->execute("
        CREATE TABLE IF NOT EXISTS `cash_ledger` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `tenant_id` INT UNSIGNED NOT NULL,
            `shift_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `type` ENUM('expense', 'addition') NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `category` VARCHAR(50) NOT NULL,
            `description` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_ledger_tenant` (`tenant_id`),
            INDEX `idx_ledger_shift` (`shift_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ Done.\n";
    
    // 3. Add Missing Columns to Bookings (if needed)
    echo "Checking 'bookings' schema... \n";
    $columns = $db->query("SHOW COLUMNS FROM bookings LIKE 'check_in_time'");
    if (empty($columns)) {
        $db->execute("ALTER TABLE bookings ADD COLUMN `check_in_time` TIME DEFAULT '14:00:00' AFTER `check_out_date`");
        echo "  - Added 'check_in_time'.\n";
    }
    
    $columns = $db->query("SHOW COLUMNS FROM bookings LIKE 'check_out_time'");
    if (empty($columns)) {
        $db->execute("ALTER TABLE bookings ADD COLUMN `check_out_time` TIME DEFAULT '11:00:00' AFTER `check_in_time`");
        echo "  - Added 'check_out_time'.\n";
    }
    echo "✅ Done.\n";

    echo "\nSetup Complete Successfully.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
