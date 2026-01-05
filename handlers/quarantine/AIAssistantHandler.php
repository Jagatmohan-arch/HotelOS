<?php
/**
 * HotelOS - AI Assistant Handler
 * 
 * Provides smart suggestions, seasonal insights, and business optimization
 * Server-light: Pre-computed suggestions via cron, no real-time AI calls
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;

class AIAssistantHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get active suggestions for dashboard
     */
    public function getActiveSuggestions(): array
    {
        return $this->db->query(
            "SELECT * FROM ai_suggestions 
             WHERE is_dismissed = FALSE 
             AND (valid_until IS NULL OR valid_until >= CURDATE())
             ORDER BY 
                FIELD(priority, 'critical', 'high', 'medium', 'low'),
                created_at DESC
             LIMIT 5"
        );
    }
    
    /**
     * Generate seasonal suggestions (called via cron)
     */
    public function generateSeasonalSuggestions(): int
    {
        $suggestions = [];
        
        // Check for upcoming holidays/seasons
        $month = (int)date('m');
        $day = (int)date('d');
        
        // Diwali season (Oct-Nov)
        if ($month === 10 && $day >= 15) {
            $suggestions[] = [
                'type' => 'seasonal',
                'priority' => 'high',
                'title' => '🪔 Diwali Season Approaching',
                'suggestion' => 'Increase room rates by 15-25% for Oct 20 - Nov 15. High demand expected for family bookings.',
                'expected_impact' => '+20% revenue potential',
                'action_type' => 'rate_increase',
                'valid_until' => date('Y') . '-11-15'
            ];
        }
        
        // New Year (Dec-Jan)
        if ($month === 12 && $day >= 20) {
            $suggestions[] = [
                'type' => 'seasonal',
                'priority' => 'high',
                'title' => '🎉 New Year Rush',
                'suggestion' => 'Peak season rates recommended. Consider minimum 2-night stay policy for Dec 30 - Jan 2.',
                'expected_impact' => '+30% revenue potential',
                'action_type' => 'rate_increase',
                'valid_until' => date('Y') + 1 . '-01-05'
            ];
        }
        
        // Summer vacation (May-Jun)
        if ($month === 4 && $day >= 15) {
            $suggestions[] = [
                'type' => 'seasonal',
                'priority' => 'medium',
                'title' => '☀️ Summer Vacation Season',
                'suggestion' => 'Family packages and longer stays expected. Consider weekly rates and family room promotions.',
                'expected_impact' => '+15% occupancy',
                'action_type' => 'promotion',
                'valid_until' => date('Y') . '-06-30'
            ];
        }
        
        // Insert suggestions
        $count = 0;
        foreach ($suggestions as $s) {
            // Check if similar suggestion exists
            $existing = $this->db->queryOne(
                "SELECT id FROM ai_suggestions 
                 WHERE title = :title AND is_dismissed = FALSE",
                ['title' => $s['title']]
            );
            
            if (!$existing) {
                $this->db->insert('ai_suggestions', $s);
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Generate occupancy-based suggestions
     */
    public function generateOccupancySuggestions(): int
    {
        // Get current week occupancy
        $occupancy = $this->db->queryOne(
            "SELECT 
                COUNT(DISTINCT b.room_id) as occupied,
                (SELECT COUNT(*) FROM rooms WHERE is_active = 1) as total
             FROM bookings b
             WHERE b.check_in_date <= CURDATE()
             AND b.check_out_date > CURDATE()
             AND b.status IN ('checked_in', 'confirmed')"
        );
        
        if (!$occupancy || $occupancy['total'] == 0) return 0;
        
        $rate = ($occupancy['occupied'] / $occupancy['total']) * 100;
        $suggestions = [];
        
        if ($rate < 30) {
            $suggestions[] = [
                'type' => 'occupancy',
                'priority' => 'high',
                'title' => '📉 Low Occupancy Alert',
                'suggestion' => "Current occupancy at {$rate}%. Consider flash sale, OTA promotions, or local tie-ups to boost bookings.",
                'expected_impact' => 'Increase bookings',
                'action_type' => 'promotion',
                'valid_until' => date('Y-m-d', strtotime('+7 days'))
            ];
        } elseif ($rate > 85) {
            $suggestions[] = [
                'type' => 'occupancy',
                'priority' => 'medium',
                'title' => '📈 High Demand - Optimize Rates',
                'suggestion' => "Excellent occupancy at {$rate}%! Consider increasing rates by 10-15% for new bookings.",
                'expected_impact' => '+15% revenue',
                'action_type' => 'rate_increase',
                'valid_until' => date('Y-m-d', strtotime('+3 days'))
            ];
        }
        
        $count = 0;
        foreach ($suggestions as $s) {
            $this->db->insert('ai_suggestions', $s);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Dismiss a suggestion
     */
    public function dismissSuggestion(int $id): bool
    {
        return $this->db->execute(
            "UPDATE ai_suggestions SET is_dismissed = TRUE WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Apply a suggestion
     */
    public function applySuggestion(int $id): array
    {
        $suggestion = $this->db->queryOne(
            "SELECT * FROM ai_suggestions WHERE id = :id",
            ['id' => $id]
        );
        
        if (!$suggestion) {
            return ['success' => false, 'error' => 'Suggestion not found'];
        }
        
        // Mark as applied
        $this->db->execute(
            "UPDATE ai_suggestions SET is_applied = TRUE, applied_at = NOW() WHERE id = :id",
            ['id' => $id]
        );
        
        return [
            'success' => true,
            'action_type' => $suggestion['action_type'],
            'message' => 'Suggestion applied successfully'
        ];
    }
    
    /**
     * Get quick AI responses (pre-defined, no external API)
     */
    public function getQuickResponse(string $query): string
    {
        $query = strtolower($query);
        
        $responses = [
            'rate' => 'To optimize rates, check your occupancy trend. High demand = increase by 10-20%. Low demand = offer discounts or packages.',
            'occupancy' => 'Track daily occupancy rate. Below 50% needs marketing push. Above 80% is great for rate increases.',
            'ota' => 'OTA commissions typically range 15-22%. Focus on direct bookings to save costs.',
            'housekeeping' => 'Standard room turnaround should be 30-45 minutes. Schedule housekeeping based on checkout list.',
            'guest' => 'Happy guests = repeat business. Focus on clean rooms, fast check-in, and solving issues quickly.'
        ];
        
        foreach ($responses as $keyword => $response) {
            if (strpos($query, $keyword) !== false) {
                return $response;
            }
        }
        
        return 'I can help with rates, occupancy, OTA management, housekeeping, and guest experience. What would you like to know?';
    }
    
    /**
     * Get dashboard AI widget data
     */
    public function getDashboardWidget(): array
    {
        $suggestions = $this->getActiveSuggestions();
        
        return [
            'active_suggestions' => count($suggestions),
            'top_priority' => $suggestions[0] ?? null,
            'greeting' => $this->getTimeBasedGreeting()
        ];
    }
    
    /**
     * Get time-based greeting
     */
    private function getTimeBasedGreeting(): string
    {
        $hour = (int)date('H');
        
        if ($hour < 12) return 'Good morning! ☀️';
        if ($hour < 17) return 'Good afternoon! 🌤️';
        return 'Good evening! 🌙';
    }
}
