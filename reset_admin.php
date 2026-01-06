<?php
// reset_admin.php
// Run this to create/reset your Admin Account
// DELETE THIS FILE AFTER USE

require_once __DIR__ . '/public/index.php';

use HotelOS\Core\Database;
use HotelOS\Core\Auth;

// Prevent unauthorized execution if possible (basic security)
// In production, you should delete this file immediately.

try {
    $db = Database::getInstance();
    
    $email = 'deployer@needkit.in';
    $password = 'jm@HS10$$';
    $firstName = 'Deployer';
    $lastName = 'Admin';
    $role = 'owner';
    
    // Hash password
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    
    // Check if user exists
    $existing = $db->queryOne("SELECT id FROM users WHERE email = :email", ['email' => $email]);
    
    if ($existing) {
        // Update
        $db->execute(
            "UPDATE users SET password_hash = :pass, role = :role, email_verified_at = NOW() WHERE email = :email",
            ['pass' => $hash, 'role' => $role, 'email' => $email]
        );
        echo "<h1>Success!</h1>";
        echo "<p>Updated password & verified email for <strong>$email</strong>.</p>";
    } else {
        // Create (needs tenant)
        // Check for default tenant
        $tenant = $db->queryOne("SELECT id FROM tenants LIMIT 1");
        if (!$tenant) {
            // Create Tenant
            $db->execute("INSERT INTO tenants (name, domain, plan) VALUES ('Default Hotel', 'hotelos.needkit.in', 'enterprise')");
            $tenantId = $db->lastInsertId();
        } else {
            $tenantId = $tenant['id'];
        }
        
        $db->execute(
            "INSERT INTO users (tenant_id, email, password_hash, first_name, last_name, role, email_verified_at) 
             VALUES (:tid, :email, :pass, :fname, :lname, :role, NOW())",
            [
                'tid' => $tenantId,
                'email' => $email,
                'pass' => $hash,
                'fname' => $firstName,
                'lname' => $lastName,
                'role' => $role
            ]
        );
        echo "<h1>Success!</h1>";
        echo "<p>Created new account: <strong>$email</strong></p>";
    }
    
    echo "<p>Password set to: <code>" . htmlspecialchars($password) . "</code></p>";
    echo "<a href='/'>Go to Login</a>";
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><strong>Possible Cause:</strong> Database not connected. Did you configure .env?</p>";
}
