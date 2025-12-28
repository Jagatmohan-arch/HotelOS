<?php
/**
 * HotelOS - Sidebar Navigation
 * 
 * Glassmorphism sidebar with collapsible desktop + overlay mobile
 * Uses Alpine.js for interactivity
 * 
 * Variables:
 * - $currentRoute: Current active route (e.g., 'dashboard', 'rooms')
 * - $user: Current authenticated user array
 */



$currentRoute = $currentRoute ?? 'dashboard';
$user = $user ?? ['first_name' => 'User', 'role' => 'user'];

// Load subscription data for trial badge
$subscriptionHandler = new \HotelOS\Handlers\SubscriptionHandler();
$subscriptionData = $subscriptionHandler->getCurrentSubscription();

// Navigation items with icons
// Navigation items - OWNER CONTROL TOWER HIERARCHY
$navItems = [
    // 1. Dashboard
    [
        'route' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'layout-dashboard',
        'href' => '/dashboard',
    ],
    // 2. Front Desk (Virtual Routing)
    [
        'route' => 'front-desk',
        'label' => 'Front Desk',
        'icon' => 'monitor-check',
        'href' => '#',
        'children' => [
            ['route' => 'bookings', 'label' => 'In-House Guests', 'href' => '/bookings?tab=inhouse'],
            ['route' => 'bookings', 'label' => 'Today Arrivals', 'href' => '/bookings?tab=arrivals'],
            ['route' => 'bookings', 'label' => 'Today Departures', 'href' => '/bookings?tab=departures'],
            ['route' => 'rooms', 'label' => 'Room Status Board', 'href' => '/rooms?view=grid'],
        ],
    ],
    // 3. Bookings
    [
        'route' => 'bookings',
        'label' => 'Bookings',
        'icon' => 'calendar-days',
        'href' => '/bookings',
        'children' => [
            ['route' => 'bookings', 'label' => 'All Bookings', 'href' => '/bookings'],
            ['route' => 'bookings', 'label' => 'New Booking', 'href' => '/bookings/create'],
            // Future: Calendar View
        ],
    ],
    // 4. Rooms
    [
        'route' => 'rooms',
        'label' => 'Rooms',
        'icon' => 'bed-double',
        'href' => '/rooms',
        'children' => [
            ['route' => 'rooms', 'label' => 'Room List', 'href' => '/rooms'],
            ['route' => 'room-types', 'label' => 'Room Types', 'href' => '/room-types'],
        ],
    ],
    // 5. Housekeeping
    [
        'route' => 'housekeeping',
        'label' => 'Housekeeping',
        'icon' => 'spray-can',
        'href' => '/housekeeping',
    ],
    // 6. POS / Extras
    [
        'route' => 'pos',
        'label' => 'POS & Extras',
        'icon' => 'shopping-cart',
        'href' => '/pos',
    ],
    // 7. Shifts & Cash
    [
        'route' => 'shifts',
        'label' => 'Shifts & Cash',
        'icon' => 'banknote',
        'href' => '/shifts',
    ],
    // 8. Reports
    [
        'route' => 'reports',
        'label' => 'Reports',
        'icon' => 'bar-chart-3',
        'href' => '/reports',
        'children' => [
            ['route' => 'reports', 'label' => 'Police Report (C-Form)', 'href' => '/reports/police'],
            ['route' => 'reports', 'label' => 'Daily Revenue', 'href' => '/reports/daily'], // Assuming route exists or will default
            ['route' => 'reports', 'label' => 'Occupancy', 'href' => '/reports/occupancy'],
        ]
    ],
    // 9. Administration (Owner Only)
    [
        'route' => 'settings',
        'label' => 'Administration',
        'icon' => 'shield-check',
        'href' => '/settings',
        'roles' => ['owner'],
        'children' => [
            ['route' => 'settings', 'label' => 'Hotel Settings', 'href' => '/settings'],
            ['route' => 'settings', 'label' => 'Tax & GST', 'href' => '/settings?tab=tax'],
            // Future: Staff Manager
        ]
    ],
];
?>

