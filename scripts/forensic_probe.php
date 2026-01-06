<?php
/**
 * HotelOS Forensic Probe - SINGLE FILE DIAGNOSTIC TOOL
 * 
 * USAGE: /scripts/forensic_probe.php?key=YOUR_SECRET_KEY&action=[files|db|env]
 * 
 * INTENT: 
 * 1. Scan File System (Permissions, Owners, Dates) - Simulates cPanel File Manager
 * 2. Scan Database Schema (Tables, Columns, Indexes) - Simulates phpMyAdmin Structure View
 * 3. Environment Check (PHP Version, Extensions, Writable Paths)
 * 
 * SECURITY: Standalone. Does NOT load Core. Hardcoded Fallback Key for inspection.
 */

// --- CONFIGURATION ---
// We use a temporary hardcoded key because .env loading might be broken
$ACCESS_KEY = 'ForensicScan2026'; 

// --- HEADERS ---
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Return JSON errors only

// --- AUTHENTICATION ---
if (($_GET['key'] ?? '') !== $ACCESS_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Access Denied']);
    exit;
}

// --- HELPER FUNCTIONS ---
function formatBytes($bytes, $precision = 2) { 
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}

function scanDirectory($dir, $depth = 0, $maxDepth = 3) {
    if ($depth > $maxDepth) return ['type' => 'dir_pruned'];
    
    $result = [];
    $files = scandir($dir);
    
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        if ($f === '.git' || $f === 'node_modules') continue; // Skip huge folders
        
        $path = $dir . '/' . $f;
        $info = [
            'name' => $f,
            'perms' => substr(sprintf('%o', fileperms($path)), -4),
            'size' => is_file($path) ? formatBytes(filesize($path)) : '-',
            'mtime' => date('Y-m-d H:i:s', filemtime($path)),
        ];

        if (is_dir($path)) {
            $info['type'] = 'dir';
            $info['children'] = scanDirectory($path, $depth + 1, $maxDepth);
        } else {
            $info['type'] = 'file';
        }
        $result[] = $info;
    }
    return $result;
}

function getDatabaseConnection() {
    // Try to parse .env manually since autoload might be broken
    $envPath = __DIR__ . '/../.env';
    $dbConfig = [];
    
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
            if ($name) $dbConfig[trim($name)] = trim($value);
        }
    }
    
    $host = $dbConfig['DB_HOST'] ?? 'localhost';
    $name = $dbConfig['DB_DATABASE'] ?? 'hotelos';
    $user = $dbConfig['DB_USERNAME'] ?? '';
    $pass = $dbConfig['DB_PASSWORD'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return [$pdo, $name];
    } catch (PDOException $e) {
        throw new Exception("DB Connection Failed: " . $e->getMessage());
    }
}

// --- ROUTER ---

$action = $_GET['action'] ?? 'info';
$response = ['status' => 'ok', 'action' => $action];

try {
    if ($action === 'info') {
        $response['system'] = [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'script_dir' => __DIR__,
            'loaded_extensions' => get_loaded_extensions(),
            'user' => get_current_user(),
            'uid' => getmyuid(),
        ];
    }
    
    elseif ($action === 'files') {
        $rootDir = realpath(__DIR__ . '/../');
        $response['files'] = scanDirectory($rootDir);
    }
    
    elseif ($action === 'db') {
        list($pdo, $dbName) = getDatabaseConnection();
        
        // Get Tables
        $stmt = $pdo->query("SELECT table_name, engine, table_rows, data_length, create_time 
                             FROM information_schema.tables 
                             WHERE table_schema = '$dbName'");
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Detailed Table Analysis
        foreach ($tables as &$table) {
            $tableName = $table['table_name'];
            
            // Get Columns
            $colStmt = $pdo->query("SELECT column_name, column_type, is_nullable, column_key, extra 
                                    FROM information_schema.columns 
                                    WHERE table_schema = '$dbName' AND table_name = '$tableName'");
            $table['columns'] = $colStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get Indexes
            $idxStmt = $pdo->query("SHOW INDEX FROM `$tableName`");
            $table['indexes'] = $idxStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $response['database'] = [
            'name' => $dbName,
            'tables' => $tables
        ];
    }
    
    elseif ($action === 'env_check') {
        $filesToCheck = [
            '../.env',
            '../vendor/autoload.php',
            '../public/index.php',
            '../core/Database.php'
        ];
        
        $results = [];
        foreach ($filesToCheck as $f) {
            $path = __DIR__ . '/' . $f;
            $res = [
                'path' => $f,
                'exists' => file_exists($path),
                'readable' => is_readable($path),
                'size' => file_exists($path) ? filesize($path) : 0,
            ];
            // Read first line of .env to verify it's not binary garbage
            if ($f === '../.env' && $res['readable']) {
                $content = file_get_contents($path, false, null, 0, 50);
                $res['preview'] = substr($content, 0, 20) . '...';
            }
            $results[] = $res;
        }
        $response['checks'] = $results;
    }

} catch (Throwable $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    $response['trace'] = $e->getTraceAsString();
}

echo json_encode($response, JSON_PRETTY_PRINT);
