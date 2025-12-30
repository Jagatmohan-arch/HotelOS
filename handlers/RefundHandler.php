<?php
/**
 * HotelOS - Refund Handler
 * 
 * Manages refund requests with 2-person approval workflow:
 * - Staff initiates refund request
 * - Manager approves or rejects
 * - On approval, Credit Note transaction is created
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;
use HotelOS\Core\Auth;

class RefundHandler
{
    private Database $db;
    private Auth $auth;
    
    // Reason codes for refunds
    public const REASON_CODES = [
        'service_complaint' => 'Service Complaint',
        'early_checkout' => 'Early Checkout',
        'booking_cancelled' => 'Booking Cancelled',
        'overcharge' => 'Overcharge Correction',
        'other' => 'Other'
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
    }
    
    /**
     * Staff initiates a refund request
     * 
     * @param int $bookingId Booking to refund
     * @param float $amount Requested refund amount
     * @param string $reasonCode Reason code (from REASON_CODES)
     * @param string|null $reasonText Additional explanation
     * @param int $requestedBy User ID of staff member
     * @return array ['success' => bool, 'request_id' => int|null, 'error' => string|null]
     */
    public function requestRefund(
        int $bookingId, 
        float $amount, 
        string $reasonCode, 
        ?string $reasonText, 
        int $requestedBy
    ): array {
        $tenantId = TenantContext::getId();
        
        // Validate booking exists and get payment info
        $booking = $this->db->queryOne(
            "SELECT b.id, b.booking_number, b.paid_amount, b.grand_total, b.status,
                    CONCAT('INV-', b.booking_number) as invoice_number
             FROM bookings b 
             WHERE b.id = :id AND b.tenant_id = :tenant_id",
            ['id' => $bookingId, 'tenant_id' => $tenantId],
            enforceTenant: false
        );
        
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }
        
        // Booking must be checked_out to refund
        if ($booking['status'] !== 'checked_out') {
            return ['success' => false, 'error' => 'Booking must be checked out before requesting refund'];
        }
        
        $paidAmount = (float)$booking['paid_amount'];
        
        // Check refund amount
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Refund amount must be greater than zero'];
        }
        
        // Calculate already refunded amount
        $existingRefunds = $this->db->queryOne(
            "SELECT COALESCE(SUM(requested_amount), 0) as total_refunded
             FROM refund_requests 
             WHERE booking_id = :booking_id AND status = 'approved'",
            ['booking_id' => $bookingId]
        );
        $totalRefunded = (float)($existingRefunds['total_refunded'] ?? 0);
        $maxRefundable = $paidAmount - $totalRefunded;
        
        if ($amount > $maxRefundable) {
            return ['success' => false, 'error' => "Refund amount cannot exceed ₹" . number_format($maxRefundable, 2)];
        }
        
        // Check for pending refund request
        $pendingExists = $this->db->queryOne(
            "SELECT id FROM refund_requests 
             WHERE booking_id = :booking_id AND status = 'pending'",
            ['booking_id' => $bookingId]
        );
        
        if ($pendingExists) {
            return ['success' => false, 'error' => 'A refund request is already pending approval for this booking'];
        }
        
        // Validate reason code
        if (!array_key_exists($reasonCode, self::REASON_CODES)) {
            return ['success' => false, 'error' => 'Invalid reason code'];
        }
        
        // Create refund request
        $this->db->execute(
            "INSERT INTO refund_requests 
             (tenant_id, booking_id, invoice_number, requested_amount, max_refundable, 
              reason_code, reason_text, requested_by, status)
             VALUES 
             (:tenant_id, :booking_id, :invoice_number, :amount, :max_refundable,
              :reason_code, :reason_text, :requested_by, 'pending')",
            [
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'invoice_number' => $booking['invoice_number'],
                'amount' => $amount,
                'max_refundable' => $maxRefundable,
                'reason_code' => $reasonCode,
                'reason_text' => $reasonText,
                'requested_by' => $requestedBy
            ],
            enforceTenant: false
        );
        
        $requestId = $this->db->lastInsertId();
        
        // Audit log
        $this->auth->logAudit('refund_request', 'refund_requests', $requestId);
        
        return ['success' => true, 'request_id' => $requestId];
    }
    
    /**
     * Get pending refund requests for manager review
     * 
     * @return array List of pending requests
     */
    public function getPendingRefunds(): array
    {
        $tenantId = TenantContext::getId();
        
        return $this->db->query(
            "SELECT rr.*, 
                    b.booking_number, b.paid_amount,
                    CONCAT(g.first_name, ' ', g.last_name) as guest_name,
                    r.room_number,
                    CONCAT(u.first_name, ' ', u.last_name) as requested_by_name,
                    u.role as requested_by_role
             FROM refund_requests rr
             JOIN bookings b ON rr.booking_id = b.id
             JOIN guests g ON b.guest_id = g.id
             LEFT JOIN rooms r ON b.room_id = r.id
             JOIN users u ON rr.requested_by = u.id
             WHERE rr.tenant_id = :tenant_id AND rr.status = 'pending'
             ORDER BY rr.requested_at ASC",
            ['tenant_id' => $tenantId],
            enforceTenant: false
        );
    }
    
    /**
     * Get count of pending refunds (for badge)
     */
    public function getPendingCount(): int
    {
        $tenantId = TenantContext::getId();
        
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM refund_requests 
             WHERE tenant_id = :tenant_id AND status = 'pending'",
            ['tenant_id' => $tenantId],
            enforceTenant: false
        );
        
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Manager approves a refund request
     * Creates Credit Note transaction
     * 
     * @param int $requestId Refund request ID
     * @param int $approverId Manager's user ID
     * @param string $refundMode Payment mode (cash/card/upi/bank_transfer)
     * @return array ['success' => bool, 'credit_note' => string|null, 'error' => string|null]
     */
    public function approveRefund(int $requestId, int $approverId, string $refundMode = 'cash'): array
    {
        $tenantId = TenantContext::getId();
        
        // Get request details
        $request = $this->db->queryOne(
            "SELECT * FROM refund_requests 
             WHERE id = :id AND tenant_id = :tenant_id AND status = 'pending'",
            ['id' => $requestId, 'tenant_id' => $tenantId],
            enforceTenant: false
        );
        
        if (!$request) {
            return ['success' => false, 'error' => 'Refund request not found or already processed'];
        }
        
        // Phase A Fix #4: Prevent refund self-approval (4-eye principle / Security)
        // This check was already implemented - validating it works correctly
        if ((int)$request['requested_by'] === $approverId) {
            return ['success' => false, 'error' => 'Approver cannot be the same as requester'];
        }
        
        // Generate Credit Note number
        $creditNoteNumber = $this->generateCreditNoteNumber();
        
        // Generate transaction UUID
        $transactionUuid = $this->generateUuid();
        $transactionNumber = $this->generateTransactionNumber();
        
        // Create debit transaction (Credit Note)
        $this->db->execute(
            "INSERT INTO transactions 
             (tenant_id, uuid, transaction_number, booking_id, amount, type, category, 
              payment_mode, reference_number, collected_by, notes)
             VALUES 
             (:tenant_id, :uuid, :txn_number, :booking_id, :amount, 'debit', 'refund',
              :payment_mode, :reference, :collected_by, :notes)",
            [
                'tenant_id' => $tenantId,
                'uuid' => $transactionUuid,
                'txn_number' => $transactionNumber,
                'booking_id' => $request['booking_id'],
                'amount' => $request['requested_amount'],
                'payment_mode' => $refundMode,
                'reference' => $creditNoteNumber,
                'collected_by' => $approverId,
                'notes' => "Credit Note: {$creditNoteNumber} | Reason: " . self::REASON_CODES[$request['reason_code']]
            ],
            enforceTenant: false
        );
        
        $transactionId = $this->db->lastInsertId();
        
        // Update booking paid_amount
        $this->db->execute(
            "UPDATE bookings SET 
             paid_amount = paid_amount - :amount,
             payment_status = CASE 
                 WHEN paid_amount - :amount2 <= 0 THEN 'refunded'
                 WHEN paid_amount - :amount3 < grand_total THEN 'partial'
                 ELSE payment_status
             END
             WHERE id = :id",
            [
                'id' => $request['booking_id'],
                'amount' => $request['requested_amount'],
                'amount2' => $request['requested_amount'],
                'amount3' => $request['requested_amount']
            ]
        );
        
        // Update refund request status
        $this->db->execute(
            "UPDATE refund_requests SET 
             status = 'approved',
             approved_by = :approver,
             approved_at = NOW(),
             credit_note_number = :cn_number,
             transaction_id = :txn_id
             WHERE id = :id",
            [
                'id' => $requestId,
                'approver' => $approverId,
                'cn_number' => $creditNoteNumber,
                'txn_id' => $transactionId
            ]
        );
        
        // Audit log with financial details
        $this->auth->logAudit(
            'refund_approved', 
            'refund_requests', 
            $requestId,
            ['status' => 'pending', 'amount' => $request['requested_amount']],
            ['status' => 'approved', 'credit_note' => $creditNoteNumber, 'approver' => $approverId]
        );
        
        return [
            'success' => true, 
            'credit_note' => $creditNoteNumber,
            'transaction_id' => $transactionId
        ];
    }
    
    /**
     * Manager rejects a refund request
     * 
     * @param int $requestId Refund request ID
     * @param int $approverId Manager's user ID
     * @param string $note Rejection reason
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function rejectRefund(int $requestId, int $approverId, string $note = ''): array
    {
        $tenantId = TenantContext::getId();
        
        // Get request details
        $request = $this->db->queryOne(
            "SELECT * FROM refund_requests 
             WHERE id = :id AND tenant_id = :tenant_id AND status = 'pending'",
            ['id' => $requestId, 'tenant_id' => $tenantId],
            enforceTenant: false
        );
        
        if (!$request) {
            return ['success' => false, 'error' => 'Refund request not found or already processed'];
        }
        
        // Update status
        $this->db->execute(
            "UPDATE refund_requests SET 
             status = 'rejected',
             approved_by = :approver,
             approved_at = NOW(),
             rejection_note = :note
             WHERE id = :id",
            [
                'id' => $requestId,
                'approver' => $approverId,
                'note' => $note
            ]
        );
        
        // Audit log
        $this->auth->logAudit('refund_rejected', 'refund_requests', $requestId);
        
        return ['success' => true];
    }
    
    /**
     * Get refund history for a booking
     */
    public function getRefundHistory(int $bookingId): array
    {
        return $this->db->query(
            "SELECT rr.*,
                    CONCAT(u1.first_name, ' ', u1.last_name) as requested_by_name,
                    CONCAT(u2.first_name, ' ', u2.last_name) as approved_by_name
             FROM refund_requests rr
             LEFT JOIN users u1 ON rr.requested_by = u1.id
             LEFT JOIN users u2 ON rr.approved_by = u2.id
             WHERE rr.booking_id = :booking_id
             ORDER BY rr.requested_at DESC",
            ['booking_id' => $bookingId]
        );
    }
    
    /**
     * Get all refunds with filters (for admin view)
     */
    public function getAllRefunds(
        ?string $status = null, 
        ?string $dateFrom = null, 
        ?string $dateTo = null,
        int $limit = 50
    ): array {
        $tenantId = TenantContext::getId();
        
        $sql = "SELECT rr.*,
                       b.booking_number,
                       CONCAT(g.first_name, ' ', g.last_name) as guest_name,
                       CONCAT(u1.first_name, ' ', u1.last_name) as requested_by_name,
                       CONCAT(u2.first_name, ' ', u2.last_name) as approved_by_name
                FROM refund_requests rr
                JOIN bookings b ON rr.booking_id = b.id
                JOIN guests g ON b.guest_id = g.id
                LEFT JOIN users u1 ON rr.requested_by = u1.id
                LEFT JOIN users u2 ON rr.approved_by = u2.id
                WHERE rr.tenant_id = :tenant_id";
        
        $params = ['tenant_id' => $tenantId];
        
        if ($status) {
            $sql .= " AND rr.status = :status";
            $params['status'] = $status;
        }
        
        if ($dateFrom) {
            $sql .= " AND DATE(rr.requested_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(rr.requested_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }
        
        $sql .= " ORDER BY rr.requested_at DESC LIMIT " . (int)$limit;
        
        return $this->db->query($sql, $params, enforceTenant: false);
    }
    
    /**
     * Generate Credit Note number
     * Format: CN-YYMMDD-NNN
     */
    private function generateCreditNoteNumber(): string
    {
        $prefix = 'CN-' . date('ymd') . '-';
        $tenantId = TenantContext::getId();
        
        $result = $this->db->queryOne(
            "SELECT credit_note_number FROM refund_requests 
             WHERE tenant_id = :tenant_id 
               AND credit_note_number LIKE :prefix
             ORDER BY id DESC LIMIT 1",
            ['tenant_id' => $tenantId, 'prefix' => $prefix . '%'],
            enforceTenant: false
        );
        
        if ($result && $result['credit_note_number']) {
            $lastNum = (int)substr($result['credit_note_number'], -3);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }
        
        return $prefix . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate Transaction number
     */
    private function generateTransactionNumber(): string
    {
        $prefix = 'TXN-' . date('ymd') . '-';
        $tenantId = TenantContext::getId();
        
        $result = $this->db->queryOne(
            "SELECT transaction_number FROM transactions 
             WHERE tenant_id = :tenant_id 
               AND transaction_number LIKE :prefix
             ORDER BY id DESC LIMIT 1",
            ['tenant_id' => $tenantId, 'prefix' => $prefix . '%'],
            enforceTenant: false
        );
        
        if ($result && $result['transaction_number']) {
            $lastNum = (int)substr($result['transaction_number'], -3);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }
        
        return $prefix . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
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
