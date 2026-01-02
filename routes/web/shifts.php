<?php
/**
 * HotelOS - Shift Routes
 * 
 * Extracted from public/index.php
 * Handles staff shift management
 */

declare(strict_types=1);

use HotelOS\Core\Auth;

function handleWebShiftRoutes(string $requestUri, string $requestMethod, Auth $auth): bool
{
    switch ($requestUri) {
        case '/shifts':
            renderShiftsPage($auth);
            return true;

        case '/shifts/start':
            if ($requestMethod === 'POST') {
                handleShiftStart($auth);
                return true; // execution stops in handleShiftStart usually, but return true if handled
            }
            break;

        case '/shifts/end':
            if ($requestMethod === 'POST') {
                handleShiftEnd($auth);
                return true;
            }
            break;

        case '/shifts/ledger/add':
            if ($requestMethod === 'POST') {
                handleLedgerAdd($auth);
                return true;
            }
            break;
    }

    return false;
}
