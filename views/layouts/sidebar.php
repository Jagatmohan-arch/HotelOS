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
$navItems = [
    [
        'route' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'layout-dashboard',
        'href' => '/dashboard',
    ],
    [
        'route' => 'rooms',
        'label' => 'Rooms',
        'icon' => 'bed-double',
        'href' => '/rooms',
        'children' => [
            ['route' => 'rooms', 'label' => 'All Rooms', 'href' => '/rooms'],
            ['route' => 'room-types', 'label' => 'Room Types', 'href' => '/room-types'],
        ]
    ],
    [
        'route' => 'bookings',
        'label' => 'Bookings',
        'icon' => 'calendar-check',
        'href' => '/bookings',
    ],
    [
        'route' => 'housekeeping',
        'label' => 'Housekeeping',
        'icon' => 'spray-can',
        'href' => '/housekeeping',
    ],
    [
        'route' => 'pos',
        'label' => 'POS & Extras',
        'icon' => 'shopping-cart',
        'href' => '/pos',
    ],
    [
        'route' => 'reports',
        'label' => 'Reports',
        'icon' => 'bar-chart-3',
        'href' => '/reports',
    ],
    [
        'route' => 'subscription',
        'label' => 'Subscription',
        'icon' => 'crown',
        'href' => '/subscription',
        'badge' => $subscriptionData['is_trial'] && !$subscriptionData['is_expired'] 
            ? $subscriptionData['days_remaining'] . 'd' 
            : null,
        'badgeColor' => $subscriptionData['days_remaining'] <= 3 ? 'red' : 'cyan',
    ],
    [
        'route' => 'settings',
        'label' => 'Settings',
        'icon' => 'settings',
        'href' => '/settings',
    ],
];
?>

<!-- Sidebar Container with Alpine.js -->
<aside 
    x-data="{ 
        expanded: localStorage.getItem('sidebarExpanded') !== 'false',
        mobileOpen: false,
        activeDropdown: null,
        toggleExpanded() {
            this.expanded = !this.expanded;
            localStorage.setItem('sidebarExpanded', this.expanded);
        },
        toggleDropdown(name) {
            this.activeDropdown = this.activeDropdown === name ? null : name;
        }
    }"
    @keydown.escape.window="mobileOpen = false"
    @toggle-mobile-sidebar.window="mobileOpen = true"
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
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden"
    ></div>
    
    <!-- Sidebar Panel -->
    <nav 
        :class="[
            'sidebar-nav',
            expanded ? 'sidebar-nav--expanded' : 'sidebar-nav--collapsed',
            mobileOpen ? 'sidebar-nav--mobile-open' : ''
        ]"
        class="fixed left-0 top-0 h-full z-50 flex flex-col transition-all duration-300"
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
            
            <!-- Collapse Button (Desktop) -->
            <button 
                @click="toggleExpanded()"
                class="hidden lg:flex items-center justify-center w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600/50 text-slate-400 hover:text-white transition-colors"
                :title="expanded ? 'Collapse' : 'Expand'"
            >
                <i :data-lucide="expanded ? 'chevron-left' : 'chevron-right'" class="w-4 h-4"></i>
            </button>
            
            <!-- Close Button (Mobile) -->
            <button 
                @click="mobileOpen = false"
                class="lg:hidden flex items-center justify-center w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-600/50 text-slate-400 hover:text-white transition-colors"
            >
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <div class="sidebar-content flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-3">
                <?php foreach ($navItems as $item): ?>
                    <?php 
                    $isActive = $currentRoute === $item['route'] || 
                                (isset($item['children']) && in_array($currentRoute, array_column($item['children'], 'route')));
                    ?>
                    
                    <?php if (isset($item['children'])): ?>
                        <!-- Dropdown Item -->
                        <li>
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
                        <li>
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
    
    /* Mobile: Hide by default, show on mobileOpen */
    @media (max-width: 1023px) {
        .sidebar-nav {
            transform: translateX(-100%);
        }
        .sidebar-nav--mobile-open {
            transform: translateX(0);
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
</style>