<!-- Sidebar Container with Alpine.js -->
<aside 
    x-data="{ 
        expanded: false,
        mobileOpen: false,
        activeDropdown: null,
        isMobile: window.innerWidth < 768,
        isTablet: window.innerWidth >= 768 && window.innerWidth < 1024,
        
        init() {
            // Device-aware initialization
            this.isMobile = window.innerWidth < 768;
            this.isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
            
            // DESKTOP RULE: Always Expanded (Control Tower)
            if (!this.isMobile && !this.isTablet) {
                this.expanded = true;
                localStorage.setItem('sidebarExpanded', 'true');
            } else if (this.isMobile) {
                this.expanded = false;
                this.mobileOpen = false;
            } else {
                // Tablet logic
                this.expanded = localStorage.getItem('sidebarExpanded') !== 'false';
            }
            
            // Listen for resize
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 768;
                this.isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
                
                // Enforce desktop rule on resize
                if (!this.isMobile && !this.isTablet) {
                    this.expanded = true;
                    if (this.mobileOpen) this.mobileOpen = false;
                }
            });
        },
        
        toggleExpanded() {
            this.expanded = !this.expanded;
            localStorage.setItem('sidebarExpanded', this.expanded);
            // Dispatch event for app-layout to listen
            document.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { expanded: this.expanded } }));
        },
        
        toggleDropdown(name) {
            this.activeDropdown = this.activeDropdown === name ? null : name;
        },
        
        closeMobile() {
            this.mobileOpen = false;
        },
        
        toggleMobile() {
            if (!this.isMobile) {
                this.toggleExpanded();
            } else {
                this.mobileOpen = !this.mobileOpen;
            }
        }
    }"
    @keydown.escape.window="closeMobile()"
    @toggle-mobile-sidebar.window="toggleMobile()"
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
        @click="mobileOpen = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[69] lg:hidden"
    ></div>
    
    <!-- Sidebar Panel -->
    <nav 
        :class="[
            'sidebar-nav',
            // Desktop/Tablet: use expanded for width
            !isMobile && expanded ? 'sidebar-nav--expanded' : '',
            !isMobile && !expanded ? 'sidebar-nav--collapsed' : '',
            // Mobile: use mobileOpen for visibility
            isMobile && mobileOpen ? 'sidebar-nav--mobile-open' : ''
        ]"
        class="fixed left-0 top-0 h-full z-[70] flex flex-col transition-all duration-300"
    >
        <!-- Logo Section -->
        <div class="sidebar-header">
            <a href="/dashboard" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center shadow-lg shadow-cyan-500/30">
                    <i data-lucide="building-2" class="w-5 h-5 text-white"></i>
                </div>
                <span 
                    x-show="expanded || mobileOpen" 
                    x-transition:enter="transition-opacity duration-200"
                    class="text-lg font-bold text-white"
                >
                    HotelOS
                </span>
            </a>
            
            <!-- Collapse Button (Removed for Desktop Control Tower) -->
            <!-- Fixed state only -->
            
            <!-- Close Button (Mobile) -->
            <button 
                @click="mobileOpen = false"
                class="lg:hidden flex items-center justify-center w-12 h-12 -mr-2 rounded-lg hover:bg-slate-600/50 text-slate-400 hover:text-white transition-colors"
                aria-label="Close Menu"
            >
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <!-- Quick Actions Bar -->
        <div class="quick-actions" x-show="expanded || mobileOpen">
            <a href="/bookings/create" class="quick-action-btn">
                <i data-lucide="plus-circle"></i>
                <span>New Booking</span>
            </a>
            <a href="#" @click.prevent="$dispatch('open-quick-checkin')" class="quick-action-btn quick-action-btn--green">
                <i data-lucide="log-in"></i>
                <span>Check-in</span>
            </a>
            <a href="/reports/police" class="quick-action-btn quick-action-btn--amber">
                <i data-lucide="shield"></i>
                <span>Police</span>
            </a>
            <a href="/reports/occupancy" class="quick-action-btn">
                <i data-lucide="bar-chart-3"></i>
                <span>Occupancy</span>
            </a>
        </div>
        
        <!-- Navigation Links -->
        <div class="sidebar-content flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-3">
                <?php foreach ($navItems as $item): ?>
                    <?php 
    // Role Based Access Control
    if (isset($item['roles']) && !in_array($user['role'], $item['roles'])) {
        continue;
    }

    $isActive = $currentRoute === $item['route'] || 
                (isset($item['children']) && in_array($currentRoute, array_column($item['children'], 'route')));
    ?>
                    
                    <?php if (isset($item['children'])): ?>
                        <!-- Dropdown Item -->
                        <li class="<?= !empty($item['hideOnMobile']) ? 'hidden md:block' : '' ?>">
                            <button 
                                @click="toggleDropdown('<?= $item['route'] ?>')"
                                class="nav-link w-full <?= $isActive ? 'nav-link--active' : '' ?>"
                            >
                                <i data-lucide="<?= $item['icon'] ?>" class="nav-link-icon"></i>
                                <span 
                                    x-show="expanded || mobileOpen" 
                                    class="nav-link-text flex-1 text-left"
                                >
                                    <?= htmlspecialchars($item['label']) ?>
                                </span>
                                <i 
                                    x-show="expanded || mobileOpen" 
                                    data-lucide="chevron-down" 
                                    class="w-4 h-4 transition-transform"
                                    :class="activeDropdown === '<?= $item['route'] ?>' ? 'rotate-180' : ''"
                                ></i>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <ul 
                                x-show="activeDropdown === '<?= $item['route'] ?>' && (expanded || mobileOpen)"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="mt-1 ml-6 space-y-1"
                            >
                                <?php foreach ($item['children'] as $child): ?>
                                    <li>
                                        <a 
                                            href="<?= $child['href'] ?>"
                                            class="nav-link nav-link--child <?= $currentRoute === $child['route'] ? 'nav-link--active' : '' ?>"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-current opacity-50"></span>
                                            <span class="nav-link-text"><?= htmlspecialchars($child['label']) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Regular Link -->
                        <li class="<?= !empty($item['hideOnMobile']) ? 'hidden md:block' : '' ?>">
                            <a 
                                href="<?= $item['href'] ?>"
                                class="nav-link <?= $isActive ? 'nav-link--active' : '' ?>"
                                <?php if (!($expanded ?? true)): ?>
                                    title="<?= htmlspecialchars($item['label']) ?>"
                                <?php endif; ?>
                            >
                                <i data-lucide="<?= $item['icon'] ?>" class="nav-link-icon"></i>
                                <span x-show="expanded || mobileOpen" class="nav-link-text flex-1">
                                    <?= htmlspecialchars($item['label']) ?>
                                </span>
                                <?php if (!empty($item['badge'])): ?>
                                <span 
                                    x-show="expanded || mobileOpen" 
                                    class="nav-badge nav-badge--<?= $item['badgeColor'] ?? 'cyan' ?>"
                                >
                                    <?= htmlspecialchars($item['badge']) ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- User Section -->
        <div class="sidebar-footer">
            <div class="user-card" x-data="{ showMenu: false }">
                <div class="flex items-center gap-3 cursor-pointer" @click="showMenu = !showMenu">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                        <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                    </div>
                    <div x-show="expanded || mobileOpen" class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">
                            <?= htmlspecialchars($user['first_name']) ?>
                        </p>
                        <p class="text-xs text-slate-400 capitalize">
                            <?= htmlspecialchars($user['role']) ?>
                        </p>
                    </div>
                    <i x-show="expanded || mobileOpen" data-lucide="chevron-up" class="w-4 h-4 text-slate-500"></i>
                </div>
                
                <!-- User Dropdown -->
                <div 
                    x-show="showMenu && (expanded || mobileOpen)"
                    x-transition
                    @click.outside="showMenu = false"
                    class="absolute bottom-full left-3 right-3 mb-2 bg-slate-800 rounded-lg border border-slate-700 shadow-xl overflow-hidden"
                >
                    <a href="/profile" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-300 hover:bg-slate-700/50">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        Profile
                    </a>
                    <a href="/logout" class="flex items-center gap-2 px-3 py-2 text-sm text-red-400 hover:bg-slate-700/50">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    </nav>
