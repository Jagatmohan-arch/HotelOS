<?php
// migrate_schema.php
// Run this once to setup the database tables

require_once __DIR__ . '/public/index.php'; // Boot app to get Database connection

use HotelOS\Core\Database;

header('Content-Type: text/plain');

try {
    $db = Database::getInstance();
    
    // Read schema file
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Error: schema.sql not found at $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Split into individual statements (simple split by ;)
    // note: this is a basic splitter, might fail on complex bodies but schema.sql is simple
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "Starting Migration...\n";
    echo "Found " . count($statements) . " statements.\n\n";
    
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        
        // Skip comments
        if (str_starts_with($stmt, '--') || str_starts_with($stmt, '/*')) continue;
        
        try {
            $db->execute($stmt);
            // Get first line for log
            $firstLine = strtok($stmt, "\n");
            echo "[SUCCESS] $firstLine...\n";
        } catch (\PDOException $e) {
            // Ignore "Table already exists" errors
            if (str_contains($e->getMessage(), 'already exists')) {
                 echo "[SKIPPED] Table already exists.\n";
            } else {
                 echo "[ERROR] Failed: " . $e->getMessage() . "\n";
                 echo "Statement: $stmt\n\n";
            }
        }
    }
    
    echo "\nMigration Complete.\n";
    echo "Please delete this file immediately.";
    
} catch (Throwable $e) {
    echo "Fatal Error: " . $e->getMessage();
    echo "\nTrace:\n" . $e->getTraceAsString();
}
