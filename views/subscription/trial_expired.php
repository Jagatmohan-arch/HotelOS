<?php
/**
 * HotelOS - Trial Expired Page
 * Shown when tenant's free trial has ended
 */

$tenant = $tenant ?? [];
$csrfToken = $csrfToken ?? '';
?>

<div class="trial-expired-page">
    <!-- Animated Background -->
    <div class="trial-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>
    
    <div class="trial-container">
        <!-- Icon -->
        <div class="trial-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        
        <!-- Content -->
        <h1 class="trial-title">Your Free Trial Has Ended</h1>
        <p class="trial-subtitle">
            Thank you for trying HotelOS! Your 14-day trial for <strong><?= htmlspecialchars($tenant['name'] ?? 'your hotel') ?></strong> has expired.
        </p>
        
        <div class="trial-info">
            <p>To continue managing your hotel operations, please upgrade to a paid plan.</p>
        </div>
        
        <!-- Plans Grid -->
        <div class="plans-grid">
            <div class="plan-card">
                <h3>Starter</h3>
                <div class="plan-price">₹999<span>/month</span></div>
                <ul class="plan-features">
                    <li>✓ Up to 25 rooms</li>
                    <li>✓ Basic reports</li>
                    <li>✓ Email support</li>
                </ul>
                <a href="/subscription/checkout?plan=starter" class="plan-btn">Choose Starter</a>
            </div>
            
            <div class="plan-card plan-featured">
                <div class="plan-badge">Most Popular</div>
                <h3>Professional</h3>
                <div class="plan-price">₹2,499<span>/month</span></div>
                <ul class="plan-features">
                    <li>✓ Up to 100 rooms</li>
                    <li>✓ Advanced reports</li>
                    <li>✓ Priority support</li>
                    <li>✓ OTA integrations</li>
                </ul>
                <a href="/subscription/checkout?plan=professional" class="plan-btn plan-btn-primary">Choose Professional</a>
            </div>
            
            <div class="plan-card">
                <h3>Enterprise</h3>
                <div class="plan-price">₹4,999<span>/month</span></div>
                <ul class="plan-features">
                    <li>✓ Unlimited rooms</li>
                    <li>✓ Custom reports</li>
                    <li>✓ 24/7 phone support</li>
                    <li>✓ White-label</li>
                </ul>
                <a href="/subscription/checkout?plan=enterprise" class="plan-btn">Choose Enterprise</a>
            </div>
        </div>
        
        <div class="trial-footer">
            <p>Need help? <a href="mailto:support@hotelos.com">Contact Support</a></p>
            <a href="/logout" class="logout-link">Logout</a>
        </div>
    </div>
</div>

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.trial-expired-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0a0a1a;
    font-family: 'Inter', -apple-system, sans-serif;
    padding: 20px;
    position: relative;
    overflow-x: hidden;
}

.trial-bg {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.orb {
    position: absolute;
    border-radius: 50%;
    opacity: 0.3;
    filter: blur(80px);
    animation: float 20s ease-in-out infinite;
}

.orb-1 {
    width: 400px; height: 400px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    top: -10%; right: -10%;
}

.orb-2 {
    width: 300px; height: 300px;
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    bottom: -5%; left: -5%;
    animation-delay: -10s;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(20px, -20px) scale(1.05); }
}

.trial-container {
    position: relative;
    z-index: 1;
    max-width: 900px;
    width: 100%;
    text-align: center;
}

.trial-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 20px 40px -10px rgba(245, 158, 11, 0.4);
}

.trial-icon svg {
    width: 40px;
    height: 40px;
    color: white;
}

.trial-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 12px;
    letter-spacing: -0.02em;
}

.trial-subtitle {
    font-size: 1.1rem;
    color: rgba(255,255,255,0.6);
    margin-bottom: 24px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.trial-subtitle strong {
    color: rgba(255,255,255,0.9);
}

.trial-info {
    background: rgba(255,255,255,0.02);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 40px;
}

.trial-info p {
    color: rgba(255,255,255,0.7);
    font-size: 1rem;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.plan-card {
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px;
    padding: 32px 24px;
    position: relative;
    transition: all 0.3s;
}

.plan-card:hover {
    transform: translateY(-4px);
    border-color: rgba(6, 182, 212, 0.3);
    box-shadow: 0 20px 40px -10px rgba(6, 182, 212, 0.2);
}

.plan-featured {
    border-color: rgba(6, 182, 212, 0.4);
    background: rgba(6, 182, 212, 0.05);
}

.plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #06b6d4, #8b5cf6);
    color: white;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.plan-card h3 {
    font-size: 1.5rem;
    color: white;
    margin-bottom: 8px;
}

.plan-price {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 20px;
}

.plan-price span {
    font-size: 1rem;
    color: rgba(255,255,255,0.5);
    font-weight: 400;
}

.plan-features {
    list-style: none;
    margin-bottom: 24px;
    text-align: left;
}

.plan-features li {
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
    margin-bottom: 8px;
    padding-left: 8px;
}

.plan-btn {
    display: block;
    width: 100%;
    padding: 14px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}

.plan-btn:hover {
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.plan-btn-primary {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    border: none;
    box-shadow: 0 8px 20px -6px rgba(6, 182, 212, 0.5);
}

.plan-btn-primary:hover {
    box-shadow: 0 12px 25px -6px rgba(6, 182, 212, 0.6);
}

.trial-footer {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.4);
    font-size: 0.85rem;
}

.trial-footer a {
    color: #22d3ee;
    text-decoration: none;
    margin: 0 8px;
}

.trial-footer a:hover {
    color: #67e8f9;
}

.logout-link {
    display: inline-block;
    margin-top: 12px;
    opacity: 0.6;
}

@media (max-width: 768px) {
    .trial-title {
        font-size: 1.75rem;
    }
    
    .plans-grid {
        grid-template-columns: 1fr;
    }
}
</style>
