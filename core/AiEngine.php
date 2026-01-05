<?php
/**
 * HotelOS - AI Engine (Read-Only)
 * 
 * MODULE 5: AI INSIGHTS
 * Provides heuristic-based insights.
 * STRICT RULE: READ-ONLY. Never modifies core data.
 */

declare(strict_types=1);

namespace HotelOS\Core;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class AiEngine
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate Occupancy Prediction for Next 7 Days
     * Logic: Simple heuristic based on current bookings + historical average
     */
    public function predictOccupancy(): array
    {
        $tenantId = TenantContext::getId();
        
        // 1. Get confirmed future bookings
        $futureBookings = $this->db->query(
            "SELECT check_in_date, check_out_date FROM bookings 
             WHERE tenant_id = :tid 
               AND status = 'confirmed' 
               AND check_in_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
            ['tid' => $tenantId],
            enforceTenant: false
        );

        // 2. Calculate daily load (Mock logic for heuristics)
        $predictions = [];
        $today = new \DateTime();

        for ($i = 0; $i < 7; $i++) {
            $date = clone $today;
            $date->modify("+$i days");
            $dateStr = $date->format('Y-m-d');
            
            // Count overlapping bookings
            $count = 0;
            foreach ($futureBookings as $b) {
                if ($b['check_in_date'] <= $dateStr && $b['check_out_date'] > $dateStr) {
                    $count++;
                }
            }

            // Simple recommendation
            $status = $count > 5 ? 'High Demand' : 'Low Demand'; // Threshold would be dynamic in real AI
            
            $predictions[$dateStr] = [
                'occupied_count' => $count,
                'status' => $status
            ];
            
            // Persist Insight if anomalous (e.g., unexpectedly high)
            if ($count > 10) {
                $this->saveInsight(
                    'occupancy', 
                    0.85, 
                    "Spike detected on $dateStr. Recommend increasing rates."
                );
            }
        }

        return $predictions;
    }

    /**
     * Detect Revenue Anomalies (Leakage)
     * Checks for checked-out bookings with pending balance or odd discounts
     */
    public function detectRevenueLeaks(): int
    {
        $tenantId = TenantContext::getId();

        // Query: Checked out entries with balance > 0 OR discount > 20%
        $anomalies = $this->db->query(
            "SELECT id, booking_number, grand_total, paid_amount, discount_amount 
             FROM bookings 
             WHERE tenant_id = :tid 
               AND status = 'checked_out'
               AND (
                   (grand_total - paid_amount) > 1 
                   OR 
                   (discount_amount > (grand_total * 0.20))
               )
               AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            ['tid' => $tenantId],
            enforceTenant: false
        );

        foreach ($anomalies as $anomaly) {
            $balance = $anomaly['grand_total'] - $anomaly['paid_amount'];
            $msg = '';
            
            if ($balance > 1) {
                $msg = "Revenue Leak: Booking #{$anomaly['booking_number']} checked out with pending balance of ₹$balance.";
            } elseif ($anomaly['discount_amount'] > 0) {
                $msg = "Unusual Discount: Booking #{$anomaly['booking_number']} has High Discount.";
            }

            if ($msg) {
                $this->saveInsight('revenue', 0.95, $msg, ['booking_id' => $anomaly['id']]);
            }
        }

        return count($anomalies);
    }

    /**
     * Save generated insight to DB
     */
    private function saveInsight(string $category, float $confidence, string $text, ?array $actionData = null): void
    {
        try {
            $this->db->execute(
                "INSERT INTO ai_insights (tenant_id, category, confidence_score, insight_text, action_data)
                 VALUES (:tid, :cat, :conf, :txt, :data)",
                [
                    'tid' => TenantContext::getId(),
                    'cat' => $category,
                    'conf' => $confidence,
                    'txt' => $text,
                    'data' => $actionData ? json_encode($actionData) : null
                ],
                enforceTenant: false
            );
        } catch (\Exception $e) {
            // Ignore DB errors in AI layer
        }
    }
}
