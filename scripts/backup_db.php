<?php
/**
 * HotelOS - Database Backup Script
 * 
 * USAGE: Run via Cron Job once per day/week
 * COMMAND: php /path/to/scripts/backup_db.php
 * 
 * Purpose: Dumps the database and emails it to the owner.
 * Why: Shared hosting has no guaranteed backups. This is your life insurance.
 */

// Load Config & Core
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Database.php';

// Configuration
$config = require __DIR__ . '/../config/app.php';
$dbConfig = require __DIR__ . '/../config/db.php'; // Assuming db.php returns array
// If db.php is not returning array, we might need to load environment vars or check core/Database.php

// HARDCODED FALLBACK (Update these for production if env not working in Cron)
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'hotelos_db';

$backupFile = __DIR__ . '/../storage/backups/db_backup_' . date('Y-m-d_H-i') . '.sql';
$gzipFile = $backupFile . '.gz';

echo "------------------------------------------------\n";
echo "HotelOS Database Backup Started: " . date('Y-m-d H:i:s') . "\n";
echo "------------------------------------------------\n";

// 1. Create Backup Directory if not exists
if (!is_dir(dirname($backupFile))) {
    mkdir(dirname($backupFile), 0755, true);
}

// 2. Dump Database
// Note: mysqldump must be in path or specify full path (e.g., /usr/bin/mysqldump)
$command = "mysqldump --opt -h {$dbHost} -u {$dbUser} -p'{$dbPass}' {$dbName} > {$backupFile}";

// Hide password in output
$displayCommand = "mysqldump --opt -h {$dbHost} -u {$dbUser} -p'****' {$dbName} > {$backupFile}";
echo "Executing: {$displayCommand}\n";

system($command, $returnVar);

if ($returnVar !== 0) {
    echo "ERROR: mysqldump failed with return code {$returnVar}\n";
    exit(1);
}

// 3. Compress
echo "Compressing backup...\n";
$gz = gzopen($gzipFile, 'w9');
gzwrite($gz, file_get_contents($backupFile));
gzclose($gz);
unlink($backupFile); // Remove raw SQL

echo "Backup created: {$gzipFile} (" . round(filesize($gzipFile) / 1024, 2) . " KB)\n";

// 4. Email to Owner (if Configured)
if (!empty($config['mail']['to_backup'])) {
    $to = $config['mail']['to_backup'];
    $subject = "HotelOS Backup: " . date('Y-m-d');
    $message = "Attached is the latest database backup for HotelOS.";
    
    // Simple mail with attachment requires PHPMailer or complex headers.
    // For simplicity in core PHP, we just notify. 
    // REAL IMPLEMENTATION SHOULD USE A MAILER LIBRARY.
    echo "Emailing to {$to} (Simulation - Install PHPMailer for attachments)...\n";
    // mail($to, $subject, $message); 
}

// 5. Cleanup Old Backups (Keep last 7 days)
$files = glob(dirname($backupFile) . '/*.gz');
$now = time();

foreach ($files as $file) {
    if (is_file($file)) {
        if ($now - filemtime($file) >= 60 * 60 * 24 * 7) { // 7 days
            unlink($file);
            echo "Deleted old backup: " . basename($file) . "\n";
        }
    }
}

echo "Done.\n";
