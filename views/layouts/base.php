<?php
/**
 * HotelOS - Base Layout Template
 * Master template for all pages with Antigravity theme
 * 
 * Variables available:
 * - $title: Page title
 * - $bodyClass: Additional body classes
 * - $content: Main content (set via view rendering)
 */

declare(strict_types=1);

// Default values
$title = $title ?? 'HotelOS';
$bodyClass = $bodyClass ?? '';
$csrfToken = $csrfToken ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="cosmic">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Meta -->
    <title><?= htmlspecialchars($title) ?> | HotelOS</title>
    <meta name="description" content="HotelOS - Next-Gen Hotel Property Management System for Indian Hotels">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Security -->
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <meta http-equiv="Content-Security-Policy" content="
        default-src 'self';
        script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net;
        style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net;
        font-src 'self' https://fonts.gstatic.com;
        img-src 'self' data: https:;
        connect-src 'self';
    ">
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/antigravity.css">
    
    <!-- Tailwind CSS (CDN for rapid development) -->
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
                            purple: '#a78bfa'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- App Scripts -->
    <script src="/assets/js/app.js" defer></script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏨</text></svg>">
    
    <?php if (!empty($headExtra)): ?>
        <?= $headExtra ?>
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>" x-data>
    <!-- Cosmic Background (Animated Orbs) -->
    <div class="cosmic-bg" aria-hidden="true">
        <div class="cosmic-orb cosmic-orb--cyan"></div>
        <div class="cosmic-orb cosmic-orb--purple"></div>
        <div class="cosmic-orb cosmic-orb--teal"></div>
    </div>
    
    <!-- Toast Notifications Container -->
    <div 
        x-data
        class="fixed top-4 right-4 z-50 flex flex-col gap-2"
        style="max-width: 380px;"
    >
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div 
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-8"
                :class="{
                    'alert--success': toast.type === 'success',
                    'alert--error': toast.type === 'error',
                    'alert--warning': toast.type === 'warning'
                }"
                class="alert glass-card"
            >
                <span x-text="toast.message"></span>
                <button @click="$store.toast.dismiss(toast.id)" class="ml-auto opacity-60 hover:opacity-100">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </template>
    </div>
    
    <!-- Main Content -->
    <main id="app">
        <?php if (isset($content)): ?>
            <?= $content ?>
        <?php else: ?>
            <!-- Content will be injected here -->
            <?php include $viewFile ?? ''; ?>
        <?php endif; ?>
    </main>
    
    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
        
        // Re-initialize icons after Alpine updates
        document.addEventListener('alpine:initialized', () => {
            lucide.createIcons();
        });
    </script>
    
    <?php if (!empty($footerExtra)): ?>
        <?= $footerExtra ?>
    <?php endif; ?>
</body>
</html>
