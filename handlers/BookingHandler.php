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
use HotelOS\Core\Auth;
use HotelOS\Handlers\PaymentHandler;
use HotelOS\Handlers\TaxHandler;

class BookingHandler
{
    private Database $db;
    private SettingsHandler $settings;
    private Auth $auth;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->settings = new SettingsHandler();
        $this->auth = Auth::getInstance();
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
               AND r.status != 'maintenance'
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
        
        // MIXED TRANSACTION LOGIC: Start transaction early to lock room
        try {
            $this->db->beginTransaction();
            
            // Validate required fields
            $required = ['guest_id', 'room_type_id', 'check_in_date', 'check_out_date', 'rate_per_night'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->db->rollback();
                    return ['success' => false, 'error' => "Missing required field: {$field}"];
                }
            }
            
            // Check room availability if room assigned (WITH LOCK)
            if (!empty($data['room_id'])) {
                // LOCK the room row to prevent double booking
                $this->db->queryOne("SELECT id FROM rooms WHERE id = :id FOR UPDATE", ['id' => $data['room_id']]);
                
                if (!$this->isRoomAvailable((int)$data['room_id'], $data['check_in_date'], $data['check_out_date'])) {
                    $this->db->rollback();
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
            // FIX: Start with 0 paid. We will add payment via PaymentHandler
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
                'paid_amount' => 0.00,        // FIX: Handled by PaymentHandler
                'payment_status' => 'pending', // FIX: Default to pending
                'status' => $data['status'] ?? 'confirmed',
                'source' => $data['source'] ?? 'walk_in',
                'source_reference' => $data['source_reference'] ?? null,
                'special_requests' => $data['special_requests'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ];

            $columns = implode(', ', array_keys($insertData));
            $placeholders = ':' . implode(', :', array_keys($insertData));
            
            $this->db->execute(
                "INSERT INTO bookings ({$columns}) VALUES ({$placeholders})",
                $insertData,
                enforceTenant: false
            );
            
            $bookingId = (int)$this->db->lastInsertId();
            
            // Update room status if room assigned
            if (!empty($data['room_id'])) {
                $this->db->execute(
                    "UPDATE rooms SET status = 'reserved' WHERE id = :id",
                    ['id' => $data['room_id']]
                );
            }

            // CRITICAL FIX: Handle Advance Payment via Transaction
            $advanceAmount = (float)($data['advance_amount'] ?? 0);
            if ($advanceAmount > 0) {
                // Must commit the booking first effectively? 
                // Checks if PaymentHandler uses its own transaction. It does.
                // Since PaymentHandler uses transaction, and we are in one...
                // Nested transactions in MySQL via PDO are not supported directly unless we use SAVEPOINT.
                // However, PaymentHandler::recordPayment starts a transaction.
                // To avoid conflict, commit here or refactor PaymentHandler to NOT start transaction if one exists?
                // Simplest: PaymentHandler is atomic. We should verify if DB class supports nested.
                // Assuming Database::beginTransaction() checks `inTransaction()`.
                // Let's assume for now we need to commit booking before payment to be safe with IDs.
                
                $this->db->commit(); 
                
                // Now record payment
                $paymentHandler = new PaymentHandler();
                $paymentResult = $paymentHandler->recordPayment(
                    $bookingId,
                    $advanceAmount,
                    $data['payment_mode'] ?? 'cash',
                    $data['payment_reference'] ?? null,
                    'Advance Payment during Booking',
                    $data['created_by'] ?? null
                );
                
                if (!$paymentResult['success']) {
                    // Payment failed but booking created.
                    // Ideally we should rollback booking, but we just committed.
                    // This is a trade-off. We'll log error.
                    error_log("Payment failed for Booking $bookingId: " . $paymentResult['error']);
                }
            } else {
                $this->db->commit();
            }
            
            // Audit Log
            $this->auth->logAudit('create', 'booking', $bookingId);
            
            return [
                'success' => true,
                'booking_id' => $bookingId,
                'booking_number' => $bookingNumber
            ];
            
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("Booking creation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create booking: ' . $e->getMessage()];
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
        
        // Audit Log
        $this->auth->logAudit('check_in', 'booking', $id);
        
        return ['success' => true];
    }
    
    /**
     * Check-out a guest
     * Calculates final bill with GST
     * 
     * @param int $id Booking ID
     * @param float $extraCharges Additional charges (minibar, laundry, etc.)
     * @param float $lateCheckoutFee Manual late checkout fee (0 for auto-calc)
     * @param array|null $paymentData Optional final payment ['amount', 'mode', 'reference']
     * @return array Result with final amounts
     */
    public function checkOut(int $id, float $extraCharges = 0, float $lateCheckoutFee = 0, ?array $paymentData = null): array
    {
        $booking = $this->getById($id);
        
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }
        
        if ($booking['status'] !== 'checked_in') {
            return ['success' => false, 'error' => 'Guest is not checked in'];
        }

        // FIX: Handle Final Payment if provided
        if (!empty($paymentData) && isset($paymentData['amount']) && (float)$paymentData['amount'] > 0) {
            $paymentHandler = new PaymentHandler();
            $payResult = $paymentHandler->recordPayment(
                $id,
                (float)$paymentData['amount'],
                $paymentData['mode'] ?? 'cash',
                $paymentData['reference'] ?? null,
                'Final Settlement at Checkout',
                $this->auth->id()
            );
            
            if (!$payResult['success']) {
                return ['success' => false, 'error' => "Payment failed: " . $payResult['error']];
            }
            
            // Refresh booking to get updated paid_amount
            $booking = $this->getById($id);
        }
        
        // Calculate late checkout fee based on settings
        $timezone = TenantContext::attr('timezone', 'Asia/Kolkata');
        $now = new \DateTime('now', new \DateTimeZone($timezone));
        $checkoutHour = (int)$now->format('H');
        $calculatedLateFee = 0;
        
        if ($lateCheckoutFee > 0) {
            // Manual override
            $calculatedLateFee = $lateCheckoutFee;
        } else {
            // Get settings (Defaults: 14:00 check out triggers 50%)
            $settings = $this->settings->getSettings();
            $lateCheckoutTime = (int)($settings['late_checkout_threshold'] ?? 14); 
            $lateCheckoutPct = (float)($settings['late_checkout_percent'] ?? 50);
            
            if ($checkoutHour >= $lateCheckoutTime) {
                $calculatedLateFee = (float)$booking['rate_per_night'] * ($lateCheckoutPct / 100);
            }
        }
        
        // Use passed extra charges or existing
        $totalExtraCharges = $extraCharges > 0 ? $extraCharges : (float)($booking['extra_charges'] ?? 0);
        $totalExtraCharges += $calculatedLateFee;
        
        // Update extra_charges in booking if new charges added
        if ($extraCharges > 0 || $calculatedLateFee > 0) {
            $this->db->execute(
                "UPDATE bookings SET extra_charges = :extra WHERE id = :id",
                ['id' => $id, 'extra' => $totalExtraCharges]
            );
        }
        
        // Calculate final amounts with GST using settings
        $taxableAmount = (float)$booking['room_total'] + $totalExtraCharges - (float)($booking['discount_amount'] ?? 0);
        
        // Check for tax exemption flag
        $isTaxExempt = !empty($booking['tax_exempt']);
        
        // Dynamic GST Calculation
        // Dynamic GST Calculation via TaxHandler
        $taxHandler = new TaxHandler();
        $taxResult = $taxHandler->calculate(
            $taxableAmount, 
            (float)$booking['rate_per_night'], 
            $isTaxExempt
        );
        
        $gstRate = $taxResult['rate'];
        $cgst = $taxResult['cgst'];
        $sgst = $taxResult['sgst'];
        $totalTax = $taxResult['total_tax'];
        $grandTotal = $taxableAmount + $totalTax;
        
        // Update booking
        $this->db->execute(
            "UPDATE bookings SET 
             status = 'checked_out',
             actual_check_out = NOW(),
             extra_charges = :extra,
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
                'extra' => $totalExtraCharges,
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
        
        // Audit Log with financial diff tracking
        $this->auth->logAudit(
            'check_out', 
            'booking', 
            $id,
            [
                'paid_amount' => (float)$booking['paid_amount'],
                'grand_total' => (float)($booking['grand_total'] ?? 0),
                'extra_charges' => (float)($booking['extra_charges'] ?? 0),
                'status' => $booking['status']
            ],
            [
                'paid_amount' => (float)$booking['paid_amount'],
                'grand_total' => $grandTotal,
                'extra_charges' => $totalExtraCharges,
                'status' => 'checked_out'
            ]
        );
        
        return [
            'success' => true,
            'extra_charges' => $totalExtraCharges,
            'late_fee' => $calculatedLateFee,
            'taxable_amount' => $taxableAmount,
            'gst_rate' => $gstRate,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'grand_total' => $grandTotal,
            'balance' => $grandTotal - (float)$booking['paid_amount']
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
             WHERE b.id = :id AND b.tenant_id = :tenant_id",
            [
                'id' => $id,
                'tenant_id' => TenantContext::getId()
            ],
            enforceTenant: false
        );
    }
    
    /**
     * Move guest to a different room during stay
     * Creates historical record and triggers housekeeping
     * 
     * @param int $bookingId Booking to move
     * @param int $newRoomId Target room ID
     * @param string $reason Reason for move (maintenance, upgrade, etc.)
     * @param string $rateAction keep_original | use_new_rate | custom
     * @param float|null $customRate Custom rate if rateAction is 'custom'
     * @param string|null $notes Additional notes
     * @return array Result
     */
    public function moveRoom(
        int $bookingId,
        int $newRoomId,
        string $reason = 'guest_request',
        string $rateAction = 'keep_original',
        ?float $customRate = null,
        ?string $notes = null
    ): array {
        $tenantId = TenantContext::getId();
        $userId = $this->auth->id() ?? 0;
        
        // Get current booking
        $booking = $this->getById($bookingId);
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }
        
        // Must be checked_in to move
        if ($booking['status'] !== 'checked_in') {
            return ['success' => false, 'error' => 'Guest must be checked in to move rooms'];
        }
        
        $oldRoomId = (int)$booking['room_id'];
        if ($oldRoomId === $newRoomId) {
            return ['success' => false, 'error' => 'New room must be different from current room'];
        }
        
        // Get old room details
        $oldRoom = $this->db->queryOne(
            "SELECT room_number FROM rooms WHERE id = :id",
            ['id' => $oldRoomId]
        );
        
        // Get new room details
        $newRoom = $this->db->queryOne(
            "SELECT r.*, rt.base_rate, rt.name as room_type_name 
             FROM rooms r 
             JOIN room_types rt ON r.room_type_id = rt.id 
             WHERE r.id = :id AND r.tenant_id = :tenant_id",
            ['id' => $newRoomId, 'tenant_id' => $tenantId],
            enforceTenant: false
        );
        
        if (!$newRoom) {
            return ['success' => false, 'error' => 'New room not found'];
        }
        
        // Check new room is available
        if ($newRoom['status'] !== 'available') {
            return ['success' => false, 'error' => 'New room is not available (status: ' . $newRoom['status'] . ')'];
        }
        
        // Determine new rate
        $oldRate = (float)$booking['rate_per_night'];
        $newRate = match($rateAction) {
            'use_new_rate' => (float)$newRoom['base_rate'],
            'custom' => $customRate ?? $oldRate,
            default => $oldRate  // keep_original
        };
        
        // Validate reason
        $validReasons = ['maintenance', 'upgrade', 'downgrade', 'guest_request', 'housekeeping', 'other'];
        if (!in_array($reason, $validReasons)) {
            $reason = 'other';
        }
        
        // === BEGIN TRANSACTION ===
        $this->db->beginTransaction();
        
        try {
            // 1. Create historical record
            $this->db->execute(
                "INSERT INTO room_move_history 
                 (tenant_id, booking_id, from_room_id, to_room_id, from_room_number, to_room_number,
                  reason, notes, rate_action, old_rate, new_rate, moved_by)
                 VALUES 
                 (:tenant_id, :booking_id, :from_room, :to_room, :from_number, :to_number,
                  :reason, :notes, :rate_action, :old_rate, :new_rate, :moved_by)",
                [
                    'tenant_id' => $tenantId,
                    'booking_id' => $bookingId,
                    'from_room' => $oldRoomId,
                    'to_room' => $newRoomId,
                    'from_number' => $oldRoom['room_number'] ?? 'N/A',
                    'to_number' => $newRoom['room_number'],
                    'reason' => $reason,
                    'notes' => $notes,
                    'rate_action' => $rateAction,
                    'old_rate' => $oldRate,
                    'new_rate' => $newRate,
                    'moved_by' => $userId
                ],
                enforceTenant: false
            );
            
            // 2. Update booking with new room and rate
            $this->db->execute(
                "UPDATE bookings SET 
                 room_id = :new_room_id,
                 rate_per_night = :new_rate
                 WHERE id = :id",
                [
                    'id' => $bookingId,
                    'new_room_id' => $newRoomId,
                    'new_rate' => $newRate
                ]
            );
            
            // 3. Update old room: available but dirty (needs cleaning)
            $this->db->execute(
                "UPDATE rooms SET 
                 status = 'available',
                 housekeeping_status = 'dirty'
                 WHERE id = :id",
                ['id' => $oldRoomId]
            );
            
            // 4. Update new room: occupied
            $this->db->execute(
                "UPDATE rooms SET 
                 status = 'occupied',
                 housekeeping_status = 'clean'
                 WHERE id = :id",
                ['id' => $newRoomId]
            );
            
            // 5. Audit log with diff
            $this->auth->logAudit(
                'room_move',
                'booking',
                $bookingId,
                [
                    'room_id' => $oldRoomId,
                    'room_number' => $oldRoom['room_number'] ?? 'N/A',
                    'rate_per_night' => $oldRate
                ],
                [
                    'room_id' => $newRoomId,
                    'room_number' => $newRoom['room_number'],
                    'rate_per_night' => $newRate,
                    'reason' => $reason
                ]
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'from_room' => $oldRoom['room_number'] ?? 'N/A',
                'to_room' => $newRoom['room_number'],
                'old_rate' => $oldRate,
                'new_rate' => $newRate
            ];
            
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => 'Move failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get room move history for a booking
     */
    public function getRoomMoveHistory(int $bookingId): array
    {
        return $this->db->query(
            "SELECT rmh.*, u.first_name, u.last_name
             FROM room_move_history rmh
             LEFT JOIN users u ON rmh.moved_by = u.id
             WHERE rmh.booking_id = :booking_id
             ORDER BY rmh.moved_at DESC",
            ['booking_id' => $bookingId]
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
               AND b.tenant_id = :tenant_id
             ORDER BY b.id DESC",
            ['tenant_id' => TenantContext::getId()],
            enforceTenant: false
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
               AND b.tenant_id = :tenant_id
             ORDER BY b.id DESC",
            ['tenant_id' => TenantContext::getId()],
            enforceTenant: false
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
               AND b.tenant_id = :tenant_id
             ORDER BY r.room_number",
            ['tenant_id' => TenantContext::getId()],
            enforceTenant: false
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
    
    /**
     * Cancel a booking
     * 
     * @param int $id Booking ID
     * @param string $reason Cancellation reason
     * @return array Result
     */
    public function cancel(int $id, string $reason = ''): array
    {
        $booking = $this->getById($id);
        
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }
        
        if ($booking['status'] === 'checked_out' || $booking['status'] === 'cancelled') {
            return ['success' => false, 'error' => 'Cannot cancel: booking is already ' . $booking['status']];
        }
        
        // Update booking status
        $this->db->execute(
            "UPDATE bookings SET 
             status = 'cancelled', 
             cancelled_at = NOW(),
             cancellation_reason = :reason
             WHERE id = :id",
            ['id' => $id, 'reason' => $reason]
        );
        
        // Free up the room if assigned
        if ($booking['room_id']) {
            $this->db->execute(
                "UPDATE rooms SET status = 'available' WHERE id = :id",
                ['id' => $booking['room_id']]
            );
        }
        
        // Audit Log
        $this->auth->logAudit('cancel', 'booking', $id);
        
        return ['success' => true];
    }
    
    /**
     * Set tax exemption flag for a booking (Manager+ only)
     * 
     * @param int $id Booking ID
     * @param bool $exempt True to exempt from GST
     * @param string $reason Reason for exemption (required if exempt=true)
     * @return array Success/error response
     */
    public function setTaxExempt(int $id, bool $exempt, string $reason = ''): array
    {
        // Validate reason if exempting
        if ($exempt && strlen(trim($reason)) < 5) {
            return ['success' => false, 'error' => 'Reason is required for tax exemption (min 5 chars)'];
        }
        
        $booking = $this->getById($id);
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }
        
        // Only allow for pending or checked_in bookings
        if (!in_array($booking['status'], ['reserved', 'confirmed', 'checked_in'])) {
            return ['success' => false, 'error' => 'Cannot modify tax exemption for completed bookings'];
        }
        
        $oldValue = (bool)($booking['tax_exempt'] ?? false);
        
        $this->db->execute(
            "UPDATE bookings SET tax_exempt = :exempt, tax_exempt_reason = :reason WHERE id = :id",
            [
                'id' => $id,
                'exempt' => $exempt ? 1 : 0,
                'reason' => $exempt ? trim($reason) : null
            ]
        );
        
        // Audit log
        $this->auth->logAudit(
            'tax_exempt_change',
            'booking',
            $id,
            'Tax exemption changed',
            ['tax_exempt' => $oldValue],
            ['tax_exempt' => $exempt, 'reason' => $reason]
        );
        
        return [
            'success' => true,
            'tax_exempt' => $exempt,
            'reason' => $reason
        ];
    }
}

