<?php
/**
 * HotelOS - Reports Handler
 * 
 * Generates revenue, occupancy, and GST reports
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class ReportsHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get daily revenue report
     */
    public function getDailyRevenue(string $startDate, string $endDate): array
    {
        return $this->db->query(
            "SELECT 
                DATE(t.collected_at) as date,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN t.type = 'credit' THEN t.amount ELSE 0 END) as revenue,
                SUM(CASE WHEN t.type = 'debit' THEN t.amount ELSE 0 END) as refunds,
                SUM(CASE WHEN t.payment_mode = 'cash' AND t.type = 'credit' THEN t.amount ELSE 0 END) as cash,
                SUM(CASE WHEN t.payment_mode = 'upi' AND t.type = 'credit' THEN t.amount ELSE 0 END) as upi,
                SUM(CASE WHEN t.payment_mode = 'card' AND t.type = 'credit' THEN t.amount ELSE 0 END) as card,
                SUM(CASE WHEN t.payment_mode NOT IN ('cash', 'upi', 'card') AND t.type = 'credit' THEN t.amount ELSE 0 END) as other
             FROM transactions t
             WHERE DATE(t.collected_at) BETWEEN :start_date AND :end_date
             GROUP BY DATE(t.collected_at)
             ORDER BY date DESC",
            ['start_date' => $startDate, 'end_date' => $endDate]
        );
    }
    
    /**
     * Get revenue summary for date range
     */
    public function getRevenueSummary(string $startDate, string $endDate): array
    {
        return $this->db->queryOne(
            "SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN t.type = 'credit' THEN t.amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN t.type = 'debit' THEN t.amount ELSE 0 END) as total_refunds,
                SUM(CASE WHEN t.payment_mode = 'cash' AND t.type = 'credit' THEN t.amount ELSE 0 END) as cash_total,
                SUM(CASE WHEN t.payment_mode = 'upi' AND t.type = 'credit' THEN t.amount ELSE 0 END) as upi_total,
                SUM(CASE WHEN t.payment_mode = 'card' AND t.type = 'credit' THEN t.amount ELSE 0 END) as card_total
             FROM transactions t
             WHERE DATE(t.collected_at) BETWEEN :start_date AND :end_date",
            ['start_date' => $startDate, 'end_date' => $endDate]
        ) ?? [];
    }
    
    /**
     * Get occupancy report
     */
    public function getOccupancyReport(string $startDate, string $endDate): array
    {
        // Get total rooms
        $totalRooms = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM rooms WHERE is_active = 1"
        )['count'] ?? 0;
        
        // Get daily occupancy
        $dailyData = $this->db->query(
            "SELECT 
                DATE(b.check_in_date) as date,
                COUNT(DISTINCT b.room_id) as rooms_occupied,
                COUNT(DISTINCT b.id) as bookings,
                SUM(b.total_amount) as revenue
             FROM bookings b
             WHERE b.status IN ('checked_in', 'checked_out')
             AND b.check_in_date <= :end_date
             AND b.check_out_date >= :start_date
             GROUP BY DATE(b.check_in_date)
             ORDER BY date DESC",
            ['start_date' => $startDate, 'end_date' => $endDate]
        );
        
        // Calculate occupancy percentage
        foreach ($dailyData as &$day) {
            $day['occupancy_rate'] = $totalRooms > 0 
                ? round(($day['rooms_occupied'] / $totalRooms) * 100, 1) 
                : 0;
        }
        
        return [
            'total_rooms' => $totalRooms,
            'daily' => $dailyData
        ];
    }
    
    /**
     * Get occupancy summary
     */
    public function getOccupancySummary(string $startDate, string $endDate): array
    {
        $totalRooms = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM rooms WHERE is_active = 1"
        )['count'] ?? 0;
        
        $data = $this->db->queryOne(
            "SELECT 
                COUNT(DISTINCT b.id) as total_bookings,
                COUNT(DISTINCT b.guest_id) as unique_guests,
                SUM(DATEDIFF(LEAST(b.check_out_date, :end_date), GREATEST(b.check_in_date, :start_date))) as total_room_nights,
                AVG(b.total_amount) as avg_booking_value,
                SUM(b.total_amount) as total_revenue
             FROM bookings b
             WHERE b.status IN ('checked_in', 'checked_out')
             AND b.check_in_date <= :end_date2
             AND b.check_out_date >= :start_date2",
            [
                'start_date' => $startDate, 
                'end_date' => $endDate,
                'start_date2' => $startDate, 
                'end_date2' => $endDate
            ]
        );
        
        // Calculate average occupancy
        $days = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);
        $possibleRoomNights = $totalRooms * $days;
        $occupancyRate = $possibleRoomNights > 0 
            ? round((($data['total_room_nights'] ?? 0) / $possibleRoomNights) * 100, 1) 
            : 0;
        
        return [
            'total_rooms' => $totalRooms,
            'total_bookings' => (int)($data['total_bookings'] ?? 0),
            'unique_guests' => (int)($data['unique_guests'] ?? 0),
            'total_room_nights' => (int)($data['total_room_nights'] ?? 0),
            'avg_booking_value' => round((float)($data['avg_booking_value'] ?? 0), 2),
            'total_revenue' => (float)($data['total_revenue'] ?? 0),
            'avg_occupancy_rate' => $occupancyRate
        ];
    }
    
    /**
     * Get GST summary for tax filing
     */
    public function getGSTSummary(string $startDate, string $endDate): array
    {
        // Get bookings with GST breakdown
        $bookings = $this->db->query(
            "SELECT 
                b.id,
                b.booking_number,
                b.check_out_date as invoice_date,
                CONCAT(g.first_name, ' ', g.last_name) as guest_name,
                g.gst_number as guest_gstin,
                r.room_number,
                b.total_amount,
                b.cgst_amount,
                b.sgst_amount,
                (b.cgst_amount + b.sgst_amount) as total_gst,
                (b.total_amount - b.cgst_amount - b.sgst_amount) as taxable_amount
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id
             JOIN rooms r ON b.room_id = r.id
             WHERE b.status = 'checked_out'
             AND b.check_out_date BETWEEN :start_date AND :end_date
             ORDER BY b.check_out_date DESC",
            ['start_date' => $startDate, 'end_date' => $endDate]
        );
        
        // Calculate totals
        $totals = [
            'taxable_amount' => 0,
            'cgst' => 0,
            'sgst' => 0,
            'total_gst' => 0,
            'total_amount' => 0,
            'invoice_count' => count($bookings)
        ];
        
        foreach ($bookings as $b) {
            $totals['taxable_amount'] += (float)$b['taxable_amount'];
            $totals['cgst'] += (float)$b['cgst_amount'];
            $totals['sgst'] += (float)$b['sgst_amount'];
            $totals['total_gst'] += (float)$b['total_gst'];
            $totals['total_amount'] += (float)$b['total_amount'];
        }
        
        return [
            'bookings' => $bookings,
            'totals' => $totals
        ];
    }
    
    /**
     * Get room-wise revenue
     */
    public function getRoomWiseRevenue(string $startDate, string $endDate): array
    {
        return $this->db->query(
            "SELECT 
                r.room_number,
                rt.name as room_type,
                COUNT(b.id) as booking_count,
                SUM(b.total_amount) as total_revenue,
                AVG(b.total_amount) as avg_revenue
             FROM rooms r
             LEFT JOIN room_types rt ON r.room_type_id = rt.id
             LEFT JOIN bookings b ON r.id = b.room_id 
                AND b.status = 'checked_out'
                AND b.check_out_date BETWEEN :start_date AND :end_date
             WHERE r.is_active = 1
             GROUP BY r.id, r.room_number, rt.name
             ORDER BY total_revenue DESC",
            ['start_date' => $startDate, 'end_date' => $endDate]
        );
    }
    
    /**
     * Get payment mode breakdown
     */
    public function getPaymentModeBreakdown(string $startDate, string $endDate): array
    {
        return $this->db->query(
            "SELECT 
                payment_mode,
                COUNT(*) as count,
                SUM(amount) as total
             FROM transactions
             WHERE type = 'credit'
             AND DATE(collected_at) BETWEEN :start_date AND :end_date
             GROUP BY payment_mode
             ORDER BY total DESC",
            ['start_date' => $startDate, 'end_date' => $endDate]
        );
    }
}
