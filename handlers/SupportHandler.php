<?php
/**
 * HotelOS - Support Handler
 * 
 * MODULE 4: SLA & SUPPORT
 * Manages support tickets, system health logging, and SLA enforcement.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class SupportHandler
{
    private Database $db;

    // SLA Configuration (Minutes)
    private const SLA_LIMITS = [
        'critical' => 60,    // 1 Hour
        'high'     => 240,   // 4 Hours
        'medium'   => 1440,  // 24 Hours (1 Day)
        'low'      => 4320   // 3 Days
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new Support Ticket
     */
    public function createTicket(int $userId, string $category, string $priority, string $subject, string $description): array
    {
        $tenantId = TenantContext::getId();

        try {
            $ticketId = $this->db->execute(
                "INSERT INTO support_incidents (tenant_id, user_id, category, priority, subject, description, status)
                 VALUES (:tid, :uid, :cat, :prio, :subj, :desc, 'open')",
                [
                    'tid' => $tenantId,
                    'uid' => $userId,
                    'cat' => $category,
                    'prio' => $priority,
                    'subj' => $subject,
                    'desc' => $description
                ],
                enforceTenant: false
            );

            // Log SLA Target immediately
            $minutes = self::SLA_LIMITS[$priority] ?? 1440;
            $deadline = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));

            // We don't insert into sla_breaches yet, only when breached.
            // But for tracking, an 'sla_tracker' table would be better.
            // For now, we calculate dynamic breach status on read.
            
            return ['success' => true, 'id' => $ticketId, 'sla_deadline' => $deadline];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log System Health Metric
     */
    public function logHealth(string $metric, float $value, ?array $metadata = null): void
    {
        try {
            $this->db->execute(
                "INSERT INTO system_health_logs (tenant_id, metric, value, metadata)
                 VALUES (:tid, :metric, :val, :meta)",
                [
                    'tid' => TenantContext::isActive() ? TenantContext::getId() : null,
                    'metric' => $metric,
                    'val' => $value,
                    'meta' => $metadata ? json_encode($metadata) : null
                ],
                enforceTenant: false
            );
        } catch (\Exception $e) {
            // fail silently for logs
        }
    }

    /**
     * Check for SLA Breaches (Run via Cron or Admin View)
     */
    public function checkSlaBreaches(): array
    {
        // Find open tickets past their deadline
        // This is a complex query suitable for the Enterprise Reports
        
        $breaches = [];
        // Implementation placeholder for when DB is ready
        
        return $breaches;
    }
}
