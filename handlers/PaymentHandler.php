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
use HotelOS\Handlers\ShiftHandler;

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

        // Phase 2: Shift Guard (The Guard)
        // Prevent collecting money if shift is not open.
        // Exception: Online modes (Cashfree, OTA) which happen automatically.
        $drawerModes = ['cash', 'upi', 'card', 'cheque', 'bank_transfer'];
        if (in_array($mode, $drawerModes, true)) {
            $shiftHandler = new ShiftHandler();
            $activeShift = $shiftHandler->getCurrentShift($collectedBy);
            
            if (!$activeShift) {
                // Strict Block
                return [
                    'success' => false, 
                    'error' => 'SHIFT GUARD: You cannot collect payments without an open shift. Please start your shift first.'
                ];
            }
        }

        // Validate booking exists
        $booking = $this->db->queryOne(
            "SELECT id, booking_number, paid_amount, grand_total, status, check_in_date, check_out_date
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

            // Phase 2: Smart Payment Labels (Auto-Tagging logic)
            // Logic: Detect context based on booking status and timing
            $category = 'room_payment'; // Default fallback
            
            if ($booking['status'] === 'confirmed') {
                $category = 'Advance';
            } elseif ($booking['status'] === 'checked_in') {
                $category = 'Mid-Stay'; 
            } elseif ($booking['status'] === 'checked_out') {
                $category = 'Final Settlement';
            }

            // 1. Create Transaction Record
            $txNumber = $this->generateTransactionNumber();
            $uuid = $this->generateUuid();

            $this->db->execute(
                "INSERT INTO transactions 
                (tenant_id, uuid, transaction_number, booking_id, amount, type, ledger_type, category, 
                 payment_mode, reference_number, collected_by, collected_at, notes)
                 VALUES 
                (:tid, :uuid, :tx_num, :bid, :amount, 'credit', :ledger, :category,
                 :mode, :ref, :uid, NOW(), :notes)",
                [
                    'tid' => $tenantId,
                    'uuid' => $uuid,
                    'tx_num' => $txNumber,
                    'bid' => $bookingId,
                    'amount' => $amount,
                    'ledger' => $ledgerType,
                    'category' => $category,
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

    /**
     * Get transaction details for receipt
     */
    public function getTransaction(int $transactionId): ?array
    {
        $tenantId = TenantContext::getId();
        
        $transaction = $this->db->queryOne(
            "SELECT t.*, 
                    b.booking_number, b.grand_total, b.paid_amount, b.room_id,
                    g.first_name, g.last_name, g.phone as guest_phone,
                    r.room_number, rt.name as room_type,
                    u.first_name as collector_first_name, u.last_name as collector_last_name,
                    tn.name as hotel_name, tn.address as hotel_address, tn.phone as hotel_phone, tn.gst_number
             FROM transactions t
             JOIN bookings b ON t.booking_id = b.id
             JOIN guests g ON b.guest_id = g.id
             LEFT JOIN rooms r ON b.room_id = r.id
             LEFT JOIN room_types rt ON b.room_type_id = rt.id
             LEFT JOIN users u ON t.collected_by = u.id
             LEFT JOIN tenants tn ON t.tenant_id = tn.id
             WHERE t.id = :id AND t.tenant_id = :tid",
            ['id' => $transactionId, 'tid' => $tenantId],
            enforceTenant: false
        );

        if (!$transaction) {
            return null;
        }

        // Calculate previous paid amount (before this transaction)
        $previousPaid = (float)$transaction['paid_amount'] - (float)$transaction['amount'];
        $transaction['previous_paid'] = max(0, $previousPaid);
        $transaction['balance_after'] = (float)$transaction['grand_total'] - (float)$transaction['paid_amount'];
        $transaction['amount_in_words'] = $this->numberToWords((int)$transaction['amount']);

        return $transaction;
    }

    /**
     * Convert number to words (Indian format)
     */
    public function numberToWords(int $number): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($number == 0) return 'Zero';
        if ($number < 0) return 'Minus ' . $this->numberToWords(abs($number));

        $words = '';

        if ($number >= 10000000) {
            $words .= $this->numberToWords((int)($number / 10000000)) . ' Crore ';
            $number %= 10000000;
        }

        if ($number >= 100000) {
            $words .= $this->numberToWords((int)($number / 100000)) . ' Lakh ';
            $number %= 100000;
        }

        if ($number >= 1000) {
            $words .= $this->numberToWords((int)($number / 1000)) . ' Thousand ';
            $number %= 1000;
        }

        if ($number >= 100) {
            $words .= $ones[(int)($number / 100)] . ' Hundred ';
            $number %= 100;
        }

        if ($number >= 20) {
            $words .= $tens[(int)($number / 10)] . ' ';
            $number %= 10;
        }

        if ($number > 0) {
            $words .= $ones[$number] . ' ';
        }

        return trim($words) . ' Only';
    }
}
