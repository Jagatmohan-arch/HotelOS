<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'mbstring' => extension_loaded('mbstring'),
        'openssl' => extension_loaded('openssl'),
        'json' => extension_loaded('json'),
        'ctype' => extension_loaded('ctype'),
    ],
    'files' => [
        'env_exists' => file_exists(__DIR__ . '/../.env'),
        'vendor_autoload_exists' => file_exists(__DIR__ . '/../vendor/autoload.php'),
        'core_auth_exists' => file_exists(__DIR__ . '/../core/Auth.php'),
    ],
    'env_vars' => [],
    'db_connection' => 'not_attempted',
    'errors' => []
];

// Try to load .env manually if vendor autoload isn't working or just to check content
try {
    $envPath = __DIR__ . '/../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Redact sensitive info
            if (in_array($name, ['DB_PASSWORD', 'APP_KEY', 'WHATSAPP_API_KEY', 'DB_USERNAME', 'DB_DATABASE', 'DB_HOST'])) {
                $results['env_vars'][$name] = 'EXISTS (Length: ' . strlen($value) . ')';
            } else {
                $results['env_vars'][$name] = $value;
            }
            
            // manually set for current script execution if not loaded
            if (!getenv($name)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    } else {
        $results['errors'][] = '.env file not found at ' . $envPath;
    }
} catch (Throwable $e) {
    $results['errors'][] = 'Error reading .env: ' . $e->getMessage();
}

// Attempt Database Connection
try {
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
    $db   = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');
    $user = getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? '');
    $pass = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    $results['db_connection'] = 'success';
    
    // Test a reliable query
    $stmt = $pdo->query("SELECT 1");
    $results['db_query_test'] = $stmt->fetchColumn();

} catch (Throwable $e) {
    $results['db_connection'] = 'failed';
    $results['db_error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
