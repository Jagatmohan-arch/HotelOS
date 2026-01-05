<?php
/**
 * HotelOS - Sidebar Navigation (Phase 1: Role Realization)
 * 
 * Strict Role-Based Access Control (RBAC) Navigation
 * Each menu item is explicitly filtered by the 'roles' array.
 * 
 * Target Roles: Owner, Manager, Reception, Staff
 */

$currentRoute = $currentRoute ?? 'dashboard';
$user = $user ?? ['first_name' => 'User', 'role' => 'staff'];
$userRole = $user['role'] ?? 'staff';

// Load subscription for trial badge (safe fail)
$subscriptionData = [];
if (class_exists('\HotelOS\Handlers\SubscriptionHandler')) {
    try {
        $subHandler = new \HotelOS\Handlers\SubscriptionHandler();
        $subscriptionData = $subHandler->getCurrentSubscription();
    } catch (\Exception $e) {}
}

// ==========================================
// PHASE 1: STRICT ROLE-BASED NAVIGATION
// ==========================================

$allNavItems = [
    // 1. DASHBOARD (Everyone)
    [
        'route' => 'dashboard',
        'label' => 'Dashboard',
        'icon'  => 'layout-dashboard',
        'href'  => '/dashboard',
        'roles' => ['owner', 'manager', 'reception', 'accountant', 'housekeeping', 'staff'],
    ],

    // 2. FRONT DESK (Manager & Reception)
    [
        'route' => 'front-desk',
        'label' => 'Front Desk',
        'icon'  => 'monitor-check',
        'href'  => '#',
        'roles' => ['manager', 'reception', 'owner'],
        'children' => [
            ['route' => 'bookings', 'label' => 'New Booking', 'href' => '/bookings/create'],
            ['route' => 'bookings', 'label' => 'Today Arrivals', 'href' => '/bookings?tab=arrivals'],
            ['route' => 'bookings', 'label' => 'In-House Guests', 'href' => '/bookings?tab=inhouse'],
            ['route' => 'bookings', 'label' => 'Express Checkout', 'href' => '/bookings?tab=departures'],
        ]
    ],

    // 3. BOOKINGS (Admin/Manager/Reception)
    [
        'route' => 'bookings',
        'label' => 'All Bookings',
        'icon'  => 'calendar-days',
        'href'  => '/bookings',
        'roles' => ['owner', 'manager', 'reception'],
    ],

    // 4. ROOM STATUS (Different views for Housekeeping vs Admin)
    [
        'route' => 'rooms',
        'label' => 'Room Status',
        'icon'  => 'bed-double',
        'href'  => '/rooms',
        'roles' => ['owner', 'manager', 'reception', 'housekeeping'],
        'children' => [
            ['route' => 'rooms', 'label' => 'Room View', 'href' => '/rooms'],
            ['route' => 'room-types', 'label' => 'Room Types & Rates', 'href' => '/room-types', 'roles' => ['owner', 'manager']],
        ]
    ],

    // 5. HOUSEKEEPING TASKS (Staff)
    [
        'route' => 'housekeeping',
        'label' => 'My Tasks',
        'icon'  => 'clipboard-check',
        'href'  => '/housekeeping',
        'roles' => ['housekeeping', 'staff'], 
    ],

    // 6. POS (Admin/Manager/Reception)
    [
        'route' => 'pos',
        'label' => 'POS & Service',
        'icon'  => 'coffee',
        'href'  => '/pos',
        'roles' => ['owner', 'manager', 'reception'],
    ],

    // 7. SHIFTS (Everyone who works shifts)
    [
        'route' => 'shifts',
        'label' => 'My Shift',
        'icon'  => 'timer',
        'href'  => '/shifts',
        'roles' => ['manager', 'reception', 'staff'],
    ],

    // 8. REPORTS (Owner/Manager/Accountant)
    [
        'route' => 'reports',
        'label' => 'Reports & Analytics',
        'icon'  => 'bar-chart-3',
        'href'  => '/reports',
        'roles' => ['owner', 'manager', 'accountant'],
        'children' => [
            ['route' => 'reports', 'label' => 'Daily Revenue', 'href' => '/reports/daily'],
            ['route' => 'reports', 'label' => 'Police Report', 'href' => '/reports/police'],
            ['route' => 'reports', 'label' => 'Occupancy', 'href' => '/reports/occupancy'],
        ]
    ],

    // 9. REFUND APPROVALS (Owner/Manager)
    [
        'route' => 'admin-refunds',
        'label' => 'Refund Approvals',
        'icon'  => 'receipt-refund',
        'href'  => '/admin/refunds',
        'roles' => ['owner', 'manager'],
    ],

    // 10. SYSTEM SETTINGS (Owner Only)
    [
        'route' => 'settings',
        'label' => 'System Settings',
        'icon'  => 'settings-2',
        'href'  => '/settings',
        'roles' => ['owner'],
        'children' => [
            ['route' => 'settings', 'label' => 'Hotel Profile', 'href' => '/settings'],
            ['route' => 'users', 'label' => 'Staff Management', 'href' => '/settings?tab=users'],
            ['route' => 'settings', 'label' => 'Tax & GST', 'href' => '/settings?tab=tax'],
        ]
    ],
];

