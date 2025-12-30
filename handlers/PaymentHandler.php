<?php
/**
 * HotelOS - Payment Handler
 * 
 * Centralizes all money collection logic to ensure data integrity.
 * Guarantees that every change to `bookings.paid_amount` has a corresponding `transaction`.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;
use HotelOS\Core\Auth;

class PaymentHandler
{
    private Database $db;
    private Auth $auth;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
    }

    /**
     * Record a payment for a booking
     * 
     * @param int $bookingId Booking ID
     * @param float $amount Amount paid
     * @param string $mode Payment mode ('cash', 'upi', 'card', 'bank_transfer', 'cashfree', 'cheque')
     * @param string|null $reference Transaction reference (UPI ID, Cheque No, etc.)
     * @param string|null $notes Optional notes
     * @param int|null $collectedBy User ID (defaults to current user)
     * @return array ['success' => bool, 'transaction_id' => int, 'new_balance' => float]
     */
    public function recordPayment(
        int $bookingId, 
        float $amount, 
        string $mode, 
        ?string $reference = null,
        ?string $notes = null,
        ?int $collectedBy = null
    ): array {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than zero'];
        }

        // Phase A Fix #3: Strict payment mode validation (Security: Prevent spoofing)
        $allowedModes = ['cash', 'upi', 'card', 'bank_transfer', 'cheque', 'cashfree', 'online', 'ota_prepaid', 'credit', 'post_bill', 'wallet'];
        if (!in_array($mode, $allowedModes, true)) {
            return ['success' => false, 'error' => 'Invalid payment mode. Allowed: ' . implode(', ', $allowedModes)];
        }

        $tenantId = TenantContext::getId();
        $collectedBy = $collectedBy ?? $this->auth->id();

        if (!$collectedBy) {
            return ['success' => false, 'error' => 'Payment collector not identified'];
        }

        // Validate booking exists
        $booking = $this->db->queryOne(
            "SELECT id, booking_number, paid_amount, grand_total, status 
             FROM bookings WHERE id = :id AND tenant_id = :tid",
            ['id' => $bookingId, 'tid' => $tenantId],
            enforceTenant: false
        );

        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }

        try {
            $this->db->beginTransaction();

            // Determine Ledger Type
            $ledgerType = 'cash_drawer'; // Default
            
            $bankModes = ['upi', 'card', 'bank_transfer', 'cashfree', 'cheque'];
            if (in_array($mode, $bankModes)) {
                $ledgerType = 'bank';
            } elseif ($mode === 'ota_prepaid' || $mode === 'online') {
                $ledgerType = 'ota_receivable';
            } elseif ($mode === 'credit' || $mode === 'post_bill') {
                $ledgerType = 'credit_ledger';
            }

            // 1. Create Transaction Record
            $txNumber = $this->generateTransactionNumber();
            $uuid = $this->generateUuid();

            $this->db->execute(
                "INSERT INTO transactions 
                (tenant_id, uuid, transaction_number, booking_id, amount, type, ledger_type, category, 
                 payment_mode, reference_number, collected_by, collected_at, notes)
                 VALUES 
                (:tid, :uuid, :tx_num, :bid, :amount, 'credit', :ledger, 'room_payment',
                 :mode, :ref, :uid, NOW(), :notes)",
                [
                    'tid' => $tenantId,
                    'uuid' => $uuid,
                    'tx_num' => $txNumber,
                    'bid' => $bookingId,
                    'amount' => $amount,
                    'ledger' => $ledgerType,
                    'mode' => $mode,
                    'ref' => $reference,
                    'uid' => $collectedBy,
                    'notes' => $notes
                ],
                enforceTenant: false
            );

            $transactionId = $this->db->lastInsertId();

            // 2. Update Booking Paid Amount
            $newPaidAmount = (float)$booking['paid_amount'] + $amount;
            $balance = (float)$booking['grand_total'] - $newPaidAmount;
            
            // Determine payment status
            $paymentStatus = 'partial';
            if ($balance <= 0) {
                $paymentStatus = 'paid';
            }

            $this->db->execute(
                "UPDATE bookings SET 
                 paid_amount = :paid,
                 payment_status = :status,
                 updated_at = NOW()
                 WHERE id = :id",
                [
                    'id' => $bookingId,
                    'paid' => $newPaidAmount,
                    'status' => $paymentStatus
                ]
            );

            // 3. Audit Log
            $this->auth->logAudit(
                'payment_collected',
                'booking',
                $bookingId,
                ['paid_amount' => $booking['paid_amount']],
                ['paid_amount' => $newPaidAmount, 'transaction_id' => $transactionId, 'mode' => $mode]
            );

            $this->db->commit();

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'transaction_number' => $txNumber,
                'new_balance' => $balance,
                'payment_status' => $paymentStatus
            ];

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Payment recording failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to record payment: ' . $e->getMessage()];
        }
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
