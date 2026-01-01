<?php
// cpanel_debug.php
// Setup error reporting immediately
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>HotelOS Diagnostics</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// 1. Check PHP Version
echo "<h2>1. PHP Version</h2>";
echo "Current Version: " . phpversion() . "<br>";
if (version_compare(phpversion(), '8.0.0', '<')) {
    echo "<strong style='color:red'>FAIL: PHP 8.0+ required.</strong>";
} else {
    echo "<strong style='color:green'>PASS</strong>";
}

// 2. Check File Structure
echo "<h2>2. File Structure</h2>";
$basePath = dirname(__DIR__);
echo "Base Path: $basePath<br>";

$files = [
    '/.env',
    '/core/Database.php',
    '/core/Auth.php',
    '/handlers/BookingHandler.php'
];

foreach ($files as $file) {
    if (file_exists($basePath . $file)) {
        echo "$file: <span style='color:green'>FOUND</span><br>";
    } else {
        echo "$file: <span style='color:red'>MISSING</span><br>";
    }
}

// 3. Environment Environment
echo "<h2>3. Environment Headers</h2>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";

// 4. Test Database Connection
echo "<h2>4. Database Test</h2>";

if (file_exists($basePath . '/.env')) {
    $lines = file($basePath . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name == 'DB_HOST') $host = trim($value);
        if ($name == 'DB_NAME') $dbname = trim($value);
        if ($name == 'DB_USER') $user = trim($value);
        if ($name == 'DB_PASS') $pass = trim($value);
    }
    
    echo "Attempting connection to <strong>$dbname</strong> on <strong>$host</strong> with user <strong>$user</strong>...<br>";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<strong style='color:green'>SUCCESS: Connected to Database!</strong><br>";
        
        // Check tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . count($tables) . "<br>";
        echo "List: " . implode(", ", $tables);
        
    } catch (PDOException $e) {
        echo "<strong style='color:red'>CONNECTION FAILED:</strong> " . $e->getMessage();
    }
} else {
    echo "<strong style='color:red'>SKIPPED: .env file missing. Cannot test DB.</strong>";
}

// 5. Check Permissions
echo "<h2>5. Writable Directories</h2>";
$dirs = [
    '/public/uploads',
    '/logs' // Assuming logs is in root, if not, adjust
];

foreach ($dirs as $dir) {
    $fullPath = $basePath . $dir;
    if (is_writable($fullPath)) {
         echo "$dir: <span style='color:green'>WRITABLE</span><br>";
    } else {
         echo "$dir: <span style='color:red'>NOT WRITABLE (Is: " . substr(sprintf('%o', fileperms($fullPath)), -4) . ")</span><br>";
    }
}

echo "<hr><p>End of Diagnostics</p>";
