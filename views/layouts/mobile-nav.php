<?php
/**
 * HotelOS - Mobile Bottom Navigation
 * 
 * Fixed bottom navigation bar for mobile devices
 * Shows on screens smaller than lg (1024px)
 * 
 * Variables:
 * - $currentRoute: Current active route
 */



$currentRoute = $currentRoute ?? 'dashboard';

$mobileNavItems = [
    ['route' => 'dashboard', 'label' => 'Home', 'icon' => 'home', 'href' => '/dashboard'],
    ['route' => 'rooms', 'label' => 'Rooms', 'icon' => 'bed-double', 'href' => '/rooms'],
    ['route' => 'add', 'label' => 'Quick', 'icon' => 'plus', 'href' => '#', 'isAction' => true],
    ['route' => 'bookings', 'label' => 'Bookings', 'icon' => 'calendar-check', 'href' => '/bookings'],
    ['route' => 'menu', 'label' => 'Menu', 'icon' => 'menu', 'href' => '#', 'isMenu' => true],
];
?>

<!-- Mobile Bottom Navigation -->
<nav 
    class="mobile-nav"
    x-data="{ showQuickMenu: false }"
>
    <!-- Quick Action Modal -->
    <div 
        x-show="showQuickMenu"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showQuickMenu = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40"
    ></div>
    
    <div 
        x-show="showQuickMenu"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        class="fixed bottom-20 left-4 right-4 z-50 bg-slate-800 rounded-2xl border border-slate-700 shadow-2xl overflow-hidden"
    >
        <div class="p-4 border-b border-slate-700">
            <h3 class="text-sm font-semibold text-white">Quick Actions</h3>
        </div>
        <div class="grid grid-cols-3 gap-1 p-2">
            <a href="/bookings/create" class="quick-action-btn">
                <div class="quick-action-icon bg-emerald-500/20 text-emerald-400">
                    <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                </div>
                <span class="text-xs">New Booking</span>
            </a>
            <button @click="showQuickMenu = false; $dispatch('open-quick-checkin')" class="quick-action-btn">
                <div class="quick-action-icon bg-cyan-500/20 text-cyan-400">
                    <i data-lucide="log-in" class="w-5 h-5"></i>
                </div>
                <span class="text-xs">Check-in</span>
            </button>
            <a href="/bookings" class="quick-action-btn">
                <div class="quick-action-icon bg-orange-500/20 text-orange-400">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </div>
                <span class="text-xs">Check-out</span>
            </a>
            <a href="/rooms" class="quick-action-btn">
                <div class="quick-action-icon bg-purple-500/20 text-purple-400">
                    <i data-lucide="bed-double" class="w-5 h-5"></i>
                </div>
                <span class="text-xs">Manage Rooms</span>
            </a>
            <a href="/housekeeping" class="quick-action-btn">
                <div class="quick-action-icon bg-blue-500/20 text-blue-400">
                    <i data-lucide="sparkles" class="w-5 h-5"></i>
                </div>
                <span class="text-xs">Housekeeping</span>
            </a>
            <a href="/pos" class="quick-action-btn">
                <div class="quick-action-icon bg-yellow-500/20 text-yellow-400">
                    <i data-lucide="indian-rupee" class="w-5 h-5"></i>
                </div>
                <span class="text-xs">POS</span>
            </a>
        </div>
    </div>

    <!-- Navigation Bar -->
    <div class="mobile-nav-bar">
        <?php foreach ($mobileNavItems as $item): ?>
            <?php if (!empty($item['isAction'])): ?>
                <!-- Center FAB Button -->
                <button 
                    @click="showQuickMenu = !showQuickMenu"
                    class="mobile-nav-fab"
                    :class="showQuickMenu ? 'bg-slate-600' : 'bg-gradient-to-r from-cyan-500 to-cyan-400'"
                >
                    <i :data-lucide="showQuickMenu ? 'x' : 'plus'" class="w-6 h-6 text-white"></i>
                </button>
            <?php elseif (!empty($item['isMenu'])): ?>
                <!-- Menu Toggle (opens sidebar) -->
                <button 
                    @click="$dispatch('toggle-mobile-sidebar')"
                    class="mobile-nav-item <?= $currentRoute === $item['route'] ? 'mobile-nav-item--active' : '' ?>"
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
</nav>

<style>
    .mobile-nav {
        display: none;
    }
    
    @media (max-width: 1023px) {
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
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding: 0 0.5rem;
        z-index: 40;
        padding-bottom: env(safe-area-inset-bottom);
    }
    
    .mobile-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        padding: 0.5rem 0.75rem;
        color: #64748b;
        text-decoration: none;
        transition: color 0.2s;
        min-width: 60px;
    }
    
    .mobile-nav-item--active {
        color: #22d3ee;
    }
    
    .mobile-nav-icon {
        width: 1.25rem;
        height: 1.25rem;
    }
    
    .mobile-nav-label {
        font-size: 0.625rem;
        font-weight: 500;
    }
    
    .mobile-nav-fab {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 20px rgba(34, 211, 238, 0.4);
        transform: translateY(-12px);
        transition: all 0.2s;
    }
    
    .mobile-nav-fab:active {
        transform: translateY(-10px) scale(0.95);
    }
    
    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem 0.5rem;
        border-radius: 0.75rem;
        color: #e2e8f0;
        text-decoration: none;
        transition: background 0.2s;
    }
    
    .quick-action-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    
    .quick-action-icon {
        width: 44px;
        height: 44px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Add padding to main content when mobile nav is visible */
    @media (max-width: 1023px) {
        .main-content {
            padding-bottom: calc(64px + env(safe-area-inset-bottom) + 1rem);
        }
    }
</style>
