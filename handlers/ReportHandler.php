<?php

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class ReportHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get Daily Summary Report (Phase F4)
     * Aggregates shifts, transactions, and bookings for a specific day.
     */
    public function getDailySummary(string $date): array
    {
        $tenantId = TenantContext::getId();
        
        // 1. Shift Stats
        $shiftStats = $this->db->queryOne(
            "SELECT 
                COUNT(*) as total_shifts,
                SUM(opening_cash) as total_opening,
                SUM(closing_cash) as total_closing,
                SUM(variance_amount) as total_variance,
                SUM(system_expected_cash) as total_expected
             FROM shifts 
             WHERE tenant_id = :tid 
             AND DATE(shift_end_at) = :date 
             AND status = 'CLOSED'",
            ['tid' => $tenantId, 'date' => $date]
        );

        // 2. Transaction Stats (Breakdown by Mode)
        $txns = $this->db->query(
            "SELECT 
                payment_mode,
                SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as refunds
             FROM transactions
             WHERE tenant_id = :tid
             AND DATE(collected_at) = :date
             GROUP BY payment_mode",
            ['tid' => $tenantId, 'date' => $date]
        );
        
        $modeBreakdown = [];
        $totalRevenue = 0;
        
        foreach ($txns as $txn) {
            $net = $txn['income'] - $txn['refunds'];
            $modeBreakdown[$txn['payment_mode']] = $net;
            $totalRevenue += $net;
        }

        // 3. Petty Cash Stats
        $ledger = $this->db->queryOne(
            "SELECT 
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
                SUM(CASE WHEN type = 'addition' THEN amount ELSE 0 END) as total_added
             FROM cash_ledger
             WHERE tenant_id = :tid
             AND DATE(created_at) = :date",
            ['tid' => $tenantId, 'date' => $date]
        );

        return [
            'date' => $date,
            'shifts' => $shiftStats,
            'revenue' => [
                'total' => $totalRevenue,
                'breakdown' => $modeBreakdown // 'cash' => 5000, 'upi' => 2000
            ],
            'petty_cash' => [
                'expense' => (float)($ledger['total_expense'] ?? 0),
                'addition' => (float)($ledger['total_added'] ?? 0)
            ]
        ];
    }
}
