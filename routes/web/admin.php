<?php
// routes/web/admin.php
// Enterprise Admin Routes (Chain Management)

use HotelOS\Core\Auth;
use HotelOS\Core\ChainContext;
use HotelOS\Core\TenantContext;

// Ensure User is Logged In
if (!Auth::getInstance()->check()) {
    return;
}

// Enterprise Dashboard (Super Admin)
if ($requestUri === '/super-admin/dashboard' && $requestMethod === 'GET') {
    // 1. Must implement Chain Context
    if (!ChainContext::isActive()) {
        // Not a chain user -> Redirect to standard dashboard
        header("Location: /dashboard");
        exit;
    }

    // 2. Load View
    $pageTitle = "Enterprise Dashboard";
    require_once VIEWS_PATH . '/layouts/app.php'; // Will include admin/super_dashboard.php inside logic
    exit;
}

// Context Switcher (HQ -> Property)
if (preg_match('#^/super-admin/switch/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET') {
    $targetTenantId = (int)$matches[1];
    
    // 1. Validate Access
    if (!ChainContext::isActive()) {
        die("Access Denied: Not a Chain User");
    }

    $handler = new \HotelOS\Handlers\ChainHandler();
    if (!$handler->canAccessTenant(Auth::getInstance()->id(), $targetTenantId)) {
        die("Access Denied: You do not own this property.");
    }

    // 2. Switch Context
    $_SESSION['tenant_id'] = $targetTenantId;
    TenantContext::loadById($targetTenantId);

    // 3. Log Audit
    Auth::getInstance()->logAudit('context_switch', 'tenant', $targetTenantId, null, ['reason' => 'Admin Switch']);

    // 4. Redirect to Property Dashboard
    header("Location: /dashboard?context_switched=1");
    exit;
}
