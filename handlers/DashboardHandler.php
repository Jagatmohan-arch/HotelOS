<?php
/**
 * HotelOS - Dashboard Handler
 * 
 * Fetches statistics and data for the dashboard view
 * All queries are tenant-isolated via Database class
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class DashboardHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all dashboard statistics
     */
    public function getStats(): array
    {
        return [
            'totalRooms' => $this->getTotalRooms(),
            'occupancy' => $this->getOccupancyPercentage(),
            'todayArrivals' => $this->getTodayArrivals(),
            'todayRevenue' => $this->getTodayRevenue(),
        ];
    }
    
    /**
     * Get total room count
     */
    public function getTotalRooms(): int
    {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM rooms WHERE is_active = 1"
        );
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Get occupancy percentage
     */
    public function getOccupancyPercentage(): float
    {
        $total = $this->getTotalRooms();
        if ($total === 0) {
            return 0.0;
        }
        
        $occupied = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM rooms WHERE status = 'occupied' AND is_active = 1"
        );
        
        $occupiedCount = (int) ($occupied['count'] ?? 0);
        return round(($occupiedCount / $total) * 100, 1);
    }
    
    /**
     * Get today's arrival count (check-ins expected today)
     */
    public function getTodayArrivals(): int
    {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM bookings 
             WHERE DATE(check_in_date) = CURDATE() 
             AND status IN ('confirmed', 'checked_in')"
        );
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Get today's departures count
     */
    public function getTodayDepartures(): int
    {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM bookings 
             WHERE DATE(check_out_date) = CURDATE() 
             AND status = 'checked_in'"
        );
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Get today's revenue (payments collected today)
     */
    public function getTodayRevenue(): float
    {
        $result = $this->db->queryOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
             WHERE DATE(collected_at) = CURDATE() 
             AND type = 'credit'"
        );
        return (float) ($result['total'] ?? 0);
    }
    
    /**
     * Get count of rooms needing cleaning
     */
    public function getDirtyRoomsCount(): int
    {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM rooms 
             WHERE housekeeping_status = 'dirty' AND is_active = 1"
        );
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Get room status summary for grid display
     */
    public function getRoomStatusSummary(): array
    {
        $results = $this->db->query(
            "SELECT status, COUNT(*) as count FROM rooms 
             WHERE is_active = 1 
             GROUP BY status"
        );
        
        $summary = [
            'available' => 0,
            'occupied' => 0,
            'reserved' => 0,
            'maintenance' => 0,
            'blocked' => 0,
        ];
        
        foreach ($results as $row) {
            $summary[$row['status']] = (int) $row['count'];
        }
        
        return $summary;
    }
    
    /**
     * Get rooms with their status for the grid
     */
    public function getRoomsForGrid(): array
    {
        $tenantId = TenantContext::getId();
        return $this->db->query(
            "SELECT r.id, r.room_number, r.status, r.housekeeping_status,
                    r.floor, rt.name as room_type, rt.code as room_type_code
             FROM rooms r
             JOIN room_types rt ON r.room_type_id = rt.id AND rt.tenant_id = :tenant_id
             WHERE r.is_active = 1 AND r.tenant_id = :tenant_id2
             ORDER BY r.floor, r.room_number",
            ['tenant_id' => $tenantId, 'tenant_id2' => $tenantId],
            enforceTenant: false  // Manual tenant filter to avoid ambiguity
        );
    }
    
    /**
     * Get today's arrivals list
     */
    public function getTodayArrivalsDetail(): array
    {
        $tenantId = TenantContext::getId();
        return $this->db->query(
            "SELECT b.*, 
                    g.first_name, g.last_name, g.phone as guest_phone,
                    r.room_number,
                    rt.name as room_type
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id AND g.tenant_id = :tenant_id1
             LEFT JOIN rooms r ON b.room_id = r.id AND r.tenant_id = :tenant_id2
             JOIN room_types rt ON b.room_type_id = rt.id AND rt.tenant_id = :tenant_id3
             WHERE DATE(b.check_in_date) = CURDATE()
             AND b.status IN ('confirmed', 'pending')
             AND b.tenant_id = :tenant_id4
             ORDER BY b.check_in_time",
            ['tenant_id1' => $tenantId, 'tenant_id2' => $tenantId, 'tenant_id3' => $tenantId, 'tenant_id4' => $tenantId],
            enforceTenant: false
        );
    }
    
    /**
     * Get today's departures list
     */
    public function getTodayDeparturesDetail(): array
    {
        $tenantId = TenantContext::getId();
        return $this->db->query(
            "SELECT b.*, 
                    g.first_name, g.last_name, g.phone as guest_phone,
                    r.room_number,
                    rt.name as room_type,
                    b.balance_amount
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id AND g.tenant_id = :tenant_id1
             LEFT JOIN rooms r ON b.room_id = r.id AND r.tenant_id = :tenant_id2
             JOIN room_types rt ON b.room_type_id = rt.id AND rt.tenant_id = :tenant_id3
             WHERE DATE(b.check_out_date) = CURDATE()
             AND b.status = 'checked_in'
             AND b.tenant_id = :tenant_id4
             ORDER BY b.check_out_time",
            ['tenant_id1' => $tenantId, 'tenant_id2' => $tenantId, 'tenant_id3' => $tenantId, 'tenant_id4' => $tenantId],
            enforceTenant: false
        );
    }
    
    /**
     * Get recent activity for activity feed
     */
    public function getRecentActivity(int $limit = 10): array
    {
        return $this->db->query(
            "SELECT al.*, 
                    u.first_name, u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    /**
     * Get quick stats for header/cards
     */
    public function getQuickStats(): array
    {
        $tenantId = TenantContext::getId();
        
        if (!$tenantId) {
            return $this->getEmptyStats();
        }
        
        return $this->getStats();
    }
    
    /**
     * Return empty stats structure
     */
    private function getEmptyStats(): array
    {
        return [
            'totalRooms' => 0,
            'occupancy' => 0.0,
            'todayArrivals' => 0,
            'todayRevenue' => 0.0,
        ];
    }
}
