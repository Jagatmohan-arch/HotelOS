<?php
/**
 * HotelOS - Daily Cleanup Cron Script
 * 
 * Usage: php scripts/cron_cleanup.php
 * Frequency: Daily (e.g., 03:00 AM)
 * 
 * cPanel Setup:
 * 0 3 * * * /usr/local/bin/php /home/username/public_html/scripts/cron_cleanup.php >> /home/username/logs/cron.log 2>&1
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('Access denied. CLI only.');
}

// Define paths and bootstrap
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('HANDLERS_PATH', BASE_PATH . '/handlers');
define('LOGS_PATH', BASE_PATH . '/logs');

// Load .env
$envPath = BASE_PATH . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if (!getenv($name)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
}

require_once CORE_PATH . '/Database.php';

echo "[".date('Y-m-d H:i:s')."] Starting cleanup...\n";

try {
    $db = \HotelOS\Core\Database::getInstance();
    
    // 1. Cleanup Old Sessions (> 30 Days)
    $sessionsDeleted = $db->execute(
        "DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))",
        [],
        enforceTenant: false
    );
    echo " - Deleted $sessionsDeleted expired sessions.\n";

    // 2. Cleanup Old Audit Logs (> 90 Days)
    $logsDeleted = $db->execute(
        "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)",
        [],
        enforceTenant: false
    );
    echo " - Deleted $logsDeleted old audit logs.\n";
    
    // 3. Cleanup Expired Password Reset Tokens (> 1 Hour)
    $tokensCleared = $db->execute(
        "UPDATE users SET reset_token = NULL, reset_token_expires_at = NULL WHERE reset_token_expires_at < NOW()",
        [],
        enforceTenant: false
    );
    echo " - Cleaned up $tokensCleared expired password reset tokens.\n";

    echo "[".date('Y-m-d H:i:s')."] Cleanup completed successfully.\n";

} catch (Exception $e) {
    echo "[".date('Y-m-d H:i:s')."] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
