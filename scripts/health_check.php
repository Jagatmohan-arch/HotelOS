<?php
/**
 * HotelOS - System Health Check (Crash-Proof Version)
 * 
 * URL: /scripts/health_check.php
 */

// 1. Defend against Fatal Errors (Parse/Compile)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'critical_error', 'error' => $error['message'], 'file' => $error['file'], 'line' => $error['line']]);
        exit;
    }
});

// 2. Settings
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json');

$status = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => [],
    'version' => '5.0.0-ENT'
];

$rootDir = dirname(__DIR__);

// 3. Check Dependencies (Soft Fail)
try {
    if (file_exists($rootDir . '/vendor/autoload.php')) {
        require_once $rootDir . '/vendor/autoload.php';
        $status['checks']['vendor'] = 'loaded';
    } else {
        $status['checks']['vendor'] = 'missing';
    }
} catch (Throwable $e) {
    $status['checks']['vendor'] = 'error: ' . $e->getMessage();
}

// 4. Load Env (Soft Fail)
try {
    if (class_exists('Dotenv\Dotenv') && file_exists($rootDir . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($rootDir);
        $dotenv->safeLoad();
        $status['checks']['env'] = 'loaded';
    }
} catch (Throwable $e) {
    $status['checks']['env'] = 'error: ' . $e->getMessage();
}

// 5. Database Connection (Try/Catch)
try {
    if (file_exists($rootDir . '/core/Database.php')) {
        require_once $rootDir . '/core/Database.php';
        if (file_exists($rootDir . '/core/TenantContext.php')) {
             require_once $rootDir . '/core/TenantContext.php';
        }
        
        if (class_exists('\HotelOS\Core\Database')) {
            $db = \HotelOS\Core\Database::getInstance();
            $db->query("SELECT 1", [], false);
            $status['checks']['database'] = 'connected';
        } else {
            $status['checks']['database'] = 'class_missing';
        }
    } else {
         $status['checks']['database'] = 'file_missing';
    }
} catch (Throwable $e) {
    $status['checks']['database'] = 'error: ' . $e->getMessage();
    // 500 only if DB specifically fails, as that's critical
    http_response_code(500);
    $status['status'] = 'db_error';
}

// 6. Disk Space
$free = @disk_free_space(__DIR__);
$total = @disk_total_space(__DIR__);
if ($free && $total) {
    $status['checks']['disk_free_mb'] = round($free / 1024 / 1024, 2);
}

echo json_encode($status, JSON_PRETTY_PRINT);
