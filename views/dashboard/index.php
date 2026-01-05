<?php
/**
 * HotelOS - Role-Based Dashboard Switcher
 * 
 * Phase 1: User Experience & Role Realization
 * Instead of one giant dashboard, we route to role-specific views.
 * 
 * 1. Owner -> views/dashboard/owner.php
 * 2. Manager -> views/dashboard/manager.php
 * 3. Reception -> views/dashboard/front_desk.php
 * 4. Staff -> views/dashboard/staff.php
 */

// Get current user role
$user = \HotelOS\Core\Auth::getInstance()->user();
$role = $user['role'] ?? 'staff';

// Shift warning component (applies to all operational roles)
$showShiftWarning = in_array($role, ['manager', 'reception', 'staff']);
if ($showShiftWarning) {
    require_once __DIR__ . '/../components/shift-warning.php';
}

// Route to role-specific dashboard
switch ($role) {
    case 'owner':
        require __DIR__ . '/owner.php';
        break;
        
    case 'manager':
        require __DIR__ . '/manager.php';
        break;
        
    case 'reception':
        require __DIR__ . '/front_desk.php';
        break;
        
    case 'housekeeping':
    case 'staff':
        require __DIR__ . '/staff.php';
        break;
        
    case 'accountant':
        require __DIR__ . '/owner.php'; // Accountants see similar data to owners for now
        break;
        
    default:
        // Fallback for unknown roles
        require __DIR__ . '/staff.php';
        break;
}
?>
