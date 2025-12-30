<?php
/**
 * HotelOS - Owner Registration Page
 * 14-Day Free Trial Sign

 Up
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
    
    <!-- Registration Container -->
    <div class="login-container" style="max-width: 520px;">
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
            <p class="brand-tagline">Start Your 14-Day Free Trial</p>
        </div>
        
        <!-- Registration Form Card -->
        <div class="login-form-card animate-slideUp" style="animation-delay: 0.1s;">
            <h2 class="form-title">Create Your Account</h2>
            <p class="form-subtitle">No credit card required • Cancel anytime</p>
            
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
            
            <form method="POST" action="/register" class="login-form" autocomplete="off">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <!-- Hotel Information -->
                <div class="form-section-title">Hotel Information</div>
                
                <div class="input-group">
                    <label for="hotel_name">Hotel Name *</label>
                    <div class="input-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2Z"/>
                        </svg>
                        <input 
                            type="text" 
                            id="hotel_name" 
                            name="hotel_name" 
                            placeholder="e.g., Grand Palace Hotel"
                            value="<?= htmlspecialchars($_POST['hotel_name'] ?? '') ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="input-row">
                    <div class="input-group">
                        <label for="city">City</label>
                        <div class="input-field">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <input 
                                type="text" 
                                id="city" 
                                name="city" 
                                placeholder="Mumbai"
                                value="<?= htmlspecialchars($_POST['city'] ?? 'Mumbai') ?>"
                            >
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="state">State</label>
                        <div class="input-field">
                            <input 
                                type="text" 
                                id="state" 
                                name="state" 
                                placeholder="Maharashtra"
                                value="<?= htmlspecialchars($_POST['state'] ?? 'Maharashtra') ?>"
                            >
                        </div>
                    </div>
                </div>
                
                <!-- Owner Information -->
                <div class="form-section-title" style="margin-top: 20px;">Owner Information</div>
                
                <div class="input-row">
                    <div class="input-group">
                        <label for="owner_first_name">First Name *</label>
                        <div class="input-field">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <input 
                                type="text" 
                                id="owner_first_name" 
                                name="owner_first_name" 
                                placeholder="John"
                                value="<?= htmlspecialchars($_POST['owner_first_name'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="owner_last_name">Last Name *</label>
                        <div class="input-field">
                            <input 
                                type="text" 
                                id="owner_last_name" 
                                name="owner_last_name" 
                                placeholder="Doe"
                                value="<?= htmlspecialchars($_POST['owner_last_name'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="email">Email Address *</label>
                    <div class="input-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="owner@hotel.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="phone">Phone Number *</label>
                    <div class="input-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            placeholder="9876543210"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            pattern="[0-9]{10}"
                            required
                        >
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="password">Password *</label>
                    <div class="input-field">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Minimum 8 characters" 
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                    </div>
                    <small class="input-hint">Use at least 8 characters with mix of letters & numbers</small>
                </div>
                
                <div class="form-options" style="margin-top: 16px;">
                    <label class="checkbox-wrap">
                        <input type="checkbox" name="terms" required>
                        <span>I agree to the Terms of Service and Privacy Policy</span>
                    </label>
                </div>
                
                <button type="submit" class="submit-btn" style="margin-top: 20px;">
                    <span>Start Free Trial</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </button>
            </form>
            
            <div class="form-footer">
                <span>Already have an account?</span>
                <a href="/login" class="link">Sign In</a>
            </div>
        </div>
        
        <!-- Trust Indicators -->
        <div class="features-preview animate-slideUp" style="animation-delay: 0.2s;">
            <div class="feature">
                <span class="feature-icon">✓</span>
                <span>14 Days Free</span>
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                <span>No Credit Card</span>
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                <span>Cancel Anytime</span>
            </div>
        </div>
        
        <!-- Version -->
        <p class="version">v2.0 · © 2024 HotelOS</p>
    </div>
</div>

<style>
/* Reuse login page styles */
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

/* Background Orbs */
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
    filter: blur(80px);
}

.orb-1 {
    width: 400px; height: 400px;
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    top: -10%; left: -10%;
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
    top: 40%; right: 10%;
    animation-delay: -14s;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -30px) scale(1.05); }
    50% { transform: translate(-20px, 20px) scale(0.95); }
    75% { transform: translate(10px, 10px) scale(1.02); }
}

/* Container */
.login-container {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 520px;
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
}

.brand-tagline {
    color: rgba(255,255,255,0.4);
    font-size: 0.85rem;
    margin-top: 2px;
}

/* Form Card */
.login-form-card {
    width: 100%;
    padding: 32px;
    background: rgba(255,255,255,0.02);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255,255,255,0.04);
}

.form-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
    margin-bottom: 4px;
}

.form-subtitle {
    color: rgba(255,255,255,0.4);
    font-size: 0.85rem;
    margin-bottom: 24px;
}

.form-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
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
    margin-bottom: 20px;
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

.input-hint {
    display: block;
    color: rgba(255,255,255,0.3);
    font-size: 0.7rem;
    margin-top: 4px;
}

/* Input Row for side-by-side fields */
.input-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* Form Options */
.form-options {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
}

.checkbox-wrap {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    cursor: pointer;
    color: rgba(255,255,255,0.5);
    font-size: 0.8rem;
    line-height: 1.4;
}

.checkbox-wrap input {
    width: 16px; height: 16px;
    margin-top: 2px;
    accent-color: #06b6d4;
    cursor: pointer;
    flex-shrink: 0;
}

.link {
    color: #22d3ee;
    text-decoration: none;
    font-weight: 500;
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

/* Footer */
.form-footer {
    text-align: center;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,0.04);
    color: rgba(255,255,255,0.4);
    font-size: 0.85rem;
}

/* Features */
.features-preview {
    display: flex;
    gap: 12px;
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
    color: #10b981;
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

/* Responsive */
@media (max-width: 640px) {
    .input-row {
        grid-template-columns: 1fr;
    }
    
    .login-form-card {
        padding: 24px;
    }
    
    .orb {
        filter: blur(40px);
    }
}
</style>
