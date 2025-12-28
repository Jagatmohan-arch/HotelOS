<?php
/**
 * HotelOS - Police Report Handler
 * 
 * Generates police reports for guest check-ins as per Indian regulations
 * Auto-generates daily reports from check-in data
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;

class PoliceReportHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get police report data for a date
     */
    public function getReportByDate(string $date): array
    {
        $guests = $this->db->query(
            "SELECT 
                b.id as booking_id,
                b.booking_number,
                b.check_in_date,
                b.check_out_date,
                DATE(b.actual_check_in) as actual_checkin,
                r.room_number,
                g.id as guest_id,
                g.first_name,
                g.last_name,
                g.phone,
                g.email,
                g.id_type,
                g.id_number,
                g.address,
                g.city,
                g.state,
                g.pincode,
                g.nationality,
                g.date_of_birth,
                g.gender,
                g.id_document_url
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id

             JOIN rooms r ON b.room_id = r.id
             WHERE b.tenant_id = :tenant_id
             AND DATE(b.actual_check_in) = :report_date
             AND b.status IN ('checked_in', 'checked_out')
             ORDER BY b.actual_check_in ASC",
            [
                'tenant_id' => \HotelOS\Core\TenantContext::getId(),
                'report_date' => $date
            ],
            false // Disable auto-injection to avoid ambiguous columns
        );
        
        return [
            'date' => $date,
            'guest_count' => count($guests),
            'guests' => $guests
        ];
    }
    
    /**
     * Get pending police reports (not yet submitted)
     */
    public function getPendingReports(): array
    {
        // Check for dates with check-ins but no submitted police report
        return $this->db->query(
            "SELECT 
                DATE(b.actual_check_in) as report_date,
                COUNT(DISTINCT b.id) as guest_count,
                MAX(pr.status) as report_status
             FROM bookings b
             LEFT JOIN police_reports pr ON DATE(b.actual_check_in) = pr.report_date
             WHERE b.actual_check_in IS NOT NULL
             AND b.status IN ('checked_in', 'checked_out')
             AND DATE(b.actual_check_in) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             AND (pr.id IS NULL OR pr.status = 'pending')
             GROUP BY DATE(b.actual_check_in)
             ORDER BY report_date DESC"
        );
    }
    
    /**
     * Get today's arrivals for police report
     */
    public function getTodayArrivals(): array
    {
        return $this->getReportByDate(date('Y-m-d'));
    }
    
    /**
     * Mark police report as submitted
     */
    public function markAsSubmitted(string $date): bool
    {
        // Check if record exists
        $existing = $this->db->queryOne(
            "SELECT id FROM police_reports WHERE report_date = :date",
            ['date' => $date]
        );
        
        if ($existing) {
            $this->db->execute(
                "UPDATE police_reports SET status = 'submitted', submitted_at = NOW() WHERE id = :id",
                ['id' => $existing['id']]
            );
        } else {
            $this->db->execute(
                "INSERT INTO police_reports (report_date, status, submitted_at, created_at) 
                 VALUES (:date, 'submitted', NOW(), NOW())",
                ['date' => $date]
            );
        }
        
        return true;
    }
    
    /**
     * Get guest details for police report
     */
    public function getGuestDetailsForReport(int $bookingId): ?array
    {
        return $this->db->queryOne(
            "SELECT 
                b.booking_number,
                b.check_in_date,
                b.check_out_date,
                b.actual_check_in,
                b.actual_check_out,
                r.room_number,
                g.*,
                CONCAT(g.first_name, ' ', COALESCE(g.last_name, '')) as full_name
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id
             JOIN rooms r ON b.room_id = r.id
             WHERE b.id = :booking_id",
            ['booking_id' => $bookingId]
        );
    }
    
    /**
     * Generate police report text (for WhatsApp/Email)
     */
    public function generateReportText(string $date): string
    {
        $data = $this->getReportByDate($date);
        
        $text = "🏨 POLICE REPORT\n";
        $text .= "Date: " . date('d-M-Y', strtotime($date)) . "\n";
        $text .= "Guests: " . count($data['guests']) . "\n";
        $text .= "─────────────────\n\n";
        
        foreach ($data['guests'] as $index => $guest) {
            $text .= ($index + 1) . ". " . $guest['first_name'] . " " . ($guest['last_name'] ?? '') . "\n";
            $text .= "   📱 " . $guest['phone'] . "\n";
            $text .= "   🪪 " . strtoupper($guest['id_type'] ?? 'N/A') . ": " . ($guest['id_number'] ?? 'N/A') . "\n";
            $text .= "   🏠 Room: " . $guest['room_number'] . "\n";
            $text .= "   📍 " . ($guest['city'] ?? 'N/A') . ", " . ($guest['state'] ?? 'N/A') . "\n";
            $text .= "   🌍 " . ($guest['nationality'] ?? 'Indian') . "\n\n";
        }
        
        $text .= "─────────────────\n";
        $text .= "Generated: " . date('d-M-Y H:i');
        
        return $text;
    }
    
    /**
     * Get report statistics for dashboard
     */
    public function getReportStats(): array
    {
        $pending = $this->db->queryOne(
            "SELECT COUNT(DISTINCT DATE(b.actual_check_in)) as count
             FROM bookings b
             LEFT JOIN police_reports pr ON DATE(b.actual_check_in) = pr.report_date
             WHERE b.actual_check_in IS NOT NULL
             AND (pr.id IS NULL OR pr.status = 'pending')
             AND DATE(b.actual_check_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        );
        
        $today = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM bookings 
             WHERE DATE(actual_check_in) = CURDATE() 
             AND status IN ('checked_in', 'checked_out')"
        );
        
        return [
            'pending_reports' => (int)($pending['count'] ?? 0),
            'today_checkins' => (int)($today['count'] ?? 0)
        ];
    }
}
