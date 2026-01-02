<?php
/**
 * HotelOS - Booking Routes
 * 
 * Extracted from public/index.php
 * Handles booking management, calendar, and invoices
 */

declare(strict_types=1);

use HotelOS\Core\Auth;

function handleWebBookingRoutes(string $requestUri, string $requestMethod, Auth $auth): bool
{
    // Regex Routes first
    
    // Checkout Page (Mobile Flow)
    if (preg_match('#^/bookings/(\d+)/checkout$#', $requestUri, $matches)) {
        requireAuth();
        renderCheckoutPage($auth, (int)$matches[1]);
        return true;
    }

    // Invoice Page
    if (preg_match('#^/bookings/(\d+)/invoice$#', $requestUri, $matches)) {
        if (!$auth->check()) { header('Location: /'); exit; }
        renderInvoicePage($auth, (int)$matches[1]);
        return true;
    }
    
    // Cancel booking: /bookings/{id}/cancel (POST)
    if (preg_match('#^/bookings/(\d+)/cancel$#', $requestUri, $matches) && $requestMethod === 'POST') {
        if (!$auth->check()) { header('Location: /'); exit; }
        handleBookingCancel($auth, (int)$matches[1]);
        return true;
    }

    // Exact Match Routes
    switch ($requestUri) {
        case '/bookings':
            if (!$auth->check()) { header('Location: /login'); exit; }
            renderBookingsPage($auth);
            return true;
            
        case '/bookings/create':
            if (!$auth->check()) { header('Location: /login'); exit; }
            renderBookingCreatePage($auth);
            return true;

        case '/bookings/calendar':
            if (!$auth->check()) { header('Location: /login'); exit; }
            renderCalendarPage($auth);
            return true;
    }

    return false;
}
