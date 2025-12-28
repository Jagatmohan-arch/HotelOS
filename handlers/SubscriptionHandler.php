<?php
/**
 * HotelOS - Subscription Handler
 * 
 * Manages subscription plans, trials, and upgrade flow
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class SubscriptionHandler
{
    private Database $db;
    
    // Plan definitions with features
    public const PLANS = [
        'trial' => [
            'name' => 'Free Trial',
            'price' => 0,
            'duration' => '14 days',
            'rooms' => 5,
            'features' => [
                'basic_pms' => true,
                'gst_billing' => true,
                'reports' => false,
                'pos' => false,
                'multi_user' => false,
                'api_access' => false,
                'priority_support' => false,
            ]
        ],
        'starter' => [
            'name' => 'Starter',
            'price' => 999,
            'duration' => 'month',
            'rooms' => 15,
            'features' => [
                'basic_pms' => true,
                'gst_billing' => true,
                'reports' => true,
                'pos' => false,
                'multi_user' => false,
                'api_access' => false,
                'priority_support' => false,
            ]
        ],
        'professional' => [
            'name' => 'Professional',
            'price' => 2499,
            'duration' => 'month',
            'rooms' => 50,
            'popular' => true,
            'features' => [
                'basic_pms' => true,
                'gst_billing' => true,
                'reports' => true,
                'pos' => true,
                'multi_user' => true,
                'api_access' => false,
                'priority_support' => false,
            ]
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 4999,
            'duration' => 'month',
            'rooms' => 999,
            'features' => [
                'basic_pms' => true,
                'gst_billing' => true,
                'reports' => true,
                'pos' => true,
                'multi_user' => true,
                'api_access' => true,
                'priority_support' => true,
            ]
        ],
    ];
    
    // Feature labels for display
    public const FEATURE_LABELS = [
        'basic_pms' => 'Property Management',
        'gst_billing' => 'GST Compliant Billing',
        'reports' => 'Advanced Reports',
        'pos' => 'POS (Minibar, Laundry)',
        'multi_user' => 'Multi-User Access',
        'api_access' => 'API Access',
        'priority_support' => 'Priority Support',
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get current subscription details for tenant
     */
    public function getCurrentSubscription(): array
    {
        $tenantId = TenantContext::getId();
        
        $tenant = $this->db->queryOne(
            "SELECT plan, trial_ends_at, subscription_ends_at, created_at FROM tenants WHERE id = :id",
            ['id' => $tenantId],
            enforceTenant: false
        );
        
        if (!$tenant) {
            return $this->getDefaultSubscription();
        }
        
        $plan = $tenant['plan'] ?? 'trial';
        $planDetails = self::PLANS[$plan] ?? self::PLANS['trial'];
        
        return [
            'plan' => $plan,
            'plan_name' => $planDetails['name'],
            'price' => $planDetails['price'],
            'rooms_limit' => $planDetails['rooms'],
            'features' => $planDetails['features'],
            'trial_ends_at' => $tenant['trial_ends_at'],
            'subscription_ends_at' => $tenant['subscription_ends_at'],
            'is_trial' => $plan === 'trial',
            'is_expired' => $this->isExpired($tenant),
            'days_remaining' => $this->getDaysRemaining($tenant),
        ];
    }
    
    /**
     * Check if subscription/trial is expired
     */
    public function isExpired(array $tenant): bool
    {
        $plan = $tenant['plan'] ?? 'trial';
        
        if ($plan === 'trial') {
            $endDate = $tenant['trial_ends_at'] ?? null;
            if (!$endDate) {
                // Calculate from created_at + 14 days
                $created = new \DateTime($tenant['created_at']);
                $endDate = $created->modify('+14 days')->format('Y-m-d');
            }
        } else {
            $endDate = $tenant['subscription_ends_at'] ?? null;
        }
        
        if (!$endDate) {
            return false;
        }
        
        return strtotime($endDate) < strtotime('today');
    }
    
    /**
     * Get days remaining in trial/subscription
     */
    public function getDaysRemaining(array $tenant): int
    {
        $plan = $tenant['plan'] ?? 'trial';
        
        if ($plan === 'trial') {
            $endDate = $tenant['trial_ends_at'] ?? null;
            if (!$endDate) {
                $created = new \DateTime($tenant['created_at']);
                $endDate = $created->modify('+14 days')->format('Y-m-d');
            }
        } else {
            $endDate = $tenant['subscription_ends_at'] ?? null;
        }
        
        if (!$endDate) {
            return 999; // No expiry
        }
        
        $end = new \DateTime($endDate);
        $today = new \DateTime('today');
        $diff = $today->diff($end);
        
        return $diff->invert ? 0 : $diff->days;
    }
    
    /**
     * Get all plan details for pricing page
     */
    public function getAllPlans(): array
    {
        $plans = [];
        foreach (self::PLANS as $key => $plan) {
            if ($key === 'trial') continue; // Don't show trial in pricing
            
            $plans[$key] = array_merge($plan, [
                'slug' => $key,
                'feature_list' => $this->getFeatureList($plan['features']),
            ]);
        }
        return $plans;
    }
    
    /**
     * Get feature list with labels
     */
    private function getFeatureList(array $features): array
    {
        $list = [];
        foreach ($features as $key => $enabled) {
            $list[] = [
                'key' => $key,
                'label' => self::FEATURE_LABELS[$key] ?? $key,
                'enabled' => $enabled,
            ];
        }
        return $list;
    }
    
    /**
     * Start trial for new tenant
     */
    public function startTrial(int $tenantId): bool
    {
        $trialEnd = date('Y-m-d', strtotime('+14 days'));
        
        return $this->db->execute(
            "UPDATE tenants SET plan = 'trial', trial_ends_at = :end WHERE id = :id",
            ['id' => $tenantId, 'end' => $trialEnd],
            enforceTenant: false
        ) > 0;
    }
    
    /**
     * Upgrade subscription (called after payment)
     */
    public function upgradePlan(string $plan, int $months = 1): bool
    {
        $tenantId = TenantContext::getId();
        
        if (!isset(self::PLANS[$plan]) || $plan === 'trial') {
            return false;
        }
        
        $endDate = date('Y-m-d', strtotime("+{$months} months"));
        
        return $this->db->execute(
            "UPDATE tenants SET 
             plan = :plan, 
             subscription_ends_at = :end,
             trial_ends_at = NULL
             WHERE id = :id",
            ['id' => $tenantId, 'plan' => $plan, 'end' => $endDate],
            enforceTenant: false
        ) > 0;
    }
    
    /**
     * Check if feature is available in current plan
     */
    public function hasFeature(string $feature): bool
    {
        $subscription = $this->getCurrentSubscription();
        return $subscription['features'][$feature] ?? false;
    }
    
    /**
     * Check if room limit is reached
     */
    public function canAddRoom(): bool
    {
        $subscription = $this->getCurrentSubscription();
        $limit = $subscription['rooms_limit'];
        
        $currentCount = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM rooms WHERE is_active = 1"
        );
        
        return ((int)($currentCount['count'] ?? 0)) < $limit;
    }
    
    /**
     * Get default subscription for new/invalid tenants
     */
    private function getDefaultSubscription(): array
    {
        return [
            'plan' => 'trial',
            'plan_name' => 'Free Trial',
            'price' => 0,
            'rooms_limit' => 5,
            'features' => self::PLANS['trial']['features'],
            'trial_ends_at' => date('Y-m-d', strtotime('+14 days')),
            'subscription_ends_at' => null,
            'is_trial' => true,
            'is_expired' => false,
            'days_remaining' => 14,
        ];
    }
}
