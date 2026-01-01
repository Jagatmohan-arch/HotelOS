<?php
/**
 * HotelOS - Daily Cleanup Cron Script
 * 
 * Usage: php scripts/cron_cleanup.php
 * Frequency: Daily (e.g., 03:00 AM)
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('Access denied. CLI only.');
}

// Define paths
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/core/Database.php';

// Load config (Simulated for this script if not using DotEnv)
// In a real scenario, we'd load .env here. 
// Assuming Database class handles connection via its own config/methods or we verify credentials.
// If Database.php relies on $_ENV, we might need to load it. 
// Let's assume standard config/database.php exists or Database connects successfully.

echo "[".date('Y-m-d H:i:s')."] Starting cleanup...\n";

try {
    $db = \HotelOS\Core\Database::getInstance();
    
    // 1. Cleanup Old Sessions (> 30 Days)
    $stmt = $db->prepare("DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $sessionsDeleted = $stmt->rowCount();
    echo " - Deleted $sessionsDeleted expired sessions.\n";

    // 2. Cleanup Old Audit Logs (> 90 Days)
    $stmt = $db->prepare("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $stmt->execute();
    $logsDeleted = $stmt->rowCount();
    echo " - Deleted $logsDeleted old audit logs.\n";
    
    // 3. Cleanup Expired Password Reset Tokens (> 1 Hour)
    $stmt = $db->prepare("UPDATE users SET reset_token = NULL, reset_token_expires_at = NULL WHERE reset_token_expires_at < NOW()");
    $stmt->execute();
    echo " - Cleaned up expired password reset tokens.\n";

    echo "[".date('Y-m-d H:i:s')."] Cleanup completed successfully.\n";

} catch (Exception $e) {
    echo "[".date('Y-m-d H:i:s')."] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
