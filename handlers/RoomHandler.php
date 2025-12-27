<?php
/**
 * HotelOS - Room Handler
 * 
 * CRUD operations for individual rooms
 * All queries are tenant-isolated
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class RoomHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all rooms with optional filter
     */
    public function list(?int $roomTypeId = null, ?string $status = null): array
    {
        $sql = "SELECT r.*, 
                       rt.name as room_type_name, 
                       rt.code as room_type_code,
                       rt.base_rate,
                       rt.gst_rate
                FROM rooms r
                JOIN room_types rt ON r.room_type_id = rt.id
                WHERE r.is_active = 1";
        
        $params = [];
        
        if ($roomTypeId !== null) {
            $sql .= " AND r.room_type_id = :room_type_id";
            $params['room_type_id'] = $roomTypeId;
        }
        
        if ($status !== null) {
            $sql .= " AND r.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY r.floor, r.room_number";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Get single room by ID
     */
    public function get(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT r.*, 
                    rt.name as room_type_name, 
                    rt.code as room_type_code,
                    rt.base_rate,
                    rt.gst_rate
             FROM rooms r
             JOIN room_types rt ON r.room_type_id = rt.id
             WHERE r.id = :id AND r.is_active = 1",
            ['id' => $id]
        );
    }
    
    /**
     * Get room by room number
     */
    public function getByNumber(string $roomNumber): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM rooms WHERE room_number = :room_number AND is_active = 1",
            ['room_number' => $roomNumber]
        );
    }
    
    /**
     * Create new room
     */
    public function create(array $data): int
    {
        $tenantId = TenantContext::getId();
        if (!$tenantId) {
            throw new \RuntimeException('No active tenant');
        }
        
        // Validate
        $this->validate($data);
        
        // Check duplicate room number
        $existing = $this->getByNumber($data['room_number']);
        if ($existing) {
            throw new \InvalidArgumentException("Room number '{$data['room_number']}' already exists");
        }
        
        $this->db->execute(
            "INSERT INTO rooms (
                tenant_id, room_type_id, room_number, floor, building,
                status, housekeeping_status, notes, sort_order
             ) VALUES (
                :tenant_id, :room_type_id, :room_number, :floor, :building,
                :status, :housekeeping_status, :notes, :sort_order
             )",
            [
                'tenant_id' => $tenantId,
                'room_type_id' => (int) $data['room_type_id'],
                'room_number' => trim($data['room_number']),
                'floor' => trim($data['floor'] ?? ''),
                'building' => trim($data['building'] ?? ''),
                'status' => $data['status'] ?? 'available',
                'housekeeping_status' => $data['housekeeping_status'] ?? 'clean',
                'notes' => trim($data['notes'] ?? ''),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ],
            enforceTenant: false
        );
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Update room
     */
    public function update(int $id, array $data): bool
    {
        $existing = $this->get($id);
        if (!$existing) {
            return false;
        }
        
        // Validate
        $this->validate($data, $id);
        
        // Check room number uniqueness if changed
        $roomNumber = trim($data['room_number'] ?? $existing['room_number']);
        if ($roomNumber !== $existing['room_number']) {
            $duplicate = $this->getByNumber($roomNumber);
            if ($duplicate && $duplicate['id'] !== $id) {
                throw new \InvalidArgumentException("Room number '$roomNumber' already exists");
            }
        }
        
        $this->db->execute(
            "UPDATE rooms SET
                room_type_id = :room_type_id,
                room_number = :room_number,
                floor = :floor,
                building = :building,
                notes = :notes,
                sort_order = :sort_order
             WHERE id = :id",
            [
                'id' => $id,
                'room_type_id' => (int) ($data['room_type_id'] ?? $existing['room_type_id']),
                'room_number' => $roomNumber,
                'floor' => trim($data['floor'] ?? $existing['floor'] ?? ''),
                'building' => trim($data['building'] ?? $existing['building'] ?? ''),
                'notes' => trim($data['notes'] ?? $existing['notes'] ?? ''),
                'sort_order' => (int) ($data['sort_order'] ?? $existing['sort_order']),
            ]
        );
        
        return true;
    }
    
    /**
     * Update room status
     */
    public function updateStatus(int $id, string $status): bool
    {
        $validStatuses = ['available', 'occupied', 'reserved', 'maintenance', 'blocked'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid room status: $status");
        }
        
        $this->db->execute(
            "UPDATE rooms SET status = :status WHERE id = :id",
            ['id' => $id, 'status' => $status]
        );
        
        return true;
    }
    
    /**
     * Update housekeeping status
     */
    public function updateHousekeepingStatus(int $id, string $status): bool
    {
        $validStatuses = ['clean', 'dirty', 'inspected', 'out_of_order'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid housekeeping status: $status");
        }
        
        $this->db->execute(
            "UPDATE rooms SET housekeeping_status = :status WHERE id = :id",
            ['id' => $id, 'status' => $status]
        );
        
        return true;
    }
    
    /**
     * Soft delete room
     */
    public function delete(int $id): bool
    {
        // Check if room has active bookings
        $activeBooking = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM bookings 
             WHERE room_id = :id AND status IN ('confirmed', 'checked_in')",
            ['id' => $id]
        );
        
        if (($activeBooking['count'] ?? 0) > 0) {
            throw new \InvalidArgumentException('Cannot delete room with active bookings');
        }
        
        $this->db->execute(
            "UPDATE rooms SET is_active = 0 WHERE id = :id",
            ['id' => $id]
        );
        
        return true;
    }
    
    /**
     * Get room status counts for dashboard
     */
    public function getStatusCounts(): array
    {
        $results = $this->db->query(
            "SELECT status, COUNT(*) as count FROM rooms 
             WHERE is_active = 1 
             GROUP BY status"
        );
        
        $counts = [
            'available' => 0,
            'occupied' => 0,
            'reserved' => 0,
            'maintenance' => 0,
            'blocked' => 0,
        ];
        
        foreach ($results as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Get available rooms by type
     */
    public function getAvailableByType(int $roomTypeId): array
    {
        return $this->db->query(
            "SELECT * FROM rooms 
             WHERE room_type_id = :room_type_id 
             AND status = 'available' 
             AND is_active = 1
             ORDER BY room_number",
            ['room_type_id' => $roomTypeId]
        );
    }
    
    /**
     * Validate room data
     */
    private function validate(array $data, ?int $excludeId = null): void
    {
        if (empty($data['room_number']) && $excludeId === null) {
            throw new \InvalidArgumentException('Room number is required');
        }
        
        if (empty($data['room_type_id']) && $excludeId === null) {
            throw new \InvalidArgumentException('Room type is required');
        }
        
        if (!empty($data['room_number']) && strlen($data['room_number']) > 10) {
            throw new \InvalidArgumentException('Room number must be under 10 characters');
        }
    }
    
    /**
     * Get status display configs
     */
    public static function getStatusConfig(): array
    {
        return [
            'available' => ['label' => 'Available', 'color' => 'green', 'icon' => 'check-circle'],
            'occupied' => ['label' => 'Occupied', 'color' => 'red', 'icon' => 'user'],
            'reserved' => ['label' => 'Reserved', 'color' => 'yellow', 'icon' => 'clock'],
            'maintenance' => ['label' => 'Maintenance', 'color' => 'blue', 'icon' => 'wrench'],
            'blocked' => ['label' => 'Blocked', 'color' => 'gray', 'icon' => 'ban'],
        ];
    }
}
