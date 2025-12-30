<?php
/**
 * HotelOS - Subscription Middleware
 * 
 * Enforces subscription status and trial expiry
 * Redirects to billing page if account is locked
 */

declare(strict_types=1);

namespace HotelOS\Core;

use HotelOS\Handlers\SubscriptionHandler;

class SubscriptionMiddleware
{
    /**
     * Check subscription status before allowing access
     * 
     * @param callable $next Next middleware/handler
     * @return mixed
     */
    public static function check(callable $next)
    {
        // Skip check for public routes
        $publicRoutes = ['/login', '/logout', '/register', '/subscription/checkout', '/subscription/webhook', '/subscription/trial-expired'];
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($publicRoutes as $route) {
            if (strpos($currentPath, $route) !== false) {
                return $next();
            }
        }
        
        // Skip if not authenticated
        $auth = Auth::getInstance();
        if (!$auth->check()) {
            return $next();
        }
        
        // Check subscription status
        $subscription = new SubscriptionHandler();
        
        // If billing is locked, redirect to upgrade page
        if ($subscription->isBillingLocked()) {
            // Allow access to billing/subscription pages
            if (strpos($currentPath, '/subscription') !== false || strpos($currentPath, '/billing') !== false) {
                return $next();
            }
            
            // Redirect to trial expired page
            header('Location: /subscription/trial-expired');
            exit;
        }
        
        // Continue to next handler
        return $next();
    }
    
    /**
     * Check feature access
     */
    public static function requireFeature(string $feature, callable $next)
    {
        $subscription = new SubscriptionHandler();
        
        if (!$subscription->hasFeatureAccess($feature)) {
            // Return error or redirect
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'This feature requires an upgrade to a higher plan',
                'feature' => $feature
            ]);
            exit;
        }
        
        return $next();
    }
    
    /**
     * Get subscription info for display in header/banner
     */
    public static function getSubscriptionBanner(): ?array
    {
        $auth = Auth::getInstance();
        if (!$auth->check()) {
            return null;
        }
        
        $subscription = new SubscriptionHandler();
        $plan = $subscription->getCurrentPlan();
        
        // Show trial banner if on trial
        if ($subscription->isTrialActive()) {
            $daysLeft = $subscription->getTrialDaysRemaining();
            
            return [
                'type' => 'trial',
                'message' => "Free trial: {$daysLeft} days remaining",
                'action_text' => 'Upgrade Now',
                'action_url' => '/subscription/plans',
                'urgency' => $daysLeft <= 3 ? 'high' : 'normal'
            ];
        }
        
        // Show locked banner if billing issue
        if ($subscription->isBillingLocked()) {
            return [
                'type' => 'locked',
                'message' => 'Your subscription has expired. Please renew to continue.',
                'action_text' => 'Renew Now',
                'action_url' => '/subscription/upgrade',
                'urgency' => 'critical'
            ];
        }
        
        return null;
    }
}
