<?php
/**
 * HotelOS - Root Entry Point
 * 
 * This file acts as a simple bootstrap to include the main front controller
 * from the public directory.
 * 
 * For shared hosting, the document root points here instead of public/
 */

declare(strict_types=1);

// Include the actual front controller
require_once __DIR__ . '/public/index.php';
