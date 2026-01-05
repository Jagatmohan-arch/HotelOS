<?php
/**
 * HotelOS - Auditor Export Handler
 * 
 * MODULE 2: COMPLIANCE & AUDIT LEYER
 * Generates immutable export packages for external auditors.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class AuditorExportHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate Full Ledger Export (JSON)
     */
    public function generateLedgerExport(string $startDate, string $endDate): string
    {
        $tenantId = TenantContext::getId();

        // 1. Fetch Invoices
        $bookings = $this->db->query(
            "SELECT id, booking_number, check_in_date, check_out_date, 
                    rate_per_night, tax_total, grand_total, payment_status, created_at 
             FROM bookings 
             WHERE tenant_id = :tid 
               AND created_at BETWEEN :start AND :end
             ORDER BY created_at ASC",
            ['tid' => $tenantId, 'start' => $startDate, 'end' => $endDate . ' 23:59:59'],
            enforceTenant: false
        );

        // 2. Fetch Payments
        $payments = $this->db->query(
            "SELECT id, booking_id, amount, payment_mode, category, created_at, created_by 
             FROM payments 
             WHERE tenant_id = :tid 
               AND created_at BETWEEN :start AND :end",
            ['tid' => $tenantId, 'start' => $startDate, 'end' => $endDate . ' 23:59:59'],
            enforceTenant: false
        );

        // 3. Build Export Structure
        $export = [
            'meta' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'tenant_id' => $tenantId,
                'period' => "$startDate to $endDate",
                'system_version' => 'HotelOS v5.0-ENT'
            ],
            'ledger' => [
                'bookings_count' => count($bookings),
                'total_revenue_booked' => array_sum(array_column($bookings, 'grand_total')),
                'transactions' => $bookings
            ],
            'cash_flow' => [
                'payments_count' => count($payments),
                'total_collected' => array_sum(array_column($payments, 'amount')),
                'entries' => $payments
            ],
            'checksum' => ''
        ];

        // 4. Sign the Export
        $export['checksum'] = hash('sha256', json_encode($export));

        return json_encode($export, JSON_PRETTY_PRINT);
    }
}
