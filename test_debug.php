<?php
/**
 * Debug test file - Simulates exact router initialization
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set up base paths (same logic as public/index.php)
$currentDir = __DIR__;
if (is_dir($currentDir . '/config')) {
    define('BASE_PATH', $currentDir);
} else {
    define('BASE_PATH', dirname($currentDir));
}
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', BASE_PATH . '/views');
define('HANDLERS_PATH', BASE_PATH . '/handlers');
define('LOGS_PATH', BASE_PATH . '/logs');

// AUTOLOADER - Same as public/index.php
spl_autoload_register(function (string $class): void {
    $corePrefix = 'HotelOS\\Core\\';
    $coreDir = CORE_PATH . '/';
    
    if (strncmp($corePrefix, $class, strlen($corePrefix)) === 0) {
        $relativeClass = substr($class, strlen($corePrefix));
        $file = $coreDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }
    
    $handlersPrefix = 'HotelOS\\Handlers\\';
    $handlersDir = HANDLERS_PATH . '/';
    
    if (strncmp($handlersPrefix, $class, strlen($handlersPrefix)) === 0) {
        $relativeClass = substr($class, strlen($handlersPrefix));
        $file = $handlersDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }
});

use HotelOS\Core\Auth;

echo "<h1>Router Debug Test</h1>";
echo "<pre>";

// Check session
echo "=== SESSION STATUS ===\n";
echo "Session Status: " . session_status() . " (0=disabled, 1=none, 2=active)\n";

// Start session like router does
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "After session_start: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";

// Now try Auth::getInstance() which is where router crashes
echo "\n=== AUTH INITIALIZATION ===\n";
try {
    $auth = Auth::getInstance();
    echo "Auth::getInstance() SUCCESS\n";
    echo "User logged in: " . ($auth->check() ? 'YES' : 'NO') . "\n";
    
    if ($auth->check()) {
        echo "User: " . print_r($auth->user(), true) . "\n";
    }
} catch (Throwable $e) {
    echo "Auth::getInstance() FAILED\n";
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " Line " . $e->getLine() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DONE ===\n";
echo "</pre>";
