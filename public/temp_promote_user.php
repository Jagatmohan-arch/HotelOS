<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

use HotelOS\Core\Database;

try {
    $db = Database::getInstance();
    $email = 'shifttest_v6_4829@example.com';
    
    // Check current role
    $user = $db->queryOne("SELECT * FROM users WHERE email = :email", ['email' => $email], enforceTenant: false);
    
    if (!$user) {
        die("User not found: $email");
    }
    
    echo "Current Role: " . $user['role'] . "\n";
    
    // Update role
    $db->execute("UPDATE users SET role = 'manager' WHERE email = :email", ['email' => $email], enforceTenant: false);
    
    // Verify
    $user = $db->queryOne("SELECT * FROM users WHERE email = :email", ['email' => $email], enforceTenant: false);
    echo "New Role: " . $user['role'] . "\n";
    echo "SUCCESS";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
