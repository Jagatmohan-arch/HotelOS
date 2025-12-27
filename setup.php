<?php
/**
 * HotelOS - Initial Setup Script
 * 
 * Run this ONCE after importing schema.sql
 * It will update the admin password with a proper hash
 * 
 * URL: https://hotelos.needkit.in/setup.php
 * 
 * DELETE THIS FILE AFTER RUNNING!
 */

declare(strict_types=1);

// Configuration
require_once __DIR__ . '/config/db.php';

$password = 'Admin@123';
$email = 'admin@hotelos.in';
$message = '';
$success = false;

try {
    $db = getDB();
    
    if ($db === null) {
        throw new Exception('Database connection failed');
    }
    
    // Add missing columns if they don't exist
    $alterCommands = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) NULL",
    ];
    
    foreach ($alterCommands as $sql) {
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            // Ignore "column already exists" errors
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                throw $e;
            }
        }
    }
    
    // Generate proper password hash
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update admin user
    $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
    $stmt->execute([
        'hash' => $hash,
        'email' => $email
    ]);
    
    $affected = $stmt->rowCount();
    
    if ($affected > 0) {
        $success = true;
        $message = "✅ Password hash updated successfully!<br><br>";
        $message .= "📧 Email: <strong>{$email}</strong><br>";
        $message .= "🔑 Password: <strong>{$password}</strong><br><br>";
        $message .= "⚠️ <span style='color:red;'>DELETE THIS FILE NOW!</span>";
    } else {
        $message = "❌ User not found. Make sure you imported schema.sql first.";
    }
    
} catch (Exception $e) {
    $message = "❌ Error: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelOS Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
        }
        h1 { color: #22d3ee; margin-bottom: 20px; }
        p { line-height: 1.8; }
        .success { color: #34d399; }
        .error { color: #f87171; }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #22d3ee, #06b6d4);
            color: #0f172a;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        a:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="card">
        <h1>🏨 HotelOS Setup</h1>
        <p class="<?= $success ? 'success' : 'error' ?>">
            <?= $message ?>
        </p>
        <?php if ($success): ?>
        <a href="/">Go to Login →</a>
        <?php endif; ?>
    </div>
</body>
</html>
