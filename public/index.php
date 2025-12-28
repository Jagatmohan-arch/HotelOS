<?php
/**
 * HotelOS - Front Controller
 * 
 * Main entry point - handles all routing for web and API requests
 * Phase 2: Now includes Dashboard, Room Types, and Rooms routes
 */

declare(strict_types=1);

// Error handling - Environment-based debug mode
error_reporting(E_ALL);

// Simple .env Loader for Shared Hosting
if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
            if ($name !== NULL && $value !== NULL) {
                $name = trim($name);
                $value = trim($value);
                if (!getenv($name)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                }
            }
        }
    }
}

// Load .env from root if exists
if (defined('BASE_PATH')) {
    loadEnv(BASE_PATH . '/.env');
} else {
    // Try to guess root
    $guessRoot = dirname(__DIR__); 
    // If running from public/index.php, root is one up. 
    // Be careful with include paths.
    // We will let the define('BASE_PATH') block below handle the path definition first,
    // then call loadEnv.
}


$isDebug = (bool)(getenv('APP_DEBUG') ?: false);
ini_set('display_errors', $isDebug ? '1' : '0');
ini_set('log_errors', '1');

// Define base paths - Works whether accessed directly or via root/index.php
// On shared hosting: document root = project root (not public/)
// On local/proper setup: document root = public/
if (!defined('BASE_PATH')) {
    $currentDir = __DIR__;
    
    // Check if we're in the public subfolder (proper setup)
    if (basename($currentDir) === 'public') {
        define('BASE_PATH', dirname($currentDir));
    } else {
        // We're in the root folder (shared hosting OR included from root/index.php)
        // Check if config folder exists in current dir (we're at project root)
        if (is_dir($currentDir . '/config')) {
            define('BASE_PATH', $currentDir);
        } else {
            // Fallback to parent dir
            define('BASE_PATH', dirname($currentDir));
        }
    }
}

if (!defined('PUBLIC_PATH')) {
    // On shared hosting, public folder might be same as root
    if (is_dir(BASE_PATH . '/public')) {
        define('PUBLIC_PATH', BASE_PATH . '/public');
    } else {
        define('PUBLIC_PATH', BASE_PATH);
    }
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config');
    define('CORE_PATH', BASE_PATH . '/core');
    define('VIEWS_PATH', BASE_PATH . '/views');
    define('HANDLERS_PATH', BASE_PATH . '/handlers');
    define('CACHE_PATH', BASE_PATH . '/cache');
    define('LOGS_PATH', BASE_PATH . '/logs');
}

// Now load .env
loadEnv(BASE_PATH . '/.env');

// Autoload core classes
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

// Load configuration
$appConfig = require CONFIG_PATH . '/app.php';

// Set timezone
date_default_timezone_set($appConfig['timezone']);

// Initialize error logging
ini_set('error_log', LOGS_PATH . '/php_errors.log');

// Phase C: Enable Database Sessions
define('USE_DB_SESSIONS', true);

use HotelOS\Core\Auth;
use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;
use HotelOS\Handlers\DashboardHandler;
use HotelOS\Handlers\RoomTypeHandler;
use HotelOS\Handlers\RoomHandler;

// ============================================
// Request Handling
// ============================================

