<?php
/**
 * HotelOS - Database Connection
 * 
 * Secure PDO connection with error handling.
 * NEVER expose raw errors to users.
 */

declare(strict_types=1);

/**
 * Get PDO database connection (Singleton pattern)
 * 
 * @return PDO|null Returns PDO instance or null on failure
 */
function getDB(): ?PDO
{
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // ============================================
    // DATABASE CREDENTIALS
    // ============================================
    $config = [
        'host'     => getenv('DB_HOST'),
        'dbname'   => getenv('DB_NAME'),
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'), // Secured
        'charset'  => 'utf8mb4',
    ];
    
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['dbname'],
        $config['charset']
    );
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];
    
    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log the actual error (server-side only)
        error_log('[HotelOS DB Error] ' . $e->getMessage());
        return null;
    }
}

/**
 * Test database connection
 * 
 * @return bool True if connected, false otherwise
 */
function testDBConnection(): bool
{
    $db = getDB();
    return $db !== null;
}
