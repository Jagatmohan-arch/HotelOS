<?php

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;

class AuditHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get audit timeline with filters
     *
     * @param int $tenantId
     * @param array $filters ['user_id', 'action', 'date_from', 'date_to', 'limit']
     * @return array
     */
    public function getTimeline(int $tenantId, array $filters = []): array
    {
        $sql = "SELECT 
                    a.*,
                    u.first_name, 
                    u.last_name,
                    u.role
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE a.tenant_id = :tenant_id";
        
        $params = ['tenant_id' => $tenantId];

        // Apply filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND a.action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(a.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(a.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        // Search description/context
        if (!empty($filters['search'])) {
            $sql .= " AND (a.description LIKE :search OR a.entity_type LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY a.created_at DESC";
        
        $limit = (int)($filters['limit'] ?? 50);
        $sql .= " LIMIT " . $limit;

        return $this->db->query($sql, $params, enforceTenant: false);
    }

    /**
     * Get unique action types for filter dropdown
     */
    public function getActionTypes(int $tenantId): array
    {
        return $this->db->query(
            "SELECT DISTINCT action FROM audit_logs WHERE tenant_id = :tenant_id ORDER BY action",
            ['tenant_id' => $tenantId],
            enforceTenant: false
        );
    }
}
