<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/TenantContext.php';

use HotelOS\Core\Database;

echo "Running Phase 4 Migration (Chains)...\n";

try {
    $db = Database::getInstance();
    $sql = file_get_contents(__DIR__ . '/../database/migrations/phase4_chains.sql');
    
    // Split by semicolon to execute one by one if needed, or just exec raw if PDO supports multi requests
    // PDO::exec works better with single statements, but let's try raw exec
    // NOTE: This simple split might break if semicolons are in strings, but our migration is simple.
    $statements = explode(';', $sql);
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            $db->getPdo()->exec($stmt);
            echo "Executed: " . substr($stmt, 0, 50) . "...\n";
        }
    }
    
    echo "Migration Complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
