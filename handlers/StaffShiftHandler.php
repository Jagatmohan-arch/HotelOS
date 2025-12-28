<?php
/**
 * HotelOS - Staff Shift Handler
 * 
 * Manages staff shifts, duty tracking, and handover workflow
 * Tracks cash collection, bookings, and activities per shift
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;

class StaffShiftHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Start a new shift for a staff member
     */
    public function startShift(int $userId, float $openingCash = 0): array
    {
        // Check if user has an active shift
        $activeShift = $this->getActiveShift($userId);
        if ($activeShift) {
            return ['success' => false, 'error' => 'You already have an active shift'];
        }
        
        $shiftId = $this->db->insert('staff_shifts', [
            'user_id' => $userId,
            'shift_date' => date('Y-m-d'),
            'shift_start' => date('Y-m-d H:i:s'),
            'opening_cash' => $openingCash,
            'status' => 'active'
        ]);
        
        return [
            'success' => true,
            'shift_id' => $shiftId,
            'message' => 'Shift started successfully'
        ];
    }
    
    /**
     * Get active shift for a user
     */
    public function getActiveShift(int $userId): ?array
    {
        return $this->db->queryOne(
            "SELECT s.*, u.first_name, u.last_name 
             FROM staff_shifts s
             JOIN users u ON s.user_id = u.id
             WHERE s.user_id = :user_id 
             AND s.status = 'active'",
            ['user_id' => $userId]
        );
    }
    
    /**
     * Log an activity in the current shift
     */
    public function logActivity(int $shiftId, string $type, array $data): bool
    {
        $this->db->insert('shift_activities', [
            'shift_id' => $shiftId,
            'activity_type' => $type,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'amount' => $data['amount'] ?? 0,
            'payment_mode' => $data['payment_mode'] ?? 'cash',
            'description' => $data['description'] ?? null
        ]);
        
        // Update shift totals
        $this->updateShiftTotals($shiftId);
        
        return true;
    }
    
    /**
     * Update shift totals from activities
     */
    private function updateShiftTotals(int $shiftId): void
    {
        $totals = $this->db->queryOne(
            "SELECT 
                SUM(CASE WHEN payment_mode = 'cash' AND amount > 0 THEN amount ELSE 0 END) as cash_collected,
                SUM(CASE WHEN payment_mode = 'upi' AND amount > 0 THEN amount ELSE 0 END) as upi_collected,
                SUM(CASE WHEN payment_mode = 'card' AND amount > 0 THEN amount ELSE 0 END) as card_collected,
                SUM(CASE WHEN activity_type = 'checkin' THEN 1 ELSE 0 END) as checkins_count,
                SUM(CASE WHEN activity_type = 'checkout' THEN 1 ELSE 0 END) as checkouts_count,
                SUM(CASE WHEN activity_type IN ('checkin', 'checkout') THEN 1 ELSE 0 END) as bookings_count
             FROM shift_activities
             WHERE shift_id = :shift_id",
            ['shift_id' => $shiftId]
        );
        
        if ($totals) {
            $totalCollected = ($totals['cash_collected'] ?? 0) + 
                              ($totals['upi_collected'] ?? 0) + 
                              ($totals['card_collected'] ?? 0);
            
            $this->db->execute(
                "UPDATE staff_shifts SET 
                    cash_collected = :cash,
                    upi_collected = :upi,
                    card_collected = :card,
                    total_collected = :total,
                    checkins_count = :checkins,
                    checkouts_count = :checkouts,
                    bookings_count = :bookings
                 WHERE id = :id",
                [
                    'id' => $shiftId,
                    'cash' => $totals['cash_collected'] ?? 0,
                    'upi' => $totals['upi_collected'] ?? 0,
                    'card' => $totals['card_collected'] ?? 0,
                    'total' => $totalCollected,
                    'checkins' => $totals['checkins_count'] ?? 0,
                    'checkouts' => $totals['checkouts_count'] ?? 0,
                    'bookings' => $totals['bookings_count'] ?? 0
                ]
            );
        }
    }
    
    /**
     * Get shift summary with all activities
     */
    public function getShiftSummary(int $shiftId): array
    {
        $shift = $this->db->queryOne(
            "SELECT s.*, u.first_name, u.last_name 
             FROM staff_shifts s
             JOIN users u ON s.user_id = u.id
             WHERE s.id = :id",
            ['id' => $shiftId]
        );
        
        if (!$shift) {
            return ['success' => false, 'error' => 'Shift not found'];
        }
        
        $activities = $this->db->query(
            "SELECT * FROM shift_activities 
             WHERE shift_id = :shift_id 
             ORDER BY created_at DESC",
            ['shift_id' => $shiftId]
        );
        
        // Calculate expected cash
        $expectedCash = $shift['opening_cash'] + $shift['cash_collected'];
        
        return [
            'success' => true,
            'shift' => $shift,
            'activities' => $activities,
            'expected_cash' => $expectedCash,
            'summary' => [
                'duration' => $this->calculateDuration($shift['shift_start'], $shift['shift_end']),
                'total_transactions' => count($activities),
                'cash_in_hand' => $expectedCash
            ]
        ];
    }
    
    /**
     * End shift and prepare for handover
     */
    public function endShift(int $shiftId, float $closingCash, ?string $notes = null): array
    {
        $shift = $this->db->queryOne(
            "SELECT * FROM staff_shifts WHERE id = :id AND status = 'active'",
            ['id' => $shiftId]
        );
        
        if (!$shift) {
            return ['success' => false, 'error' => 'Active shift not found'];
        }
        
        $expectedCash = $shift['opening_cash'] + $shift['cash_collected'];
        $difference = $closingCash - $expectedCash;
        
        $this->db->execute(
            "UPDATE staff_shifts SET 
                shift_end = NOW(),
                closing_cash = :closing,
                expected_cash = :expected,
                cash_difference = :diff,
                handover_notes = :notes,
                status = 'handover_pending'
             WHERE id = :id",
            [
                'id' => $shiftId,
                'closing' => $closingCash,
                'expected' => $expectedCash,
                'diff' => $difference,
                'notes' => $notes
            ]
        );
        
        return [
            'success' => true,
            'expected_cash' => $expectedCash,
            'closing_cash' => $closingCash,
            'difference' => $difference,
            'status' => $difference == 0 ? 'balanced' : ($difference > 0 ? 'excess' : 'shortage')
        ];
    }
    
    /**
     * Complete handover to next staff
     */
    public function completeHandover(int $fromShiftId, int $toUserId, float $cashHanded): array
    {
        $fromShift = $this->db->queryOne(
            "SELECT * FROM staff_shifts WHERE id = :id AND status = 'handover_pending'",
            ['id' => $fromShiftId]
        );
        
        if (!$fromShift) {
            return ['success' => false, 'error' => 'No pending handover found'];
        }
        
        // Start new shift for receiving user
        $newShift = $this->startShift($toUserId, $cashHanded);
        
        if (!$newShift['success']) {
            return $newShift;
        }
        
        // Record handover
        $this->db->insert('shift_handovers', [
            'from_shift_id' => $fromShiftId,
            'to_shift_id' => $newShift['shift_id'],
            'from_user_id' => $fromShift['user_id'],
            'to_user_id' => $toUserId,
            'handover_time' => date('Y-m-d H:i:s'),
            'cash_handed' => $cashHanded,
            'status' => 'pending'
        ]);
        
        // Update old shift
        $this->db->execute(
            "UPDATE staff_shifts SET 
                handover_to_user_id = :to_user,
                status = 'handover_complete'
             WHERE id = :id",
            ['id' => $fromShiftId, 'to_user' => $toUserId]
        );
        
        return [
            'success' => true,
            'new_shift_id' => $newShift['shift_id'],
            'message' => 'Handover completed successfully'
        ];
    }
    
    /**
     * Get today's shifts summary for dashboard
     */
    public function getTodayShiftsSummary(): array
    {
        $shifts = $this->db->query(
            "SELECT s.*, u.first_name, u.last_name
             FROM staff_shifts s
             JOIN users u ON s.user_id = u.id
             WHERE s.shift_date = CURDATE()
             ORDER BY s.shift_start DESC"
        );
        
        $summary = $this->db->queryOne(
            "SELECT 
                COUNT(*) as total_shifts,
                SUM(cash_collected) as total_cash,
                SUM(upi_collected) as total_upi,
                SUM(card_collected) as total_card,
                SUM(total_collected) as grand_total,
                SUM(checkins_count) as total_checkins,
                SUM(checkouts_count) as total_checkouts
             FROM staff_shifts
             WHERE shift_date = CURDATE()"
        );
        
        return [
            'shifts' => $shifts,
            'summary' => $summary ?? [],
            'active_count' => count(array_filter($shifts, fn($s) => $s['status'] === 'active'))
        ];
    }
    
    /**
     * Get available staff for handover
     */
    public function getAvailableStaffForHandover(int $excludeUserId): array
    {
        return $this->db->query(
            "SELECT id, first_name, last_name, email
             FROM users
             WHERE id != :exclude
             AND is_active = 1
             AND role IN ('staff', 'manager')
             ORDER BY first_name",
            ['exclude' => $excludeUserId]
        );
    }
    
    /**
     * Calculate shift duration
     */
    private function calculateDuration(?string $start, ?string $end): string
    {
        if (!$start) return '0h 0m';
        
        $startTime = strtotime($start);
        $endTime = $end ? strtotime($end) : time();
        
        $diff = $endTime - $startTime;
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        
        return "{$hours}h {$minutes}m";
    }
}
