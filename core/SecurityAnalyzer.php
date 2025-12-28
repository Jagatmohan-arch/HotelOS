<?php

declare(strict_types=1);

namespace HotelOS\Core;

/**
 * SecurityAnalyzer
 * 
 * Analyzes session data for suspicious patterns.
 * Soft detection only - no blocking.
 */
class SecurityAnalyzer
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Analyze active sessions for specific tenant and return warnings
     * 
     * @param int $tenantId
     * @return array List of warnings
     */
    public function analyze(int $tenantId): array
    {
        $warnings = [];

        // 1. Multiple active IPs for same user
        // Users are allowed multiple devices, but distinct IPs might indicate shared credentials or breach
        $multiIpUsers = $this->db->query(
            "SELECT user_id, COUNT(DISTINCT ip_address) as ip_count 
             FROM sessions 
             WHERE tenant_id = :tenant_id 
             GROUP BY user_id 
             HAVING ip_count > 1",
            ['tenant_id' => $tenantId],
            enforceTenant: false
        );

        if ($multiIpUsers) {
            foreach ($multiIpUsers as $row) {
                $user = $this->getUserName((int)$row['user_id']);
                $warnings[] = [
                    'type' => 'MULTI_IP',
                    'severity' => 'medium',
                    'message' => "User {$user} is logged in from {$row['ip_count']} different IP addresses simultaneously.",
                    'user_id' => $row['user_id']
                ];
            }
        }

        // 2. Odd Hours Activity (1 AM - 5 AM)
        $oddHoursSessions = $this->db->query(
            "SELECT user_id, last_activity 
             FROM sessions 
             WHERE tenant_id = :tenant_id 
             AND HOUR(FROM_UNIXTIME(last_activity)) BETWEEN 1 AND 4",
             ['tenant_id' => $tenantId],
             enforceTenant: false
        );

        if ($oddHoursSessions) {
            foreach ($oddHoursSessions as $row) {
                $user = $this->getUserName((int)$row['user_id']);
                $warnings[] = [
                    'type' => 'ODD_HOURS',
                    'severity' => 'low',
                    'message' => "User {$user} is active during odd hours (1 AM - 5 AM).",
                    'user_id' => $row['user_id']
                ];
            }
        }

        return $warnings;
    }

    private function getUserName(int $userId): string
    {
        $user = $this->db->queryOne(
            "SELECT first_name, last_name FROM users WHERE id = :id", 
            ['id' => $userId],
            enforceTenant: false
        );
        return $user ? ($user['first_name'] . ' ' . $user['last_name']) : "ID#$userId";
    }
}
