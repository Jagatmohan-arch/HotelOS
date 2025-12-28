<?php
/**
 * HotelOS - App Layout (Dashboard Shell)
 * 
 * Master layout for authenticated pages with sidebar + content area
 * 
 * Variables:
 * - $title: Page title
 * - $currentRoute: Current route name for sidebar highlighting
 * - $user: Authenticated user array
 * - $content: Main page content (from view)
 * - $csrfToken: CSRF token
 * - $breadcrumbs: Optional breadcrumb array [['label' => '', 'href' => ''], ...]
 */



$title = $title ?? 'Dashboard';
$currentRoute = $currentRoute ?? 'dashboard';
$user = $user ?? ['first_name' => 'User', 'role' => 'user'];
$breadcrumbs = $breadcrumbs ?? [];
?>
<!DOCTYPE html>
<html lang="en" data-theme="cosmic">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Meta -->
    <title><?= htmlspecialchars($title) ?> | HotelOS</title>
    <meta name="description" content="HotelOS - Next-Gen Hotel Property Management System">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Security -->
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self';
        script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net https://cdn.tailwindcss.com;
        style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net;
        font-src 'self' https://fonts.gstatic.com;
        img-src 'self' data: https:;
        connect-src 'self';
    ">
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: ['class', '[data-theme="cosmic"]'],
            theme: {
                extend: {
                    colors: {
                        cosmic: {
                            bg: '#0f172a',
                            card: '#1e293b',
                            border: 'rgba(255,255,255,0.1)'
                        },
                        neon: {
                            cyan: '#22d3ee',
                            purple: '#a78bfa',
                            green: '#34d399',
                            red: '#f87171',
                            yellow: '#fbbf24'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏨</text></svg>">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed: 72px;
            --header-height: 64px;
            --bg-cosmic: #0f172a;
            --glass-bg: rgba(30, 41, 59, 0.8);
            --neon-cyan: #22d3ee;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-cosmic);
            color: #e2e8f0;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Layout Grid */
        .app-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .main-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }
        
        .sidebar-collapsed .main-wrapper {
            margin-left: var(--sidebar-collapsed);
        }
        
        /* Mobile (<768px): No margin */
        @media (max-width: 767px) {
            .main-wrapper {
                margin-left: 0 !important;
            }
        }
        
        /* Tablet (768-1023px): Mini sidebar margin */
        @media (min-width: 768px) and (max-width: 1023px) {
            .main-wrapper {
                margin-left: var(--sidebar-collapsed);
            }
            .sidebar-expanded .main-wrapper {
                margin-left: var(--sidebar-width);
            }
        }
        
        /* Header */
        .app-header {
            height: var(--header-height);
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 30;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            transition: max-width 0.3s ease;
        }
        
        /* When sidebar is collapsed, content can expand more */
        .sidebar-collapsed .main-content {
            max-width: 1600px;
        }
        
        /* Full width for certain pages like booking wizard */
        .main-content--full {
            max-width: none;
        }
        
        @media (max-width: 1023px) {
            .main-content {
                padding: 1rem;
                padding-bottom: calc(80px + env(safe-area-inset-bottom));
                max-width: none;
            }
        }
        
        @media (min-width: 1920px) {
            .main-content {
                max-width: 1600px;
            }
            
            .sidebar-collapsed .main-content {
                max-width: 1800px;
            }
        }
        
        /* Glass Card */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
        }
        
        .glass-card--glow {
            box-shadow: 0 0 40px rgba(34, 211, 238, 0.1);
        }
        
        /* Stats Card */
        .stats-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--card-accent, var(--neon-cyan));
        }
        
        .stats-card--cyan { --card-accent: #22d3ee; }
        .stats-card--purple { --card-accent: #a78bfa; }
        .stats-card--green { --card-accent: #34d399; }
        .stats-card--gold { --card-accent: #fbbf24; }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }
        
        .stats-label {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }
        
        /* Mobile Dashboard: Calm Flow Design */
        @media (max-width: 767px) {
            /* Stats as horizontal scroll */
            .grid.grid-cols-2.lg\\:grid-cols-4 {
                display: flex;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                gap: 0.75rem;
                padding-bottom: 0.5rem;
                margin: 0 -1rem;
                padding-left: 1rem;
                padding-right: 1rem;
                scrollbar-width: none;
            }
            
            .grid.grid-cols-2.lg\\:grid-cols-4::-webkit-scrollbar {
                display: none;
            }
            
            .grid.grid-cols-2.lg\\:grid-cols-4 > .stats-card {
                flex: 0 0 70%;
                min-width: 200px;
                scroll-snap-align: start;
            }
            
            /* Smaller stats on mobile */
            .stats-value {
                font-size: 1.5rem;
            }
            
            .stats-card {
                padding: 1rem;
            }
            
            /* Single column for all grids */
            .grid.lg\\:grid-cols-3 {
                grid-template-columns: 1fr !important;
            }
            
            .lg\\:col-span-2 {
                grid-column: span 1 !important;
            }
            
            /* Quick actions: 2 per row */
            .glass-card .flex.flex-wrap.gap-2 {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }
            
            .glass-card .flex.flex-wrap.gap-2 .btn {
                justify-content: center;
                font-size: 0.75rem;
                padding: 0.5rem;
            }
            
            /* Page header mobile */
            .dashboard-page .flex.flex-col.md\\:flex-row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .dashboard-page h1 {
                font-size: 1.5rem;
            }
        }
        
        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        
        .btn--primary {
            background: linear-gradient(135deg, #22d3ee, #06b6d4);
            color: #0f172a;
            box-shadow: 0 4px 15px rgba(34, 211, 238, 0.3);
        }
        
        .btn--primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(34, 211, 238, 0.4);
        }
        
        .btn--secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn--secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .btn--ghost {
            background: transparent;
            color: #94a3b8;
        }
        
        .btn--ghost:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
        }
        
        /* Form Inputs */
        .form-input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            color: white;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--neon-cyan);
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.15);
        }
        
        .form-input::placeholder {
            color: #64748b;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        
        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .data-table td {
            padding: 0.875rem 1rem;
            font-size: 0.875rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge--green { background: rgba(34, 197, 94, 0.15); color: #34d399; }
        .badge--red { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .badge--yellow { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
        .badge--blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .badge--gray { background: rgba(100, 116, 139, 0.15); color: #94a3b8; }
        
        /* Room Status Grid */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.75rem;
        }
        
        .room-box {
            aspect-ratio: 1;
            border-radius: 0.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 2px solid transparent;
        }
        
        .room-box:hover {
            transform: scale(1.05);
        }
        
        .room-box--available {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
            color: #34d399;
        }
        
        .room-box--occupied {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        
        .room-box--reserved {
            background: rgba(251, 191, 36, 0.15);
            border-color: rgba(251, 191, 36, 0.3);
            color: #fbbf24;
        }
        
        .room-box--maintenance {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }
        
        .room-box--blocked {
            background: rgba(100, 116, 139, 0.15);
            border-color: rgba(100, 116, 139, 0.3);
            color: #94a3b8;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.4s ease forwards;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Quick Check-in FAB */
        .quick-checkin-fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);
            z-index: 30;
            transition: all 0.3s ease;
        }
        
        .quick-checkin-fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 30px rgba(34, 197, 94, 0.5);
        }
        
        .quick-checkin-fab:active {
            transform: scale(0.95);
        }
        
        /* Hide FAB on mobile (use bottom nav instead) */
        @media (max-width: 1023px) {
            .quick-checkin-fab {
                display: none;
            }
        }
    </style>
</head>
<body 
    x-data="{ sidebarCollapsed: localStorage.getItem('sidebarExpanded') === 'false' }"
    :class="{ 'sidebar-collapsed': sidebarCollapsed }"
>
    <div class="app-layout">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Header -->
            <header class="app-header">
                <!-- Mobile Menu Toggle -->
                <button 
                    @click="$dispatch('toggle-mobile-sidebar')"
                    class="lg:hidden mr-4 p-2 -ml-2 rounded-lg hover:bg-slate-700/50 text-slate-400"
                >
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-sm">
                    <a href="/dashboard" class="text-slate-500 hover:text-slate-300">
                        <i data-lucide="home" class="w-4 h-4"></i>
                    </a>
                    <?php if (!empty($breadcrumbs)): ?>
                        <?php foreach ($breadcrumbs as $crumb): ?>
                            <span class="text-slate-600">/</span>
                            <?php if (!empty($crumb['href'])): ?>
                                <a href="<?= $crumb['href'] ?>" class="text-slate-400 hover:text-slate-200">
                                    <?= htmlspecialchars($crumb['label']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-slate-300"><?= htmlspecialchars($crumb['label']) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-slate-600">/</span>
                        <span class="text-slate-300"><?= htmlspecialchars($title) ?></span>
                    <?php endif; ?>
                </nav>
                
                <!-- Spacer -->
                <div class="flex-1"></div>
                
                <!-- Header Actions -->
                <div class="flex items-center gap-3">
                    <!-- Notifications -->
                    <button class="p-2 rounded-lg hover:bg-slate-700/50 text-slate-400 hover:text-slate-200 relative">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-cyan-400 rounded-full"></span>
                    </button>
                    
                    <!-- Quick Search -->
                    <button class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/50 border border-slate-700/50 text-slate-500 text-sm hover:border-slate-600">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>Search...</span>
                        <kbd class="ml-2 px-1.5 py-0.5 rounded bg-slate-700 text-xs">⌘K</kbd>
                    </button>
                </div>
            </header>
            
            <!-- Main Content Area -->
            <main class="main-content">
                <?php if (isset($content)): ?>
                    <?= $content ?>
                <?php endif; ?>
            </main>
        </div>
        
        <!-- Mobile Navigation -->
        <?php include __DIR__ . '/mobile-nav.php'; ?>
        
        <!-- Quick Check-in Modal (Global) -->
        <?php include __DIR__ . '/../bookings/quick-checkin.php'; ?>
        
        <!-- Quick Check-in FAB (Desktop) -->
        <button 
            @click="$dispatch('open-quick-checkin')"
            class="quick-checkin-fab"
            title="Quick Check-in"
        >
            <i data-lucide="log-in" class="w-6 h-6"></i>
        </button>
    </div>
    
    <!-- Initialize Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            
            // Sync sidebar state with app-layout class
            const appLayout = document.querySelector('.app-layout');
            const sidebarState = localStorage.getItem('sidebarExpanded');
            
            if (sidebarState === 'false') {
                appLayout?.classList.add('sidebar-collapsed');
            }
            
            // Listen for sidebar toggle events
            window.addEventListener('storage', (e) => {
                if (e.key === 'sidebarExpanded') {
                    if (e.newValue === 'false') {
                        appLayout?.classList.add('sidebar-collapsed');
                    } else {
                        appLayout?.classList.remove('sidebar-collapsed');
                    }
                }
            });
            
            // Also listen for Alpine-triggered changes
            document.addEventListener('sidebar-toggle', (e) => {
                if (e.detail?.expanded === false) {
                    appLayout?.classList.add('sidebar-collapsed');
                } else {
                    appLayout?.classList.remove('sidebar-collapsed');
                }
            });
        });
        
        // Re-init after Alpine updates
        document.addEventListener('alpine:initialized', () => {
            // Small delay to let Alpine render
            setTimeout(() => lucide.createIcons(), 100);
        });
        
        // Watch for Alpine changes
        if (window.Alpine) {
            document.addEventListener('alpine:init', () => {
                Alpine.effect(() => {
                    setTimeout(() => lucide.createIcons(), 50);
                });
            });
        }
    </script>
</body>
</html>
