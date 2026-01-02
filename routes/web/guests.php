<?php
/**
 * HotelOS - Guest Routes
 * 
 * Extracted from public/index.php
 * Handles guest management and profiles
 */

declare(strict_types=1);

use HotelOS\Core\Auth;
use HotelOS\Core\Database;

/**
 * Handle Guest routes
 */
function handleWebGuestRoutes(string $requestUri, string $requestMethod, Auth $auth): bool
{
    switch ($requestUri) {
        // ========== Guest Routes ==========
        case '/guests':
            if (!$auth->check()) { header('Location: /login'); exit; }
            renderGuestPage($auth);
            return true;
            
        case '/guests/profile':
            if (!$auth->check()) { header('Location: /login'); exit; }
            renderGuestProfilePage($auth);
            return true;

        case '/api/guests/upload-id':
            if ($requestMethod === 'POST') {
                requireApiAuth();
                $handler = new \HotelOS\Handlers\UploadHandler();
                $guestId = (int)($_POST['guest_id'] ?? 0);
                $result = $handler->uploadGuestIdPhoto($guestId, $_FILES['id_photo']);
                
                if ($result['success']) {
                    header('Location: /guests/profile?id=' . $guestId . '&tab=documents');
                } else {
                    die('Upload failed: ' . $result['error']); 
                }
                exit;
            }
            return true;
    }

    return false;
}
