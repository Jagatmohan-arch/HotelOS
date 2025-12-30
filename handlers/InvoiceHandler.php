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
     * CRITICAL FIX: Delegates to PaymentHandler to ensure ledger_type is set
     * 
     * @deprecated Use PaymentHandler::recordPayment() directly
     * @param int $bookingId Booking ID
     * @param float $amount Payment amount
     * @param string $method Payment method (cash, upi, card, etc)
     * @param string|null $reference Optional reference number
     * @return int Transaction ID
     * @throws \Exception if payment fails
     */
    public function recordPayment(int $bookingId, float $amount, string $method, ?string $reference = null): int
    {
        // Delegate to PaymentHandler which handles:
        // 1. Transaction insert with ledger_type (cash_drawer, bank, ota, credit)
        // 2. Booking paid_amount update with atomicity
        // 3. Audit logging
        $paymentHandler = new PaymentHandler();
        $result = $paymentHandler->recordPayment(
            $bookingId,
            $amount,
            $method,
            $reference,
            'Payment via Invoice Handler', // notes
            null // collected_by (will use Auth::id())
        );
        
        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Payment failed');
        }
        
        return (int)$result['transaction_id'];
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
    
    /**
     * Phase F: Generate PDF Invoice
     * Creates professional GST-compliant invoice PDF
     * 
     * @param int $bookingId Booking ID
     * @return void Outputs PDF directly
     */
    public function generateInvoicePDF(int $bookingId): void
    {
        require_once BASE_PATH . '/core/PDFGenerator.php';
        $data = $this->getInvoiceData($bookingId);
        
        if (!$data) {
            die('Invoice not found');
        }
        
        $html = $this->buildInvoiceHTML($data);
        \HotelOS\Utils\PDFGenerator::generateFromHTML(
            $html,
            $data['invoice_number'],
            true // download
        );
    }
    
    /**
     * Build HTML for invoice PDF
     */
    private function buildInvoiceHTML(array $data): string
    {
        $invoice = $data;
        $hotel = $data['hotel'];
        $guest = $data['guest'];
        $charges = $data['charges'];
        $stay = $data['stay'];
        
        $amountInWords = \HotelOS\Utils\PDFGenerator::numberToWords($charges['grand_total']);
        
        return <<<HTML
<div class="invoice-container">
    <!-- Header -->
    <div class="header">
        <div class="hotel-name">{$hotel['name']}</div>
        <div style="font-size: 9pt;">{$hotel['address']}, {$hotel['city']}, {$hotel['state']} - {$hotel['pincode']}</div>
        <div style="font-size: 9pt;">Phone: {$hotel['phone']} | Email: {$hotel['email']}</div>
        <div style="font-size: 9pt;"><strong>GSTIN:</strong> {$hotel['gst_number']}</div>
    </div>
    
    <div class="invoice-title">TAX INVOICE</div>
    
    <!-- Invoice & Billing Details -->
    <table class="info-table">
        <tr>
            <td width="50%">
                <strong>Invoice To:</strong><br>
                {$guest['name']}<br>
                {$guest['address']}<br>
                Phone: {$guest['phone']}<br>
                Email: {$guest['email']}
                " . ($guest['gstin'] ? "<br><strong>GSTIN:</strong> {$guest['gstin']}" : "") . "
            </td>
            <td width="50%" style="text-align: right;">
                <strong>Invoice No:</strong> {$invoice['invoice_number']}<br>
                <strong>Date:</strong> " . date('d-M-Y') . "<br>
                <strong>Booking No:</strong> {$invoice['booking']['booking_number']}<br>
                <strong>Room:</strong> {$data['room']['number']} ({$data['room']['type']})<br>
            </td>
        </tr>
    </table>
    
    <!-- Stay Details -->
    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    <strong>Room Charges</strong><br>
                    Check-in: " . date('d-M-Y', strtotime($stay['check_in'])) . "<br>
                    Check-out: " . date('d-M-Y', strtotime($stay['check_out'])) . "<br>
                    Guests: {$stay['adults']} Adults, {$stay['children']} Children
                </td>
                <td class="text-center">{$stay['nights']} Night(s)</td>
                <td class="text-right">₹ " . number_format($charges['room_rate'], 2) . "</td>
                <td class="text-right">₹ " . number_format($charges['room_total'], 2) . "</td>
            </tr>
            " . ($charges['extra'] > 0 ? "<tr>
                <td>2</td>
                <td>Extra Charges / Services</td>
                <td class=\"text-center\">-</td>
                <td class=\"text-right\">-</td>
                <td class=\"text-right\">₹ " . number_format($charges['extra'], 2) . "</td>
            </tr>" : "") . "
            " . ($charges['discount'] > 0 ? "<tr>
                <td>-</td>
                <td>Discount</td>
                <td class=\"text-center\">-</td>
                <td class=\"text-right\">-</td>
                <td class=\"text-right\">(₹ " . number_format($charges['discount'], 2) . ")</td>
            </tr>" : "") . "
        </tbody>
    </table>
    
    <!-- Totals -->
    <div class="totals-section">
        <table>
            <tr>
                <td><strong>Taxable Amount:</strong></td>
                <td class="text-right">₹ " . number_format($charges['taxable'], 2) . "</td>
            </tr>
            <tr>
                <td>CGST @ " . ($charges['gst_rate'] / 2) . "%:</td>
                <td class="text-right">₹ " . number_format($charges['cgst'], 2) . "</td>
            </tr>
            <tr>
                <td>SGST @ " . ($charges['gst_rate'] / 2) . "%:</td>
                <td class="text-right">₹ " . number_format($charges['sgst'], 2) . "</td>
            </tr>
            <tr style="border-top: 2px solid #000; font-weight: bold; font-size: 12pt;">
                <td>Grand Total:</td>
                <td class="text-right">₹ " . number_format($charges['grand_total'], 2) . "</td>
            </tr>
            <tr style="border-top: 1px solid #ccc;">
                <td>Paid:</td>
                <td class="text-right">₹ " . number_format($charges['paid'], 2) . "</td>
            </tr>
            <tr style="font-weight: bold;">
                <td>Balance Due:</td>
                <td class="text-right">₹ " . number_format($charges['balance'], 2) . "</td>
            </tr>
        </table>
    </div>
    
    <div style="clear: both; margin-top: 60px;">
        <p><strong>Amount in Words:</strong> {$amountInWords}</p>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p><strong>Terms & Conditions:</strong></p>
        <ul style="margin-left: 20px; font-size: 8pt;">
            <li>Check-in time: 2:00 PM, Check-out time: 11:00 AM</li>
            <li>Late check-out charges may apply</li>
            <li>Payment due upon receipt of invoice</li>
            <li>This is a computer-generated invoice</li>
        </ul>
        <p style="text-align: center; margin-top: 20px;">Thank you for choosing {$hotel['name']}!</p>
    </div>
</div>
HTML;
    }
}

