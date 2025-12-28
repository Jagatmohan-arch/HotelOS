<?php
// Database Setup / Migration Script
// Access via /setup_db.php?key=hotelos_setup_2024

require_once __DIR__ . '/../public/index_shift_append.php'; // For context if needed, but actually we need basic db
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/app.php';

use HotelOS\Core\Database;

$secretKey = 'hotelos_setup_2024';
if (($_GET['key'] ?? '') !== $secretKey) {
    die('Access Denied');
}

echo "<h1>Database Setup</h1>";

try {
    $db = Database::getInstance();
    
    // 1. Create Shifts Table
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
    echo "✅ Checked/Created 'shifts' table.<br>";
    
    // 2. Create Cash Ledger Table
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
    echo "✅ Checked/Created 'cash_ledger' table.<br>";
    
    // 3. Add Missing Columns to Bookings (if needed)
    // check_in_time, check_out_time
    $columns = $db->query("SHOW COLUMNS FROM bookings LIKE 'check_in_time'");
    if (empty($columns)) {
        $db->execute("ALTER TABLE bookings ADD COLUMN `check_in_time` TIME DEFAULT '14:00:00' AFTER `check_out_date`");
        echo "✅ Added 'check_in_time' to bookings.<br>";
    } else {
        echo "ℹ️ 'check_in_time' already exists.<br>";
    }
    
    $columns = $db->query("SHOW COLUMNS FROM bookings LIKE 'check_out_time'");
    if (empty($columns)) {
        $db->execute("ALTER TABLE bookings ADD COLUMN `check_out_time` TIME DEFAULT '11:00:00' AFTER `check_in_time`");
        echo "✅ Added 'check_out_time' to bookings.<br>";
    } else {
        echo "ℹ️ 'check_out_time' already exists.<br>";
    }

    echo "<h3>Setup Complete</h3>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