// Active Filtering Logic
$navItems = [];
foreach ($allNavItems as $item) {
    if (isset($item['roles']) && !in_array($userRole, $item['roles'])) continue;

    if (isset($item['children'])) {
        $filteredChildren = [];
        foreach ($item['children'] as $child) {
            if (isset($child['roles']) && !in_array($userRole, $child['roles'])) continue;
            $filteredChildren[] = $child;
        }
        if (empty($filteredChildren) && $item['href'] === '#') continue;
        $item['children'] = $filteredChildren;
    }
    $navItems[] = $item;
}
?>

<!-- UX: Sidebar Container -->
<aside 
    x-data="{ 
        expanded: false,
        mobileOpen: false,
        activeDropdown: null,
        isMobile: window.innerWidth < 768,
        isTablet: window.innerWidth >= 768 && window.innerWidth < 1024,
        
        init() {
            this.handleResize();
            window.addEventListener('resize', () => this.handleResize());
            
            // Restore desktop state
            if (!this.isMobile && !this.isTablet) {
                this.expanded = localStorage.getItem('sidebarExpanded') !== 'false';
            }
        },
        
        handleResize() {
            this.isMobile = window.innerWidth < 768;
            this.isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
            if (!this.isMobile && !this.isTablet && this.mobileOpen) this.mobileOpen = false;
        },
        
        toggleExpanded() {
            this.expanded = !this.expanded;
            localStorage.setItem('sidebarExpanded', this.expanded);
            document.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { expanded: this.expanded } }));
        },
        
        toggleDropdown(name) {
            this.activeDropdown = this.activeDropdown === name ? null : name;
        },
        
        closeMobile() { this.mobileOpen = false; }
    }"
    @keydown.escape.window="closeMobile()"
    @toggle-mobile-sidebar.window="mobileOpen = !mobileOpen"
    @close-mobile-sidebar.window="closeMobile()"
    class="sidebar-container"
