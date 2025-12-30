<?php

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class ShiftHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get the current OPEN shift for a user
     */
    public function getCurrentShift(int $userId): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM shifts 
             WHERE user_id = :user_id 
             AND status = 'OPEN' 
             AND tenant_id = :tenant_id
             LIMIT 1",
            [
                'user_id' => $userId, 
                'tenant_id' => TenantContext::getId()
            ],
            enforceTenant: false
        );
    }

    /**
     * Start a new shift
     */
    public function startShift(int $userId, float $openingCash): array
    {
        // 1. Check if already open
        if ($this->getCurrentShift($userId)) {
            return ['success' => false, 'message' => 'You already have an active shift.'];
        }

        $tenantId = TenantContext::getId();

        // 2. Create Shift (INSERT doesn't need enforceTenant: false as long as we provide tenant_id, but safer to be explicit if wrapper behavior is weird)
        // Actually Database::execute for INSERT adds tenant_id if missing. Here we provide it.
        // Let's force enforceTenant: false to be sure we control the SQL.
        $id = $this->db->execute(
            "INSERT INTO shifts (tenant_id, user_id, opening_cash, shift_start_at, status)
             VALUES (:tenant_id, :user_id, :opening_cash, NOW(), 'OPEN')",
            [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'opening_cash' => $openingCash
            ],
            enforceTenant: false
        );

        return ['success' => true, 'shift_id' => $id, 'message' => 'Shift started successfully.'];
    }

    /**
     * Calculate expected cash for the current shift
     * Logic: Opening Cash + Total Cash Transactions + Ledger Additions - Ledger Expenses
     */
    /**
     * Calculate expected cash for the current shift
     * Logic: Opening Cash + Total Cash Transactions + Ledger Additions - Ledger Expenses
     * @param string|null $endTime Optional end time for historical verification
     */
    public function getExpectedCash(int $userId, int $shiftId, string $startTime, ?string $endTime = null): float
    {
        $tenantId = TenantContext::getId();

        // Get opening cash
        $shift = $this->db->queryOne(
            "SELECT opening_cash FROM shifts WHERE id = :id",
            ['id' => $shiftId]
        );
        $opening = (float)($shift['opening_cash'] ?? 0);
        
        // Prepare query conditions
        $timeCondition = "AND collected_at >= :start_time";
        $params = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'start_time' => $startTime
        ];
        
        if ($endTime) {
            $timeCondition .= " AND collected_at <= :end_time";
            $params['end_time'] = $endTime;
        }

        // Get all CASH transactions collected by this user within shift window
        $txns = $this->db->queryOne(
            "SELECT 
                SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_in,
                SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_out
             FROM transactions 
             WHERE tenant_id = :tenant_id 
             AND collected_by = :user_id 
             AND ledger_type = 'cash_drawer'
             $timeCondition",
            $params,
            enforceTenant: false
        );

        $bookingCash = ((float)$txns['total_in']) - ((float)$txns['total_out']);
        
        // Phase F2: Include Ledger Entries
        $ledger = $this->db->queryOne(
            "SELECT 
                SUM(CASE WHEN type = 'addition' THEN amount ELSE 0 END) as additions,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses
             FROM cash_ledger
             WHERE shift_id = :shift_id",
            ['shift_id' => $shiftId]
        );
        
        $ledgerNet = ((float)$ledger['additions']) - ((float)$ledger['expenses']);
        
        return $opening + $bookingCash + $ledgerNet;
    }

    /**
     * Add a petty cash entry (Phase F2)
     */
    public function addLedgerEntry(int $userId, int $shiftId, string $type, float $amount, string $category, string $description): bool
    {
        $tenantId = TenantContext::getId();
        
        // Verify shift belongs to user and is open
        $shift = $this->db->queryOne(
            "SELECT id FROM shifts WHERE id = :id AND user_id = :uid AND status = 'OPEN'",
            ['id' => $shiftId, 'uid' => $userId]
        );
        
        if (!$shift) return false;
        
        return (bool) $this->db->execute(
            "INSERT INTO cash_ledger (tenant_id, shift_id, user_id, type, amount, category, description, created_at)
             VALUES (:tid, :sid, :uid, :type, :amount, :cat, :desc, NOW())",
            [
                'tid' => $tenantId,
                'sid' => $shiftId,
                'uid' => $userId,
                'type' => $type,
                'amount' => $amount,
                'cat' => $category,
                'desc' => $description
            ]
        );
    }

    /**
     * Get ledger entries for a shift
     */
    public function getShiftLedger(int $shiftId): array
    {
        return $this->db->query(
            "SELECT * FROM cash_ledger WHERE shift_id = :sid ORDER BY created_at DESC",
            ['sid' => $shiftId],
            enforceTenant: false // shift_id is unique enough, but safe practice
        );
    }

    /**
     * End a shift
     */
    public function endShift(int $shiftId, int $userId, float $closingCash, ?int $handoverTo, ?string $notes): array
    {
        $tenantId = TenantContext::getId();
        
        // Get shift details (check any status, not just OPEN)
        $shift = $this->db->queryOne(
            "SELECT * FROM shifts WHERE id = :id AND tenant_id = :tenant_id",
            ['id' => $shiftId, 'tenant_id' => $tenantId],
            enforceTenant: false
        );

        if (!$shift) {
            return ['success' => false, 'message' => 'Shift not found.'];
        }
        
        // Phase D: Shift Immutability Protection (Financial Security)
        // Prevent modification of already closed shifts to stop fraud
        if ($shift['status'] === 'CLOSED') {
            // Log the attempted modification for owner review
            $auth = \HotelOS\Core\Auth::getInstance();
            $auth->logAudit(
                'shift_modification_blocked',
                'shifts',
                $shiftId,
                ['status' => 'CLOSED', 'closing_cash' => $shift['closing_cash']],
                ['attempted_by' => $userId, 'reason' => 'Shift already closed - immutable']
            );
            
            return [
                'success' => false, 
                'message' => 'This shift is already closed and cannot be modified. Contact owner if correction needed.',
                'shift_id' => $shiftId,
                'closed_at' => $shift['shift_end_at']
            ];
        }
        
        // Verify user owns this shift
        if ((int)$shift['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'You can only close your own shift.'];
        }

        // Calculate expected
        $expected = $this->getExpectedCash($userId, $shiftId, $shift['shift_start_at']);
        $variance = $closingCash - $expected;

        // Close Shift
        $this->db->execute(
            "UPDATE shifts SET 
                closing_cash = :closing,
                system_expected_cash = :expected,
                variance_amount = :variance,
                handover_to_user_id = :handover,
                notes = :notes,
                shift_end_at = NOW(),
                status = 'CLOSED'
             WHERE id = :id",
            [
                'closing' => $closingCash,
                'expected' => $expected,
                'variance' => $variance,
                'handover' => $handoverTo,
                'notes' => $notes,
                'id' => $shiftId
            ]
        );

        return ['success' => true, 'message' => 'Shift closed successfully.'];
    }

    /**
     * Get recent shifts for history
     */
    public function getRecentShifts(int $limit = 5): array
    {
        return $this->db->query(
            "SELECT s.*, 
                    u1.first_name as user_name,
                    u2.first_name as handover_name
             FROM shifts s
             JOIN users u1 ON s.user_id = u1.id
             LEFT JOIN users u2 ON s.handover_to_user_id = u2.id
             WHERE s.tenant_id = :tenant_id
             ORDER BY s.created_at DESC
             LIMIT :limit",
            [
                'tenant_id' => TenantContext::getId(),
                'limit' => $limit
            ],
            enforceTenant: false
        );
    }
    
    /**
     * Phase F3: Verify a shift (Manager Action)
     */
    public function verifyShift(int $shiftId, int $managerId, string $note = ''): bool
    {
        $tenantId = TenantContext::getId();
        
        return (bool) $this->db->execute(
            "UPDATE shifts SET 
                verified_by = :mid, 
                verified_at = NOW(), 
                manager_note = :note 
             WHERE id = :sid AND tenant_id = :tid",
            [
                'mid' => $managerId,
                'note' => $note,
                'sid' => $shiftId,
                'tid' => $tenantId
            ]
        );
    }
    
    /**
     * Phase F3: Get all closed shifts for Audit
     */
    public function getAllClosedShifts(int $limit = 50, int $offset = 0): array
    {
        $tenantId = TenantContext::getId();
        return $this->db->query(
            "SELECT s.*, 
                    u.first_name, u.last_name,
                    v.first_name as verifier_name
             FROM shifts s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN users v ON s.verified_by = v.id
             WHERE s.tenant_id = :tid AND s.status = 'CLOSED'
             ORDER BY s.shift_end_at DESC
             LIMIT :limit OFFSET :offset",
            ['tid' => $tenantId, 'limit' => $limit, 'offset' => $offset]
        );
    }
}
