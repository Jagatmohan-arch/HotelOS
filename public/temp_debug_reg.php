<?php
// public/temp_debug_reg.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Define Paths & Env Loader (Copied from index.php)
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

if (!defined('PUBLIC_PATH')) {
    if (is_dir(BASE_PATH . '/public')) {
        define('PUBLIC_PATH', BASE_PATH . '/public');
    } else {
        define('PUBLIC_PATH', BASE_PATH);
    }
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config');
    define('CORE_PATH', BASE_PATH . '/core');
    define('VIEWS_PATH', BASE_PATH . '/views');
    define('HANDLERS_PATH', BASE_PATH . '/handlers');
    define('CACHE_PATH', BASE_PATH . '/cache');
    define('LOGS_PATH', BASE_PATH . '/logs');
}

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

// 2. Autoloader (Copied from index.php)
spl_autoload_register(function (string $class): void {
    $corePrefix = 'HotelOS\\Core\\';
    $coreDir = CORE_PATH . '/';
    
    if (strncmp($corePrefix, $class, strlen($corePrefix)) === 0) {
        $relativeClass = substr($class, strlen($corePrefix));
        $file = $coreDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }
    
    $handlersPrefix = 'HotelOS\\Handlers\\';
    $handlersDir = HANDLERS_PATH . '/';
    
    if (strncmp($handlersPrefix, $class, strlen($handlersPrefix)) === 0) {
        $relativeClass = substr($class, strlen($handlersPrefix));
        $file = $handlersDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }
});

use HotelOS\Handlers\RegistrationHandler;

echo "<h1>Debug Registration</h1>";
echo "BASE_PATH: " . BASE_PATH . "<br>";

$timestamp = time();
$data = [
    'hotel_name' => "Debug Hotel $timestamp",
    'owner_first_name' => "Debug",
    'owner_last_name' => "User",
    'email' => "debug_{$timestamp}@hotelos.test",
    'phone' => "1234567890",
    'password' => "Password@123",
    'city' => "DebugCity",
    'state' => "DebugState",
    'address' => "123 Debug Lane"
];

echo "<pre>Attempting registration with data:\n";
print_r($data);
echo "</pre>";

try {
    $handler = new RegistrationHandler();
    echo "Handler instantiated.<br>";
    $result = $handler->registerOwner($data);
    
    echo "<h2>Result:</h2>";
    echo "<pre>";
    var_dump($result);
    echo "</pre>";
    
    if (isset($result['success']) && !$result['success']) {
        echo "<h3>Re-running logic manually to expose exception...</h3>";
        
        $db = \HotelOS\Core\Database::getInstance();
        $db->beginTransaction();
        
        // 1. Create Tenant
        $slug = "debug-hotel-$timestamp";
        $uuid = bin2hex(random_bytes(16)); // simple uuid
        
        echo "Manually Creating Tenant...<br>";
        try {
            $db->execute(
                "INSERT INTO tenants (
                    uuid, name, slug, email, phone, 
                    address_line1, city, state, pincode,
                    plan, status, trial_ends_at,
                    created_at
                ) VALUES (
                    :uuid, :name, :slug, :email, :phone,
                    :address, :city, :state, '400001',
                    :plan, :status, :trial_ends_at,
                    NOW()
                )",
                [
                    'uuid' => $uuid,
                    'name' => $data['hotel_name'],
                    'slug' => $slug,
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'plan' => 'trial',
                    'status' => 'active',
                    'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+14 days'))
                ],
                enforceTenant: false
            );
            $tenantId = (int)$db->lastInsertId();
            echo "Tenant ID: $tenantId<br>";
            
            // 2. Create User
            $userUuid = bin2hex(random_bytes(16));
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            
            echo "Manually Creating User...<br>";
            $db->execute(
                "INSERT INTO users (
                    tenant_id, uuid, email, password_hash, phone,
                    first_name, last_name, role, is_active, 
                    email_verified_at, email_verification_token,
                    created_at
                ) VALUES (
                    :tenant_id, :uuid, :email, :password_hash, :phone,
                    :first_name, :last_name, 'owner', 1, 
                    NULL, :token,
                    NOW()
                )",
                [
                    'tenant_id' => $tenantId,
                    'uuid' => $userUuid,
                    'email' => $data['email'],
                    'password_hash' => $hash,
                    'phone' => $data['phone'],
                    'first_name' => $data['owner_first_name'],
                    'last_name' => $data['owner_last_name'],
                    'token' => $token
                ],
                enforceTenant: false
            );
            $userId = (int)$db->lastInsertId();
            echo "User ID: $userId<br>";
            
            // 3. Create Default Room Types
            echo "Manually Creating Room Types...<br>";
             $defaultTypes = [
                ['name' => 'Standard Room', 'code' => 'STD', 'rate' => 2000.00],
                ['name' => 'Deluxe Room', 'code' => 'DLX', 'rate' => 3500.00],
                ['name' => 'Suite', 'code' => 'STE', 'rate' => 6000.00]
            ];
    
            foreach ($defaultTypes as $type) {
                $db->execute(
                    "INSERT INTO room_types (
                        tenant_id, name, code, base_rate, is_active, created_at
                    ) VALUES (
                        :tenant_id, :name, :code, :rate, 1, NOW()
                    )",
                    [
                        'tenant_id' => $tenantId,
                        'name' => $type['name'],
                        'code' => $type['code'],
                        'rate' => $type['rate']
                    ],
                    enforceTenant: false
                );
            }
            echo "Room types created.<br>";
            
            $db->rollback(); 
            echo "<b>Manual test successful (rolled back) - logic or specific DB constraint failure not reproduced here.</b>";

        } catch (Throwable $manualEx) {
            echo "<h3>MANUAL TEST EXCEPTION:</h3>";
            echo "<pre>";
            echo $manualEx->getMessage() . "\n";
            echo $manualEx->getTraceAsString();
            echo "</pre>";
            if (isset($db)) $db->rollback();
        }
    }
    
} catch (Throwable $e) {
    echo "<h2>TOP LEVEL EXCEPTION:</h2>";
    echo "<pre>";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
