<?php
/**
 * HotelOS - Room Type Handler
 * 
 * CRUD operations for room types (categories like Deluxe, Suite, etc.)
 * All queries are tenant-isolated
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class RoomTypeHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all room types
     */
    public function list(): array
    {
        return $this->db->query(
            "SELECT rt.*, 
                    (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id AND r.is_active = 1) as room_count
             FROM room_types rt
             WHERE rt.is_active = 1
             ORDER BY rt.sort_order, rt.name"
        );
    }
    
    /**
     * Get single room type by ID
     */
    public function get(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM room_types WHERE id = :id AND is_active = 1",
            ['id' => $id]
        );
    }
    
    /**
     * Get room type by code
     */
    public function getByCode(string $code): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM room_types WHERE code = :code AND is_active = 1",
            ['code' => strtoupper($code)]
        );
    }
    
    /**
     * Create new room type
     */
    public function create(array $data): int
    {
        $tenantId = TenantContext::getId();
        if (!$tenantId) {
            throw new \RuntimeException('No active tenant');
        }
        
        // Validate required fields
        $this->validate($data);
        
        // Generate code if not provided or empty
        $code = !empty($data['code']) ? strtoupper(trim($data['code'])) : strtoupper($this->generateCode($data['name']));
        
        // Check for duplicate code
        $existing = $this->getByCode($code);
        if ($existing) {
            throw new \InvalidArgumentException("Room type code '$code' already exists");
        }
        
        // Prepare amenities as JSON
        $amenities = isset($data['amenities']) && is_array($data['amenities']) 
            ? json_encode($data['amenities']) 
            : null;
        
        $this->db->execute(
            "INSERT INTO room_types (
                tenant_id, name, code, description, 
                base_rate, extra_adult_rate, extra_child_rate,
                base_adults, base_children, max_adults, max_children, max_occupancy,
                amenities, sort_order
             ) VALUES (
                :tenant_id, :name, :code, :description,
                :base_rate, :extra_adult_rate, :extra_child_rate,
                :base_adults, :base_children, :max_adults, :max_children, :max_occupancy,
                :amenities, :sort_order
             )",
            [
                'tenant_id' => $tenantId,
                'name' => trim($data['name']),
                'code' => $code,
                'description' => trim($data['description'] ?? ''),
                'base_rate' => (float) ($data['base_rate'] ?? 0),
                'extra_adult_rate' => (float) ($data['extra_adult_rate'] ?? 0),
                'extra_child_rate' => (float) ($data['extra_child_rate'] ?? 0),
                'base_adults' => (int) ($data['base_adults'] ?? 2),
                'base_children' => (int) ($data['base_children'] ?? 0),
                'max_adults' => (int) ($data['max_adults'] ?? 3),
                'max_children' => (int) ($data['max_children'] ?? 2),
                'max_occupancy' => (int) ($data['max_occupancy'] ?? 4),
                'amenities' => $amenities,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ],
            enforceTenant: false // We manually added tenant_id
        );
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Update room type
     */
    public function update(int $id, array $data): bool
    {
        // Check exists
        $existing = $this->get($id);
        if (!$existing) {
            return false;
        }
        
        // Validate
        $this->validate($data, $id);
        
        // Handle code update
        $code = isset($data['code']) ? strtoupper(trim($data['code'])) : $existing['code'];
        if ($code !== $existing['code']) {
            $duplicate = $this->getByCode($code);
            if ($duplicate && $duplicate['id'] !== $id) {
                throw new \InvalidArgumentException("Room type code '$code' already exists");
            }
        }
        
        // Prepare amenities
        $amenities = isset($data['amenities']) && is_array($data['amenities'])
            ? json_encode($data['amenities'])
            : ($data['amenities'] ?? $existing['amenities']);
        
        $this->db->execute(
            "UPDATE room_types SET
                name = :name,
                code = :code,
                description = :description,
                base_rate = :base_rate,
                extra_adult_rate = :extra_adult_rate,
                extra_child_rate = :extra_child_rate,
                base_adults = :base_adults,
                base_children = :base_children,
                max_adults = :max_adults,
                max_children = :max_children,
                max_occupancy = :max_occupancy,
                amenities = :amenities,
                sort_order = :sort_order
             WHERE id = :id",
            [
                'id' => $id,
                'name' => trim($data['name'] ?? $existing['name']),
                'code' => $code,
                'description' => trim($data['description'] ?? $existing['description'] ?? ''),
                'base_rate' => (float) ($data['base_rate'] ?? $existing['base_rate']),
                'extra_adult_rate' => (float) ($data['extra_adult_rate'] ?? $existing['extra_adult_rate']),
                'extra_child_rate' => (float) ($data['extra_child_rate'] ?? $existing['extra_child_rate']),
                'base_adults' => (int) ($data['base_adults'] ?? $existing['base_adults']),
                'base_children' => (int) ($data['base_children'] ?? $existing['base_children']),
                'max_adults' => (int) ($data['max_adults'] ?? $existing['max_adults']),
                'max_children' => (int) ($data['max_children'] ?? $existing['max_children']),
                'max_occupancy' => (int) ($data['max_occupancy'] ?? $existing['max_occupancy']),
                'amenities' => $amenities,
                'sort_order' => (int) ($data['sort_order'] ?? $existing['sort_order']),
            ]
        );
        
        return true;
    }
    
    /**
     * Soft delete room type
     */
    public function delete(int $id): bool
    {
        // Check if has rooms
        $roomCount = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM rooms WHERE room_type_id = :id AND is_active = 1",
            ['id' => $id]
        );
        
        if (($roomCount['count'] ?? 0) > 0) {
            throw new \InvalidArgumentException('Cannot delete room type with active rooms');
        }
        
        $this->db->execute(
            "UPDATE room_types SET is_active = 0 WHERE id = :id",
            ['id' => $id]
        );
        
        return true;
    }
    
    /**
     * Validate room type data
     */
    private function validate(array $data, ?int $excludeId = null): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Room type name is required');
        }
        
        if (strlen($data['name']) > 100) {
            throw new \InvalidArgumentException('Room type name must be under 100 characters');
        }
        
        if (isset($data['base_rate']) && $data['base_rate'] < 0) {
            throw new \InvalidArgumentException('Base rate cannot be negative');
        }
    }
    
    /**
     * Generate short code from name
     */
    private function generateCode(string $name): string
    {
        // Take first 3 letters of each word, max 3 words
        $words = preg_split('/\s+/', trim($name));
        $code = '';
        
        foreach (array_slice($words, 0, 3) as $word) {
            $code .= strtoupper(substr($word, 0, 3));
        }
        
        return substr($code, 0, 10);
    }
    
    /**
     * Get available amenities list
     */
    public static function getAmenitiesList(): array
    {
        return [
            'wifi' => 'WiFi',
            'ac' => 'Air Conditioning',
            'tv' => 'Television',
            'minibar' => 'Mini Bar',
            'safe' => 'In-Room Safe',
            'balcony' => 'Balcony',
            'bathtub' => 'Bathtub',
            'shower' => 'Shower',
            'hairdryer' => 'Hair Dryer',
            'iron' => 'Iron',
            'coffee' => 'Coffee Maker',
            'breakfast' => 'Breakfast Included',
            'parking' => 'Free Parking',
            'gym' => 'Gym Access',
            'pool' => 'Pool Access',
            'spa' => 'Spa Access',
        ];
    }
}
