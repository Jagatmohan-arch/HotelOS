<?php
// public/debug_login_live.php
// Self-contained implementation to debug login issues on production

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Login Debugger</h1>";

// 1. Define Paths & Env Loader (Copied from index.php for standalone execution)
if (!defined('BASE_PATH')) {
    $currentDir = __DIR__;
    if (basename($currentDir) === 'public') {
        define('BASE_PATH', dirname($currentDir));
    } else {
        if (is_dir($currentDir . '/config')) {
            define('BASE_PATH', $currentDir);
        } else {
            define('BASE_PATH', dirname($currentDir));
        }
    }
}

// Load Env Logic
if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
            if ($name !== NULL && $value !== NULL) {
                $name = trim($name);
                $value = trim($value);
                if (!getenv($name)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                }
            }
        }
    }
}

loadEnv(BASE_PATH . '/.env');

echo "Base Path: " . BASE_PATH . "<br>";

// 2. Database Connection (Manual PDO)
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

echo "<h2>Database Check</h2>";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully.<br>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// 3. Check User
$testEmail = 'prod_verify_fixed_final@hotelos.test'; 
// Use the email created in the previous successful registration
echo "<h2>Checking User: $testEmail</h2>";

$stmt = $pdo->prepare("
    SELECT u.*, t.name as tenant_name 
    FROM users u 
    JOIN tenants t ON u.tenant_id = t.id 
    WHERE u.email = :email
");
$stmt->execute(['email' => $testEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<strong style='color:red'>User Not Found!</strong><br>";
    echo "Dumping simple query on users table to see if email exists without join...<br>";
    $stmt2 = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt2->execute(['email' => $testEmail]);
    $u2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($u2) {
        echo "User exists in `users` table. ID: {$u2['id']}, Tenant ID: {$u2['tenant_id']}<br>";
        echo "Issue is likely the JOIN with tenants table.<br>";
    } else {
         echo "User does NOT exist in `users` table either.<br>";
    }
} else {
    echo "User found!<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Is Active: " . $user['is_active'] . "<br>";
    echo "Email Verified At: " . ($user['email_verified_at'] ?? 'NULL') . "<br>";
    echo "Password Hash Length: " . strlen($user['password_hash']) . "<br>";
    echo "Password Hash Start: " . substr($user['password_hash'], 0, 10) . "...<br>";
    
    // 4. Verify Password
    echo "<h2>Password Verification</h2>";
    $password = 'Password@123';
    echo "Testing password: $password<br>";
    
    $algo = password_get_info($user['password_hash']);
    echo "Hash Algo Info: " . print_r($algo, true) . "<br>";
    
    if (password_verify($password, $user['password_hash'])) {
        echo "<strong style='color:green'>Password Verify PASSED</strong><br>";
    } else {
        echo "<strong style='color:red'>Password Verify FAILED</strong><br>";
        echo "Re-hashing test password with default...<br>";
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "New Hash: $newHash<br>";
    }
}

echo "<h2>PHP Info</h2>";
echo "Version: " . phpversion() . "<br>";
echo "Argon2 Support: " . (defined('PASSWORD_ARGON2ID') ? 'Yes' : 'No') . "<br>";
