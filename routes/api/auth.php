<?php
/**
 * HotelOS - API Auth Routes
 * 
 * Extracted from public/index.php
 * These routes handle JSON API authentication
 * 
 * Routes:
 * - POST /api/auth/login
 * - POST /api/auth/logout
 */

declare(strict_types=1);

use HotelOS\Core\Auth;

/**
 * Register auth API routes
 * 
 * @param string $requestUri Current request URI
 * @param string $requestMethod HTTP method
 * @return bool True if route was handled
 */
function handleApiAuthRoutes(string $requestUri, string $requestMethod): bool
{
    // POST /api/auth/login
    if ($requestUri === '/api/auth/login' && $requestMethod === 'POST') {
        apiAuthLogin();
        return true;
    }
    
    // POST /api/auth/logout
    if ($requestUri === '/api/auth/logout' && $requestMethod === 'POST') {
        apiAuthLogout();
        return true;
    }
    
    return false;
}

/**
 * Handle login API request
 */
function apiAuthLogin(): void
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

/**
 * Handle logout API request
 */
function apiAuthLogout(): void
{
    $auth = Auth::getInstance();
    $auth->logout();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully', 'redirect' => '/']);
}
