<?php
/**
 * HotelOS - Booking Handler
 * 
 * Manages booking lifecycle: reservation, check-in, check-out
 * Includes room availability checking with overlap detection
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class BookingHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check if a specific room is available for given dates
     * Uses overlap detection formula
     * 
     * @param int $roomId Room to check
     * @param string $checkIn Check-in date (Y-m-d)
     * @param string $checkOut Check-out date (Y-m-d)
     * @param int|null $excludeBookingId Exclude this booking (for edits)
     * @return bool True if available
     */
    public function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): bool
    {
        $tenantId = TenantContext::getId();
        
        $params = [
            'tenant_id' => $tenantId,
            'room_id' => $roomId,
            'check_in' => $checkIn,
            'check_out' => $checkOut
        ];
        
        $excludeClause = '';
        if ($excludeBookingId) {
            $excludeClause = 'AND id != :exclude_id';
            $params['exclude_id'] = $excludeBookingId;
        }
        
        // Overlap detection: NOT (new_checkout <= existing_checkin OR new_checkin >= existing_checkout)
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM bookings 
             WHERE tenant_id = :tenant_id
               AND room_id = :room_id
               AND status IN ('confirmed', 'checked_in')
               AND NOT (check_out_date <= :check_in OR check_in_date >= :check_out)
               {$excludeClause}",
            $params,
            enforceTenant: false
        );
        
        return (int)($result['count'] ?? 0) === 0;
    }
    
    /**
     * Get all available rooms for given date range and room type
     * 
     * @param string $checkIn Check-in date
     * @param string $checkOut Check-out date
     * @param int|null $roomTypeId Optional room type filter
     * @return array Available rooms
     */
    public function getAvailableRooms(string $checkIn, string $checkOut, ?int $roomTypeId = null): array
    {
        $tenantId = TenantContext::getId();
        
        $typeFilter = '';
        $params = [
            'tenant_id' => $tenantId,
            'check_in' => $checkIn,
            'check_out' => $checkOut
        ];
        
        if ($roomTypeId) {
            $typeFilter = 'AND r.room_type_id = :room_type_id';
            $params['room_type_id'] = $roomTypeId;
        }
        
        return $this->db->query(
            "SELECT r.*, rt.name as room_type_name, rt.code as room_type_code, rt.base_rate
             FROM rooms r
             JOIN room_types rt ON r.room_type_id = rt.id
             WHERE r.tenant_id = :tenant_id
               AND r.status = 'available'
               AND r.is_active = 1
               {$typeFilter}
               AND r.id NOT IN (
                   SELECT room_id FROM bookings 
                   WHERE tenant_id = :tenant_id
                     AND room_id IS NOT NULL
                     AND status IN ('confirmed', 'checked_in')
                     AND NOT (check_out_date <= :check_in OR check_in_date >= :check_out)
               )
             ORDER BY rt.sort_order, r.floor, r.room_number",
            $params,
            enforceTenant: false
        );
    }
    
    /**
     * Create a new booking
     * 
     * @param array $data Booking data
     * @return array ['success' => bool, 'booking_id' => int|null, 'booking_number' => string|null, 'error' => string|null]
     */
    public function create(array $data): array
    {
        $tenantId = TenantContext::getId();
        
        // Validate required fields
        $required = ['guest_id', 'room_type_id', 'check_in_date', 'check_out_date', 'rate_per_night'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Missing required field: {$field}"];
            }
        }
        
        // Check room availability if room assigned
        if (!empty($data['room_id'])) {
            if (!$this->isRoomAvailable((int)$data['room_id'], $data['check_in_date'], $data['check_out_date'])) {
                return ['success' => false, 'error' => 'Room is not available for selected dates'];
            }
        }
        
        // Generate booking number
        $bookingNumber = $this->generateBookingNumber();
        $uuid = $this->generateUuid();
        
        // Calculate nights and room total
        $nights = $this->calculateNights($data['check_in_date'], $data['check_out_date']);
        $roomTotal = (float)$data['rate_per_night'] * $nights;
        
        // Prepare insert data
        $insertData = [
            'tenant_id' => $tenantId,
            'uuid' => $uuid,
            'booking_number' => $bookingNumber,
            'guest_id' => (int)$data['guest_id'],
            'room_id' => !empty($data['room_id']) ? (int)$data['room_id'] : null,
            'room_type_id' => (int)$data['room_type_id'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'adults' => (int)($data['adults'] ?? 1),
            'children' => (int)($data['children'] ?? 0),
            'rate_per_night' => (float)$data['rate_per_night'],
            'room_total' => $roomTotal,
            'grand_total' => $roomTotal,  // Will be updated with tax at checkout
            'paid_amount' => (float)($data['advance_amount'] ?? 0),
            'payment_status' => (float)($data['advance_amount'] ?? 0) > 0 ? 'partial' : 'pending',
            'status' => $data['status'] ?? 'confirmed',
            'source' => $data['source'] ?? 'walk_in',
            'source_reference' => $data['source_reference'] ?? null,
            'special_requests' => $data['special_requests'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ];
        
        try {
            $columns = implode(', ', array_keys($insertData));
            $placeholders = ':' . implode(', :', array_keys($insertData));
            
            $this->db->execute(
                "INSERT INTO bookings ({$columns}) VALUES ({$placeholders})",
                $insertData,
                enforceTenant: false
            );
            
            $bookingId = $this->db->lastInsertId();
            
            // Update room status if room assigned
            if (!empty($data['room_id'])) {
                $this->db->execute(
                    "UPDATE rooms SET status = 'reserved' WHERE id = :id",
                    ['id' => $data['room_id']]
                );
            }
            
            return [
                'success' => true,
                'booking_id' => $bookingId,
                'booking_number' => $bookingNumber
            ];
            
        } catch (\Throwable $e) {
            error_log("Booking creation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create booking'];
        }
    }
    
    /**
     * Check-in a guest
     * 
     * @param int $id Booking ID
     * @param int|null $roomId Room to assign (if not already)
     * @return array Result
     */
    public function checkIn(int $id, ?int $roomId = null): array
    {
        $booking = $this->getById($id);
        
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }
        
        if ($booking['status'] === 'checked_in') {
            return ['success' => false, 'error' => 'Guest already checked in'];
        }
        
        if ($booking['status'] !== 'confirmed' && $booking['status'] !== 'pending') {
            return ['success' => false, 'error' => 'Cannot check-in: booking is ' . $booking['status']];
        }
        
        // Assign room if provided
        $roomUpdate = '';
        $params = ['id' => $id];
        
        if ($roomId) {
            // Check availability
            if (!$this->isRoomAvailable($roomId, $booking['check_in_date'], $booking['check_out_date'], $id)) {
                return ['success' => false, 'error' => 'Selected room is not available'];
            }
            $roomUpdate = ', room_id = :room_id';
            $params['room_id'] = $roomId;
            
            // Update room status
            $this->db->execute("UPDATE rooms SET status = 'occupied' WHERE id = :id", ['id' => $roomId]);
        } elseif ($booking['room_id']) {
            // Update existing room
            $this->db->execute("UPDATE rooms SET status = 'occupied' WHERE id = :id", ['id' => $booking['room_id']]);
        } else {
            return ['success' => false, 'error' => 'No room assigned to booking'];
        }
        
        $this->db->execute(
            "UPDATE bookings SET status = 'checked_in', actual_check_in = NOW() {$roomUpdate} WHERE id = :id",
            $params
        );
        
        return ['success' => true];
    }
    
    /**
     * Check-out a guest
     * Calculates final bill with GST
     * 
     * @param int $id Booking ID
     * @return array Result with final amounts
     */
    public function checkOut(int $id): array
    {
        $booking = $this->getById($id);
        
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }
        
        if ($booking['status'] !== 'checked_in') {
            return ['success' => false, 'error' => 'Guest is not checked in'];
        }
        
        // Calculate final amounts with GST
        $taxableAmount = $booking['room_total'] + $booking['extra_charges'] - $booking['discount_amount'];
        
        // Get GST rate from room type
        $roomType = $this->db->queryOne(
            "SELECT gst_rate FROM room_types WHERE id = :id",
            ['id' => $booking['room_type_id']]
        );
        
        $gstRate = (float)($roomType['gst_rate'] ?? 12);
        $halfGst = $gstRate / 2;
        
        // CGST + SGST (same state) or IGST (different state)
        $cgst = round($taxableAmount * ($halfGst / 100), 2);
        $sgst = $cgst;
        $totalTax = $cgst + $sgst;
        $grandTotal = $taxableAmount + $totalTax;
        
        // Update booking
        $this->db->execute(
            "UPDATE bookings SET 
             status = 'checked_out',
             actual_check_out = NOW(),
             taxable_amount = :taxable,
             gst_rate = :gst_rate,
             cgst_amount = :cgst,
             sgst_amount = :sgst,
             tax_total = :tax_total,
             grand_total = :grand_total,
             payment_status = CASE 
                 WHEN paid_amount >= :grand_total THEN 'paid'
                 WHEN paid_amount > 0 THEN 'partial'
                 ELSE 'pending'
             END
             WHERE id = :id",
            [
                'id' => $id,
                'taxable' => $taxableAmount,
                'gst_rate' => $gstRate,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'tax_total' => $totalTax,
                'grand_total' => $grandTotal
            ]
        );
        
        // Free up the room
        if ($booking['room_id']) {
            $this->db->execute(
                "UPDATE rooms SET status = 'available', housekeeping_status = 'dirty' WHERE id = :id",
                ['id' => $booking['room_id']]
            );
        }
        
        // Update guest stats
        $guestHandler = new GuestHandler();
        $guestHandler->updateStayStats((int)$booking['guest_id'], $grandTotal);
        
        return [
            'success' => true,
            'taxable_amount' => $taxableAmount,
            'gst_rate' => $gstRate,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'grand_total' => $grandTotal,
            'balance' => $grandTotal - $booking['paid_amount']
        ];
    }
    
    /**
     * Get booking by ID
     */
    public function getById(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT b.*, 
                    g.first_name, g.last_name, g.phone as guest_phone, g.email as guest_email,
                    r.room_number, rt.name as room_type_name
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id
             LEFT JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON b.room_type_id = rt.id
             WHERE b.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Get today's arrivals (expected check-ins)
     */
    public function getTodayArrivals(): array
    {
        return $this->db->query(
            "SELECT b.*, g.first_name, g.last_name, g.phone as guest_phone,
                    r.room_number, rt.name as room_type_name
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id
             LEFT JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON b.room_type_id = rt.id
             WHERE DATE(b.check_in_date) = CURDATE()
               AND b.status IN ('confirmed', 'pending')
             ORDER BY b.check_in_time"
        );
    }
    
    /**
     * Get today's departures (expected check-outs)
     */
    public function getTodayDepartures(): array
    {
        return $this->db->query(
            "SELECT b.*, g.first_name, g.last_name, g.phone as guest_phone,
                    r.room_number, rt.name as room_type_name
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id
             LEFT JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON b.room_type_id = rt.id
             WHERE DATE(b.check_out_date) = CURDATE()
               AND b.status = 'checked_in'
             ORDER BY b.check_out_time"
        );
    }
    
    /**
     * Get in-house guests (currently checked in)
     */
    public function getInHouseGuests(): array
    {
        return $this->db->query(
            "SELECT b.*, g.first_name, g.last_name, g.phone as guest_phone,
                    r.room_number, rt.name as room_type_name
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id
             JOIN rooms r ON b.room_id = r.id
             JOIN room_types rt ON b.room_type_id = rt.id
             WHERE b.status = 'checked_in'
             ORDER BY r.room_number"
        );
    }
    
    /**
     * Generate unique booking number
     * Format: BK-YYMMDD-NNN
     */
    private function generateBookingNumber(): string
    {
        $prefix = 'BK-' . date('ymd') . '-';
        $tenantId = TenantContext::getId();
        
        // Get last booking number for today
        $result = $this->db->queryOne(
            "SELECT booking_number FROM bookings 
             WHERE tenant_id = :tenant_id 
               AND booking_number LIKE :prefix
             ORDER BY id DESC LIMIT 1",
            ['tenant_id' => $tenantId, 'prefix' => $prefix . '%'],
            enforceTenant: false
        );
        
        if ($result && $result['booking_number']) {
            $lastNum = (int)substr($result['booking_number'], -3);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }
        
        return $prefix . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Calculate nights between dates
     */
    private function calculateNights(string $checkIn, string $checkOut): int
    {
        $in = new \DateTime($checkIn);
        $out = new \DateTime($checkOut);
        return (int)$in->diff($out)->days;
    }
    
    /**
     * Generate UUID v4
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
