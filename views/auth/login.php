<?php
/**
 * HotelOS - Login View Component
 * Modern floating design with dynamic background
 */

$error = $error ?? null;
$csrfToken = $csrfToken ?? '';
?>

<div class="login-page">
    <!-- Animated Background -->
    <div class="login-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    
    <!-- Login Container -->
    <div class="login-container">
        <!-- Branding -->
        <div class="login-brand animate-float">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M18 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2Z"/>
                    <path d="m9 16 .348-.24c1.465-1.013 3.84-1.013 5.304 0L15 16"/>
                    <circle cx="8" cy="7" r="0.5" fill="currentColor"/>
                    <circle cx="12" cy="7" r="0.5" fill="currentColor"/>
                    <circle cx="16" cy="7" r="0.5" fill="currentColor"/>
                    <circle cx="8" cy="11" r="0.5" fill="currentColor"/>
                    <circle cx="12" cy="11" r="0.5" fill="currentColor"/>
                    <circle cx="16" cy="11" r="0.5" fill="currentColor"/>
                </svg>
            </div>
            <h1 class="brand-name">HotelOS</h1>
            <p class="brand-tagline">Next-Gen Property Management</p>
        </div>
        
        <!-- Login Form Card -->
        <div class="login-form-card animate-slideUp">
            <h2 class="form-title">Welcome Back</h2>
            <p class="form-subtitle">Enter your credentials to continue</p>
            
            <?php if ($error): ?>
            <div class="error-toast">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="/login" class="login-form">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="input-group">
                    <label for="email">Email</label>
                    <div class="input-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" id="email" name="email" placeholder="admin@hotel.com" autocomplete="email" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="input-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-wrap">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        <span>Remember me</span>
                    </label>
                    <a href="/forgot-password" class="link">Forgot password?</a>
                </div>
                
                <button type="submit" class="submit-btn">
                    <span>Sign In</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </button>
            </form>
            
            <div class="form-footer">
                <span>Need access?</span>
                <a href="/register" class="link">Contact Administrator</a>
            </div>
        </div>
        
        <!-- Version -->
        <p class="version">v2.0 · © 2024 HotelOS</p>
    </div>
</div>

<style>
/* Reset & Base */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.login-page {
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0a0a1a;
    font-family: 'Inter', -apple-system, sans-serif;
    overflow: hidden;
    position: relative;
}

/* Animated Background Orbs */
.login-bg {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.5;
    animation: float 20s ease-in-out infinite;
}

.orb-1 {
    width: 400px; height: 400px;
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    top: -10%; left: -10%;
    animation-delay: 0s;
}

.orb-2 {
    width: 300px; height: 300px;
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    bottom: -5%; right: -5%;
    animation-delay: -7s;
}

.orb-3 {
    width: 200px; height: 200px;
    background: linear-gradient(135deg, #10b981, #059669);
    top: 40%; right: 20%;
    animation-delay: -14s;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -30px) scale(1.05); }
    50% { transform: translate(-20px, 20px) scale(0.95); }
    75% { transform: translate(10px, 10px) scale(1.02); }
}

/* Login Container */
.login-container {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 420px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 32px;
}

/* Branding */
.login-brand {
    text-align: center;
}

.brand-icon {
    width: 72px; height: 72px;
    margin: 0 auto 16px;
    background: linear-gradient(135deg, #06b6d4 0%, #8b5cf6 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 20px 40px -10px rgba(6, 182, 212, 0.4);
}

.brand-icon svg {
    width: 36px; height: 36px;
    color: white;
}

.brand-name {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    letter-spacing: -0.02em;
}

.brand-tagline {
    color: rgba(255,255,255,0.5);
    font-size: 0.9rem;
    margin-top: 4px;
}

/* Form Card */
.login-form-card {
    width: 100%;
    padding: 32px;
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255,255,255,0.06);
}

.form-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
}

.form-subtitle {
    color: rgba(255,255,255,0.5);
    font-size: 0.875rem;
    margin-bottom: 24px;
}

/* Error Toast */
.error-toast {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(239, 68, 68, 0.15);
    border-radius: 12px;
    color: #fca5a5;
    font-size: 0.875rem;
    margin-bottom: 20px;
}

/* Input Groups */
.input-group {
    margin-bottom: 20px;
}

.input-group label {
    display: block;
    color: rgba(255,255,255,0.7);
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.input-field {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(255,255,255,0.05);
    border-radius: 14px;
    transition: all 0.2s;
}

.input-field:focus-within {
    background: rgba(255,255,255,0.08);
    box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.3);
}

.input-field svg {
    color: rgba(255,255,255,0.4);
    flex-shrink: 0;
}

.input-field input {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    color: white;
    font-size: 1rem;
}

.input-field input::placeholder {
    color: rgba(255,255,255,0.3);
}

/* Form Options */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.checkbox-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: rgba(255,255,255,0.6);
    font-size: 0.875rem;
}

.checkbox-wrap input {
    width: 18px; height: 18px;
    accent-color: #06b6d4;
}

.link {
    color: #22d3ee;
    font-size: 0.875rem;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.link:hover {
    color: #67e8f9;
}

/* Submit Button */
.submit-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    border: none;
    border-radius: 14px;
    color: white;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
    box-shadow: 0 10px 30px -10px rgba(6, 182, 212, 0.5);
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px -10px rgba(6, 182, 212, 0.6);
}

.submit-btn:active {
    transform: translateY(0);
}

/* Footer */
.form-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid rgba(255,255,255,0.06);
    color: rgba(255,255,255,0.5);
    font-size: 0.875rem;
}

.form-footer .link {
    margin-left: 4px;
}

.version {
    color: rgba(255,255,255,0.3);
    font-size: 0.75rem;
}

/* Animations */
.animate-float {
    animation: gentleFloat 6s ease-in-out infinite;
}

@keyframes gentleFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.animate-slideUp {
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive - Tablet */
@media (min-width: 768px) {
    .login-container {
        max-width: 440px;
    }
    
    .brand-icon {
        width: 80px; height: 80px;
        border-radius: 24px;
    }
    
    .brand-icon svg {
        width: 40px; height: 40px;
    }
    
    .brand-name {
        font-size: 2.25rem;
    }
    
    .login-form-card {
        padding: 40px;
    }
}

/* Responsive - Mobile */
@media (max-width: 480px) {
    .login-container {
        padding: 16px;
        gap: 24px;
    }
    
    .brand-icon {
        width: 60px; height: 60px;
        border-radius: 16px;
    }
    
    .brand-icon svg {
        width: 30px; height: 30px;
    }
    
    .brand-name {
        font-size: 1.75rem;
    }
    
    .login-form-card {
        padding: 24px;
        border-radius: 20px;
    }
    
    .form-title {
        font-size: 1.25rem;
    }
    
    .input-field {
        padding: 12px 14px;
    }
    
    .submit-btn {
        padding: 14px;
    }
}

/* Very small screens */
@media (max-width: 340px) {
    .form-options {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
