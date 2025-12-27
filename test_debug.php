<?php
/**
 * Debug test file - DELETE AFTER USE
 * Tests the login page rendering with full error display
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
    // HotelOS\Core namespace
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
    
    // HotelOS\Handlers namespace
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

echo "<h1>Login Render Test</h1>";
echo "<pre>";
echo "BASE_PATH: " . BASE_PATH . "\n";
echo "VIEWS_PATH: " . VIEWS_PATH . "\n";
echo "CORE_PATH: " . CORE_PATH . "\n\n";

// Test 1: Check Auth class
try {
    echo "=== Loading Auth Class ===\n";
    require_once CORE_PATH . '/Auth.php';
    echo "Auth.php loaded\n";
    
    echo "=== Loading Config ===\n";
    $appConfig = require CONFIG_PATH . '/app.php';
    echo "Config loaded\n";
    
    echo "=== Getting Auth Instance ===\n";
    $auth = \HotelOS\Core\Auth::getInstance();
    echo "Auth instance OK\n";
    
    echo "=== CSRF Token ===\n";
    $csrfToken = $auth->csrfToken();
    echo "CSRF: " . substr($csrfToken, 0, 20) . "...\n";
    
    // Test login.php include
    echo "\n=== Including views/auth/login.php ===\n";
    $error = null;
    $title = 'Login';
    $bodyClass = 'page-login';
    
    ob_start();
    include VIEWS_PATH . '/auth/login.php';
    $content = ob_get_clean();
    echo "login.php included, content length: " . strlen($content) . " bytes\n";
    
    // Test base.php include
    echo "\n=== Including views/layouts/base.php ===\n";
    ob_start();
    include VIEWS_PATH . '/layouts/base.php';
    $output = ob_get_clean();
    echo "base.php included, output length: " . strlen($output) . " bytes\n";
    
    echo "\n=== SUCCESS - All files loaded ===\n";
    echo "</pre>";
    
    // Show actual rendered content
    echo "\n<hr><h2>Rendered Login Page:</h2>\n";
    echo $output;
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " Line " . $e->getLine() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
    echo "</pre>";
}
