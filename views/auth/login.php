<?php
/**
 * HotelOS - Login View
 * Hotel Operator System - Multi-role login with Staff/Owner tabs
 */

$error = $error ?? null;
$csrfToken = $csrfToken ?? '';
$loginType = $_GET['type'] ?? 'owner';
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
            <h1 class="brand-name">Hotel<span class="highlight">OS</span></h1>
            <p class="brand-tagline">Hotel Operator System</p>
        </div>
        
        <!-- Login Type Tabs -->
        <div class="login-tabs animate-slideUp">
            <a href="?type=owner" class="tab <?= $loginType === 'owner' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Owner</span>
            </a>
            <a href="?type=staff" class="tab <?= $loginType === 'staff' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Staff</span>
            </a>
        </div>
        
        <!-- Login Form Card -->
        <div class="login-form-card animate-slideUp" style="animation-delay: 0.1s;">
            <h2 class="form-title">
                <?= $loginType === 'staff' ? 'Staff Login' : 'Owner Login' ?>
            </h2>
            <p class="form-subtitle">
                <?= $loginType === 'staff' ? 'Access your assigned tasks' : 'Manage your property' ?>
            </p>
            
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
            
            <form method="POST" action="/login" class="login-form" autocomplete="off">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="login_type" value="<?= htmlspecialchars($loginType) ?>">
                
                <div class="input-group">
                    <label for="operator_id">Operator ID</label>
                    <div class="input-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="12" cy="10" r="3"/>
                            <path d="M7 21v-2a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/>
                        </svg>
                        <input 
                            type="text" 
                            id="operator_id" 
                            name="email" 
                            placeholder="<?= $loginType === 'staff' ? 'staff@hotel.com' : 'owner@hotel.com' ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="input-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••" 
                            autocomplete="current-password" 
                            required
                        >
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
                <span>New to HotelOS?</span>
                <a href="/register" class="link">Start 14-Day Free Trial</a>
            </div>
        </div>
        
        <!-- Features Preview -->
        <div class="features-preview animate-slideUp" style="animation-delay: 0.2s;">
            <div class="feature">
                <span class="feature-icon">📸</span>
                <span>OCR Check-in</span>
            </div>
            <div class="feature">
                <span class="feature-icon">🧾</span>
                <span>GST Billing</span>
            </div>
            <div class="feature">
                <span class="feature-icon">📊</span>
                <span>Reports</span>
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
    overflow-x: hidden;
    position: relative;
    padding: 20px;
}

/* Animated Background Orbs */
.login-bg {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
}

.orb {
    position: absolute;
    border-radius: 50%;
    opacity: 0.4;
    animation: float 20s ease-in-out infinite;
    /* Mobile Optimization: Reduce blur to save GPU */
    will-change: transform;
}

@media (max-width: 640px) {
    .orb {
        filter: blur(40px); /* Reduced from 80px */
        animation-duration: 30s; /* Slower animation for less busy-ness */
    }
}

.orb-1 {
    width: min(400px, 80vw); height: min(400px, 80vw);
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    top: -10%; left: -10%;
}

.orb-2 {
    width: min(300px, 60vw); height: min(300px, 60vw);
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    bottom: -5%; right: -5%;
    animation-delay: -7s;
}

