<?php
/**
 * HotelOS - Database Migration Runner
 * 
 * Automatically applies database migrations from database/migrations/ directory
 * Tracks applied migrations to prevent duplicate execution
 * 
 * Usage:
 *   php migrate.php                    # Apply all pending migrations
 *   php migrate.php --dry-run          # Preview migrations without applying
 *   php migrate.php --force-file=xxx   # Re-run specific migration (danger!)
 */

declare(strict_types=1);

// Configuration
define('BASE_PATH', __DIR__);
define('MIGRATIONS_DIR', BASE_PATH . '/database/migrations');

// Load database config
require_once BASE_PATH . '/config/database.php';

class MigrationRunner
{
    private PDO $db;
    private bool $dryRun = false;
    private ?string $forceFile = null;
    
    public function __construct()
    {
        $this->connectDatabase();
        $this->ensureMigrationsLogExists();
    }
    
    private function connectDatabase(): void
    {
        $config = require BASE_PATH . '/config/database.php';
        
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['database']
        );
        
        try {
            $this->db = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            echo "✅ Connected to database: {$config['database']}\n";
        } catch (PDOException $e) {
            die("❌ Database connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    private function ensureMigrationsLogExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations_log` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration_file` VARCHAR(255) NOT NULL,
            `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `applied_by` VARCHAR(100) DEFAULT 'migrate.php',
            `checksum` VARCHAR(64) NULL,
            `execution_time_ms` INT UNSIGNED NULL,
            `status` ENUM('success', 'failed') DEFAULT 'success',
            `error_message` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_migration_file` (`migration_file`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->exec($sql);
    }
    
    public function run(bool $dryRun = false, ?string $forceFile = null): void
    {
        $this->dryRun = $dryRun;
        $this->forceFile = $forceFile;
        
        if ($dryRun) {
            echo "\n🔍 DRY RUN MODE - No changes will be made\n";
        }
        
        echo "\n📂 Scanning migrations directory: " . MIGRATIONS_DIR . "\n";
        
        $migrationFiles = $this->getMigrationFiles();
        $appliedMigrations = $this->getAppliedMigrations();
        $pendingMigrations = $this->filterPendingMigrations($migrationFiles, $appliedMigrations);
        
        if (empty($pendingMigrations)) {
            echo "\n✅ No pending migrations. Database is up to date!\n";
            return;
        }
        
        echo "\n📋 Found " . count($pendingMigrations) . " pending migration(s):\n";
        foreach ($pendingMigrations as $file) {
            echo "   - $file\n";
        }
        
        if (!$dryRun) {
            echo "\n🚀 Applying migrations...\n\n";
            
            foreach ($pendingMigrations as $file) {
                $this->applyMigration($file);
            }
            
            echo "\n✅ All migrations applied successfully!\n";
        }
    }
    
    private function getMigrationFiles(): array
    {
        if (!is_dir(MIGRATIONS_DIR)) {
            die("❌ Migrations directory not found: " . MIGRATIONS_DIR . "\n");
        }
        
        $files = glob(MIGRATIONS_DIR . '/*.sql');
        
        if ($files === false) {
            return [];
        }
        
        // Sort alphabetically to ensure consistent order
        sort($files);
        
        // Return just filenames, not full paths
        return array_map('basename', $files);
    }
    
    private function getAppliedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration_file FROM migrations_log WHERE status = 'success'");
        $applied = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $applied ?: [];
    }
    
    private function filterPendingMigrations(array $allMigrations, array $appliedMigrations): array
    {
        if ($this->forceFile) {
            // Force re-run specific file
            if (!in_array($this->forceFile, $allMigrations)) {
                die("❌ File not found: {$this->forceFile}\n");
            }
            return [$this->forceFile];
        }
        
        return array_diff($allMigrations, $appliedMigrations);
    }
    
    private function applyMigration(string $filename): void
    {
        $filepath = MIGRATIONS_DIR . '/' . $filename;
        $sql = file_get_contents($filepath);
        
        if ($sql === false) {
            echo "❌ Failed to read file: $filename\n";
            return;
        }
        
        echo "⏳ Applying: $filename ... ";
        
        $startTime = microtime(true);
        $checksum = hash('sha256', $sql);
        
        try {
            // Execute SQL (may contain multiple statements)
            $this->executeSqlFile($sql);
            
            $executionTime = (int)((microtime(true) - $startTime) * 1000);
            
            // Log successful migration
            $stmt = $this->db->prepare(
                "INSERT INTO migrations_log (migration_file, checksum, execution_time_ms, status) 
                 VALUES (:file, :checksum, :time, 'success')"
            );
            
            $stmt->execute([
                'file' => $filename,
                'checksum' => $checksum,
                'time' => $executionTime
            ]);
            
            echo "✅ ({$executionTime}ms)\n";
            
        } catch (PDOException $e) {
            echo "❌ FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
            
            // Log failed migration
            $stmt = $this->db->prepare(
                "INSERT INTO migrations_log (migration_file, checksum, status, error_message) 
                 VALUES (:file, :checksum, 'failed', :error)"
            );
            
            $stmt->execute([
                'file' => $filename,
                'checksum' => $checksum,
                'error' => $e->getMessage()
            ]);
            
            die("\n❌ Migration failed. Fix the error and run again.\n");
        }
    }
    
    private function executeSqlFile(string $sql): void
    {
        // Remove comments and split by semicolon
        $sql = preg_replace('/--.*$/m', '', $sql); // Remove line comments
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove block comments
        
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt)
        );
        
        foreach ($statements as $statement) {
            if (stripos($statement, 'DELIMITER') !== false) {
                // Skip DELIMITER commands (not supported in PDO)
                continue;
            }
            
            $this->db->exec($statement);
        }
    }
}

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv ?? []);
$forceFile = null;

foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--force-file=') === 0) {
        $forceFile = substr($arg, strlen('--force-file='));
    }
}

// Run migrations
try {
    $runner = new MigrationRunner();
    $runner->run($dryRun, $forceFile);
} catch (Throwable $e) {
    echo "\n❌ Fatal error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
