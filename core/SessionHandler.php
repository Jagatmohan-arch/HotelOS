<?php

declare(strict_types=1);

namespace HotelOS\Core;

/**
 * SessionHandler
 * 
 * Manages active session retrieval and administrative actions (kill session).
 * Distinct from DatabaseSessionHandler which implements the storage interface.
 * 
 * Responsibilities:
 * - List active sessions for a tenant
 * - Terminate specific sessions
 */
class SessionHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all active sessions for a specific tenant
     * 
     * @param int $tenantId
     * @return array List of sessions
     */
    public function getActiveSessions(int $tenantId): array
    {
        return $this->db->query(
            "SELECT 
                s.id,
                s.user_id,
                u.first_name, 
                u.last_name,
                u.role,
                u.email,
                s.ip_address,
                s.user_agent,
                s.last_activity,
                s.id = :current_session_id as is_current
             FROM sessions s
             JOIN users u ON u.id = s.user_id
             WHERE s.tenant_id = :tenant_id
             ORDER BY s.last_activity DESC",
            [
                'tenant_id' => $tenantId,
                'current_session_id' => session_id()
            ],
            enforceTenant: false
        );
    }

    /**
     * Terminate a specific session
     * 
     * @param string $sessionId
     * @param int $tenantId (Security: Ensure owner can only kill their own tenant's sessions)
     * @return bool
     */
    public function killSession(string $sessionId, int $tenantId): bool
    {
        // Prevent killing own session typically handled in UI, but good to have backend check if needed.
        // For now, we allow it but UI should warn.
        
        // Hard security check: Can only delete session belonging to same tenant
        return (bool) $this->db->execute(
            "DELETE FROM sessions WHERE id = :id AND tenant_id = :tenant_id",
            [
                'id' => $sessionId,
                'tenant_id' => $tenantId
            ],
            enforceTenant: false
        );
    }
}
