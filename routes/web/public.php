<?php
/**
 * HotelOS - Public Booking Routes
 * 
 * MODULE 1: DIRECT BOOKING ENGINE
 * Routes for the public facing booking page.
 * No Authentication Required.
 */

declare(strict_types=1);

use HotelOS\Handlers\DirectBookingHandler;

function handleWebPublicRoutes(string $requestUri, string $requestMethod): bool
{
    // Route: /book/{slug} - Landing Page
    if (preg_match('#^/book/([a-zA-Z0-9-_]+)$#', $requestUri, $matches)) {
        $slug = $matches[1];
        $handler = new DirectBookingHandler();
        $hotel = $handler->resolveHotel($slug);

        if (!$hotel) {
            http_response_code(404);
            echo "<h1>Hotel Not Found</h1><p>The hotel you are looking for does not exist or is inactive.</p>";
            return true;
        }

        // Load data for view
        $branding = $handler->getBranding((int)$hotel['id']);
        $roomTypes = $handler->getPublicRoomTypes((int)$hotel['id']);

        // Render Public View
        // We will create this view file next
        require_once __DIR__ . '/../../views/public/booking_engine.php';
        return true;
    }

    // Route: POST /book/{slug}/submit - Handle Form
    if (preg_match('#^/book/([a-zA-Z0-9-_]+)/submit$#', $requestUri, $matches) && $requestMethod === 'POST') {
        $slug = $matches[1];
        $handler = new DirectBookingHandler();
        $hotel = $handler->resolveHotel($slug);

        if (!$hotel) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Hotel not found']);
            return true;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $handler->processBooking($input);
        
        header('Content-Type: application/json');
        echo json_encode($result);
        return true;
    }

    return false;
}
