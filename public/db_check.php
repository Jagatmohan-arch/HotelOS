<?php
// db_check.php - Diagnostics for HotelOS
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>HotelOS Database Health Check (Standard Mode)</h1>";

$envPath = null;
if (file_exists('../.env')) {
    $envPath = '../.env';
} elseif (file_exists('.env')) {
    $envPath = '.env';
} else {
    // Attempt to look in common cPanel paths
    if (file_exists('/home/uplfveim/public_html/.env')) $envPath = '/home/uplfveim/public_html/.env';
    elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . '/../.env')) $envPath = $_SERVER['DOCUMENT_ROOT'] . '/../.env';
}

if (!$envPath) {
    die("<h3 style='color:red'>CRITICAL: .env file NOT FOUND anywhere.</h3><p>Current Dir: " . __DIR__ . "</p>");
} else {
    echo "<p>Loaded configuration from: $envPath</p>";
}

try {
    $env = parse_ini_file($envPath);
} catch (Throwable $e) {
    die("<h3 style='color:red'>Env Parse Error: " . $e->getMessage() . "</h3>");
}

$host = $env['DB_HOST'] ?? 'localhost';
$db   = $env['DB_NAME'] ?? 'uplfveim_hotelos';
$user = $env['DB_USER'] ?? 'uplfveim';
$pass = $env['DB_PASS'] ?? '';

// Debugging Output (Masked Password)
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>Database: $db</li>";
echo "<li>User: $user</li>";
echo "<li>Password Length: " . strlen($pass) . " (Should be > 0)</li>";
echo "</ul>";

echo "Connecting to database '<b>$db</b>' on '$host'...<br>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h3 style='color:green'>Database Connection Successful!</h3>";
} catch (PDOException $e) {
    die("<h3 style='color:red'>Connection Failed: " . $e->getMessage() . "</h3>");
}

// 2. Define Required Schema
$requirements = [
    'users' => [
        'email_verified_at', // New in V4
        'email_verification_token', // New in V4
        'is_active',
        'pin_hash',
        'locked_until'
    ],
    'tenants' => [
        'billing_status',
        'trial_ends_at',
        'plan'
    ],
    'sessions' => [
        'id', 'payload' // Essential for login
    ]
];

// 3. Check Tables & Columns
echo "<h2>Schema Verification</h2>";
$failCount = 0;

foreach ($requirements as $table => $columns) {
    echo "Checking table <b>$table</b>... ";
    
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missing = [];
        foreach ($columns as $col) {
            if (!in_array($col, $existingColumns)) {
                $missing[] = $col;
            }
        }
        
        if (empty($missing)) {
            echo "<span style='color:green'>OK</span><br>";
        } else {
            echo "<span style='color:red'>MISSING COLUMNS: " . implode(', ', $missing) . "</span><br>";
            $failCount++;
        }
        
    } catch (PDOException $e) {
        echo "<span style='color:red'>TABLE MISSING!</span><br>";
        $failCount++;
    }
}

// 4. Check Data Integrity
echo "<h2>Data Integrity Check</h2>";
try {
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users found: $userCount<br>";
    
    if ($userCount > 0) {
        $verifiedCount = $pdo->query("SELECT COUNT(*) FROM users WHERE email_verified_at IS NOT NULL")->fetchColumn();
        echo "Verified Users: $verifiedCount<br>";
        
        if ($verifiedCount == 0 && $userCount > 0) {
            echo "<strong style='color:orange'>WARNING: No users are verified. Login will block everyone.</strong><br>";
            echo "<a href='?fix_verification=1' style='background:blue;color:white;padding:5px;'>CLICK TO AUTO-VERIFY ALL USERS</a><br>";
        }
    }
} catch (Exception $e) {}

// 5. Auto-Fix Action
if (isset($_GET['fix_verification']) && $_GET['fix_verification'] == 1) {
    try {
        $pdo->exec("UPDATE users SET email_verified_at = NOW() WHERE email_verified_at IS NULL");
        echo "<h3 style='color:green'>SUCCESS: All users marked as verified! Try logging in now.</h3>";
    } catch (Exception $e) {
        echo "<h3 style='color:red'>Fix Failed: " . $e->getMessage() . "</h3>";
    }
}

if ($failCount > 0) {
    echo "<hr><h2 style='color:red'>Result: $failCount Critical Issues Found</h2>";
    echo "<p>Please ensure you imported <code>database/schema.sql</code> and ALL migrations.</p>";
} else {
    echo "<hr><h2 style='color:green'>Result: System Ready</h2>";
}
