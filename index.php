<?php
/**
 * HotelOS - Front Controller & Login
 * 
 * Production-ready entry point with Antigravity UI.
 * Handles login display and authentication.
 */

declare(strict_types=1);

// ============================================
// INITIALIZATION
// ============================================

// Load configuration
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/db.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Error handling - Never show raw errors in production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ============================================
// DATABASE CONNECTION CHECK
// ============================================

$dbConnected = testDBConnection();
$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!$dbConnected) {
        $error = 'System is temporarily offline. Please try again later.';
    } else {
        // TODO: Implement actual login logic in Phase 2
        $error = 'Login system will be activated after database setup.';
    }
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="cosmic">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO -->
    <title>Login | HotelOS</title>
    <meta name="description" content="HotelOS - Next-Gen Hotel Property Management System">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        cosmic: { bg: '#0f172a', card: '#1e293b' },
                        neon: { cyan: '#22d3ee', purple: '#a78bfa' }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Custom Styles -->
    <style>
        :root {
            --bg-cosmic: #0f172a;
            --glass-bg: rgba(30, 41, 59, 0.8);
            --glass-border: rgba(255, 255, 255, 0.1);
            --neon-cyan: #22d3ee;
            --neon-glow: 0 0 30px rgba(34, 211, 238, 0.4);
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-cosmic);
            min-height: 100vh;
        }
        
        /* Animated Background Orbs */
        .cosmic-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
        }
        
        .orb-cyan {
            width: 400px;
            height: 400px;
            background: #22d3ee;
            top: -150px;
            right: -100px;
        }
        
        .orb-purple {
            width: 350px;
            height: 350px;
            background: #a78bfa;
            bottom: -150px;
            left: -100px;
            animation-delay: -10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.05); }
            66% { transform: translate(-20px, 20px) scale(0.95); }
        }
        
        /* Glassmorphism Card */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), var(--neon-glow);
        }
        
        /* Floating Input Style */
        .input-field {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            border-color: var(--neon-cyan);
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
            outline: none;
        }
        
        /* Neon Button */
        .btn-neon {
            background: linear-gradient(135deg, #22d3ee, #06b6d4);
            box-shadow: 0 4px 15px rgba(34, 211, 238, 0.4);
            transition: all 0.3s ease;
        }
        
        .btn-neon:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(34, 211, 238, 0.5);
        }
        
        .btn-neon:active {
            transform: translateY(0);
        }
        
        /* Status Badge */
        .status-online { color: #34d399; }
        .status-offline { color: #f87171; }
    </style>
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏨</text></svg>">
</head>
<body class="dark">
    <!-- Animated Background -->
    <div class="cosmic-orb orb-cyan"></div>
    <div class="cosmic-orb orb-purple"></div>
    
    <!-- Main Container -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            
            <!-- Login Card -->
            <div class="glass-card rounded-2xl p-8 animate-[fadeIn_0.6s_ease]" x-data="{ showPassword: false }">
                
                <!-- Logo & Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-500 mb-4 shadow-lg shadow-cyan-500/30">
                        <i data-lucide="building-2" class="w-8 h-8 text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-white mb-1">Welcome to HotelOS</h1>
                    <p class="text-slate-400 text-sm">Sign in to manage your property</p>
                    
                    <!-- Connection Status -->
                    <div class="mt-3 flex items-center justify-center gap-2 text-xs">
                        <?php if ($dbConnected): ?>
                            <span class="flex items-center gap-1 status-online">
                                <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                                System Online
                            </span>
                        <?php else: ?>
                            <span class="flex items-center gap-1 status-offline">
                                <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                                System Offline
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Success Message -->
                <?php if ($success): ?>
                <div class="mb-6 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-start gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="" class="space-y-5">
                    <input type="hidden" name="action" value="login">
                    
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                                <i data-lucide="mail" class="w-5 h-5"></i>
                            </span>
                            <input 
                                type="email" 
                                id="email" 
                                name="email"
                                class="input-field w-full h-12 pl-12 pr-4 rounded-lg text-white placeholder-slate-500"
                                placeholder="you@hotel.com"
                                autocomplete="email"
                                required
                            >
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">
                                <i data-lucide="lock" class="w-5 h-5"></i>
                            </span>
                            <input 
                                :type="showPassword ? 'text' : 'password'"
                                id="password" 
                                name="password"
                                class="input-field w-full h-12 pl-12 pr-12 rounded-lg text-white placeholder-slate-500"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required
                            >
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors"
                            >
                                <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                                <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-400 hover:text-slate-300">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0">
                            Remember me
                        </label>
                        <a href="#" class="text-cyan-400 hover:text-cyan-300 transition-colors">Forgot password?</a>
                    </div>
                    
                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="btn-neon w-full h-12 rounded-lg text-slate-900 font-semibold flex items-center justify-center gap-2"
                    >
                        Sign In
                        <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    </button>
                </form>
                
                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-slate-700/50 text-center">
                    <p class="text-slate-500 text-xs">
                        © <?= date('Y') ?> HotelOS Enterprise v<?= APP_VERSION ?>
                    </p>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Initialize Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
    
    <!-- Fade In Animation -->
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</body>
</html>
