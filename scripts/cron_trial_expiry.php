<?php
/**
 * HotelOS - Trial Expiry Notification Cron
 * 
 * Runs daily to:
 * 1. Notify users whose trial expires in 3 days
 * 2. Notify users whose trial expires tomorrow
 * 3. Lock accounts whose trial has expired
 * 
 * Usage: php scripts/cron_trial_expiry.php
 */

require_once __DIR__ . '/../public/index.php'; // Bootstrap

use HotelOS\Core\Database;
use HotelOS\Core\EmailService;

echo "⏰ Starting Trial Expiry Check...\n";

$db = Database::getInstance();
$emailService = EmailService::getInstance();

// 1. Get expiring trials (3 days left)
$threeDayWarn = $db->query(
    "SELECT t.id, t.name, t.email, t.trial_ends_at, u.first_name 
     FROM tenants t 
     JOIN users u ON t.id = u.tenant_id 
     WHERE t.status = 'active'
     AND t.plan = 'trial'
     AND u.role = 'owner'
     AND DATEDIFF(t.trial_ends_at, NOW()) = 3",
    [],
    enforceTenant: false
);

foreach ($threeDayWarn as $tenant) {
    echo "Sending 3-day warning to {$tenant['name']}...\n";
    $emailService->send($tenant['email'], "3 Days Left in your HotelOS Trial", "emails/trial_warning", [
        'name' => $tenant['first_name'],
        'days_left' => 3,
        'link' => getenv('APP_URL') . '/subscription/plans'
    ]);
}

// 2. Get expiring trials (1 day left)
$oneDayWarn = $db->query(
    "SELECT t.id, t.name, t.email, t.trial_ends_at, u.first_name 
     FROM tenants t 
     JOIN users u ON t.id = u.tenant_id 
     WHERE t.status = 'active'
     AND t.plan = 'trial'
     AND u.role = 'owner'
     AND DATEDIFF(t.trial_ends_at, NOW()) = 1",
    [],
    enforceTenant: false
);

foreach ($oneDayWarn as $tenant) {
    echo "Sending 1-day warning to {$tenant['name']}...\n";
    $emailService->send($tenant['email'], "Last Day of your HotelOS Trial!", "emails/trial_warning_urgent", [
        'name' => $tenant['first_name'],
        'days_left' => 1,
        'link' => getenv('APP_URL') . '/subscription/plans'
    ]);
}

// 3. Lock expired trials
$expired = $db->query(
    "SELECT id, name FROM tenants 
     WHERE status = 'active' 
     AND plan = 'trial' 
     AND trial_ends_at < NOW()",
    [],
    enforceTenant: false
);

foreach ($expired as $tenant) {
    echo "Locking expired tenant: {$tenant['name']} (ID: {$tenant['id']})\n";
    $db->execute(
        "UPDATE tenants SET status = 'locked' WHERE id = :id",
        ['id' => $tenant['id']],
        enforceTenant: false
    );
}

echo "✅ Done.\n";
