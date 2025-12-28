<?php
// Standalone Database Setup Script
// Access via /public/setup_db_standalone.php?key=hotelos_setup_2024

// 1. Define Constants
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');

// 2. Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 3. Security Check
$secretKey = 'hotelos_setup_2024';
if (($_GET['key'] ?? '') !== $secretKey) {
    die('Access Denied');
}

echo "<h1>Standalone Database Setup</h1>";

// 4. Manually Require Dependencies (No Autoloader reliance)
if (!file_exists(CORE_PATH . '/TenantContext.php')) die('Missing TenantContext.php');
require_once CORE_PATH . '/TenantContext.php';

if (!file_exists(CORE_PATH . '/Database.php')) die('Missing Database.php');
require_once CORE_PATH . '/Database.php';

use HotelOS\Core\Database;

try {
    $db = Database::getInstance();
    
    // 5. Create Shifts Table
    echo "Creating 'shifts' table... ";
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
    ", [], false); // false = no tenant enforcement
    echo "✅ Done.<br>";
    
    // 6. Create Cash Ledger Table
    echo "Creating 'cash_ledger' table... ";
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
    ", [], false);
    echo "✅ Done.<br>";
    
    // 7. Add Missing Columns to Bookings
    echo "Checking 'bookings' schema... ";
    
    // check_in_time
    try {
        $columns = $db->query("SHOW COLUMNS FROM bookings LIKE 'check_in_time'", [], false);
        if (empty($columns)) {
            $db->execute("ALTER TABLE bookings ADD COLUMN `check_in_time` TIME DEFAULT '14:00:00' AFTER `check_out_date`", [], false);
            echo "Added check_in_time. ";
        }
    } catch (Exception $e) { echo "(check_in_time error: " . $e->getMessage() . ") "; }
    
    // check_out_time
    try {
        $columns = $db->query("SHOW COLUMNS FROM bookings LIKE 'check_out_time'", [], false);
        if (empty($columns)) {
            $db->execute("ALTER TABLE bookings ADD COLUMN `check_out_time` TIME DEFAULT '11:00:00' AFTER `check_in_time`", [], false);
            echo "Added check_out_time. ";
        }
    } catch (Exception $e) { echo "(check_out_time error: " . $e->getMessage() . ") "; }
    echo "✅ Done.<br>";

    echo "<h3>SETUP COMPLETED SUCCESSFULLY</h3>";
    
} catch (Exception $e) {
    echo "<h1>❌ FATAL ERROR</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
