<?php
/**
 * HotelOS - Mobile Bottom Navigation
 * 
 * Role-aware bottom navigation bar for mobile devices
 * Shows only on screens < 768px
 * 
 * Features:
 * - FAB button for Quick Check-in
 * - Quick Actions Sheet with common actions
 * - Role-based visibility
 * - Haptic feedback support
 * 
 * Variables:
 * - $currentRoute: Current route for active state
 * - $user: Current user data
 */

$currentRoute = $currentRoute ?? 'dashboard';
$user = $user ?? ['role' => 'receptionist'];
$userRole = $user['role'] ?? 'receptionist';

$mobileNavItems = [
    ['route' => 'dashboard', 'label' => 'Home', 'icon' => 'home', 'href' => '/dashboard'],
    ['route' => 'rooms', 'label' => 'Rooms', 'icon' => 'bed-double', 'href' => '/rooms'],
    ['route' => 'add', 'label' => '', 'icon' => 'plus', 'href' => '#', 'isFab' => true],
    ['route' => 'bookings', 'label' => 'Bookings', 'icon' => 'calendar-check', 'href' => '/bookings'],
    ['route' => 'menu', 'label' => 'Menu', 'icon' => 'menu', 'href' => '#', 'isMenu' => true],
];
?>

<!-- Mobile Bottom Navigation -->
<nav 
    x-data="mobileBottomNav()"
    class="mobile-nav"
    role="navigation"
    aria-label="Mobile navigation"
>
    <!-- Navigation Bar -->
    <div class="mobile-nav-bar">
        <?php foreach ($mobileNavItems as $item): ?>
            <?php if (!empty($item['isFab'])): ?>
                <!-- Center FAB Button -->
                <button 
                    @click="toggleQuickActions()"
                    class="mobile-nav-fab"
                    :class="{ 'mobile-nav-fab--active': quickActionsOpen }"
                    aria-label="Quick actions"
                >
                    <i data-lucide="plus" class="w-6 h-6 text-slate-900 transition-transform" :class="{ 'rotate-45': quickActionsOpen }"></i>
                </button>
            <?php elseif (!empty($item['isMenu'])): ?>
                <!-- Menu Toggle (opens sidebar) -->
                <button 
                    @click="$dispatch('toggle-mobile-sidebar')"
                    class="mobile-nav-item"
                >
                    <i data-lucide="<?= $item['icon'] ?>" class="mobile-nav-icon"></i>
                    <span class="mobile-nav-label"><?= $item['label'] ?></span>
                </button>
            <?php else: ?>
                <!-- Regular Nav Item -->
                <a 
                    href="<?= $item['href'] ?>"
                    class="mobile-nav-item <?= $currentRoute === $item['route'] ? 'mobile-nav-item--active' : '' ?>"
                >
                    <i data-lucide="<?= $item['icon'] ?>" class="mobile-nav-icon"></i>
                    <span class="mobile-nav-label"><?= $item['label'] ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Quick Actions Sheet -->
    <div 
        x-show="quickActionsOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeQuickActions()"
        class="quick-actions-backdrop"
    ></div>
    
    <div 
        x-show="quickActionsOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        @click.stop
        class="quick-actions-sheet"
    >
        <div class="quick-actions-handle"></div>
        <h3 class="quick-actions-title">Quick Actions</h3>
        
        <div class="quick-actions-grid">
            <!-- Quick Check-in -->
            <button @click="$dispatch('open-quick-checkin'); closeQuickActions()" class="quick-action-card quick-action--emerald">
                <div class="quick-action-icon-wrap">
                    <i data-lucide="log-in" class="w-6 h-6"></i>
                </div>
                <span class="quick-action-label">Check-in</span>
            </button>
            
            <!-- Check-out -->
            <a href="/bookings?tab=departures" @click="closeQuickActions()" class="quick-action-card quick-action--orange">
                <div class="quick-action-icon-wrap">
                    <i data-lucide="log-out" class="w-6 h-6"></i>
                </div>
                <span class="quick-action-label">Check-out</span>
            </a>
            
            <!-- New Booking -->
            <a href="/bookings/create" @click="closeQuickActions()" class="quick-action-card quick-action--cyan">
                <div class="quick-action-icon-wrap">
                    <i data-lucide="calendar-plus" class="w-6 h-6"></i>
                </div>
                <span class="quick-action-label">New Booking</span>
            </a>
            
            <!-- Housekeeping -->
            <a href="/housekeeping" @click="closeQuickActions()" class="quick-action-card quick-action--blue">
                <div class="quick-action-icon-wrap">
                    <i data-lucide="sparkles" class="w-6 h-6"></i>
                </div>
                <span class="quick-action-label">Housekeeping</span>
            </a>
            
            <!-- POS -->
            <a href="/pos" @click="closeQuickActions()" class="quick-action-card quick-action--purple">
                <div class="quick-action-icon-wrap">
                    <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                </div>
                <span class="quick-action-label">POS</span>
            </a>
            
            <!-- Shifts -->
            <a href="/shifts" @click="closeQuickActions()" class="quick-action-card quick-action--amber">
                <div class="quick-action-icon-wrap">
                    <i data-lucide="banknote" class="w-6 h-6"></i>
                </div>
                <span class="quick-action-label">Cash & Shifts</span>
            </a>
        </div>
    </div>
