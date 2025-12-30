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
            
            // Add badge info to each guest for frontend display
            foreach ($results as &$guest) {
                $guest['badge'] = \HotelOS\Handlers\GuestHandler::getCategoryBadge($guest['category'] ?? 'regular');
                $guest['is_blacklisted'] = ($guest['category'] ?? '') === 'blacklisted';
            }
            
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
        
        // POST /api/guests/{id}/category - Update guest category (Manager+)
        if (preg_match('#^/api/guests/(\d+)/category$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            
            if (!$auth->isManager()) {
                http_response_code(403);
                echo json_encode(['error' => 'Manager permission required']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if (empty($data['category'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Category is required']);
                exit;
            }
            
            $handler = new \HotelOS\Handlers\GuestHandler();
            $result = $handler->updateCategory(
                (int)$matches[1],
                $data['category'],
                $data['notes'] ?? null
            );
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            exit;
        }
        
        // ========== Room APIs (Phase 4) ==========

        // POST /api/rooms/{id}/status - Update room status (Manager+)
        if (preg_match('#^/api/rooms/(\d+)/status$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            if (!$auth->isManager()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $roomId = (int)$matches[1];
            $status = $data['status'] ?? '';
            
            $validStatuses = ['available', 'maintenance', 'blocked'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'error' => 'Invalid status']);
                exit;
            }
            
            $db = \HotelOS\Core\Database::getInstance();
            $db->execute(
                "UPDATE rooms SET status = :status WHERE id = :id AND tenant_id = :tid",
                ['status' => $status, 'id' => $roomId, 'tid' => \HotelOS\Core\TenantContext::getId()],
                enforceTenant: false
            );
            
            echo json_encode(['success' => true]);
            exit;
        }

        // POST /api/rooms/{id}/housekeeping - Update housekeeping status
        if (preg_match('#^/api/rooms/(\d+)/housekeeping$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $roomId = (int)$matches[1];
            $status = $data['status'] ?? ''; // clean, dirty
            
            if (!in_array($status, ['clean', 'dirty'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid status']);
                exit;
            }
            
            $db = \HotelOS\Core\Database::getInstance();
            $db->execute(
                "UPDATE rooms SET housekeeping_status = :status WHERE id = :id AND tenant_id = :tid",
                ['status' => $status, 'id' => $roomId, 'tid' => \HotelOS\Core\TenantContext::getId()],
                enforceTenant: false
            );
            
            echo json_encode(['success' => true]);
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
        
        // GET /api/bookings/today (Consolidated for Mobile)
        if ($requestUri === '/api/bookings/today' && $requestMethod === 'GET') {
            requireApiAuth();
            $handler = new \HotelOS\Handlers\BookingHandler();
            
            $arrivals = $handler->getTodayArrivals();
            $inhouse = $handler->getInHouseGuests();
            $departures = $handler->getTodayDepartures(); // Only for count
            
            // Combine arrivals and inhouse for the list (departures are subset of inhouse)
            $all = array_merge($arrivals, $inhouse);
            
            // Format for UI
            $bookings = array_map(function($b) {
                $b['guest_name'] = trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? ''));
                // Ensure check_out_date is Y-m-d
                if (isset($b['check_out_date'])) {
                    $b['check_out_date'] = substr($b['check_out_date'], 0, 10);
                }
                return $b;
            }, $all);
            
            echo json_encode([
                'success' => true,
                'bookings' => $bookings,
                'arrivals_count' => count($arrivals),
                'departures_count' => count($departures),
                'inhouse_count' => count($inhouse)
            ]);
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
            
            // Process payment if amount provided
            $amountPaid = (float)($data['amount_paid'] ?? 0);
            if ($amountPaid > 0) {
                // Determine payment method and reference
                $paymentMethod = $data['payment_method'] ?? 'cash';
                $reference = $data['reference'] ?? null;
                
                $invoiceHandler = new \HotelOS\Handlers\InvoiceHandler();
                $invoiceHandler->recordPayment((int)$matches[1], $amountPaid, $paymentMethod, $reference);
            }
            
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
        
        // POST /api/bookings/{id}/tax-exempt - Set tax exemption (Manager+)
        if (preg_match('#^/api/bookings/(\d+)/tax-exempt$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            
            if (!$auth->isManager()) {
                http_response_code(403);
                echo json_encode(['error' => 'Manager permission required']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $handler = new \HotelOS\Handlers\BookingHandler();
            $result = $handler->setTaxExempt(
                (int)$matches[1],
                (bool)($data['exempt'] ?? false),
                $data['reason'] ?? ''
            );
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            exit;
        }
        
        // POST /api/bookings/{id}/move-room
        if (preg_match('#^/api/bookings/(\d+)/move-room$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            
            // Manager+ only
            if (!$auth->isManager()) {
                http_response_code(403);
                echo json_encode(['error' => 'Manager permission required']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if (empty($data['new_room_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'new_room_id required']);
                exit;
            }
            
            $handler = new \HotelOS\Handlers\BookingHandler();
            $result = $handler->moveRoom(
                (int)$matches[1],
                (int)$data['new_room_id'],
                $data['reason'] ?? 'guest_request',
                $data['rate_action'] ?? 'keep_original',
                isset($data['custom_rate']) ? (float)$data['custom_rate'] : null,
                $data['notes'] ?? null
            );
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            exit;
        }
        
        // GET /api/bookings/{id}/move-history
        if (preg_match('#^/api/bookings/(\d+)/move-history$#', $requestUri, $matches) && $requestMethod === 'GET') {
            requireApiAuth();
            
            $handler = new \HotelOS\Handlers\BookingHandler();
            $history = $handler->getRoomMoveHistory((int)$matches[1]);
            
            echo json_encode(['success' => true, 'history' => $history]);
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
        
        // ========== Refund APIs (2-Person Approval) ==========
        
        // POST /api/refunds/request - Staff initiates refund
        if ($requestUri === '/api/refunds/request' && $requestMethod === 'POST') {
            requireApiAuth();
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $handler = new \HotelOS\Handlers\RefundHandler();
            $userId = Auth::getInstance()->user()['id'] ?? 0;
            
            if (empty($data['booking_id']) || empty($data['amount']) || empty($data['reason_code'])) {
                http_response_code(400);
                echo json_encode(['error' => 'booking_id, amount, and reason_code are required']);
                exit;
            }
            
            $result = $handler->requestRefund(
                (int)$data['booking_id'],
                (float)$data['amount'],
                $data['reason_code'],
                $data['reason_text'] ?? null,
                $userId
            );
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            exit;
        }
        
        // GET /api/refunds/pending - Manager fetches pending requests
        if ($requestUri === '/api/refunds/pending' && $requestMethod === 'GET') {
            requireApiAuth();
            $auth = Auth::getInstance();
            
            if (!$auth->isManager()) {
                http_response_code(403);
                echo json_encode(['error' => 'Manager access required']);
                exit;
            }
            
            $handler = new \HotelOS\Handlers\RefundHandler();
            echo json_encode(['success' => true, 'data' => $handler->getPendingRefunds()]);
            exit;
        }
        
        // POST /api/refunds/{id}/approve - Manager approves
        if (preg_match('#^/api/refunds/(\d+)/approve$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            
            if (!$auth->isManager()) {
                http_response_code(403);
                echo json_encode(['error' => 'Manager access required']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $handler = new \HotelOS\Handlers\RefundHandler();
            $result = $handler->approveRefund(
                (int)$matches[1],
                $auth->user()['id'],
                $data['refund_mode'] ?? 'cash'
            );
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            exit;
        }
        
        // POST /api/refunds/{id}/reject - Manager rejects
        if (preg_match('#^/api/refunds/(\d+)/reject$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            
            if (!$auth->isManager()) {
                http_response_code(403);
                echo json_encode(['error' => 'Manager access required']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $handler = new \HotelOS\Handlers\RefundHandler();
            $result = $handler->rejectRefund(
                (int)$matches[1],
                $auth->user()['id'],
                $data['note'] ?? ''
            );
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            exit;
        }
        
        // GET /api/refunds/booking/{id} - Get refund history for booking
        if (preg_match('#^/api/refunds/booking/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET') {
            requireApiAuth();
            $handler = new \HotelOS\Handlers\RefundHandler();
            echo json_encode(['success' => true, 'data' => $handler->getRefundHistory((int)$matches[1])]);
            exit;
        }
        
        // ========== ENGINE APIs (Owner-Only) ==========
        
        // GET /api/engine/dashboard - Engine overview data
        if ($requestUri === '/api/engine/dashboard' && $requestMethod === 'GET') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) {
                http_response_code(403);
                exit;
            }
            
            $handler = new \HotelOS\Handlers\EngineHandler();
            echo json_encode([
                'success' => true,
                'hotel_setup' => $handler->getHotelSetup(),
                'branding' => $handler->getBrandingAssets(),
                'staff_count' => count($handler->getStaffList())
            ]);
            exit;
        }
        
        // GET /api/engine/staff - Get all staff
        if ($requestUri === '/api/engine/staff' && $requestMethod === 'GET') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $handler = new \HotelOS\Handlers\EngineHandler();
            echo json_encode(['success' => true, 'staff' => $handler->getStaffList()]);
            exit;
        }
        
        // POST /api/engine/staff/{id}/pin - Generate PIN for staff
        if (preg_match('#^/api/engine/staff/(\d+)/pin$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->generateStaffPin((int)$matches[1], $data['reason'] ?? 'PIN generated by owner');
            
            echo json_encode($result);
            exit;
        }
        
        // POST /api/engine/staff/{id}/block - Block/unblock staff
        if (preg_match('#^/api/engine/staff/(\d+)/block$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->setStaffActive(
                (int)$matches[1],
                (bool)($data['active'] ?? false),
                $data['reason'] ?? 'Status changed by owner'
            );
            
            echo json_encode($result);
            exit;
        }
        
        // POST /api/engine/staff/{id}/logout - Force logout staff
        if (preg_match('#^/api/engine/staff/(\d+)/logout$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->forceLogoutStaff((int)$matches[1], $data['reason'] ?? 'Force logout by owner');
            
            echo json_encode($result);
            exit;
        }
        
        // POST /api/engine/branding/upload - Upload branding asset
        if ($requestUri === '/api/engine/branding/upload' && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $assetType = $_POST['asset_type'] ?? '';
            $reason = $_POST['reason'] ?? 'Branding uploaded';
            
            if (empty($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No file uploaded']);
                exit;
            }
            
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->uploadBrandingAsset($assetType, $_FILES['file'], $reason);
            
            echo json_encode($result);
            exit;
        }
        
        // POST /api/engine/setup - Update hotel setup
        if ($requestUri === '/api/engine/setup' && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->updateHotelSetup($data, $data['reason'] ?? 'Settings updated');
            
            echo json_encode($result);
            exit;
        }
        
        // POST /api/engine/data-lock - Set data lock date
        if ($requestUri === '/api/engine/data-lock' && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->setDataLock($data['lock_until'] ?? null, $data['reason'] ?? 'Data locked');
            
            echo json_encode($result);
            exit;
        }
        
        // GET /api/engine/logs - Get engine action logs
        if ($requestUri === '/api/engine/logs' && $requestMethod === 'GET') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $handler = new \HotelOS\Handlers\EngineHandler();
            $logs = $handler->getEngineLogs(
                $_GET['from'] ?? null,
                $_GET['to'] ?? null,
                isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
                $_GET['action'] ?? null,
                $_GET['risk'] ?? null
            );
            
            echo json_encode(['success' => true, 'logs' => $logs]);
            exit;
        }
        
        // POST /api/engine/maintenance - Toggle maintenance mode
        if ($requestUri === '/api/engine/maintenance' && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->setMaintenanceMode(
                (bool)($data['enabled'] ?? false),
                $data['message'] ?? null,
                $data['reason'] ?? 'Maintenance mode changed'
            );
            
            echo json_encode($result);
            exit;
        }
        
        // POST /api/engine/cash-adjust - Cash adjustment
        if ($requestUri === '/api/engine/cash-adjust' && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if (empty($data['shift_id']) || !isset($data['amount']) || empty($data['reason']) || empty($data['confirm_password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'All fields required']);
                exit;
            }
            
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->adjustShiftCash(
                (int)$data['shift_id'],
                (float)$data['amount'],
                $data['reason'],
                $data['confirm_password']
            );
            
            echo json_encode($result);
            exit;
        }
        
        // POST /api/engine/invoice/{id}/modify - Modify invoice (DANGER)
        if (preg_match('#^/api/engine/invoice/(\d+)/modify$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if (empty($data['reason']) || strlen($data['reason']) < 20 || empty($data['confirm_password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Reason (20+ chars) and password required']);
                exit;
            }
            
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->modifyInvoice(
                (int)$matches[1],
                $data,
                $data['reason'],
                $data['confirm_password']
            );
            
            echo json_encode($result);
            exit;
        }
        
        // POST /api/engine/invoice/{id}/void - Void invoice (DANGER)
        if (preg_match('#^/api/engine/invoice/(\d+)/void$#', $requestUri, $matches) && $requestMethod === 'POST') {
            requireApiAuth();
            $auth = Auth::getInstance();
            if (!$auth->isOwner()) { http_response_code(403); exit; }
            
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            
            if (empty($data['reason']) || strlen($data['reason']) < 20 || empty($data['confirm_password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Reason (20+ chars) and password required']);
                exit;
            }
            
            $handler = new \HotelOS\Handlers\EngineHandler();
            $result = $handler->voidInvoice(
                (int)$matches[1],
                $data['reason'],
                $data['confirm_password']
            );
            
            echo json_encode($result);
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
    // Checkout Page (Mobile Flow)
    if (preg_match('#^/bookings/(\d+)/checkout$#', $requestUri, $matches)) {
        requireAuth();
        renderCheckoutPage($auth, (int)$matches[1]);
        exit;
    }

    // Invoice Page
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
        case '/register':
            // Handle POST registration (form submission)
            if ($requestMethod === 'POST') {
                handleRegisterForm($auth);
                exit;
            }
            // Redirect if already logged in
            if ($auth->check()) {
                header('Location: /dashboard');
                exit;
            }
            renderRegisterPage($auth);
            break;
            
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
            
        case '/subscription/trial-expired':
            renderTrialExpiredPage($auth);
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
            
        // ========== Admin: Refund Queue (2-Person Approval) ==========
        case '/admin/refunds':
            renderAdminRefundsPage($auth);
            break;
        
        // ========== HOTEL ENGINE (Owner-Only Super Control) ==========
        case '/engine':
        case '/engine/dashboard':
            renderEngineDashboard($auth);
            break;
        case '/engine/staff':
            renderEngineStaff($auth);
            break;
        case '/engine/branding':
            renderEngineBranding($auth);
            break;
        case '/engine/audit':
            renderEngineAudit($auth);
            break;
        case '/engine/setup':
            renderEngineSetup($auth);
            break;
        case '/engine/bills':
            renderEngineBills($auth);
            break;
        case '/engine/finance':
            renderEngineFinance($auth);
            break;
        case '/admin/shifts/verify':
            if ($requestMethod === 'POST') handleShiftVerify($auth);
            break;
            
        // NOTE: /setup_db backdoor REMOVED for security (2025-12-29)
        // All schema changes should go through database/migrations/ folder
            
        case '/admin/reports/daily':
            renderDailyReportPage($auth);
            break;
            
        // ========== Legal Pages ==========
        case '/terms':
        case '/terms-of-service':
            include VIEWS_PATH . '/legal/terms.php';
            exit;
            
        case '/privacy':
        case '/privacy-policy':
            include VIEWS_PATH . '/legal/privacy.php';
            exit;
            
        case '/forgot-password':
            // TODO: Implement password reset flow
            renderComingSoonPage($auth, 'Password Reset');
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
    $loginType = $_POST['login_type'] ?? 'owner';
    
    // Staff PIN Login
    if ($loginType === 'staff' && !empty($_POST['pin'])) {
        $pin = $_POST['pin'];
        $result = $auth->attemptPIN($pin);
        
        if ($result['success']) {
            header('Location: /dashboard');
            exit;
        } else {
            $errorCode = 'invalid';
            if (str_contains($result['message'], 'locked')) {
                $errorCode = 'locked';
            } elseif (str_contains($result['message'], 'deactivated')) {
                $errorCode = 'inactive';
            }
            header('Location: /login?type=staff&error=' . $errorCode);
            exit;
        }
    }
    
    // Owner Email/Password Login
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
    
    // Build alerts for Owner (pending actions needing attention)
    $alerts = [];
    
    // Check for pending refunds (Owner/Manager)
    if ($auth->isManager()) {
        $db = \HotelOS\Core\Database::getInstance();
        $pendingRefunds = $db->queryOne(
            "SELECT COUNT(*) as count FROM refund_requests WHERE status = 'pending'",
            []
        );
        if ($pendingRefunds && $pendingRefunds['count'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'receipt-refund',
                'title' => $pendingRefunds['count'] . ' Refund Request(s)',
                'description' => 'Pending approval',
                'href' => '/admin/refunds'
            ];
        }
    }
    
    // Check for dirty rooms
    if ($dirtyRooms > 3) {
        $alerts[] = [
            'type' => 'info',
            'icon' => 'sparkles',
            'title' => $dirtyRooms . ' Rooms Need Cleaning',
            'description' => 'Housekeeping required',
            'href' => '/housekeeping'
        ];
    }
    
    // Check for pending checkouts
    if ($todayDepartures > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'log-out',
            'title' => $todayDepartures . ' Pending Checkout(s)',
            'description' => 'Due today',
            'href' => '/bookings?tab=departures'
        ];
    }
    
    // Add dirty rooms count to status summary
    $statusSummary['dirty'] = $dirtyRooms;
    
    $title = 'Dashboard';
    $currentRoute = 'dashboard';
    $breadcrumbs = [];
    
    ob_start();
    // Include both desktop and mobile views
    include VIEWS_PATH . '/dashboard/index.php';
    include VIEWS_PATH . '/dashboard/mobile.php';
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

function renderTrialExpiredPage(Auth $auth): void
{
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    // Get tenant info
    $db = \HotelOS\Core\Database::getInstance();
    $tenant = $db->queryOne(
        "SELECT name, trial_ends_at FROM tenants WHERE id = :id",
        ['id' => $user['tenant_id']],
        enforceTenant: false
    );
    
    $title = 'Trial Expired';
    $bodyClass = 'page-trial-expired';
    
    ob_start();
    include VIEWS_PATH . '/subscription/trial_expired.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/base.php';
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
    // Desktop view (hidden on mobile)
    include VIEWS_PATH . '/bookings/index.php';
    // Mobile view (hidden on desktop)
    include VIEWS_PATH . '/bookings/mobile.php';
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
    
    // Fetch branding assets (logo, stamp, signature)
    $branding = [];
    try {
        $db = \HotelOS\Core\Database::getInstance();
        $tenantId = \HotelOS\Core\TenantContext::getId();
        
        $assets = $db->query(
            "SELECT asset_type, file_path FROM branding_assets WHERE tenant_id = :tid AND is_active = 1",
            ['tid' => $tenantId],
            enforceTenant: false
        );
        
        foreach ($assets as $asset) {
            $branding[$asset['asset_type']] = $asset['file_path'];
        }
    } catch (\Throwable $e) {
        // Branding not available - continue without it
    }
    
    // Render standalone (no layout)
    include VIEWS_PATH . '/bookings/invoice.php';
}

function renderCheckoutPage(Auth $auth, int $bookingId): void
{
    $handler = new \HotelOS\Handlers\InvoiceHandler();
    $invoice = $handler->getInvoiceData($bookingId);
    
    if (!$invoice) {
        http_response_code(404);
        echo '<h1>Booking not found</h1>';
        return;
    }
    
    $bookingHandler = new \HotelOS\Handlers\BookingHandler();
    $booking = $invoice['booking'];
    
    // Safety: If already checked out, redirect to invoice
    if ($booking['status'] === 'checked_out') {
        header("Location: /bookings/{$bookingId}/invoice");
        exit;
    }
    
    $user = $auth->user();
    $title = 'Checkout - ' . $booking['guest_name'];
    $currentRoute = 'bookings';
    $breadcrumbs = [
        ['label' => 'Front Desk', 'href' => '/bookings'],
        ['label' => 'Checkout']
    ];
    
    ob_start();
    include VIEWS_PATH . '/bookings/checkout.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
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

function renderAdminRefundsPage(Auth $auth): void {
    if (!$auth->isManager()) {
        header('Location: /dashboard');
        exit;
    }
    
    $user = $auth->user();
    $csrfToken = $auth->csrfToken();
    
    $handler = new \HotelOS\Handlers\RefundHandler();
    $pendingRefunds = $handler->getPendingRefunds();
    $allRefunds = $handler->getAllRefunds(null, null, null, 100);
    $reasonCodes = \HotelOS\Handlers\RefundHandler::REASON_CODES;
    
    $title = 'Refund Requests';
    $currentRoute = 'admin-refunds';
    $breadcrumbs = [
        ['label' => 'Admin'],
        ['label' => 'Refund Requests']
    ];
    
    ob_start();
    include VIEWS_PATH . '/admin/refunds/index.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

// ============================================
// HOTEL ENGINE RENDER FUNCTIONS (Owner-Only)
// ============================================

function renderEngineDashboard(Auth $auth): void {
    if (!$auth->isOwner()) {
        header('Location: /dashboard');
        exit;
    }
    
    $handler = new \HotelOS\Handlers\EngineHandler();
    $hotelSetup = $handler->getHotelSetup();
    $branding = $handler->getBrandingAssets();
    $staffCount = count($handler->getStaffList());
    $recentLogs = $handler->getEngineLogs(null, null, null, null, null, 10);
    
    $title = 'Hotel Engine';
    $currentRoute = 'engine';
    $breadcrumbs = [
        ['label' => 'Engine'],
        ['label' => 'Dashboard']
    ];
    
    ob_start();
    include VIEWS_PATH . '/engine/dashboard.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderEngineStaff(Auth $auth): void {
    if (!$auth->isOwner()) {
        header('Location: /dashboard');
        exit;
    }
    
    $handler = new \HotelOS\Handlers\EngineHandler();
    $staffList = $handler->getStaffList();
    
    $title = 'Staff Engine';
    $currentRoute = 'engine-staff';
    $breadcrumbs = [
        ['label' => 'Engine', 'href' => '/engine'],
        ['label' => 'Staff']
    ];
    
    ob_start();
    include VIEWS_PATH . '/engine/staff.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderEngineBranding(Auth $auth): void {
    if (!$auth->isOwner()) {
        header('Location: /dashboard');
        exit;
    }
    
    $handler = new \HotelOS\Handlers\EngineHandler();
    $branding = $handler->getBrandingAssets();
    
    $title = 'Branding Engine';
    $currentRoute = 'engine-branding';
    $breadcrumbs = [
        ['label' => 'Engine', 'href' => '/engine'],
        ['label' => 'Branding']
    ];
    
    ob_start();
    include VIEWS_PATH . '/engine/branding.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderEngineAudit(Auth $auth): void {
    if (!$auth->isOwner()) {
        header('Location: /dashboard');
        exit;
    }
    
    $handler = new \HotelOS\Handlers\EngineHandler();
    $logs = $handler->getEngineLogs(
        $_GET['from'] ?? null,
        $_GET['to'] ?? null,
        isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
        $_GET['action'] ?? null,
        $_GET['risk'] ?? null
    );
    $staffList = $handler->getStaffList();
    
    $title = 'Audit & Forensics';
    $currentRoute = 'engine-audit';
    $breadcrumbs = [
        ['label' => 'Engine', 'href' => '/engine'],
        ['label' => 'Audit']
    ];
    
    ob_start();
    include VIEWS_PATH . '/engine/audit.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderEngineSetup(Auth $auth): void {
    if (!$auth->isOwner()) {
        header('Location: /dashboard');
        exit;
    }
    
    $handler = new \HotelOS\Handlers\EngineHandler();
    $hotelSetup = $handler->getHotelSetup();
    
    $title = 'Setup Engine';
    $currentRoute = 'engine-setup';
    $breadcrumbs = [
        ['label' => 'Engine', 'href' => '/engine'],
        ['label' => 'Setup']
    ];
    
    ob_start();
    include VIEWS_PATH . '/engine/setup.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderEngineBills(Auth $auth): void {
    if (!$auth->isOwner()) {
        header('Location: /dashboard');
        exit;
    }
    
    $title = 'Bill Modification';
    $currentRoute = 'engine-bills';
    $breadcrumbs = [
        ['label' => 'Engine', 'href' => '/engine'],
        ['label' => 'Bills']
    ];
    
    ob_start();
    include VIEWS_PATH . '/engine/bills.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

function renderEngineFinance(Auth $auth): void {
    if (!$auth->isOwner()) {
        header('Location: /dashboard');
        exit;
    }
    
    $handler = new \HotelOS\Handlers\EngineHandler();
    $hotelSetup = $handler->getHotelSetup();
    
    $title = 'Financial Override';
    $currentRoute = 'engine-finance';
    $breadcrumbs = [
        ['label' => 'Engine', 'href' => '/engine'],
        ['label' => 'Finance']
    ];
    
    ob_start();
    include VIEWS_PATH . '/engine/finance.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/app.php';
}

// ============================================
// Registration Handler Functions
// ============================================

function renderRegisterPage(Auth $auth): void
{
    $error = null;
    $csrfToken = $auth->csrfToken();
    
    if (isset($_GET['error'])) {
        $error = match($_GET['error']) {
            'exists' => 'This email is already registered. Please login instead.',
            'invalid' => 'Invalid registration data. Please check all fields.',
            'failed' => 'Registration failed. Please try again.',
            default => 'An error occurred. Please try again.'
        };
    }
    
    $title = 'Register';
    $bodyClass = 'page-register';
    
    ob_start();
    include VIEWS_PATH . '/auth/register.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/base.php';
}

function handleRegisterForm(Auth $auth): void
{
    // Get form data
    $data = [
        'hotel_name' => trim($_POST['hotel_name'] ?? ''),
        'owner_first_name' => trim($_POST['owner_first_name'] ?? ''),
        'owner_last_name' => trim($_POST['owner_last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'city' => trim($_POST['city'] ?? 'Mumbai'),
        'state' => trim($_POST['state'] ?? 'Maharashtra')
    ];
    
    // Initialize registration handler
    $handler = new \HotelOS\Handlers\RegistrationHandler();
    $result = $handler->registerOwner($data);
    
    if ($result['success']) {
        // Auto-login the new owner
        $loginResult = $auth->attempt($data['email'], $data['password']);
        
        if ($loginResult['success']) {
            // Redirect to dashboard with welcome message
            $_SESSION['flash_success'] = 'Welcome to HotelOS! Your 14-day free trial has started.';
            header('Location: /dashboard');
        } else {
            // Registration succeeded but login failed - rare edge case
            header('Location: /login?registered=1');
        }
    } else {
        // Registration failed - redirect back with error
        $errorCode = 'failed';
        if (str_contains($result['message'], 'already registered')) {
            $errorCode = 'exists';
        } elseif (str_contains($result['message'], 'required')) {
            $errorCode = 'invalid';
        }
        
        header('Location: /register?error=' . $errorCode);
    }
    exit;
}

