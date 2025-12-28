<?php
// debug_probe.php
// UPLOAD THIS FILE TO YOUR PUBLIC_HTML FOLDER AND VISIT IT

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>HotelOS Debug Probe</h1>";
echo "<p>Checking server environment...</p>";

// 1. PHP Version
echo "<h2>1. PHP Version</h2>";
echo "Current PHP Version: " . phpversion() . "<br>";
if (version_compare(phpversion(), '8.0.0', '<')) {
    echo "<strong style='color:red'>ERROR: HotelOS requires PHP 8.0 or higher.</strong>";
} else {
    echo "<span style='color:green'>OK</span>";
}

// 2. Directory Structure
echo "<h2>2. Directory Structure</h2>";
$dirs = ['config', 'core', 'handlers', 'views', 'logs'];
$root = __DIR__;
echo "Root Path: $root<br>";

foreach ($dirs as $dir) {
    if (is_dir($root . '/' . $dir)) {
        echo "$dir: <span style='color:green'>Found</span><br>";
    } else {
        echo "$dir: <strong style='color:red'>MISSING</strong> (Check upload)<br>";
    }
}

// 3. .env Check
echo "<h2>3. Configuration (.env)</h2>";
if (file_exists($root . '/.env')) {
    echo ".env file: <span style='color:green'>Found</span><br>";
    
    // Test parsing
    $lines = file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name !== NULL && $value !== NULL) {
            putenv(trim($name) . "=" . trim($value));
        }
    }
    
} else {
    echo ".env file: <strong style='color:red'>MISSING</strong>. Did you rename .env.example?<br>";
}

// 4. Database Connection
echo "<h2>4. Database Connection</h2>";
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

echo "Host: $host<br>";
echo "DB Name: $db<br>";
echo "User: $user<br>";
echo "Pass: " . ($pass ? "********" : "EMPTY") . "<br>";

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    echo "<strong style='color:green'>SUCCESS: Connected to Database!</strong><br>";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables Found: " . count($tables) . "<br>";
    if (in_array('users', $tables)) {
        echo "users table: <span style='color:green'>OK</span><br>";
    } else {
        echo "users table: <strong style='color:red'>MISSING</strong> (Did you import schema.sql?)<br>";
    }
    
} catch (PDOException $e) {
    echo "<strong style='color:red'>CONNECTION FAILED: " . $e->getMessage() . "</strong>";
}

echo "<h2>5. Session Test</h2>";
session_start();
$_SESSION['test'] = 'works';
if ($_SESSION['test'] === 'works') {
    echo "Sessions: <span style='color:green'>Working</span>";
} else {
    echo "Sessions: <strong style='color:red'>Failed</strong>";
}

echo "<br><br><hr>End of Probe.";
