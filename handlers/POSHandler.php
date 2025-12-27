<?php
/**
 * HotelOS - POS Handler
 * 
 * Manages POS items (minibar, laundry) and charges to bookings
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class POSHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all POS items, optionally filtered by category
     */
    public function getItems(?string $category = null): array
    {
        $sql = "SELECT * FROM pos_items WHERE is_active = 1";
        $params = [];
        
        if ($category) {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }
        
        $sql .= " ORDER BY category, name";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Get single item by ID
     */
    public function getItem(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM pos_items WHERE id = :id AND is_active = 1",
            ['id' => $id]
        );
    }
    
    /**
     * Create new POS item
     */
    public function createItem(array $data): int
    {
        $tenantId = TenantContext::getId();
        
        $this->db->execute(
            "INSERT INTO pos_items (tenant_id, category, name, code, price, gst_rate)
             VALUES (:tenant_id, :category, :name, :code, :price, :gst_rate)",
            [
                'tenant_id' => $tenantId,
                'category' => $data['category'] ?? 'other',
                'name' => trim($data['name']),
                'code' => strtoupper(trim($data['code'] ?? '')),
                'price' => (float)($data['price'] ?? 0),
                'gst_rate' => (float)($data['gst_rate'] ?? 18)
            ],
            enforceTenant: false
        );
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update POS item
     */
    public function updateItem(int $id, array $data): bool
    {
        $this->db->execute(
            "UPDATE pos_items SET
             category = :category,
             name = :name,
             code = :code,
             price = :price,
             gst_rate = :gst_rate
             WHERE id = :id",
            [
                'id' => $id,
                'category' => $data['category'] ?? 'other',
                'name' => trim($data['name']),
                'code' => strtoupper(trim($data['code'] ?? '')),
                'price' => (float)($data['price'] ?? 0),
                'gst_rate' => (float)($data['gst_rate'] ?? 18)
            ]
        );
        
        return true;
    }
    
    /**
     * Soft delete item
     */
    public function deleteItem(int $id): bool
    {
        $this->db->execute(
            "UPDATE pos_items SET is_active = 0 WHERE id = :id",
            ['id' => $id]
        );
        return true;
    }
    
    /**
     * Add charge to a booking
     */
    public function addCharge(int $bookingId, int $itemId, int $quantity, int $chargedBy, ?string $notes = null): int
    {
        $tenantId = TenantContext::getId();
        
        // Get item details
        $item = $this->getItem($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('Item not found');
        }
        
        $unitPrice = (float)$item['price'];
        $totalPrice = $unitPrice * $quantity;
        
        $this->db->execute(
            "INSERT INTO pos_charges (tenant_id, booking_id, item_id, description, quantity, unit_price, total_price, gst_rate, charged_by, notes)
             VALUES (:tenant_id, :booking_id, :item_id, :description, :quantity, :unit_price, :total_price, :gst_rate, :charged_by, :notes)",
            [
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'item_id' => $itemId,
                'description' => $item['name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'gst_rate' => (float)$item['gst_rate'],
                'charged_by' => $chargedBy,
                'notes' => $notes
            ],
            enforceTenant: false
        );
        
        $chargeId = $this->db->lastInsertId();
        
        // Update booking extra_charges
        $this->db->execute(
            "UPDATE bookings SET extra_charges = extra_charges + :amount WHERE id = :id",
            ['id' => $bookingId, 'amount' => $totalPrice]
        );
        
        return $chargeId;
    }
    
    /**
     * Add custom charge (without item)
     */
    public function addCustomCharge(int $bookingId, string $description, float $amount, int $chargedBy, ?string $notes = null): int
    {
        $tenantId = TenantContext::getId();
        
        $this->db->execute(
            "INSERT INTO pos_charges (tenant_id, booking_id, item_id, description, quantity, unit_price, total_price, gst_rate, charged_by, notes)
             VALUES (:tenant_id, :booking_id, NULL, :description, 1, :amount, :amount, 18.00, :charged_by, :notes)",
            [
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'description' => $description,
                'amount' => $amount,
                'charged_by' => $chargedBy,
                'notes' => $notes
            ],
            enforceTenant: false
        );
        
        $chargeId = $this->db->lastInsertId();
        
        // Update booking extra_charges
        $this->db->execute(
            "UPDATE bookings SET extra_charges = extra_charges + :amount WHERE id = :id",
            ['id' => $bookingId, 'amount' => $amount]
        );
        
        return $chargeId;
    }
    
    /**
     * Get all charges for a booking
     */
    public function getBookingCharges(int $bookingId): array
    {
        return $this->db->query(
            "SELECT pc.*, pi.category, u.first_name as charged_by_name
             FROM pos_charges pc
             LEFT JOIN pos_items pi ON pc.item_id = pi.id
             LEFT JOIN users u ON pc.charged_by = u.id
             WHERE pc.booking_id = :booking_id
             ORDER BY pc.charged_at DESC",
            ['booking_id' => $bookingId]
        );
    }
    
    /**
     * Get in-house guests for charge dropdown
     */
    public function getInHouseGuests(): array
    {
        $tenantId = TenantContext::getId();
        return $this->db->query(
            "SELECT b.id as booking_id, b.booking_number, 
                    CONCAT(g.first_name, ' ', g.last_name) as guest_name,
                    r.room_number
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id AND g.tenant_id = :tenant_id1
             JOIN rooms r ON b.room_id = r.id AND r.tenant_id = :tenant_id2
             WHERE b.status = 'checked_in' AND b.tenant_id = :tenant_id3
             ORDER BY r.room_number",
            ['tenant_id1' => $tenantId, 'tenant_id2' => $tenantId, 'tenant_id3' => $tenantId],
            enforceTenant: false
        );
    }
    
    /**
     * Get category counts
     */
    public function getCategoryCounts(): array
    {
        $results = $this->db->query(
            "SELECT category, COUNT(*) as count FROM pos_items WHERE is_active = 1 GROUP BY category"
        );
        
        $counts = ['minibar' => 0, 'laundry' => 0, 'room_service' => 0, 'other' => 0];
        foreach ($results as $row) {
            $counts[$row['category']] = (int)$row['count'];
        }
        
        return $counts;
    }
}
