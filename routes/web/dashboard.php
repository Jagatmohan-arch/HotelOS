<?php
/**
 * HotelOS - Dashboard Routes
 * 
 * Extracted from public/index.php
 */

declare(strict_types=1);

use HotelOS\Core\Auth;

function handleWebDashboardRoutes(string $requestUri, string $requestMethod, Auth $auth): bool
{
    if ($requestUri === '/dashboard') {
        renderDashboard($auth);
        return true;
    }
    
    return false;
}