// Phase F: Load Shift Module Functions
require_once __DIR__ . '/index_shift_append.php';

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
        // POST /api/auth/login
        if ($requestUri === '/api/auth/login' && $requestMethod === 'POST') {
            handleLoginApi();
            exit;
        }
        
        // POST /api/auth/logout
        if ($requestUri === '/api/auth/logout' && $requestMethod === 'POST') {
            handleLogoutApi();
            exit;
        }
        
        // GET /api/dashboard/stats
        if ($requestUri === '/api/dashboard/stats' && $requestMethod === 'GET') {
            requireApiAuth();
            $handler = new DashboardHandler();
            echo json_encode(['success' => true, 'data' => $handler->getStats()]);
            exit;
        }
        
        // GET /api/room-types
        if ($requestUri === '/api/room-types' && $requestMethod === 'GET') {
            requireApiAuth();
            $handler = new RoomTypeHandler();
            echo json_encode(['success' => true, 'data' => $handler->list()]);
            exit;
        }
        
        // GET /api/rooms
        if ($requestUri === '/api/rooms' && $requestMethod === 'GET') {
            requireApiAuth();
            $handler = new RoomHandler();
            echo json_encode(['success' => true, 'data' => $handler->list()]);
            exit;
        }
        
        // ========== Guest APIs (Phase 3) ==========
        
        // GET /api/guests/search?q=phone_or_name - Debounced search
        if ($requestUri === '/api/guests/search' && $requestMethod === 'GET') {
            requireApiAuth();
            $query = $_GET['q'] ?? '';
            $limit = min((int)($_GET['limit'] ?? 5), 10); // Max 10
            
            $handler = new \HotelOS\Handlers\GuestHandler();
            $results = $handler->search($query, $limit);
            echo json_encode(['success' => true, 'data' => $results]);
            exit;
        }
        
        // GET /api/guests/{id}
        if (preg_match('#^/api/guests/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET') {
            requireApiAuth();
            $handler = new \HotelOS\Handlers\GuestHandler();
            $guest = $handler->getById((int)$matches[1]);
            
            if ($guest) {
                echo json_encode(['success' => true, 'data' => $guest]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Guest not found']);
            }
            exit;
        }
        
        // POST /api/guests - Create new guest
        if ($requestUri === '/api/guests' && $requestMethod === 'POST') {
            requireApiAuth();
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $handler = new \HotelOS\Handlers\GuestHandler();
            
            // Validate required fields
            if (empty($data['first_name']) || empty($data['phone'])) {
                http_response_code(400);
                echo json_encode(['error' => 'First name and phone are required']);
                exit;
            }
            
            $guestId = $handler->create($data);
            echo json_encode(['success' => true, 'guest_id' => $guestId]);
            exit;
        }
        
        // ========== Booking APIs (Phase 3) ==========
        
        // GET /api/rooms/available?check_in=X&check_out=Y&room_type_id=Z
        if ($requestUri === '/api/rooms/available' && $requestMethod === 'GET') {
            requireApiAuth();
            
            $checkIn = $_GET['check_in'] ?? date('Y-m-d');
            $checkOut = $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
            $roomTypeId = !empty($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : null;
            
            $handler = new \HotelOS\Handlers\BookingHandler();
            $rooms = $handler->getAvailableRooms($checkIn, $checkOut, $roomTypeId);
            echo json_encode(['success' => true, 'data' => $rooms]);
            exit;
        }
        
        // POST /api/bookings - Create booking
        if ($requestUri === '/api/bookings' && $requestMethod === 'POST') {
            requireApiAuth();
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $data['created_by'] = Auth::getInstance()->user()['id'] ?? null;
            
            $handler = new \HotelOS\Handlers\BookingHandler();
            $result = $handler->create($data);
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            exit;
        }
        
        // GET /api/bookings/today-arrivals
        if ($requestUri === '/api/bookings/today-arrivals' && $requestMethod === 'GET') {
            requireApiAuth();
            $handler = new \HotelOS\Handlers\BookingHandler();
            echo json_encode(['success' => true, 'data' => $handler->getTodayArrivals()]);
            exit;
        }
        
        // GET /api/bookings/today-departures
        if ($requestUri === '/api/bookings/today-departures' && $requestMethod === 'GET') {
            requireApiAuth();
            $handler = new \HotelOS\Handlers\BookingHandler();
            echo json_encode(['success' => true, 'data' => $handler->getTodayDepartures()]);
            exit;
        }
        
        // GET /api/bookings/in-house
        if ($requestUri === '/api/bookings/in-house' && $requestMethod === 'GET') {
            requireApiAuth();
            $handler = new \HotelOS\Handlers\BookingHandler();
            echo json_encode(['success' => true, 'data' => $handler->getInHouseGuests()]);
            exit;
        }
        
        // POST /api/bookings/{id}/check-in
        if (preg_match('#^/api/bookings/(\d+)/check-in$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $handler = new \HotelOS\Handlers\BookingHandler();
            $result = $handler->checkIn((int)$matches[1], $data['room_id'] ?? null);
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            exit;
        }
        
        // POST /api/bookings/{id}/check-out
        if (preg_match('#^/api/bookings/(\d+)/check-out$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $extraCharges = (float)($data['extra_charges'] ?? 0);
            $lateCheckoutFee = (float)($data['late_checkout_fee'] ?? 0);
            
            $handler = new \HotelOS\Handlers\BookingHandler();
            $result = $handler->checkOut((int)$matches[1], $extraCharges, $lateCheckoutFee);
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            exit;
        }
        
        // ========== Housekeeping APIs ==========
        
        // POST /api/housekeeping/status - Update room cleaning status
        if ($requestUri === '/api/housekeeping/status' && $requestMethod === 'POST') {
            requireApiAuth();
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if (empty($data['room_id']) || empty($data['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'room_id and status required']);
                exit;
            }
            
            $handler = new \HotelOS\Handlers\HousekeepingHandler();
            try {
                $handler->updateStatus((int)$data['room_id'], $data['status']);
                echo json_encode(['success' => true]);
            } catch (\Throwable $e) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
            }
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
    
    // Redirect authenticated users to dashboard from login
    if ($auth->check() && in_array($requestUri, ['/', '/login'])) {
        header('Location: /dashboard');
        exit;
    }
    
    // Protected routes - require authentication
    $protectedRoutes = ['/dashboard', '/room-types', '/rooms', '/guests', '/bookings', '/settings', '/housekeeping', '/subscription', '/pos', '/reports'];
    if (in_array($requestUri, $protectedRoutes) && !$auth->check()) {
        header('Location: /');
        exit;
    }
    
    // Dynamic routes (before switch)
    // Invoice: /bookings/{id}/invoice
    if (preg_match('#^/bookings/(\d+)/invoice$#', $requestUri, $matches)) {
        if (!$auth->check()) { header('Location: /'); exit; }
        renderInvoicePage($auth, (int)$matches[1]);
        exit;
    }
    
    // Cancel booking: /bookings/{id}/cancel (POST)
    if (preg_match('#^/bookings/(\d+)/cancel$#', $requestUri, $matches) && $requestMethod === 'POST') {
        if (!$auth->check()) { header('Location: /'); exit; }
        handleBookingCancel($auth, (int)$matches[1]);
        exit;
    }
    
    // Route handling
    switch ($requestUri) {
        // ========== Auth Routes ==========
        case '/':
        case '/login':
            // Handle POST login (form submission)
            if ($requestMethod === 'POST') {
                handleLoginForm($auth);
                exit;
            }
            renderLoginPage($auth);
            break;
            
        case '/logout':
            // Phase F1: Check for open shift
            $user = $auth->user();
            if ($user && isset($user['id'])) {
                $shiftHandler = new \HotelOS\Handlers\ShiftHandler();
                // Ensure ID is integer
                $openShift = $shiftHandler->getCurrentShift((int)$user['id']); 
                
                if ($openShift) {
                    // Block logout, redirect to shift page
                    header('Location: /shifts?error=' . urlencode('You must close your active shift before logging out.'));
                    exit;
                }
            }
            
            // Perform full logout
            $auth->logout();
            
            // Destroy session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            
            header('Location: /?logged_out=1');
            exit;
        
        // ========== Dashboard ==========
        case '/dashboard':
            renderDashboard($auth);
            break;
        
        // ========== Room Types ==========
        case '/room-types':
            renderRoomTypesPage($auth);
            break;
            
        case '/room-types/create':
            handleRoomTypeCreate($auth);
            break;
            
        case '/room-types/update':
            handleRoomTypeUpdate($auth);
            break;
            
        case '/room-types/delete':
            handleRoomTypeDelete($auth);
            break;
        
        // ========== Rooms ==========
        case '/rooms':
            renderRoomsPage($auth);
            break;
            
        case '/rooms/create':
            handleRoomCreate($auth);
            break;
            
        case '/rooms/update':
            handleRoomUpdate($auth);
            break;
            
        case '/rooms/status':
            handleRoomStatusUpdate($auth);
            break;
            
        case '/rooms/delete':
            handleRoomDelete($auth);
            break;
        
        // ========== Bookings (Phase 3 - Front Desk) ==========
        case '/bookings':
            renderBookingsPage($auth);
            break;
            
        case '/bookings/create':
            renderBookingCreatePage($auth);
            break;
        
        // ========== Housekeeping ==========
        case '/housekeeping':
            renderHousekeepingPage($auth);
            break;
        
        // ========== Placeholder Routes ==========
        // ========== Settings ==========
        case '/settings':
            renderSettingsPage($auth);
            break;
            
        case '/settings/profile':
            if ($requestMethod === 'POST') handleSettingsProfile($auth);
            else header('Location: /settings');
            break;
            
        case '/settings/tax':
            if ($requestMethod === 'POST') handleSettingsTax($auth);
            else header('Location: /settings?tab=tax');
            break;
            
        case '/settings/times':
            if ($requestMethod === 'POST') handleSettingsTimes($auth);
            else header('Location: /settings?tab=times');
            break;
        
        // ========== POS ==========
        case '/pos':
            renderPOSPage($auth);
            break;
            
        case '/pos/charge':
            if ($requestMethod === 'POST') handlePOSCharge($auth);
            else header('Location: /pos');
            break;
            
        case '/pos/item/create':
            if ($requestMethod === 'POST') handlePOSItemCreate($auth);
            else header('Location: /pos');
            break;
            
        case '/pos/item/update':
            if ($requestMethod === 'POST') handlePOSItemUpdate($auth);
            else header('Location: /pos');
            break;
        
        // ========== Reports ==========
        case '/reports':
            renderReportsPage($auth);
            break;
        
        // ========== Subscription ==========
        case '/subscription':
            renderSubscriptionPage($auth);
            break;
            
        // ========== Security / Sessions (Phase D) ==========
        case '/admin/security/sessions':
            renderSessionsPage($auth);
            break;

        case '/admin/security/audit':
            renderAuditPage($auth);
            break;
            
        case '/session/kill':
            if ($requestMethod === 'POST') handleSessionKill($auth);
            else header('Location: /admin/security/sessions');
            break;
            
        // ========== Shifts (Phase F1) ==========
        case '/shifts':
            renderShiftsPage($auth);
            break;
        case '/shifts/start':
            if ($requestMethod === 'POST') handleShiftStart($auth);
            break;
        case '/shifts/end':
            if ($requestMethod === 'POST') handleShiftEnd($auth);
            break;
        case '/shifts/ledger/add':
            if ($requestMethod === 'POST') handleLedgerAdd($auth);
            break;
            
        // ========== Admin Shift Audit (Phase F3) ==========
        case '/admin/shifts':
            renderAdminShiftsPage($auth);
            break;

        // ========== Admin: Staff Management (Phase G) ==========
        case '/admin/staff':
            renderAdminStaffPage($auth);
            break;
            
        case '/admin/staff/create':
            if ($requestMethod === 'POST') handleStaffCreate($auth);
            else renderAdminStaffCreatePage($auth);
            break;

        case '/admin/staff/edit':
            // Handles both GET (render) and POST (update)
            if ($requestMethod === 'POST') handleStaffUpdate($auth);
            else renderAdminStaffEditPage($auth);
            break;

        // ========== Admin: Cash Ledger (Phase G) ==========
        case '/admin/ledger':
            renderAdminLedgerPage($auth);
            break;
            break;
        case '/admin/shifts/verify':
            if ($requestMethod === 'POST') handleShiftVerify($auth);
            break;
           case '/setup_db':
            $key = $_GET['key'] ?? '';
            if ($key !== 'hotelos_setup_2024') die('Access Denied');
            
            // VERSION CACHE BUSTER
            echo "HOTFIX-10-LIVE<br>";
            
            $db = Database::getInstance();
            // Bypass Database wrapper to avoid 'WHERE tenant_id = ?' injection on DDL
            $pdo = $db->getPdo();
            
            // 1. Shifts
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `shifts` (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `tenant_id` INT UNSIGNED NOT NULL,
                        `user_id` INT UNSIGNED NOT NULL,
                        `shift_start_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `shift_end_at` TIMESTAMP NULL,
                        `opening_cash` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        `closing_cash` DECIMAL(10,2) NULL,
                        `system_expected_cash` DECIMAL(10,2) NULL,
                        `variance_amount` DECIMAL(10,2) NULL,
                        `handover_to_user_id` INT UNSIGNED NULL,
                        `notes` TEXT NULL,
                        `verified_by` INT UNSIGNED NULL,
                        `verified_at` TIMESTAMP NULL,
                        `manager_note` VARCHAR(255) NULL,
                        `status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        INDEX `idx_shifts_tenant` (`tenant_id`),
                        INDEX `idx_shifts_user` (`user_id`),
                        INDEX `idx_shifts_status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                echo "Shifts Table Created. ";
            } catch (Exception $e) { echo "Shift Error: " . $e->getMessage(); }
            
            // 2. Ledger
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `cash_ledger` (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `tenant_id` INT UNSIGNED NOT NULL,
                        `shift_id` INT UNSIGNED NOT NULL,
                        `user_id` INT UNSIGNED NOT NULL,
                        `type` ENUM('expense', 'addition') NOT NULL,
                        `amount` DECIMAL(10,2) NOT NULL,
                        `category` VARCHAR(50) NOT NULL,
                        `description` VARCHAR(255) NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        INDEX `idx_ledger_tenant` (`tenant_id`),
                        INDEX `idx_ledger_shift` (`shift_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                echo "Ledger Table Created. ";
            } catch (Exception $e) { echo "Ledger Error: " . $e->getMessage(); }
            
            // 3. Bookings Columns
            try {
                // Check missing columns manually to avoid valid SQL parsing issues
                $checkIn = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'check_in_time'")->fetch();
                if (!$checkIn) {
                    $pdo->exec("ALTER TABLE bookings ADD COLUMN `check_in_time` TIME DEFAULT '14:00:00' AFTER `check_out_date`");
                }
                
                $checkOut = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'check_out_time'")->fetch();
                if (!$checkOut) {
                    $pdo->exec("ALTER TABLE bookings ADD COLUMN `check_out_time` TIME DEFAULT '11:00:00' AFTER `check_in_time`");
                }
                echo "Bookings patched. ";
            } catch (Exception $ex) { echo "Booking patch error: " . $ex->getMessage(); }
            
            // 4. Police Reports
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `police_reports` (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `tenant_id` INT UNSIGNED NOT NULL,
                        `report_date` DATE NOT NULL,
                        `status` ENUM('pending', 'submitted') NOT NULL DEFAULT 'pending',
                        `submitted_at` TIMESTAMP NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        INDEX `idx_police_reports_tenant` (`tenant_id`),
                        INDEX `idx_police_reports_date` (`report_date`),
                        UNIQUE KEY `uniq_tenant_date` (`tenant_id`, `report_date`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                echo "Police Reports Table Created. ";
            } catch (Exception $e) { echo "Police Report Error: " . $e->getMessage(); }
            
            echo "Brute Force Setup Done.";
            exit;
            exit;
            
        case '/admin/shifts/verify':
            if ($requestMethod === 'POST') handleShiftVerify($auth);
            break;
            
        case '/admin/reports/daily':
            renderDailyReportPage($auth);
            break;
            
        default:
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
// API Helpers
// ============================================

function requireApiAuth(): void
{
    $auth = Auth::getInstance();
    if (!$auth->check()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

function handleLoginApi(): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request body']);
        return;
    }
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        return;
    }
    
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
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
}

function handleLogoutApi(): void
{
    $auth = Auth::getInstance();
    $auth->logout();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully', 'redirect' => '/']);
}

// ============================================
// Page Renderers
// ============================================

function renderLoginPage(Auth $auth): void
{
    $error = null;
    $csrfToken = $auth->csrfToken();
    
    if (isset($_GET['error'])) {
        $error = match($_GET['error']) {
            'invalid' => 'Invalid email or password.',
            'locked' => 'Your account is temporarily locked.',
            'inactive' => 'Your account has been deactivated.',
            default => 'An error occurred. Please try again.'
        };
    }
    
    $title = 'Login';
    $bodyClass = 'page-login';
    
    ob_start();
    include VIEWS_PATH . '/auth/login.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/base.php';
}

function handleSessionKill(Auth $auth): void
{
    $input = $_POST;
    
    // Verify CSRF
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
    
    // Verify Owner Role
    if ($auth->role() !== \HotelOS\Core\Auth::ROLE_OWNER) {
        http_response_code(403);
        die('Access Denied');
    }
    
    $sessionId = $input['session_id'] ?? '';
    
    if ($sessionId) {
        $handler = new \HotelOS\Core\SessionHandler();
        $success = $handler->killSession($sessionId, $auth->user()['tenant_id']);
        
        if ($success) {
           // Log this action
           $auth->logAudit('kill_session', 'session', 0, ['session_id' => $sessionId]);
        }
    }
    
    header('Location: /admin/security/sessions');
    exit;
}
function handleLoginForm(Auth $auth): void
{
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        header('Location: /?error=invalid');
        exit;
    }
    
    // Attempt login
    $result = $auth->attempt($email, $password);
    
    if ($result['success']) {
        // Redirect to dashboard on success
        header('Location: /dashboard');
        exit;
    } else {
        // Determine error type
        $errorCode = 'invalid';
        if (strpos($result['message'], 'locked') !== false) {
            $errorCode = 'locked';
        } elseif (strpos($result['message'], 'deactivated') !== false) {
            $errorCode = 'inactive';
        }
        header('Location: /?error=' . $errorCode);
        exit;
    }
}

function renderDashboard(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    // Get dashboard data
    $dashboardHandler = new DashboardHandler();
    $roomHandler = new RoomHandler();
    
    $stats = $dashboardHandler->getStats();
    $rooms = $dashboardHandler->getRoomsForGrid();
    $statusSummary = $roomHandler->getStatusCounts();
    
    // Additional data for Today's Activity section
    $todayDepartures = $dashboardHandler->getTodayDepartures();
    $dirtyRooms = $dashboardHandler->getDirtyRoomsCount();
    $arrivalsDetail = $dashboardHandler->getTodayArrivalsDetail();
    $departuresDetail = $dashboardHandler->getTodayDeparturesDetail();
    
    $title = 'Dashboard';
    $currentRoute = 'dashboard';
    $breadcrumbs = [];
    
    ob_start();
    include VIEWS_PATH . '/dashboard/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderRoomTypesPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $handler = new RoomTypeHandler();
    $roomTypes = $handler->list();
    
    // Check for flash messages
    $error = $_SESSION['flash_error'] ?? null;
    $success = $_SESSION['flash_success'] ?? null;
    unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    
    $title = 'Room Types';
    $currentRoute = 'room-types';
    $breadcrumbs = [
        ['label' => 'Rooms', 'href' => '/rooms'],
        ['label' => 'Room Types'],
    ];
    
    ob_start();
    include VIEWS_PATH . '/admin/room_types.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderRoomsPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $roomHandler = new RoomHandler();
    $roomTypeHandler = new RoomTypeHandler();
    
    $rooms = $roomHandler->list();
    $roomTypes = $roomTypeHandler->list();
    $statusCounts = $roomHandler->getStatusCounts();
    
    $error = $_SESSION['flash_error'] ?? null;
    $success = $_SESSION['flash_success'] ?? null;
    unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    
    $title = 'Rooms';
    $currentRoute = 'rooms';
    $breadcrumbs = [['label' => 'Rooms']];
    
    ob_start();
    include VIEWS_PATH . '/admin/rooms.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderComingSoonPage(Auth $auth, string $feature): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $title = $feature;
    $currentRoute = strtolower($feature);
    $breadcrumbs = [['label' => $feature]];
    
    ob_start();
    ?>
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="glass-card p-8 text-center max-w-md">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-purple-500/20 flex items-center justify-center">
                <i data-lucide="construction" class="w-8 h-8 text-purple-400"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2"><?= htmlspecialchars($feature) ?></h1>
            <p class="text-slate-400 mb-6">This feature is coming soon in the next update.</p>
            <a href="/dashboard" class="btn btn--primary">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to Dashboard
            </a>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderErrorPage(int $code, string $message): void
{
    $title = "Error {$code}";
    $bodyClass = 'page-error';
    $csrfToken = '';
    
    ob_start();
    ?>
    <div class="min-h-screen flex items-center justify-center p-4" style="background: #0f172a;">
        <div style="background: rgba(30,41,59,0.8); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 2rem; max-width: 400px; text-align: center;">
            <div style="font-size: 4rem; font-weight: 700; color: #22d3ee; margin-bottom: 1rem;"><?= $code ?></div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: white; margin-bottom: 0.5rem;"><?= htmlspecialchars($message) ?></h1>
            <p style="color: #94a3b8; margin-bottom: 1.5rem;">The page you're looking for doesn't exist.</p>
            <a href="/" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; background: linear-gradient(135deg, #22d3ee, #06b6d4); color: #0f172a; font-weight: 600; border-radius: 0.5rem; text-decoration: none;">
                Back to Home
            </a>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    echo $content;
}

function renderSubscriptionPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $handler = new \HotelOS\Handlers\SubscriptionHandler();
    $subscription = $handler->getCurrentSubscription();
    $plans = $handler->getAllPlans();
    
    $title = 'Subscription Plans';
    $currentRoute = 'subscription';
    $breadcrumbs = [['label' => 'Subscription']];
    
    ob_start();
    include VIEWS_PATH . '/subscription/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

// ============================================
// Form Handlers - Room Types
// ============================================

function handleRoomTypeCreate(Auth $auth): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /room-types');
        exit;
    }
    
    try {
        $handler = new RoomTypeHandler();
        $handler->create($_POST);
        $_SESSION['flash_success'] = 'Room type created successfully';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /room-types');
    exit;
}

function handleRoomTypeUpdate(Auth $auth): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /room-types');
        exit;
    }
    
    try {
        $id = (int) ($_POST['id'] ?? 0);
        $handler = new RoomTypeHandler();
        $handler->update($id, $_POST);
        $_SESSION['flash_success'] = 'Room type updated successfully';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /room-types');
    exit;
}

function handleRoomTypeDelete(Auth $auth): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /room-types');
        exit;
    }
    
    try {
        $id = (int) ($_POST['id'] ?? 0);
        $handler = new RoomTypeHandler();
        $handler->delete($id);
        $_SESSION['flash_success'] = 'Room type deleted successfully';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /room-types');
    exit;
}

// ============================================
// Form Handlers - Rooms
// ============================================

function handleRoomCreate(Auth $auth): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /rooms');
        exit;
    }
    
    try {
        $handler = new RoomHandler();
        $handler->create($_POST);
        $_SESSION['flash_success'] = 'Room created successfully';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /rooms');
    exit;
}

function handleRoomUpdate(Auth $auth): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /rooms');
        exit;
    }
    
    try {
        $id = (int) ($_POST['id'] ?? 0);
        $handler = new RoomHandler();
        $handler->update($id, $_POST);
        $_SESSION['flash_success'] = 'Room updated successfully';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /rooms');
    exit;
}

function handleRoomStatusUpdate(Auth $auth): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /rooms');
        exit;
    }
    
    try {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $handler = new RoomHandler();
        $handler->updateStatus($id, $status);
        $_SESSION['flash_success'] = 'Room status updated';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /rooms');
    exit;
}

function handleRoomDelete(Auth $auth): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /rooms');
        exit;
    }
    
    try {
        $id = (int) ($_POST['id'] ?? 0);
        $handler = new RoomHandler();
        $handler->delete($id);
        $_SESSION['flash_success'] = 'Room deleted successfully';
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /rooms');
    exit;
}

// ============================================
// Render Functions - Bookings (Phase 3)
// ============================================

function renderBookingsPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $title = 'Front Desk';
    $currentRoute = 'bookings';
    $breadcrumbs = [
        ['label' => 'Front Desk']
    ];
    
    ob_start();
    include VIEWS_PATH . '/bookings/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderBookingCreatePage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    // Get room types for dropdown
    $roomTypeHandler = new RoomTypeHandler();
    $roomTypes = $roomTypeHandler->list();
    
    $title = 'New Booking';
    $currentRoute = 'bookings';
    $breadcrumbs = [
        ['label' => 'Front Desk', 'url' => '/bookings'],
        ['label' => 'New Booking']
    ];
    
    ob_start();
    include VIEWS_PATH . '/bookings/create.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

// ============================================
// Render Functions - Housekeeping
// ============================================

function renderHousekeepingPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $handler = new \HotelOS\Handlers\HousekeepingHandler();
    
    // Get filter params
    $selectedFloor = $_GET['floor'] ?? '';
    $selectedStatus = $_GET['status'] ?? '';
    
    $rooms = $handler->getRoomBoard(
        $selectedFloor ?: null,
        $selectedStatus ?: null
    );
    $statusCounts = $handler->getStatusCounts();
    $floors = $handler->getFloors();
    
    $title = 'Housekeeping';
    $currentRoute = 'housekeeping';
    $breadcrumbs = [
        ['label' => 'Housekeeping']
    ];
    
    ob_start();
    include VIEWS_PATH . '/housekeeping/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

// ============================================
// Invoice Functions
// ============================================

function renderInvoicePage(Auth $auth, int $bookingId): void
{
    $handler = new \HotelOS\Handlers\InvoiceHandler();
    $invoice = $handler->getInvoiceData($bookingId);
    
    if (!$invoice) {
        http_response_code(404);
        echo '<h1>Invoice not found</h1>';
        return;
    }
    
    // Render standalone (no layout)
    include VIEWS_PATH . '/bookings/invoice.php';
}

function handleBookingCancel(Auth $auth, int $bookingId): void
{
    $handler = new \HotelOS\Handlers\BookingHandler();
    $result = $handler->cancel($bookingId, $_POST['reason'] ?? 'Cancelled by user');
    
    if ($result['success']) {
        $_SESSION['flash_success'] = 'Booking cancelled successfully';
    } else {
        $_SESSION['flash_error'] = $result['error'] ?? 'Failed to cancel booking';
    }
    
    header('Location: /bookings');
    exit;
}

// ============================================
// Settings Functions
// ============================================

function renderSettingsPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $handler = new \HotelOS\Handlers\SettingsHandler();
    $profile = $handler->getHotelProfile();
    $states = \HotelOS\Handlers\SettingsHandler::getStatesList();
    
    $activeTab = $_GET['tab'] ?? 'profile';
    $success = $_SESSION['flash_success'] ?? null;
    $error = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    
    $title = 'Settings';
    $currentRoute = 'settings';
    $breadcrumbs = [['label' => 'Settings']];
    
    ob_start();
    include VIEWS_PATH . '/settings/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function handleSettingsProfile(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\SettingsHandler();
    
    try {
        $handler->updateHotelProfile($_POST);
        $_SESSION['flash_success'] = 'Hotel profile updated successfully';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /settings');
    exit;
}

function handleSettingsTax(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\SettingsHandler();
    
    try {
        $handler->updateTaxSettings($_POST);
        $_SESSION['flash_success'] = 'Tax settings updated successfully';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /settings?tab=tax');
    exit;
}

function handleSettingsTimes(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\SettingsHandler();
    
    try {
        $checkIn = $_POST['check_in_time'] ?? '14:00';
        $checkOut = $_POST['check_out_time'] ?? '11:00';
        $handler->updateCheckTimes($checkIn . ':00', $checkOut . ':00');
        $_SESSION['flash_success'] = 'Check times updated successfully';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /settings?tab=times');
    exit;
}

// ============================================
// POS Functions
// ============================================

function renderPOSPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $handler = new \HotelOS\Handlers\POSHandler();
    
    $selectedCategory = $_GET['category'] ?? '';
    $items = $handler->getItems($selectedCategory ?: null);
    $inHouseGuests = $handler->getInHouseGuests();
    $categoryCounts = $handler->getCategoryCounts();
    
    $success = $_SESSION['flash_success'] ?? null;
    $error = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    
    $title = 'POS & Extras';
    $currentRoute = 'pos';
    $breadcrumbs = [['label' => 'POS & Extras']];
    
    ob_start();
    include VIEWS_PATH . '/pos/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function handlePOSCharge(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\POSHandler();
    
    try {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $notes = $_POST['notes'] ?? null;
        
        if (!$bookingId) {
            throw new \InvalidArgumentException('Please select a guest');
        }
        
        $handler->addCharge($bookingId, $itemId, $quantity, (int)$auth->user()['id'], $notes);
        $_SESSION['flash_success'] = 'Charge added to room bill';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /pos');
    exit;
}

function handlePOSItemCreate(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\POSHandler();
    
    try {
        $handler->createItem($_POST);
        $_SESSION['flash_success'] = 'Item created successfully';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /pos');
    exit;
}

function handlePOSItemUpdate(Auth $auth): void
{
    $handler = new \HotelOS\Handlers\POSHandler();
    
    try {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new \InvalidArgumentException('Invalid item ID');
        
        $handler->updateItem($id, $_POST);
        $_SESSION['flash_success'] = 'Item updated successfully';
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header('Location: /pos');
    exit;
}

// ============================================
// Reports Functions
// ============================================

function renderReportsPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $handler = new \HotelOS\Handlers\ReportsHandler();
    
    $activeTab = $_GET['tab'] ?? 'revenue';
    $startDate = $_GET['start'] ?? date('Y-m-01');
    $endDate = $_GET['end'] ?? date('Y-m-d');
    
    $reportData = [];
    $summary = [];
    
    switch ($activeTab) {
        case 'revenue':
            $reportData = $handler->getDailyRevenue($startDate, $endDate);
            $summary = $handler->getRevenueSummary($startDate, $endDate);
            break;
            
        case 'occupancy':
            $reportData = $handler->getOccupancyReport($startDate, $endDate);
            $summary = $handler->getOccupancySummary($startDate, $endDate);
            break;
            
        case 'gst':
            $gstData = $handler->getGSTSummary($startDate, $endDate);
            $reportData = $gstData;
            $summary = $gstData['totals'] ?? [];
            break;
            
        case 'rooms':
            $reportData = $handler->getRoomWiseRevenue($startDate, $endDate);
            break;
    }
    
    $title = 'Reports';
    $currentRoute = 'reports';
    $breadcrumbs = [['label' => 'Reports']];
    
    ob_start();
    include VIEWS_PATH . '/reports/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

// ==========================================
// Phase G: Admin Controllers (Staff & Ledger)
// ==========================================

function renderAdminStaffPage(Auth $auth) {
    if (!$auth->isManager()) {
        header('Location: /dashboard');
        exit;
    }
    
    // View handles UserHandler Instantiation
    require_once PUBLIC_PATH . '/../views/layouts/app.php';
}

function renderAdminStaffCreatePage(Auth $auth) {
    if (!$auth->isManager()) {
        header('Location: /dashboard');
        exit;
    }
    require_once PUBLIC_PATH . '/../views/layouts/app.php';
}

function renderAdminStaffEditPage(Auth $auth) {
    if (!$auth->isManager()) {
        header('Location: /dashboard');
        exit;
    }
    // We expect ?id=X
    if (empty($_GET['id'])) {
        header('Location: /admin/staff');
        exit;
    }
    require_once PUBLIC_PATH . '/../views/layouts/app.php';
}

function handleStaffCreate(Auth $auth) {
    if (!$auth->isManager()) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
    
    $handler = new \HotelOS\Handlers\UserHandler();
    $result = $handler->create($_POST);
    
    if ($result['success']) {
        header('Location: /admin/staff?success=created');
    } else {
        header('Location: /admin/staff/create?error=' . urlencode($result['error']));
    }
    exit;
}

function handleStaffUpdate(Auth $auth) {
    if (!$auth->isManager()) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
    
    $id = (int)$_POST['id'];
    $handler = new \HotelOS\Handlers\UserHandler();
    $result = $handler->update($id, $_POST);
    
    if ($result['success']) {
        header('Location: /admin/staff?success=updated');
    } else {
        header('Location: /admin/staff/edit?id=' . $id . '&error=' . urlencode($result['error'] ?? 'Update failed'));
    }
    exit;
}

function renderAdminLedgerPage(Auth $auth) {
    if (!$auth->isManager()) {
        header('Location: /dashboard');
        exit;
    }
    require_once PUBLIC_PATH . '/../views/layouts/app.php';
}
