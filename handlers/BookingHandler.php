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
            'sub_tenant_id' => $tenantId,
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
               AND r.is_active = 1 
               {$typeFilter}
               AND r.id NOT IN (
                   SELECT b.room_id FROM bookings b 
                   WHERE b.tenant_id = :sub_tenant_id
                     AND b.status IN ('confirmed', 'checked_in')
                     AND b.room_id IS NOT NULL
                     AND NOT (b.check_out_date <= :check_in OR b.check_in_date >= :check_out)
               )
             ORDER BY r.floor, r.room_number",
            $params,
            enforceTenant: false
        );
    }
    
    /**
     * Create a new booking
     * 
     * @param array $data Form data
     * @return array Result ['success' => bool, 'id' => int]
     */
    public function create(array $data): array
    {
        $tenantId = TenantContext::getId();
        $userId = $this->auth->id() ?? 0;
        
        // 1. Validate Guest
        $guestHandler = new GuestHandler();
        if (isset($data['guest_id']) && $data['guest_id']) {
            $guestId = (int)$data['guest_id'];
        } else {
            // Create new guest
            $guestResult = $guestHandler->create($data);
            if (!$guestResult['success']) {
                return $guestResult;
            }
            $guestId = $guestResult['id'];
        }
        
        // 2. Validate Dates
        $checkIn = $data['check_in_date'];
        $checkOut = $data['check_out_date'];
        
        // Phase 0 Validation: CheckIn < CheckOut
        if (strtotime($checkIn) >= strtotime($checkOut)) {
            return ['success' => false, 'error' => 'Check-out date must be after check-in date'];
        }
        
        // 3. Validate Room Availability
        $roomTypeId = (int)$data['room_type_id'];
        $roomId = !empty($data['room_id']) ? (int)$data['room_id'] : null; // Optional at booking time
        
        if ($roomId) {
            if (!$this->isRoomAvailable($roomId, $checkIn, $checkOut)) {
                return ['success' => false, 'error' => 'Selected room is not available for these dates'];
            }
        }
        
        // 4. Calculate Financials
        $nights = $this->calculateNights($checkIn, $checkOut);
        
        // Get base rate from room type if not overridden
        $ratePerNight = (float)$data['rate_per_night']; // Should be validated
        
        // Discount logic
        $discountAmount = 0;
        if (!empty($data['discount_type']) && !empty($data['discount_value'])) {
             // Logic for percent vs fixed
             if ($data['discount_type'] === 'percent') {
                 $discountAmount = ($ratePerNight * $nights) * ((float)$data['discount_value'] / 100);
             } else {
                 $discountAmount = (float)$data['discount_value'];
             }
        }
        
        $roomTotal = ($ratePerNight * $nights);
        $taxableAmount = $roomTotal - $discountAmount;
        
        // Calculate GST (Estimate)
        // TaxHandler handles logic (0-1000=0%, 1001-7500=12%, >7500=18%)
        $taxHandler = new TaxHandler();
        $taxResult = $taxHandler->calculate($taxableAmount, $ratePerNight);
        
        $grandTotal = $taxableAmount + $taxResult['total_tax'];
        
        // 5. Create Booking
        $bookingNumber = $this->generateBookingNumber();
        $status = 'confirmed'; // Default
        
        $this->db->execute(
            "INSERT INTO bookings 
             (tenant_id, booking_number, guest_id, room_type_id, room_id, 
              check_in_date, check_out_date, status, 
              adults, children, rate_per_night, 
              room_total, discount_type, discount_value, discount_amount,
              taxable_amount, gst_rate, cgst_amount, sgst_amount, tax_total, 
              extra_charges, grand_total, paid_amount, payment_status,
              created_by, created_at)
             VALUES 
             (:tenant_id, :booking_number, :guest_id, :room_type_id, :room_id,
              :check_in, :check_out, :status,
              :adults, :children, :rate,
              :room_total, :disc_type, :disc_val, :disc_amt,
              :taxable, :gst_rate, :cgst, :sgst, :tax_total,
              0, :grand_total, 0, 'pending',
              :created_by, NOW())",
            [
                'tenant_id' => $tenantId,
                'booking_number' => $bookingNumber,
                'guest_id' => $guestId,
                'room_type_id' => $roomTypeId,
                'room_id' => $roomId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status,
                'adults' => (int)($data['adults'] ?? 1),
                'children' => (int)($data['children'] ?? 0),
                'rate' => $ratePerNight,
                'room_total' => $roomTotal,
                'disc_type' => $data['discount_type'] ?? null,
                'disc_val' => $data['discount_value'] ?? 0,
                'disc_amt' => $discountAmount,
                'taxable' => $taxableAmount,
                'gst_rate' => $taxResult['rate'],
                'cgst' => $taxResult['cgst'],
                'sgst' => $taxResult['sgst'],
                'tax_total' => $taxResult['total_tax'],
                'grand_total' => $grandTotal,
                'created_by' => $userId
            ],
            enforceTenant: false
        );
        
        $bookingId = $this->db->lastInsertId();
        
        // 6. Handle Advance Payment
        if (!empty($data['advance_amount']) && (float)$data['advance_amount'] > 0) {
            $paymentHandler = new PaymentHandler();
            $paymentHandler->recordPayment(
                (int)$bookingId,
                (float)$data['advance_amount'],
                $data['payment_mode'] ?? 'cash',
                $data['payment_reference'] ?? null,
                'Advance Payment',
                $userId
            );
        }
        
        // Audit log
        $this->auth->logAudit('create', 'booking', (int)$bookingId);

        // Phase 3: Module 2 - WhatsApp Notification
        try {
            // Re-fetch minimal data for notification
            $notifData = [
                'id' => $bookingId,
                'guest_name' => $data['first_name'] . ' ' . ($data['last_name'] ?? ''),
                'guest_phone' => $data['phone'] ?? '',
                'hotel_name' => TenantContext::attr('name'),
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut
            ];
            \HotelOS\Core\WhatsAppService::getInstance()->sendBookingConfirmation($notifData);
        } catch (\Throwable $e) {
            // Non-blocking notification failure
            error_log("WhatsApp Trigger Failed: " . $e->getMessage());
        }
        
        return ['success' => true, 'id' => $bookingId];
    }
    
    /**
     * Check-in a guest
     * 
     * @param int $id Booking ID
     * @param int|null $roomId Assign room if not already assigned
     * @return array Result
     */
    public function checkIn(int $id, ?int $roomId = null): array
    {
        $booking = $this->getById($id);
        
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }
        
        if ($booking['status'] === 'checked_in') {
            return ['success' => false, 'error' => 'Guest is already checked in'];
        }
        
        // Assign Room Logic
        $roomUpdate = '';
        $params = ['id' => $id];
        
        if ($roomId) {
            // Verify room availability again
            if (!$this->isRoomAvailable($roomId, $booking['check_in_date'], $booking['check_out_date'], $id)) {
                 return ['success' => false, 'error' => 'Room is not available'];
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

        // Phase 3: WhatsApp Welcome
        try {
             $bFull = $this->getById($id);
             $notifData = [
                'guest_name' => $bFull['first_name'],
                'guest_phone' => $bFull['phone'],
                'hotel_name' => TenantContext::attr('name')
             ];
             \HotelOS\Core\WhatsAppService::getInstance()->sendWelcome($notifData);
        } catch (\Throwable $e) {}
        
        return ['success' => true];
    }
    
    /**
     * Check-out a guest (Phase 2: Revenue Leak Protected)
     * Calculates final bill, Checks balance, Commits updates.
     * 
     * @param int $id Booking ID
     * @param float $extraCharges Additional charges (minibar, laundry, etc.)
     * @param float $lateCheckoutFee Manual late checkout fee (0 for auto-calc)
     * @param array|null $paymentData Optional final payment ['amount', 'mode', 'reference']
     * @param bool $allowCredit Allow checkout with pending balance (Manager Override)
     * @return array Result with final amounts
     */
    public function checkOut(int $id, float $extraCharges = 0, float $lateCheckoutFee = 0, ?array $paymentData = null, bool $allowCredit = false): array
    {
        $booking = $this->getById($id);
        
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }
        
        if ($booking['status'] !== 'checked_in') {
            return ['success' => false, 'error' => 'Guest is not checked in'];
        }

        // 1. Calculate Late Fees & Totals (Dry Run)
        $timezone = TenantContext::attr('timezone', 'Asia/Kolkata');
        try {
            $now = new \DateTime('now', new \DateTimeZone($timezone));
        } catch (\Exception $e) {
            $now = new \DateTime('now'); // Fallback
        }
        
        $checkoutHour = (int)$now->format('H');
        $calculatedLateFee = 0;
        
        if ($lateCheckoutFee > 0) {
            $calculatedLateFee = $lateCheckoutFee;
        } else {
            $settings = $this->settings->getSettings();
            $lateCheckoutTime = (int)($settings['late_checkout_threshold'] ?? 14); 
            $lateCheckoutPct = (float)($settings['late_checkout_percent'] ?? 50);
            
            if ($checkoutHour >= $lateCheckoutTime) {
                $calculatedLateFee = (float)$booking['rate_per_night'] * ($lateCheckoutPct / 100);
            }
        }
        
        $projectedExtraCharges = $extraCharges > 0 ? $extraCharges : (float)($booking['extra_charges'] ?? 0);
        $projectedExtraCharges += $calculatedLateFee;
        
        // Calculate projected totals
        $taxableAmount = (float)$booking['room_total'] + $projectedExtraCharges - (float)($booking['discount_amount'] ?? 0);
        
        // GST Calc
        $isTaxExempt = !empty($booking['tax_exempt']);
        $taxHandler = new TaxHandler();
        $taxResult = $taxHandler->calculate($taxableAmount, (float)$booking['rate_per_night'], $isTaxExempt);
        $projectedGrandTotal = $taxableAmount + $taxResult['total_tax'];

        // 2. Handle Payment (If provided in checkout flow)
        $currentPaid = (float)$booking['paid_amount'];
        if (!empty($paymentData) && isset($paymentData['amount']) && (float)$paymentData['amount'] > 0) {
            // Note: We process payment FIRST to see if it clears balance
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
            $currentPaid += (float)$paymentData['amount'];
        }

        // 3. Revenue Leak Check (The Guard)
        $remainingBalance = round($projectedGrandTotal - $currentPaid, 2);
        
        // Tolerance for floating point (0.50 INR)
        if ($remainingBalance > 1.0 && !$allowCredit) {
            return [
                'success' => false, 
                'error' => "Outstanding balance: ₹" . number_format($remainingBalance, 2) . ". Payment required before checkout.",
                'balance' => $remainingBalance,
                'requires_override' => true
            ];
        }

        // 4. Commit Checkout Updates
        try {
            $this->db->beginTransaction();

            // Update bookings extra charges first
            if ($projectedExtraCharges != ($booking['extra_charges'] ?? 0)) {
                $this->db->execute(
                    "UPDATE bookings SET extra_charges = :extra WHERE id = :id",
                    ['id' => $id, 'extra' => $projectedExtraCharges]
                );
            }

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
                     ELSE 'partial'
                 END
                 WHERE id = :id",
                [
                    'id' => $id,
                    'taxable' => $taxableAmount,
                    'gst_rate' => $taxResult['rate'],
                    'cgst' => $taxResult['cgst'],
                    'sgst' => $taxResult['sgst'],
                    'tax_total' => $taxResult['total_tax'],
                    'grand_total' => $projectedGrandTotal
                ]
            );
            
            // Release Room
            if ($booking['room_id']) {
                // Phase 2 Automation: Room becomes 'dirty' automatically
                $this->db->execute(
                    "UPDATE rooms SET status = 'available', housekeeping_status = 'dirty' WHERE id = :id",
                    ['id' => $booking['room_id']]
                );
            }
            
            // Update Stats
            $guestHandler = new GuestHandler();
            $guestHandler->updateStayStats((int)$booking['guest_id'], $projectedGrandTotal);
            
            // Audit Log
            $this->auth->logAudit('check_out', 'booking', $id, [], ['balance' => $remainingBalance]);
            
            $this->db->commit();
            
            // Phase 3: WhatsApp Thank You
            try {
                $bFull = $this->getById($id);
                $notifData = [
                    'guest_name' => $bFull['first_name'],
                    'guest_phone' => $bFull['phone'],
                    'hotel_name' => TenantContext::attr('name')
                ];
                \HotelOS\Core\WhatsAppService::getInstance()->sendThankYou($notifData);
            } catch (\Throwable $e) {}
            
            return [
                'success' => true,
                'grand_total' => $projectedGrandTotal,
                'balance' => $remainingBalance
            ];

        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
     * Get data for Reservation Calendar (Tape Chart)
     * 
     * @param string $startDate Start date (Y-m-d)
     * @param int $days Number of days to show
     * @return array ['rooms' => [], 'dates' => [], 'bookings' => []]
     */
    public function getCalendarData(string $startDate, int $days = 14): array
    {
        $tenantId = TenantContext::getId();
        $start = new \DateTime($startDate);
        $end = (clone $start)->modify("+$days days");
        $endDateStr = $end->format('Y-m-d');
        
        // 1. Get all active rooms grouped by type
        $rooms = $this->db->query(
            "SELECT r.*, rt.name as room_type_name, rt.color_code 
             FROM rooms r 
             JOIN room_types rt ON r.room_type_id = rt.id 
             WHERE r.tenant_id = :tenant_id AND r.is_active = 1 
             ORDER BY r.floor, r.room_number",
            ['tenant_id' => $tenantId],
            enforceTenant: false
        );
        
        // 2. Get bookings overlapping the range
        // Overlap: NOT (end <= range_start OR start >= range_end)
        $bookings = $this->db->query(
            "SELECT b.id, b.room_id, b.guest_id, b.check_in_date, b.check_out_date, b.status, 
                    g.first_name, g.last_name, b.booking_number
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id
             WHERE b.tenant_id = :tenant_id
               AND b.room_id IS NOT NULL 
               AND b.status IN ('confirmed', 'checked_in', 'checked_out')
               AND NOT (b.check_out_date <= :start_date OR b.check_in_date >= :end_date)",
            [
                'tenant_id' => $tenantId,
                'start_date' => $startDate,
                'end_date' => $endDateStr
            ],
            enforceTenant: false
        );
        
        // 3. Generate date array for headers
        $dates = [];
        $curr = clone $start;
        for ($i = 0; $i < $days; $i++) {
            $dates[] = [
                'date' => $curr->format('Y-m-d'),
                'day' => $curr->format('D'),
                'day_num' => $curr->format('d'),
                'is_weekend' => in_array($curr->format('N'), [6, 7])
            ];
            $curr->modify('+1 day');
        }
        
        return [
            'rooms' => $rooms,
            'bookings' => $bookings,
            'dates' => $dates,
            'start' => $startDate,
            'end' => $endDateStr
        ];
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
