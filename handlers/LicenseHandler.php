<?php
/**
 * HotelOS - License Handler
 * 
 * MODULE 6: SAAS BILLING
 * Enforces subscription limits and license validation.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class LicenseHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Check if current tenant has active license
     */
    public function checkLicenseStatus(): array
    {
        if (!TenantContext::isActive()) {
            return ['status' => 'system', 'valid' => true];
        }

        $tenantId = TenantContext::getId();

        $sub = $this->db->queryOne(
            "SELECT status, expires_at, plan_id FROM tenant_subscriptions 
             WHERE tenant_id = :tid 
             ORDER BY expires_at DESC LIMIT 1",
            ['tid' => $tenantId],
            enforceTenant: false
        );

        if (!$sub) {
            // Default to Trial if no record found (Fallback)
            return ['status' => 'trial', 'valid' => true, 'days_left' => 14]; 
        }

        $isValid = $sub['status'] === 'active' || $sub['status'] === 'trial';
        $today = new \DateTime();
        $expiry = new \DateTime($sub['expires_at']);
        
        if ($today > $expiry) {
            $isValid = false;
            $sub['status'] = 'expired';
        }

        $daysLeft = $today->diff($expiry)->days;
        if ($today > $expiry) $daysLeft = 0;

        return [
            'status' => $sub['status'],
            'valid' => $isValid,
            'days_left' => $daysLeft,
            'plan_id' => $sub['plan_id']
        ];
    }

    /**
     * Check if a specific feature is enabled for the current plan
     */
    public function hasFeature(string $featureSlug): bool
    {
        $license = $this->checkLicenseStatus();
        if (!$license['valid']) return false;

        // Fetch plan features
        // Optimization: In real app, cache this
        $plan = $this->db->queryOne(
            "SELECT features FROM saas_plans WHERE id = :id",
            ['id' => $license['plan_id'] ?? 1],
            enforceTenant: false
        );

        if (!$plan || empty($plan['features'])) {
            return true; // Default to all open if plan undefined
        }

        $features = json_decode($plan['features'], true);
        return in_array($featureSlug, $features);
    }
}
