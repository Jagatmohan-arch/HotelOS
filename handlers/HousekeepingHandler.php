<?php
/**
 * HotelOS - Housekeeping Handler
 * 
 * Manages room cleaning status and housekeeping tasks
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class HousekeepingHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all rooms with housekeeping status
     * 
     * @param string|null $floor Filter by floor
     * @param string|null $status Filter by status
     * @return array
     */
    public function getRoomBoard(?string $floor = null, ?string $status = null): array
    {
        $tenantId = TenantContext::getId();
        
        $where = ['r.tenant_id = :tenant_id', 'r.is_active = 1'];
        $params = ['tenant_id' => $tenantId];
        
        if ($floor) {
            $where[] = 'r.floor = :floor';
            $params['floor'] = $floor;
        }
        
        if ($status) {
            $where[] = 'r.housekeeping_status = :hk_status';
            $params['hk_status'] = $status;
        }
        
        $whereClause = implode(' AND ', $where);
        
        return $this->db->query(
            "SELECT r.id, r.room_number, r.floor, r.status, r.housekeeping_status,
                    rt.name as room_type, rt.code as room_type_code,
                    b.guest_id, CONCAT(g.first_name, ' ', g.last_name) as guest_name,
                    b.check_out_date
             FROM rooms r
             JOIN room_types rt ON r.room_type_id = rt.id AND rt.tenant_id = :tenant_id
             LEFT JOIN bookings b ON b.room_id = r.id 
                AND b.tenant_id = :tenant_id 
                AND b.status = 'checked_in'
             LEFT JOIN guests g ON b.guest_id = g.id AND g.tenant_id = :tenant_id
             WHERE {$whereClause}
             ORDER BY r.floor, r.room_number",
            $params,
            enforceTenant: false
        );
    }
    
    /**
     * Update room housekeeping status
     * 
     * @param int $roomId Room ID
     * @param string $status New status (clean, dirty, inspected, out_of_order)
     * @return bool Success
     */
    public function updateStatus(int $roomId, string $status): bool
    {
        $allowedStatuses = ['clean', 'dirty', 'inspected', 'out_of_order'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }
        
        $result = $this->db->execute(
            "UPDATE rooms SET housekeeping_status = :status WHERE id = :id",
            ['id' => $roomId, 'status' => $status]
        );
        
        return $result > 0;
    }
    
    /**
     * Get status counts
     */
    public function getStatusCounts(): array
    {
        $tenantId = TenantContext::getId();
        
        $results = $this->db->query(
            "SELECT housekeeping_status, COUNT(*) as count 
             FROM rooms 
             WHERE tenant_id = :tenant_id AND is_active = 1
             GROUP BY housekeeping_status",
            ['tenant_id' => $tenantId],
            enforceTenant: false
        );
        
        $counts = [
            'clean' => 0,
            'dirty' => 0,
            'inspected' => 0,
            'out_of_order' => 0
        ];
        
        foreach ($results as $row) {
            $counts[$row['housekeeping_status']] = (int) $row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Get distinct floors
     */
    public function getFloors(): array
    {
        return $this->db->query(
            "SELECT DISTINCT floor FROM rooms WHERE is_active = 1 ORDER BY floor"
        );
    }
    
    /**
     * Mark room as cleaned (shortcut)
     */
    public function markClean(int $roomId): bool
    {
        return $this->updateStatus($roomId, 'clean');
    }
    
    /**
     * Mark room as dirty (shortcut) 
     */
    public function markDirty(int $roomId): bool
    {
        return $this->updateStatus($roomId, 'dirty');
    }
}