</aside>

<style>
    /* Sidebar Styles */
    .sidebar-nav {
        width: 280px;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        border-right: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .sidebar-nav--collapsed {
        width: 72px;
    }
    
    /* Mobile (<768px): 70% overlay mode - HIDDEN by default, slide in on open */
    @media (max-width: 767px) {
        /* Default: completely hidden off-screen */
        .sidebar-nav {
            transform: translateX(-100%);
            width: 70%;
            max-width: 280px;
            min-width: 240px;
            box-shadow: none;
            visibility: hidden;
        }
        
        /* Open state: slide in, visible */
        .sidebar-nav--mobile-open {
            transform: translateX(0);
            visibility: visible;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.5);
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(24px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Force show text on mobile sidebar */
        .sidebar-nav--mobile-open .nav-link-text {
            display: block !important;
            opacity: 1 !important;
        }
        
        /* Sidebar content padding for mobile */
        .sidebar-nav--mobile-open .sidebar-content {
            padding: 0.5rem;
        }
        
        /* Override any collapsed/expanded classes on mobile */
        .sidebar-nav--collapsed,
        .sidebar-nav--expanded {
            /* These classes should not affect mobile */
        }
    }
    
    /* Tablet (768px - 1023px): Mini sidebar by default */
    @media (min-width: 768px) and (max-width: 1023px) {
        .sidebar-nav {
            width: 72px;
        }
        .sidebar-nav--expanded {
            width: 280px;
        }
    }
    
    /* Desktop (1024+): Full sidebar */
    @media (min-width: 1024px) {
        .sidebar-nav--collapsed {
            width: 72px;
        }
        .sidebar-nav--expanded {
            width: 280px;
        }
    }
    
    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .sidebar-footer {
        padding: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        position: relative;
    }
    
    /* Navigation Link Styles */
    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.625rem 0.75rem;
        border-radius: 0.5rem;
        color: #94a3b8;
        transition: all 0.2s ease;
        font-size: 0.875rem;
        text-decoration: none;
    }
    
    .nav-link:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #e2e8f0;
    }
    
    .nav-link--active {
        background: rgba(34, 211, 238, 0.1);
        color: #22d3ee;
    }
    
    .nav-link--active .nav-link-icon {
        color: #22d3ee;
    }
    
    .nav-link-icon {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
    }
    
    .nav-link--child {
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
    }
    
    .user-card {
        padding: 0.5rem;
        border-radius: 0.5rem;
        background: rgba(255, 255, 255, 0.03);
    }
    
    /* Nav Badge (for trial countdown etc) */
    .nav-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.5rem;
        font-size: 0.6875rem;
        font-weight: 600;
        border-radius: 9999px;
        letter-spacing: 0.02em;
    }
    
    .nav-badge--cyan {
        background: rgba(34, 211, 238, 0.15);
        color: #22d3ee;
    }
    
    .nav-badge--red {
        background: rgba(248, 113, 113, 0.15);
        color: #f87171;
        animation: pulse-badge 2s ease-in-out infinite;
    }
    
    @keyframes pulse-badge {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    
    /* ============================================
       Smooth Animation Fixes (No Flickering)
       ============================================ */
    
    /* GPU accelerated transitions */
    .sidebar-nav {
        will-change: width;
        transform: translateZ(0);
        backface-visibility: hidden;
    }
    
    .nav-link {
        transition: background 0.15s ease, color 0.15s ease !important;
        transform: translateZ(0);
    }
    
    /* Prevent icon re-render flicker */
    .nav-link-icon,
    .nav-link-icon svg {
        transition: none !important;
        transform: translateZ(0);
    }
    
    /* Smooth dropdown animation */
    .sidebar-content ul[x-show] {
        transform-origin: top;
    }
    
    /* ============================================
       Quick Actions Bar
       ============================================ */
    
    .quick-actions {
        padding: 0.75rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .quick-action-btn {
        flex: 1;
        min-width: 80px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem;
        border-radius: 0.5rem;
        background: rgba(34, 211, 238, 0.1);
        border: 1px solid rgba(34, 211, 238, 0.2);
        color: #22d3ee;
        font-size: 0.65rem;
        text-decoration: none;
        transition: all 0.2s ease;
        text-align: center;
    }
    
    .quick-action-btn:hover {
        background: rgba(34, 211, 238, 0.2);
        transform: translateY(-2px);
    }
    
    .quick-action-btn svg,
    .quick-action-btn i {
        width: 1rem;
        height: 1rem;
    }
    
    /* Hide quick actions when collapsed */
    .sidebar-nav--collapsed .quick-actions {
        display: none;
    }
    
    /* Quick action color variants */
    .quick-action-btn--green {
        background: rgba(34, 197, 94, 0.15);
        border-color: rgba(34, 197, 94, 0.3);
        color: #22c55e;
    }
    
    .quick-action-btn--green:hover {
        background: rgba(34, 197, 94, 0.25);
    }
    
    .quick-action-btn--amber {
        background: rgba(245, 158, 11, 0.15);
        border-color: rgba(245, 158, 11, 0.3);
        color: #f59e0b;
    }
    
    .quick-action-btn--amber:hover {
        background: rgba(245, 158, 11, 0.25);
    }
</style>