</nav>

<style>
.mobile-nav {
    display: none;
}

@media (max-width: 767px) {
    .mobile-nav {
        display: block;
    }
}

.mobile-nav-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 64px;
    background: rgba(15, 23, 42, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: 0 8px;
    z-index: 60;
    padding-bottom: env(safe-area-inset-bottom);
}

.mobile-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 8px 16px;
    color: #64748b;
    text-decoration: none;
    transition: color 0.2s;
    min-width: 60px;
    -webkit-tap-highlight-color: transparent;
}

.mobile-nav-item:active {
    transform: scale(0.95);
}

.mobile-nav-item--active {
    color: #22d3ee;
}

.mobile-nav-icon {
    width: 22px;
    height: 22px;
}

.mobile-nav-label {
    font-size: 10px;
    font-weight: 500;
}

/* FAB Button */
.mobile-nav-fab {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(34, 211, 238, 0.4);
    transform: translateY(-14px);
    transition: all 0.2s;
    -webkit-tap-highlight-color: transparent;
}

.mobile-nav-fab:active {
    transform: translateY(-14px) scale(0.95);
}

.mobile-nav-fab--active {
    background: linear-gradient(135deg, #f87171, #ef4444);
    box-shadow: 0 4px 20px rgba(248, 113, 113, 0.4);
}

/* Quick Actions Sheet */
.quick-actions-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 100;
}

.quick-actions-sheet {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    border-top-left-radius: 24px;
    border-top-right-radius: 24px;
    padding: 12px 20px 24px;
    z-index: 101;
    padding-bottom: calc(24px + env(safe-area-inset-bottom));
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.5);
}

.quick-actions-handle {
    width: 40px;
    height: 4px;
    background: #475569;
    border-radius: 2px;
    margin: 0 auto 12px;
}

.quick-actions-title {
    font-size: 16px;
    font-weight: 600;
    color: #f1f5f9;
    margin-bottom: 16px;
    text-align: center;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.quick-action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 8px;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.2s;
    -webkit-tap-highlight-color: transparent;
    border: 1px solid transparent;
}

.quick-action-card:active {
    transform: scale(0.95);
}

.quick-action-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quick-action-label {
    font-size: 11px;
    font-weight: 500;
    text-align: center;
}

/* Action Colors */
.quick-action--emerald {
    background: rgba(52, 211, 153, 0.1);
    border-color: rgba(52, 211, 153, 0.2);
    color: #34d399;
}
.quick-action--emerald .quick-action-icon-wrap {
    background: rgba(52, 211, 153, 0.2);
}

.quick-action--orange {
    background: rgba(251, 146, 60, 0.1);
    border-color: rgba(251, 146, 60, 0.2);
    color: #fb923c;
}
.quick-action--orange .quick-action-icon-wrap {
    background: rgba(251, 146, 60, 0.2);
}

.quick-action--cyan {
    background: rgba(34, 211, 238, 0.1);
    border-color: rgba(34, 211, 238, 0.2);
    color: #22d3ee;
}
.quick-action--cyan .quick-action-icon-wrap {
    background: rgba(34, 211, 238, 0.2);
}

.quick-action--blue {
    background: rgba(96, 165, 250, 0.1);
    border-color: rgba(96, 165, 250, 0.2);
    color: #60a5fa;
}
.quick-action--blue .quick-action-icon-wrap {
    background: rgba(96, 165, 250, 0.2);
}

.quick-action--purple {
    background: rgba(167, 139, 250, 0.1);
    border-color: rgba(167, 139, 250, 0.2);
    color: #a78bfa;
}
.quick-action--purple .quick-action-icon-wrap {
    background: rgba(167, 139, 250, 0.2);
}

.quick-action--amber {
    background: rgba(251, 191, 36, 0.1);
    border-color: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
}
.quick-action--amber .quick-action-icon-wrap {
    background: rgba(251, 191, 36, 0.2);
}

/* Add padding to main content when mobile nav is visible */
@media (max-width: 767px) {
    .main-content {
        padding-bottom: calc(64px + env(safe-area-inset-bottom) + 16px) !important;
    }
}
</style>

<script>
function mobileBottomNav() {
    return {
        quickActionsOpen: false,
        
        toggleQuickActions() {
            this.quickActionsOpen = !this.quickActionsOpen;
            this.hapticFeedback();
        },
        
        closeQuickActions() {
            this.quickActionsOpen = false;
        },
        
        hapticFeedback() {
            if (navigator.vibrate) {
                navigator.vibrate(10);
            }
        }
    }
}
</script>