>
    <!-- Mobile Overlay -->
    <div 
        x-show="mobileOpen"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeMobile()"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[69] lg:hidden"
    ></div>
    
    <!-- Sidebar Panel -->
    <nav 
        :class="[
            'sidebar-nav',
            !isMobile && expanded ? 'sidebar-nav--expanded' : '',
            !isMobile && !expanded ? 'sidebar-nav--collapsed' : '',
            isMobile && mobileOpen ? 'sidebar-nav--mobile-open' : ''
        ]"
        class="fixed left-0 top-0 h-full z-[70] flex flex-col transition-all duration-300"
    >
        <!-- Logo -->
        <div class="sidebar-header">
            <a href="/dashboard" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center shadow-lg shadow-cyan-500/30">
                    <i data-lucide="building-2" class="w-5 h-5 text-white"></i>
                </div>
                <span x-show="expanded || mobileOpen" class="text-lg font-bold text-white tracking-tight">HotelOS</span>
            </a>
            <button @click="closeMobile()" class="lg:hidden p-2 text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <!-- Role-Specific Quick Actions -->
        <?php if (in_array($userRole, ['manager', 'reception', 'owner'])): ?>
        <div class="quick-actions" x-show="expanded || mobileOpen">
            <a href="/bookings/create" class="quick-action-btn" title="New Booking">
                <i data-lucide="plus-circle"></i> <span>Book</span>
            </a>
            <a href="#" @click.prevent="$dispatch('open-quick-checkin')" class="quick-action-btn quick-action-btn--green" title="Check-in">
                <i data-lucide="log-in"></i> <span>Check-in</span>
            </a>
            <?php if ($userRole !== 'reception'): ?>
            <a href="/reports/occupancy" class="quick-action-btn quick-action-btn--amber" title="Stats">
                <i data-lucide="pie-chart"></i> <span>Stats</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Navigation List -->
        <div class="sidebar-content flex-1 overflow-y-auto py-4 custom-scrollbar">
            <ul class="space-y-1.5 px-3">
                <?php foreach ($navItems as $item): ?>
                    <?php 
                    $isActive = $currentRoute === $item['route'] || 
                                (isset($item['children']) && in_array($currentRoute, array_column($item['children'], 'route')));
                    ?>
                    
                    <?php if (isset($item['children']) && !empty($item['children'])): ?>
                        <!-- Dropdown -->
                        <li>
                            <button @click="toggleDropdown('<?= $item['route'] ?>')" class="nav-link w-full <?= $isActive ? 'nav-link--active' : '' ?>">
                                <i data-lucide="<?= $item['icon'] ?>" class="nav-link-icon"></i>
                                <span x-show="expanded || mobileOpen" class="nav-link-text flex-1 text-left"><?= htmlspecialchars($item['label']) ?></span>
                                <i x-show="expanded || mobileOpen" data-lucide="chevron-down" class="w-4 h-4 transition-transform opacity-50" :class="activeDropdown === '<?= $item['route'] ?>' ? 'rotate-180' : ''"></i>
                            </button>
                            <ul x-show="activeDropdown === '<?= $item['route'] ?>' && (expanded || mobileOpen)" x-collapse class="mt-1 ml-9 space-y-1 border-l border-slate-700/50 pl-2">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li>
                                        <a href="<?= $child['href'] ?>" class="nav-link nav-link--child <?= ($currentRoute === $child['route'] && strpos($_SERVER['REQUEST_URI'] ?? '', $child['href']) !== false) ? 'nav-link--active' : '' ?>">
                                            <?= htmlspecialchars($child['label']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Link -->
                        <li>
                            <a href="<?= $item['href'] ?>" class="nav-link <?= $isActive ? 'nav-link--active' : '' ?>" title="<?= htmlspecialchars($item['label']) ?>">
                                <i data-lucide="<?= $item['icon'] ?>" class="nav-link-icon"></i>
                                <span x-show="expanded || mobileOpen" class="nav-link-text flex-1"><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- User Profile (Bottom) -->
        <div class="sidebar-footer">
            <div class="user-card group hover:bg-slate-800/50 transition-colors" x-data="{ showMenu: false }">
                <div class="flex items-center gap-3 cursor-pointer" @click="showMenu = !showMenu">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-md">
                        <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                    </div>
                    <div x-show="expanded || mobileOpen" class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($user['first_name']) ?></p>
                        <p class="text-[11px] text-slate-400 font-medium uppercase tracking-wider"><?= htmlspecialchars($user['role']) ?></p>
                    </div>
                    <i x-show="expanded || mobileOpen" data-lucide="more-vertical" class="w-4 h-4 text-slate-500"></i>
                </div>
                
                <div x-show="showMenu && (expanded || mobileOpen)" @click.outside="showMenu = false" class="absolute bottom-full left-2 right-2 mb-2 bg-slate-900 rounded-xl border border-slate-700 shadow-2xl p-1 z-50">
                    <a href="/profile" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 rounded-lg">
                        <i data-lucide="user" class="w-4 h-4"></i> Profile
                    </a>
                    <a href="/logout" class="flex items-center gap-2 px-3 py-2 text-sm text-red-400 hover:bg-red-500/10 rounded-lg">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                    </a>
                </div>
            </div>
        </div>
    </nav>
</aside>

<style>
    [x-cloak] { display: none !important; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 4px; }
    
    .sidebar-nav { width: 260px; background: #0f172a; border-right: 1px solid rgba(255, 255, 255, 0.05); }
    .sidebar-nav--collapsed { width: 72px; }
    
    @media (max-width: 767px) {
        .sidebar-nav { transform: translateX(-100%); width: 75%; max-width: 300px; }
        .sidebar-nav--mobile-open { transform: translateX(0); visibility: visible; box-shadow: 10px 0 50px rgba(0,0,0,0.5); }
        .sidebar-nav--mobile-open .nav-link-text { display: block !important; opacity: 1 !important; } 
    }
    
    .sidebar-header { height: 64px; display: flex; align-items: center; justify-content: space-between; padding: 0 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2); }
    
    .nav-link { display: flex; align-items: center; gap: 0.875rem; padding: 0.625rem 0.875rem; border-radius: 0.5rem; color: #94a3b8; transition: all 0.2s; font-size: 0.875rem; font-weight: 500; text-decoration: none; }
    .nav-link:hover { background: rgba(255,255,255,0.03); color: #e2e8f0; }
    .nav-link--active { background: rgba(6, 182, 212, 0.1); color: #22d3ee; }
    .nav-link--active .nav-link-icon { color: #22d3ee; filter: drop-shadow(0 0 8px rgba(34, 211, 238, 0.3)); }
    .nav-link-icon { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }
    
    .quick-actions { padding: 0.75rem 1rem; display: flex; gap: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.1); }
    .quick-action-btn { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.25rem; padding: 0.5rem; border-radius: 0.5rem; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); color: #38bdf8; font-size: 0.65rem; font-weight: 600; text-decoration: none; }
    .quick-action-btn--green { background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2); color: #4ade80; }
    .quick-action-btn--amber { background: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.2); color: #fbbf24; }
    .quick-action-btn svg { width: 1.125rem; height: 1.125rem; }
    
    /* Ensure text is hidden in collapsed desktop mode */
    .sidebar-nav--collapsed .nav-link-text, 
    .sidebar-nav--collapsed .group p,
    .sidebar-nav--collapsed .sidebar-header span,
    .sidebar-nav--collapsed i[data-lucide="chevron-down"],
    .sidebar-nav--collapsed .quick-actions { display: none; }
    
    .sidebar-nav--collapsed .sidebar-header { justify-content: center; padding: 0; }
    .sidebar-nav--collapsed .nav-link { justify-content: center; padding: 0.75rem; }
    .sidebar-nav--collapsed .user-card { display: flex; justify-content: center; }
</style>