.orb-3 {
    width: min(200px, 40vw); height: min(200px, 40vw);
    background: linear-gradient(135deg, #10b981, #059669);
    top: 40%; right: 10%;
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
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

/* Branding */
.login-brand {
    text-align: center;
}

.brand-icon {
    width: 64px; height: 64px;
    margin: 0 auto 12px;
    background: linear-gradient(135deg, #06b6d4 0%, #8b5cf6 100%);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 15px 30px -8px rgba(6, 182, 212, 0.4);
}

.brand-icon svg {
    width: 32px; height: 32px;
    color: white;
}

.brand-name {
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
    letter-spacing: -0.02em;
}

.brand-name .highlight {
    background: linear-gradient(135deg, #06b6d4, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.brand-tagline {
    color: rgba(255,255,255,0.4);
    font-size: 0.8rem;
    margin-top: 2px;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

/* Login Tabs */
.login-tabs {
    display: flex;
    gap: 8px;
    padding: 6px;
    background: rgba(255,255,255,0.03);
    border-radius: 16px;
    width: 100%;
    max-width: 280px;
}

.tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 12px;
    color: rgba(255,255,255,0.5);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}

.tab:hover {
    color: rgba(255,255,255,0.8);
}

.tab.active {
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(139, 92, 246, 0.2));
    color: #22d3ee;
    box-shadow: 0 4px 15px -5px rgba(6, 182, 212, 0.3);
}

/* Form Card */
.login-form-card {
    width: 100%;
    padding: 28px;
    background: rgba(255,255,255,0.02);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255,255,255,0.04);
}

.form-title {
    font-size: 1.35rem;
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
}

.form-subtitle {
    color: rgba(255,255,255,0.4);
    font-size: 0.85rem;
    margin-bottom: 20px;
}

/* Error Toast */
.error-toast {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(239, 68, 68, 0.12);
    border-radius: 12px;
    color: #fca5a5;
    font-size: 0.85rem;
    margin-bottom: 16px;
}

/* Input Groups */
.input-group {
    margin-bottom: 16px;
}

.input-group label {
    display: block;
    color: rgba(255,255,255,0.6);
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.input-field {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: rgba(255,255,255,0.04);
    border-radius: 14px;
    transition: all 0.2s;
}

.input-field:focus-within {
    background: rgba(255,255,255,0.06);
    box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.25);
}

.input-field svg {
    color: rgba(255,255,255,0.3);
    flex-shrink: 0;
}

.input-field input {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    color: white;
    font-size: 0.95rem;
    width: 100%;
}

.input-field input::placeholder {
    color: rgba(255,255,255,0.25);
}

/* Autofill fix */
.input-field input:-webkit-autofill,
.input-field input:-webkit-autofill:hover,
.input-field input:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 1000px rgba(20, 20, 40, 1) inset !important;
    -webkit-text-fill-color: white !important;
    caret-color: white;
}

/* Form Options */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.checkbox-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: rgba(255,255,255,0.5);
    font-size: 0.85rem;
}

.checkbox-wrap input {
    width: 16px; height: 16px;
    accent-color: #06b6d4;
    cursor: pointer;
}

.link {
    color: #22d3ee;
    font-size: 0.85rem;
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
    padding: 14px;
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    border: none;
    border-radius: 14px;
    color: white;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
    box-shadow: 0 8px 25px -8px rgba(6, 182, 212, 0.5);
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px -8px rgba(6, 182, 212, 0.6);
}

.submit-btn:active {
    transform: translateY(0);
}

/* Footer */
.form-footer {
    text-align: center;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,0.04);
    color: rgba(255,255,255,0.4);
    font-size: 0.85rem;
}

.form-footer .link {
    margin-left: 4px;
}

/* Features Preview */
.features-preview {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: center;
}

.feature {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: rgba(255,255,255,0.03);
    border-radius: 20px;
    color: rgba(255,255,255,0.5);
    font-size: 0.75rem;
}

.feature-icon {
    font-size: 0.9rem;
}

.version {
    color: rgba(255,255,255,0.2);
    font-size: 0.7rem;
    margin-top: 8px;
}

/* Animations */
.animate-float {
    animation: gentleFloat 6s ease-in-out infinite;
}

@keyframes gentleFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

.animate-slideUp {
    animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive - Tablet & Desktop */
@media (min-width: 768px) {
    .login-container {
        max-width: 440px;
    }
    
    .brand-icon {
        width: 72px; height: 72px;
    }
    
    .brand-name {
        font-size: 2rem;
    }
    
    .login-form-card {
        padding: 36px;
    }
}

/* Responsive - Small Mobile */
@media (max-width: 380px) {
    .login-page {
        padding: 16px;
    }
    
    .login-container {
        gap: 16px;
    }
    
    .brand-icon {
        width: 56px; height: 56px;
    }
    
    .brand-name {
        font-size: 1.5rem;
    }
    
    .login-form-card {
        padding: 20px;
        border-radius: 20px;
    }
    
    .login-tabs {
        max-width: 100%;
    }
    
    .form-options {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .features-preview {
        gap: 8px;
    }
    
    .feature {
        padding: 6px 10px;
        font-size: 0.7rem;
    }
}
</style>
