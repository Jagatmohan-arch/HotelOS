<?php
/**
 * HotelOS - Front Controller
 * 
 * Main entry point - handles all routing for web and API requests
 * Phase 2: Now includes Dashboard, Room Types, and Rooms routes
 */

declare(strict_types=1);

// Error handling - TEMPORARY DEBUG MODE
error_reporting(E_ALL);
ini_set('display_errors', '1');  // TEMPORARY - TURN OFF IN PRODUCTION
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

use HotelOS\Core\Auth;
use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;
use HotelOS\Handlers\DashboardHandler;
use HotelOS\Handlers\RoomTypeHandler;
use HotelOS\Handlers\RoomHandler;

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
            
            $handler = new \HotelOS\Handlers\BookingHandler();
            $result = $handler->checkOut((int)$matches[1]);
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
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
    $protectedRoutes = ['/dashboard', '/room-types', '/rooms', '/guests', '/bookings', '/settings'];
    if (in_array($requestUri, $protectedRoutes) && !$auth->check()) {
        header('Location: /');
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
            $auth->logout();
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
        
        // ========== Placeholder Routes ==========
        case '/guests':
        case '/settings':
        case '/reports':
            renderComingSoonPage($auth, ucfirst(substr($requestUri, 1)));
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
