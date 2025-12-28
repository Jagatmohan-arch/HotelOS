<?php
/**
 * HotelOS - Database Configuration
 * 
 * SECURITY: This file is protected by .htaccess
 * Never expose these credentials in version control
 * 
 * For production: Set environment variables or use a .env loader
 */

declare(strict_types=1);

// Database credentials
// Production: .env required for security

return [
    'driver'    => 'mysql',
    'host'      => getenv('DB_HOST'), // Fails if .env missing
    'port'      => getenv('DB_PORT') ?: '3306',
    'database'  => getenv('DB_NAME'),
    'username'  => getenv('DB_USER'),
    'password'  => getenv('DB_PASS'),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    
    // PDO Options for production
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ],
    
    // Connection pool settings for shared hosting
    'persistent' => false, // Disable persistent connections on shared hosting
];
