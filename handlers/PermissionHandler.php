<?php
/**
 * HotelOS - Permission Handler
 * 
 * MODULE 3: ROLE GOVERNANCE
 * Manages dynamic role-based access control.
 * SAFE MODE: If DB fails or is empty, falls back to hardcoded Phase 0 logic.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\Auth;
use HotelOS\Core\TenantContext;

class PermissionHandler
{
    private Database $db;
    
    // FALLBACK MATRIX (Phase 0-3 Logic) - DO NOT CHANGE WITHOUT APPROVAL
    private const DEFAULT_MATRIX = [
        'view_dashboard'    => [Auth::ROLE_OWNER, Auth::ROLE_MANAGER, Auth::ROLE_RECEPTION],
        'manage_bookings'   => [Auth::ROLE_OWNER, Auth::ROLE_MANAGER, Auth::ROLE_RECEPTION],
        'manage_rooms'      => [Auth::ROLE_OWNER, Auth::ROLE_MANAGER],
        'manage_users'      => [Auth::ROLE_OWNER],
        'view_reports'      => [Auth::ROLE_OWNER, Auth::ROLE_MANAGER, Auth::ROLE_ACCOUNTANT],
        'manage_billing'    => [Auth::ROLE_OWNER, Auth::ROLE_MANAGER, Auth::ROLE_ACCOUNTANT],
        'delete_records'    => [Auth::ROLE_OWNER],
        'view_audit_logs'   => [Auth::ROLE_OWNER],
        'approve_discounts' => [Auth::ROLE_OWNER, Auth::ROLE_MANAGER],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Check if a role has a specific permission
     */
    public function hasPermission(string $role, string $permission): bool
    {
        // 1. Try DB (Enterprise Layer)
        try {
            if (TenantContext::isActive()) {
                $hasPerm = $this->db->queryOne(
                    "SELECT 1 FROM role_permissions rp
                     JOIN permissions p ON rp.permission_id = p.id
                     WHERE rp.role_name = :role 
                       AND p.slug = :perm
                       AND rp.tenant_id = :tid",
                    [
                        'role' => $role,
                        'perm' => $permission,
                        'tid' => TenantContext::getId()
                    ],
                    enforceTenant: false
                );

                if ($hasPerm) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Log silent error, proceed to fallback
            // error_log("Permission Check DB Error: " . $e->getMessage());
        }

        // 2. Fallback to Hardcoded defaults (Safety Net)
        return $this->checkDefault($role, $permission);
    }

    /**
     * Check against the hardcoded v4.0 Matrix
     */
    private function checkDefault(string $role, string $permission): bool
    {
        $allowedRoles = self::DEFAULT_MATRIX[$permission] ?? [];
        return in_array($role, $allowedRoles, true);
    }

    /**
     * Get all permissions for UI Matrix
     */
    public function getAllPermissions(): array
    {
        // Return hardcoded list if DB not ready
        return array_keys(self::DEFAULT_MATRIX);
    }
}
