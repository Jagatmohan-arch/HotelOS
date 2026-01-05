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

// 1. Check Database
try {
    require_once __DIR__ . '/../core/Database.php';
    $db = \HotelOS\Core\Database::getInstance();
    $db->query("SELECT 1");
    $status['checks']['database'] = 'connected';
} catch (Exception $e) {
    $status['checks']['database'] = 'error: ' . $e->getMessage();
    $hasError = true;
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
