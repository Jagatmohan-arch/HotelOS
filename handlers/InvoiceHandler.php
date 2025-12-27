<?php
/**
 * HotelOS - Invoice Handler
 * 
 * Generates and manages invoices for bookings
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class InvoiceHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get invoice data for a booking
     * 
     * @param int $bookingId Booking ID
     * @return array|null Invoice data
     */
    public function getInvoiceData(int $bookingId): ?array
    {
        $tenantId = TenantContext::getId();
        
        // Get booking with all details
        $booking = $this->db->queryOne(
            "SELECT b.*, 
                    g.title, g.first_name, g.last_name, g.phone, g.email,
                    g.address, g.city, g.state, g.pincode, g.country,
                    g.company_name, g.company_gst,
                    r.room_number, r.floor,
                    rt.name as room_type_name, rt.code as room_type_code
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id AND g.tenant_id = :tenant_id1
             LEFT JOIN rooms r ON b.room_id = r.id AND r.tenant_id = :tenant_id2
             JOIN room_types rt ON b.room_type_id = rt.id AND rt.tenant_id = :tenant_id3
             WHERE b.id = :booking_id AND b.tenant_id = :tenant_id4",
            [
                'booking_id' => $bookingId,
                'tenant_id1' => $tenantId,
                'tenant_id2' => $tenantId,
                'tenant_id3' => $tenantId,
                'tenant_id4' => $tenantId
            ],
            enforceTenant: false
        );
        
        if (!$booking) {
            return null;
        }
        
        // Get tenant/hotel details
        $tenant = $this->db->queryOne(
            "SELECT * FROM tenants WHERE id = :id",
            ['id' => $tenantId],
            enforceTenant: false
        );
        
        // Get transactions
        $transactions = $this->db->query(
            "SELECT * FROM transactions WHERE booking_id = :booking_id ORDER BY created_at",
            ['booking_id' => $bookingId]
        );
        
        // Calculate totals
        $nights = $this->calculateNights($booking['check_in_date'], $booking['check_out_date']);
        $roomCharges = (float)$booking['rate_per_night'] * $nights;
        $extraCharges = (float)($booking['extra_charges'] ?? 0);
        $discount = (float)($booking['discount_amount'] ?? 0);
        $taxableAmount = $roomCharges + $extraCharges - $discount;
        
        $gstRate = (float)($booking['gst_rate'] ?? 12);
        $cgst = round($taxableAmount * ($gstRate / 2 / 100), 2);
        $sgst = $cgst;
        $totalTax = $cgst + $sgst;
        $grandTotal = $taxableAmount + $totalTax;
        
        $paidAmount = (float)($booking['paid_amount'] ?? 0);
        $balance = $grandTotal - $paidAmount;
        
        return [
            'invoice_number' => 'INV-' . $booking['booking_number'],
            'booking' => $booking,
            'hotel' => $tenant,
            'guest' => [
                'name' => trim($booking['title'] . ' ' . $booking['first_name'] . ' ' . $booking['last_name']),
                'phone' => $booking['phone'],
                'email' => $booking['email'],
                'address' => $this->formatAddress($booking),
                'company' => $booking['company_name'],
                'gstin' => $booking['company_gst']
            ],
            'room' => [
                'number' => $booking['room_number'],
                'type' => $booking['room_type_name'],
                'floor' => $booking['floor']
            ],
            'stay' => [
                'check_in' => $booking['check_in_date'],
                'check_out' => $booking['check_out_date'],
                'nights' => $nights,
                'adults' => $booking['adults'],
                'children' => $booking['children']
            ],
            'charges' => [
                'room_rate' => (float)$booking['rate_per_night'],
                'room_total' => $roomCharges,
                'extra' => $extraCharges,
                'discount' => $discount,
                'taxable' => $taxableAmount,
                'gst_rate' => $gstRate,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'total_tax' => $totalTax,
                'grand_total' => $grandTotal,
                'paid' => $paidAmount,
                'balance' => $balance
            ],
            'transactions' => $transactions,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Record a payment transaction
     */
    public function recordPayment(int $bookingId, float $amount, string $method, ?string $reference = null): int
    {
        $tenantId = TenantContext::getId();
        
        // Insert transaction
        $this->db->execute(
            "INSERT INTO transactions (tenant_id, booking_id, type, amount, method, reference, collected_at)
             VALUES (:tenant_id, :booking_id, 'credit', :amount, :method, :reference, NOW())",
            [
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference
            ],
            enforceTenant: false
        );
        
        $transactionId = $this->db->lastInsertId();
        
        // Update booking paid amount
        $this->db->execute(
            "UPDATE bookings SET 
             paid_amount = paid_amount + :amount,
             payment_status = CASE 
                 WHEN paid_amount + :amount2 >= grand_total THEN 'paid'
                 ELSE 'partial'
             END
             WHERE id = :id",
            ['id' => $bookingId, 'amount' => $amount, 'amount2' => $amount]
        );
        
        return $transactionId;
    }
    
    /**
     * Calculate nights between dates
     */
    private function calculateNights(string $checkIn, string $checkOut): int
    {
        $in = new \DateTime($checkIn);
        $out = new \DateTime($checkOut);
        return max(1, (int)$in->diff($out)->days);
    }
    
    /**
     * Format address from booking data
     */
    private function formatAddress(array $data): string
    {
        $parts = array_filter([
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['pincode'] ?? '',
            $data['country'] ?? ''
        ]);
        return implode(', ', $parts);
    }
}
