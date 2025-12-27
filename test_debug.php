<?php
/**
 * Debug test file - DELETE AFTER USE
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>HotelOS Debug</h1>";
echo "<pre>";

// Check paths
echo "=== PATHS ===\n";
echo "__DIR__ = " . __DIR__ . "\n";
echo "dirname(__DIR__) = " . dirname(__DIR__) . "\n";

// Include public/index.php paths
$publicIndex = __DIR__ . '/public/index.php';
echo "public/index.php exists: " . (file_exists($publicIndex) ? 'YES' : 'NO') . "\n";

// Check config
$configFile = __DIR__ . '/config/app.php';
echo "config/app.php exists: " . (file_exists($configFile) ? 'YES' : 'NO') . "\n";

// Check core
$coreDir = __DIR__ . '/core';
echo "core/ directory exists: " . (is_dir($coreDir) ? 'YES' : 'NO') . "\n";

// Try to load config
echo "\n=== CONFIG TEST ===\n";
try {
    $config = require $configFile;
    echo "Config loaded: YES\n";
    echo "App Name: " . $config['name'] . "\n";
    echo "Debug Mode: " . ($config['debug'] ? 'true' : 'false') . "\n";
} catch (Throwable $e) {
    echo "Config Error: " . $e->getMessage() . "\n";
}

// Try to load Database class
echo "\n=== CORE CLASSES ===\n";
$dbClass = __DIR__ . '/core/Database.php';
echo "Database.php exists: " . (file_exists($dbClass) ? 'YES' : 'NO') . "\n";

try {
    require_once $dbClass;
    echo "Database.php loaded: YES\n";
} catch (Throwable $e) {
    echo "Database.php Error: " . $e->getMessage() . "\n";
}

// Try Auth class
$authClass = __DIR__ . '/core/Auth.php';
echo "Auth.php exists: " . (file_exists($authClass) ? 'YES' : 'NO') . "\n";

try {
    require_once $authClass;
    echo "Auth.php loaded: YES\n";
} catch (Throwable $e) {
    echo "Auth.php Error: " . $e->getMessage() . "\n";
}

// Request info
echo "\n=== REQUEST INFO ===\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "Parsed Path: " . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) . "\n";

echo "\n=== DONE ===\n";
echo "</pre>";
