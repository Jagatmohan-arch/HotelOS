<?php
// Migration: Add ledger_type to transactions
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/app.php';

use HotelOS\Core\Database;

try {
    $db = Database::getInstance();
    
    // Check if column exists
    $columns = $db->query("SHOW COLUMNS FROM transactions LIKE 'ledger_type'");
    if (empty($columns)) {
        echo "Adding ledger_type column...\n";
        $db->execute("
            ALTER TABLE transactions 
            ADD COLUMN `ledger_type` ENUM('cash_drawer', 'bank', 'ota_receivable', 'credit_ledger') 
            NOT NULL DEFAULT 'cash_drawer' 
            AFTER `type`
        ");
        echo "✅ Column 'ledger_type' added successfully.\n";
    } else {
        echo "ℹ️ Column 'ledger_type' already exists.\n";
    }
    
    // Update existing records based on payment_mode
    echo "Updating existing records...\n";
    
    // Cash -> cash_drawer
    $db->execute("UPDATE transactions SET ledger_type = 'cash_drawer' WHERE payment_mode = 'cash'");
    
    // UPI/Card/Bank -> bank
    $db->execute("UPDATE transactions SET ledger_type = 'bank' WHERE payment_mode IN ('upi', 'card', 'bank_transfer', 'cheque', 'cashfree')");
    
    echo "✅ Existing records updated.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
