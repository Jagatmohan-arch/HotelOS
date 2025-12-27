<?php
/**
 * Password Hash Generator for HotelOS
 * 
 * Run this script ONCE to generate proper Argon2ID hash
 * Then copy the hash to schema.sql and delete this file
 * 
 * Usage: php generate_hash.php
 */

$password = 'Admin@123';

// Generate Argon2ID hash (same as Auth.php uses)
$hash = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,  // 64 MB
    'time_cost' => 4,
    'threads' => 1
]);

echo "===========================================\n";
echo "HotelOS Password Hash Generator\n";
echo "===========================================\n\n";
echo "Password: {$password}\n";
echo "Algorithm: Argon2ID\n\n";
echo "Generated Hash:\n";
echo $hash . "\n\n";

// Verify it works
if (password_verify($password, $hash)) {
    echo "✅ Verification: PASSED\n";
    echo "\nCopy this hash to schema.sql INSERT statement.\n";
} else {
    echo "❌ Verification: FAILED\n";
}

echo "===========================================\n";
