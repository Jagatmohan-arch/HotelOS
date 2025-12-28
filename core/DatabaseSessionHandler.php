<?php

declare(strict_types=1);

namespace HotelOS\Core;

use SessionHandlerInterface;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function open(string $path, string $name): bool
    {
        return true; // Database connection is already established
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $result = $this->db->queryOne(
            "SELECT payload FROM sessions WHERE id = :id",
            ['id' => $id],
            enforceTenant: false
        );

        if ($result) {
            return (string) $result['payload'];
        }

        return '';
    }

    public function write(string $id, string $data): bool
    {
        // Access global session to get user context
        // This is safe because write() is called by PHP session engine
        $userId = $_SESSION['user_id'] ?? null;
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        // Capture environment info
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $lastActivity = time();

        // Use ON DUPLICATE KEY UPDATE to handle both insert and update efficiently
        return (bool) $this->db->execute(
            "INSERT INTO sessions (id, user_id, tenant_id, ip_address, user_agent, payload, last_activity) 
             VALUES (:id, :user_id, :tenant_id, :ip_address, :user_agent, :payload, :last_activity)
             ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                tenant_id = VALUES(tenant_id),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                payload = VALUES(payload),
                last_activity = VALUES(last_activity)",
            [
                'id' => $id,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'payload' => $data,
                'last_activity' => $lastActivity
            ],
            enforceTenant: false
        );
    }

    public function destroy(string $id): bool
    {
        return (bool) $this->db->execute(
            "DELETE FROM sessions WHERE id = :id",
            ['id' => $id],
            enforceTenant: false
        );
    }

    public function gc(int $max_lifetime): int|false
    {
        $minTimestamp = time() - $max_lifetime;
        
        $this->db->execute(
            "DELETE FROM sessions WHERE last_activity < :min_timestamp",
            ['min_timestamp' => $minTimestamp],
            enforceTenant: false
        );
        
        // Return number of deleted rows is optional/hard with this abstraction, 
        // returning 1 (true-ish) is often enough or we can assume success.
        // SessionHandlerInterface::gc returns int (number of deleted sessions) or false.
        // Our execute returns bool or result, not count. 
        // But for safety let's return 0 or actual count if we could get it.
        // Since Database::execute doesn't return count, we just return 1 to indicate success.
        // Technically PHP 8.1+ expects int.
        
        return 1; 
    }
}
