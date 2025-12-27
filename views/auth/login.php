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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="48" height="48" style="width: 48px; height: 48px;">
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
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="POST" action="/login">
            <!-- CSRF Token -->
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <!-- Email Field -->
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        id="email" 
                        name="email"
                        class="form-input"
                        placeholder="you@hotel.com"
                        autocomplete="email"
                        required
                    >
                </div>
            </div>
            
            <!-- Password Field -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        class="form-input"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </div>
            </div>
            
            <!-- Remember Me & Forgot Password -->
            <div class="login-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="/forgot-password" class="forgot-link">Forgot password?</a>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn btn--primary btn--block">
                <span>Sign In</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>
        
        <!-- Footer -->
        <footer class="login-footer">
            <p>Don't have an account? <a href="/register">Contact Admin</a></p>
        </footer>
    </div>
</div>

<style>
/* Login Page Specific Styles */
.login-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
}

.login-card {
    width: 100%;
    max-width: 420px;
    padding: 2.5rem;
    background: rgba(30, 41, 59, 0.8);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1.5rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-logo {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.login-logo svg {
    color: #22d3ee;
}

.login-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: 0.5rem;
}

.login-subtitle {
    color: #94a3b8;
    font-size: 0.95rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    color: #e2e8f0;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.875rem 1rem;
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 0.75rem;
    color: #f1f5f9;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: #22d3ee;
    box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
}

.form-input::placeholder {
    color: #64748b;
}

.login-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: #94a3b8;
    font-size: 0.875rem;
}

.remember-me input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    accent-color: #22d3ee;
}

.forgot-link {
    color: #22d3ee;
    font-size: 0.875rem;
    text-decoration: none;
    transition: color 0.2s;
}

.forgot-link:hover {
    color: #67e8f9;
}

.btn--primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    color: #0f172a;
    border: none;
    border-radius: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn--primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(34, 211, 238, 0.3);
}

.btn--primary:active {
    transform: translateY(0);
}

.login-footer {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: #94a3b8;
    font-size: 0.875rem;
}

.login-footer a {
    color: #22d3ee;
    text-decoration: none;
}

.login-footer a:hover {
    text-decoration: underline;
}

.alert--error {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 0.75rem;
    color: #fca5a5;
    margin-bottom: 1.5rem;
}

.alert--error svg {
    flex-shrink: 0;
}

/* Animation */
.animate-fadeIn {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
