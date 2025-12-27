<?php
/**
 * HotelOS - Database Connection Singleton
 * 
 * Provides a secure PDO connection with multi-tenancy support
 * Uses prepared statements exclusively to prevent SQL injection
 */

declare(strict_types=1);

namespace HotelOS\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private array $config;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->connect();
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect(): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            // Log error but don't expose details
            error_log('Database Connection Error: ' . $e->getMessage());
            throw new \RuntimeException('Database connection failed. Please try again later.');
        }
    }

    /**
     * Get raw PDO instance (use sparingly)
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a SELECT query with tenant isolation
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Bound parameters
     * @param bool $enforceTenant Auto-add tenant_id filter
     * @return array Result set
     */
    public function query(string $sql, array $params = [], bool $enforceTenant = true): array
    {
        if ($enforceTenant && TenantContext::isActive()) {
            $sql = $this->injectTenantFilter($sql);
            $params['tenant_id'] = TenantContext::getId();
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a SELECT query and return single row
     */
    public function queryOne(string $sql, array $params = [], bool $enforceTenant = true): ?array
    {
        $results = $this->query($sql, $params, $enforceTenant);
        return $results[0] ?? null;
    }

    /**
     * Execute INSERT/UPDATE/DELETE query
     * 
     * @return int Affected rows or last insert ID for INSERT
     */
    public function execute(string $sql, array $params = [], bool $enforceTenant = true): int
    {
        if ($enforceTenant && TenantContext::isActive()) {
            // For INSERT, add tenant_id to the data
            if (stripos(trim($sql), 'INSERT') === 0) {
                // Tenant ID should be in params for INSERT
                if (!isset($params['tenant_id'])) {
                    $params['tenant_id'] = TenantContext::getId();
                }
            } else {
                // For UPDATE/DELETE, inject WHERE clause
                $sql = $this->injectTenantFilter($sql);
                $params['tenant_id'] = TenantContext::getId();
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // Return last insert ID for INSERT, otherwise affected rows
        if (stripos(trim($sql), 'INSERT') === 0) {
            return (int) $this->pdo->lastInsertId();
        }
        
        return $stmt->rowCount();
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Check if in transaction
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Inject tenant_id filter into WHERE clause
     * 
     * This ensures every query is tenant-scoped
     */
    private function injectTenantFilter(string $sql): string
    {
        // Check if WHERE exists
        if (stripos($sql, 'WHERE') !== false) {
            // Add tenant_id to existing WHERE
            $sql = preg_replace(
                '/\bWHERE\b/i',
                'WHERE tenant_id = :tenant_id AND',
                $sql,
                1
            );
        } else {
            // Add WHERE clause before ORDER BY, GROUP BY, LIMIT, or end
            $patterns = ['/(\s+ORDER\s+BY)/i', '/(\s+GROUP\s+BY)/i', '/(\s+LIMIT)/i', '/(\s*;?\s*$)/'];
            $replaced = false;
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $sql)) {
                    $sql = preg_replace($pattern, ' WHERE tenant_id = :tenant_id $1', $sql, 1);
                    $replaced = true;
                    break;
                }
            }
            
            if (!$replaced) {
                $sql .= ' WHERE tenant_id = :tenant_id';
            }
        }

        return $sql;
    }

    /**
     * Get count with tenant filter
     */
    public function count(string $table, array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        
        if (!empty($conditions)) {
            $where = implode(' AND ', array_map(fn($k) => "{$k} = :{$k}", array_keys($conditions)));
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->queryOne($sql, $conditions);
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Find record by ID with tenant filter
     */
    public function find(string $table, int $id): ?array
    {
        return $this->queryOne(
            "SELECT * FROM {$table} WHERE id = :id",
            ['id' => $id]
        );
    }
}
