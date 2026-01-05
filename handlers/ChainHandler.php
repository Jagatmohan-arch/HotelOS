<?php
/**
 * HotelOS - Chain Handler
 * 
 * MODULE 1: MULTI-PROPERTY MANAGEMENT
 * Handles cross-property operations, aggregate reporting, and context switching.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;
use HotelOS\Core\ChainContext;

class ChainHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all tenants belonging to a chain
     */
    public function getChainStats(int $chainId): array
    {
        // 1. Get Tenants
        $tenants = $this->db->query(
            "SELECT id, name, city, status FROM tenants WHERE chain_id = :chain_id",
            ['chain_id' => $chainId],
            enforceTenant: false
        );

        if (empty($tenants)) {
            return [];
        }

        $tenantIds = array_column($tenants, 'id');
        $idList = implode(',', $tenantIds);

        // 2. Aggregate Revenue (Today) - Cross Tenant Query
        // Note: Manual WHERE clause because we are querying across multiple tenants
        $revenue = $this->db->queryOne(
            "SELECT 
                SUM(grand_total) as total_revenue,
                COUNT(id) as total_bookings
             FROM bookings 
             WHERE tenant_id IN ($idList) 
               AND DATE(created_at) = CURDATE()",
            [],
            enforceTenant: false
        );

        // 3. Aggregate Occupancy - Cross Tenant Query
        // Count occupied rooms across all tenants
        $occupancy = $this->db->queryOne(
            "SELECT COUNT(*) as occupied_rooms
             FROM rooms 
             WHERE tenant_id IN ($idList)
               AND status = 'occupied'",
            [],
            enforceTenant: false
        );

        return [
            'tenants' => $tenants,
            'summary' => [
                'total_properties' => count($tenants),
                'total_revenue_today' => (float)($revenue['total_revenue'] ?? 0),
                'new_bookings_today' => (int)($revenue['total_bookings'] ?? 0),
                'current_occupied_rooms' => (int)($occupancy['occupied_rooms'] ?? 0)
            ]
        ];
    }

    /**
     * Verify if a user has access to a target tenant within their chain
     */
    public function canAccessTenant(int $userId, int $targetTenantId): bool
    {
        // Get User's Chain ID
        $user = $this->db->queryOne("SELECT chain_id FROM users WHERE id = :id", ['id' => $userId], enforceTenant: false);
        
        if (!$user || !$user['chain_id']) {
            return false;
        }

        // Check if target tenant belongs to that chain
        $tenant = $this->db->queryOne(
            "SELECT id FROM tenants WHERE id = :id AND chain_id = :chain",
            ['id' => $targetTenantId, 'chain' => $user['chain_id']],
            enforceTenant: false
        );

        return (bool)$tenant;
    }
}
