<?php
/**
 * HotelOS - Auto Migrator
 * 
 * URL: /scripts/auto_migrate.php?key=YOUR_APP_KEY
 * Purpose: Quickly apply pending SQL migrations (Triggers) to Production
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/TenantContext.php';

use HotelOS\Core\Database;

// Security Check
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$appKey = getenv('APP_KEY');
if (empty($_GET['key']) || $_GET['key'] !== $appKey) {
    http_response_code(403);
    die("Access Denied: Invalid Key");
}

$db = Database::getInstance();
$migrationsDir = __DIR__ . '/../database/migrations';

$files = [
    'shift_immutability_trigger.sql',
    'phase4_compliance.sql'
];

echo "<h1>Applying Migrations...</h1>";

foreach ($files as $file) {
    $path = $migrationsDir . '/' . $file;
    if (!file_exists($path)) {
        echo "<p style='color:red'>Missing: $file</p>";
        continue;
    }

    $sql = file_get_contents($path);
    if (!$sql) continue;

    echo "<p>Processing <strong>$file</strong>...</p>";

    // Split by delimiter if present, else just execute
    // Simple logic for triggers (DELIMITER $$)
    // NOTE: PHP PDO doesn't support DELIMITER syntax directly usually, 
    // but we can try to clean it or exec raw.
    // For Triggers, it's safer to strip DELIMITER lines and run the CREATE TRIGGER statement.

    // Very basic 'DELIMITER' cleaner
    $sql = preg_replace('/^DELIMITER \$\$/m', '', $sql);
    $sql = preg_replace('/^DELIMITER ;/m', '', $sql);
    $sql = str_replace('$$', ';', $sql); // Replace custom delimiter with standard

    try {
        $db->execute($sql, [], false); // No tenant enforcement for schema
        echo "<p style='color:green'>✅ Success</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange'>⚠️ Info: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>Done.";
