<?php
/**
 * HotelOS - Application Configuration
 * 
 * Central configuration for application constants and settings
 */

declare(strict_types=1);

return [
    // Application Info
    'name'      => 'HotelOS',
    'version'   => '1.0.0',
    'env'       => getenv('APP_ENV') ?: 'production',
    'debug'     => (bool)(getenv('APP_DEBUG') ?: false),
    'url'       => getenv('APP_URL') ?: 'https://hotelos.needkit.in',
    
    // Security
    'key'       => getenv('APP_KEY'), // Production: Set in .env
    
    // Session Configuration
    'session'   => [
        'name'     => 'HOTELOS_SESSION',
        'lifetime' => 120, // minutes
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    
    // Timezone & Locale
    'timezone'  => 'Asia/Kolkata',
    'locale'    => 'en_IN',
    'currency'  => 'INR',
    
    // GST Configuration (Indian Tax)
    'gst' => [
        'low_slab_rate'      => 12.00, // For rooms < ₹7500
        'high_slab_rate'     => 18.00, // For rooms >= ₹7500
        'threshold'          => 7500.00,
    ],
    
    // User Roles
    'roles' => [
        'owner'       => ['level' => 100, 'label' => 'Owner'],
        'manager'     => ['level' => 75,  'label' => 'Manager'],
        'accountant'  => ['level' => 50,  'label' => 'Accountant'],
        'reception'   => ['level' => 25,  'label' => 'Receptionist'],
        'housekeeping'=> ['level' => 10,  'label' => 'Housekeeping'],
    ],
    
    // Security Settings
    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration'   => 15, // minutes
        'password_min_length'=> 8,
        'csrf_token_lifetime'=> 3600, // seconds
    ],
    
    // File Upload Limits
    'uploads' => [
        'max_size'         => 5 * 1024 * 1024, // 5MB
        'allowed_types'    => ['jpg', 'jpeg', 'png', 'pdf'],
        'avatar_max_size'  => 1 * 1024 * 1024, // 1MB
    ],
    
    // Cache Configuration
    'cache' => [
        'driver'   => 'file',
        'path'     => __DIR__ . '/../cache',
        'lifetime' => 3600, // 1 hour
    ],
    
    // Logging
    'logging' => [
        'path'  => __DIR__ . '/../logs',
        'level' => 'error', // debug, info, warning, error
    ],
];
