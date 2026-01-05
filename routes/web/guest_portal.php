<?php

use HotelOS\Handlers\GuestPortalHandler;

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Route: GET /guest/portal
if ($requestUri === '/guest/portal' && $requestMethod === 'GET') {
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        http_response_code(403);
        die('Access Denied: Missing Token');
    }

    $handler = new GuestPortalHandler();
    $bookingId = $handler->validateToken($token);

    if (!$bookingId) {
        http_response_code(403);
        die('Access Denied: Invalid or Expired Token');
    }

    $data = $handler->getPortalData($bookingId);

    if (!$data) {
        die('Booking Data Unavailable');
    }

    require_once __DIR__ . '/../../views/guest/portal.php';
    return true; // Stop propagation
}
