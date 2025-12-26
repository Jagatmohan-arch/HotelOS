<?php
/**
 * HotelOS - Front Controller & Login Entry Point
 * 
 * This is the main entry point for the application.
 * Handles login display and authentication routing.
 */

declare(strict_types=1);

// Error handling - Never expose errors in production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', BASE_PATH . '/views');
define('CACHE_PATH', BASE_PATH . '/cache');
define('LOGS_PATH', BASE_PATH . '/logs');

// Autoload core classes (simple autoloader without Composer)
spl_autoload_register(function (string $class): void {
    // Convert namespace to path
    $prefix = 'HotelOS\\Core\\';
    $baseDir = CORE_PATH . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
$appConfig = require CONFIG_PATH . '/app.php';

// Set timezone
date_default_timezone_set($appConfig['timezone']);

// Initialize error logging
ini_set('error_log', LOGS_PATH . '/php_errors.log');

use HotelOS\Core\Auth;
use HotelOS\Core\Router;
use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

// ============================================
// Request Handling
// ============================================

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove trailing slashes (except for root)
if ($requestUri !== '/' && str_ends_with($requestUri, '/')) {
    $requestUri = rtrim($requestUri, '/');
}

// ============================================
// API Routes (JSON responses)
// ============================================

if (str_starts_with($requestUri, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // POST /api/auth/login - Handle login
        if ($requestUri === '/api/auth/login' && $requestMethod === 'POST') {
            handleLoginApi();
            exit;
        }
        
        // POST /api/auth/logout - Handle logout
        if ($requestUri === '/api/auth/logout' && $requestMethod === 'POST') {
            handleLogoutApi();
            exit;
        }
        
        // 404 for unknown API routes
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
        exit;
        
    } catch (Throwable $e) {
        error_log("API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => $appConfig['debug'] ? $e->getMessage() : 'Internal server error'
        ]);
        exit;
    }
}

// ============================================
// Web Routes (HTML responses)
// ============================================

try {
    $auth = Auth::getInstance();
    
    // Redirect authenticated users to dashboard
    if ($auth->check() && in_array($requestUri, ['/', '/login'])) {
        header('Location: /dashboard');
        exit;
    }
    
    // Route handling
    switch ($requestUri) {
        case '/':
        case '/login':
            renderLoginPage($auth);
            break;
            
        case '/dashboard':
            if (!$auth->check()) {
                header('Location: /');
                exit;
            }
            renderDashboard($auth);
            break;
            
        case '/logout':
            $auth->logout();
            header('Location: /?logged_out=1');
            exit;
            
        default:
            // 404 Page
            http_response_code(404);
            renderErrorPage(404, 'Page Not Found');
            break;
    }
    
} catch (Throwable $e) {
    error_log("Application Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    if ($appConfig['debug']) {
        echo '<h1>Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        renderErrorPage(500, 'Something went wrong');
    }
}

// ============================================
// API Handlers
// ============================================

function handleLoginApi(): void
{
    // Get JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request body']);
        return;
    }
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required'
        ]);
        return;
    }
    
    // Attempt authentication
    $auth = Auth::getInstance();
    $result = $auth->attempt($email, $password);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => '/dashboard',
            'user' => [
                'name' => $result['user']['first_name'] . ' ' . $result['user']['last_name'],
                'email' => $result['user']['email'],
                'role' => $result['user']['role'],
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
}

function handleLogoutApi(): void
{
    $auth = Auth::getInstance();
    $auth->logout();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully',
        'redirect' => '/'
    ]);
}

// ============================================
// View Renderers
// ============================================

function renderLoginPage(Auth $auth): void
{
    $error = null;
    
    // Check for logout message
    if (isset($_GET['logged_out'])) {
        // Could show a success message
    }
    
    // Check for error from form submission (non-JS fallback)
    if (isset($_GET['error'])) {
        $error = match($_GET['error']) {
            'invalid' => 'Invalid email or password.',
            'locked' => 'Your account is temporarily locked.',
            'inactive' => 'Your account has been deactivated.',
            default => 'An error occurred. Please try again.'
        };
    }
    
    // Generate CSRF token
    $csrfToken = $auth->csrfToken();
    
    // Render the page
    $title = 'Login';
    $bodyClass = 'page-login';
    
    // Start output buffering for content
    ob_start();
    include VIEWS_PATH . '/auth/login.php';
    $content = ob_get_clean();
    
    // Include base layout
    include VIEWS_PATH . '/layouts/base.php';
}

function renderDashboard(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $title = 'Dashboard';
    $bodyClass = 'page-dashboard';
    
    // Placeholder dashboard content
    ob_start();
    ?>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="glass-card glass-card--glow p-8 max-w-lg w-full text-center animate-fadeIn">
            <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-gradient-to-br from-cyan-400 to-purple-500 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-8 h-8 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">Welcome, <?= htmlspecialchars($user['first_name']) ?>!</h1>
            <p class="text-slate-400 mb-6">You're logged in as <span class="text-cyan-400"><?= htmlspecialchars(ucfirst($user['role'])) ?></span></p>
            
            <div class="bg-slate-800/50 rounded-lg p-4 mb-6 text-left">
                <h3 class="text-sm font-medium text-slate-300 mb-2">Session Info</h3>
                <p class="text-xs text-slate-500">Email: <?= htmlspecialchars($user['email']) ?></p>
                <p class="text-xs text-slate-500">Tenant ID: <?= htmlspecialchars(TenantContext::getId() ?? 'N/A') ?></p>
            </div>
            
            <div class="flex gap-4 justify-center">
                <a href="/logout" class="btn btn--secondary">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    Sign Out
                </a>
            </div>
            
            <p class="mt-8 text-xs text-slate-600">
                🚧 Dashboard UI coming in Phase 2
            </p>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/base.php';
}

function renderErrorPage(int $code, string $message): void
{
    $title = "Error {$code}";
    $bodyClass = 'page-error';
    
    ob_start();
    ?>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="glass-card p-8 max-w-md w-full text-center">
            <div class="text-6xl font-bold text-cyan-400 mb-4"><?= $code ?></div>
            <h1 class="text-xl font-semibold text-white mb-2"><?= htmlspecialchars($message) ?></h1>
            <p class="text-slate-400 mb-6">The page you're looking for doesn't exist.</p>
            <a href="/" class="btn btn--primary">
                <i data-lucide="home" class="w-4 h-4"></i>
                Back to Home
            </a>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    
    $csrfToken = '';
    include VIEWS_PATH . '/layouts/base.php';
}
