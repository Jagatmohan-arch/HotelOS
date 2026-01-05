<?php
/**
 * HotelOS - System Health Check
 * 
 * URL: /scripts/health_check.php
 * Purpose: Quick diagnostic for "Is it down?"
 */

header('Content-Type: application/json');

$status = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => [],
    'version' => '5.0.0-ENT'
];

$hasError = false;

// 1. Load Dependencies & Environment
try {
    // Determine root directory
    $rootDir = realpath(__DIR__ . '/..');
    
    // Autoload (for Dotenv etc.)
    if (file_exists($rootDir . '/vendor/autoload.php')) {
        require_once $rootDir . '/vendor/autoload.php';
        
        // Load .env if Dotenv exists and .env file exists
        if (class_exists('Dotenv\Dotenv') && file_exists($rootDir . '/.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($rootDir);
            $dotenv->safeLoad();
        }
    }

    // Manual include of core files if autoloader misses them or custom structure
    // Order Matters: Database uses TenantContext
    $coreFiles = [
        '/core/TenantContext.php', 
        '/core/Database.php'
    ];
    
    foreach ($coreFiles as $file) {
        if (file_exists($rootDir . $file)) {
            require_once $rootDir . $file;
        } else {
            throw new Exception("Core file missing: $file");
        }
    }

    // Connect to DB
    if (!class_exists('\HotelOS\Core\Database')) {
        throw new Exception("Database class not loaded");
    }

    $db = \HotelOS\Core\Database::getInstance();
    
    // Test Query (use enforceTenant: false to avoid TenantContext checks if context not set)
    // Actually, simple SELECT 1 doesn't invoke table logic, but "injectTenantFilter" runs on ALL queries if active.
    // TenantContext is inactive by default (null), so injectTenantFilter won't run.
    $db->query("SELECT 1", [], false); 
    
    $status['checks']['database'] = 'connected';
} catch (Exception $e) {
    $status['checks']['database'] = 'error: ' . $e->getMessage();
    $hasError = true;
    
    // Debug info (optional, remove in strict prod)
    $status['checks']['db_debug'] = [
        'host' => getenv('DB_HOST') ? 'set' : 'missing',
        'db'   => getenv('DB_NAME') ? 'set' : 'missing'
    ];
}

// 2. Check Disk Space
$freeSpace = disk_free_space(__DIR__);
$totalSpace = disk_total_space(__DIR__);
$status['checks']['disk_free_mb'] = round($freeSpace / 1024 / 1024, 2);
$status['checks']['disk_usage_pct'] = round((1 - ($freeSpace / $totalSpace)) * 100, 1) . '%';

if ($status['checks']['disk_free_mb'] < 100) { // Alert if < 100MB
    $status['checks']['disk_alert'] = 'LOW DISK SPACE';
    $hasError = true;
}

// 3. Check Write Permissions
$storagePath = __DIR__ . '/../storage';
if (!is_dir($storagePath)) {
    @mkdir($storagePath, 0755, true);
}

if (is_writable($storagePath)) {
    $status['checks']['storage_writable'] = true;
} else {
    $status['checks']['storage_writable'] = false;
    $hasError = true;
}

// 4. Check PHP Version
$status['checks']['php_version'] = phpversion();
if (version_compare(phpversion(), '7.4.0', '<')) {
    $status['checks']['php_alert'] = 'Upgrade recommended (Use PHP 8.0+)';
}

// Final Status
if ($hasError) {
    $status['status'] = 'error';
    http_response_code(500);
}

echo json_encode($status, JSON_PRETTY_PRINT);
