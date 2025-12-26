<?php
/**
 * HotelOS - Login View Component
 * Glassmorphism login card with Antigravity theme
 */

declare(strict_types=1);

$error = $error ?? null;
$csrfToken = $csrfToken ?? '';
?>

<div class="login-wrapper">
    <div class="login-card glass-card glass-card--glow animate-fadeIn">
        <!-- Header -->
        <header class="login-header">
            <div class="login-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2Z"/>
                    <path d="m9 16 .348-.24c1.465-1.013 3.84-1.013 5.304 0L15 16"/>
                    <path d="M8 7h.01"/>
                    <path d="M16 7h.01"/>
                    <path d="M12 7h.01"/>
                    <path d="M12 11h.01"/>
                    <path d="M16 11h.01"/>
                    <path d="M8 11h.01"/>
                    <path d="M10 22v-6.5m4 0V22"/>
                </svg>
            </div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to HotelOS Dashboard</p>
        </header>
        
        <!-- Error Alert -->
        <?php if ($error): ?>
        <div class="alert alert--error mb-md" role="alert">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form 
            x-data="loginForm" 
            @submit.prevent="submit"
            method="POST" 
            action="/api/auth/login"
            novalidate
        >
            <!-- CSRF Token -->
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <!-- JS Error Display -->
            <template x-if="error">
                <div class="alert alert--error mb-md" role="alert">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-text="error"></span>
                </div>
            </template>
            
            <!-- Email Field -->
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        id="email" 
                        name="email"
                        x-model="email"
                        :class="{ 'form-input--error': errors.email }"
                        class="form-input"
                        placeholder="you@hotel.com"
                        autocomplete="email"
                        required
                    >
                </div>
                <template x-if="errors.email">
                    <p class="form-error" x-text="errors.email"></p>
                </template>
            </div>
            
            <!-- Password Field -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <input 
                        :type="showPassword ? 'text' : 'password'" 
                        id="password" 
                        name="password"
                        x-model="password"
                        :class="{ 'form-input--error': errors.password }"
                        class="form-input"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button 
                        type="button" 
                        class="input-toggle"
                        @click="togglePassword"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    >
                        <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                        <i x-show="showPassword" data-lucide="eye-off" class="w-5 h-5"></i>
                    </button>
                </div>
                <template x-if="errors.password">
                    <p class="form-error" x-text="errors.password"></p>
                </template>
            </div>
            
            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between mb-md">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="remember" 
                        class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0"
                    >
                    <span class="text-sm text-slate-400">Remember me</span>
                </label>
                <a href="/forgot-password" class="text-sm text-cyan-400 hover:text-cyan-300 transition-colors">
                    Forgot password?
                </a>
            </div>
            
            <!-- Submit Button -->
            <button 
                type="submit" 
                class="btn btn--primary btn--block"
                :class="{ 'btn--loading': loading }"
                :disabled="loading"
            >
                <span x-show="!loading">
                    Sign In
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </span>
            </button>
        </form>
        
        <!-- Footer -->
        <footer class="login-footer">
            <p>
                Don't have an account? 
                <a href="/register" class="text-cyan-400 hover:text-cyan-300">Contact Admin</a>
            </p>
        </footer>
    </div>
</div>

<!-- Theme Toggle (Floating) -->
<div class="fixed bottom-4 right-4">
    <div class="theme-toggle glass-card" x-data>
        <button 
            class="theme-toggle__btn"
            :class="{ 'theme-toggle__btn--active': $store.theme.current === 'cosmic' }"
            @click="$store.theme.set('cosmic')"
            title="Cosmic (Dark)"
        >
            <i data-lucide="moon" class="w-4 h-4"></i>
        </button>
        <button 
            class="theme-toggle__btn"
            :class="{ 'theme-toggle__btn--active': $store.theme.current === 'royal' }"
            @click="$store.theme.set('royal')"
            title="Royal (Light)"
        >
            <i data-lucide="sun" class="w-4 h-4"></i>
        </button>
        <button 
            class="theme-toggle__btn"
            :class="{ 'theme-toggle__btn--active': $store.theme.current === 'comfort' }"
            @click="$store.theme.set('comfort')"
            title="Comfort (Sepia)"
        >
            <i data-lucide="coffee" class="w-4 h-4"></i>
        </button>
    </div>
</div>
