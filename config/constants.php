<?php
/**
 * HotelOS - Application Constants
 * 
 * Global configuration values for the application.
 */

declare(strict_types=1);

// ============================================
// APPLICATION
// ============================================
define('APP_NAME', 'HotelOS');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production'); // development | production

// ============================================
// URLs
// ============================================
define('BASE_URL', 'https://hotelos.needkit.in');
define('ASSETS_URL', BASE_URL . '/assets');

// ============================================
// PATHS
// ============================================
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', __DIR__);
define('VIEWS_PATH', ROOT_PATH . '/views');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('LOGS_PATH', ROOT_PATH . '/logs');

// ============================================
// TIMEZONE & LOCALE
// ============================================
define('APP_TIMEZONE', 'Asia/Kolkata');
define('APP_CURRENCY', 'INR');
define('APP_LOCALE', 'en_IN');

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// ============================================
// SESSION CONFIGURATION
// ============================================
define('SESSION_NAME', 'HOTELOS_SID');
define('SESSION_LIFETIME', 7200); // 2 hours in seconds

// ============================================
// SECURITY
// ============================================
define('CSRF_TOKEN_NAME', '_csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds

// ============================================
// GST CONFIGURATION (INDIAN TAX)
// ============================================
define('GST_THRESHOLD', 7500.00);      // Room rate threshold
define('GST_RATE_LOW', 12.00);         // For rooms < 7500
define('GST_RATE_HIGH', 18.00);        // For rooms >= 7500

// ============================================
// RBAC ROLE LEVELS
// ============================================
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_OWNER', 'owner');
define('ROLE_MANAGER', 'manager');
define('ROLE_RECEPTION', 'reception');
define('ROLE_HOUSEKEEPING', 'housekeeping');
