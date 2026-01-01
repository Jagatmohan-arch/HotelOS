<?php
/**
 * HotelOS - Subscription/Pricing Page
 * 
 * Beautiful pricing page with 3 tiers and feature comparison
 */

$subscription = $subscription ?? [];
$plans = $plans ?? [];
$csrfToken = $csrfToken ?? '';
?>

<div class="subscription-page">
    <!-- Header -->
    <div class="text-center mb-10">
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">
            Choose Your <span class="text-gradient">Perfect Plan</span>
        </h1>
        <p class="text-slate-400 text-lg max-w-2xl mx-auto">
            Start with a 14-day free trial. No credit card required. Upgrade anytime.
        </p>
    </div>
    
    <?php if ($subscription['is_trial'] && !$subscription['is_expired']): ?>
    <!-- Trial Banner -->
    <div class="trial-banner glass-card p-4 mb-8 flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-cyan-500/20 flex items-center justify-center">
                <i data-lucide="clock" class="w-5 h-5 text-cyan-400"></i>
            </div>
            <div>
                <p class="text-white font-medium">Free Trial Active</p>
                <p class="text-slate-400 text-sm">
                    <?= $subscription['days_remaining'] ?> days remaining
                </p>
            </div>
        </div>
        <div class="trial-countdown">
            <span class="countdown-number"><?= $subscription['days_remaining'] ?></span>
            <span class="countdown-label">Days Left</span>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($subscription['is_expired']): ?>
    <!-- Expired Banner -->
    <div class="expired-banner glass-card p-4 mb-8 border-red-500/30 bg-red-500/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-400"></i>
            </div>
            <div>
                <p class="text-red-300 font-medium">
                    <?= $subscription['is_trial'] ? 'Trial Expired' : 'Subscription Expired' ?>
                </p>
                <p class="text-red-400/70 text-sm">
                    Please upgrade to continue using HotelOS
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Pricing Cards -->
    <div class="pricing-grid">
        <?php foreach ($plans as $slug => $plan): ?>
        <div class="pricing-card <?= !empty($plan['popular']) ? 'pricing-card--popular' : '' ?>">
            <?php if (!empty($plan['popular'])): ?>
            <div class="popular-badge">
                <i data-lucide="star" class="w-3 h-3"></i>
                Most Popular
            </div>
            <?php endif; ?>
            
            <div class="pricing-header">
                <h3 class="plan-name"><?= htmlspecialchars($plan['name']) ?></h3>
                <div class="plan-price">
                    <span class="currency">₹</span>
                    <span class="amount"><?= number_format($plan['price']) ?></span>
                    <span class="period">/<?= $plan['duration'] ?></span>
                </div>
                <p class="plan-rooms">
                    <i data-lucide="bed-double" class="w-4 h-4"></i>
                    Up to <?= $plan['rooms'] == 999 ? 'Unlimited' : $plan['rooms'] ?> Rooms
                </p>
            </div>
            
            <ul class="feature-list">
                <?php foreach ($plan['feature_list'] as $feature): ?>
                <li class="<?= $feature['enabled'] ? 'enabled' : 'disabled' ?>">
                    <?php if ($feature['enabled']): ?>
                        <i data-lucide="check" class="w-4 h-4 text-green-400"></i>
                    <?php else: ?>
                        <i data-lucide="x" class="w-4 h-4 text-slate-600"></i>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($feature['label']) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="pricing-footer">
                <?php if ($slug === $subscription['plan']): ?>
                    <button class="btn-plan btn-current" disabled>
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        Current Plan
                    </button>
                <?php elseif ($slug === 'enterprise'): ?>
                    <a href="mailto:sales@hotelos.in?subject=Enterprise%20Plan%20Inquiry" class="btn-plan btn-contact">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        Contact Sales
                    </a>
                <?php else: ?>
                    <button 
                        class="btn-plan btn-upgrade"
                        onclick="selectPlan('<?= $slug ?>')"
                    >
                        <i data-lucide="zap" class="w-4 h-4"></i>
                        <?= $subscription['is_trial'] ? 'Start Now' : 'Upgrade' ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Feature Comparison Table -->
    <div class="comparison-section glass-card p-6 mt-12">
        <h2 class="text-xl font-semibold text-white mb-6 text-center">
            <i data-lucide="git-compare" class="w-5 h-5 inline-block mr-2"></i>
            Feature Comparison
        </h2>
        
        <div class="overflow-x-auto">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Starter</th>
                        <th class="highlight">Professional</th>
                        <th>Enterprise</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Room Limit</td>
                        <td>15 Rooms</td>
                        <td class="highlight">50 Rooms</td>
                        <td>Unlimited</td>
                    </tr>
                    <tr>
                        <td>Property Management</td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                        <td class="highlight"><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                    </tr>
                    <tr>
                        <td>GST Billing</td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                        <td class="highlight"><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                    </tr>
                    <tr>
                        <td>Advanced Reports</td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                        <td class="highlight"><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                    </tr>
                    <tr>
                        <td>POS (Minibar, Laundry)</td>
                        <td><i data-lucide="x" class="w-4 h-4 text-slate-600"></i></td>
                        <td class="highlight"><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                    </tr>
                    <tr>
                        <td>Multi-User Access</td>
                        <td><i data-lucide="x" class="w-4 h-4 text-slate-600"></i></td>
                        <td class="highlight"><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                    </tr>
                    <tr>
                        <td>API Access</td>
                        <td><i data-lucide="x" class="w-4 h-4 text-slate-600"></i></td>
                        <td class="highlight"><i data-lucide="x" class="w-4 h-4 text-slate-600"></i></td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                    </tr>
                    <tr>
                        <td>Priority Support</td>
                        <td><i data-lucide="x" class="w-4 h-4 text-slate-600"></i></td>
                        <td class="highlight"><i data-lucide="x" class="w-4 h-4 text-slate-600"></i></td>
                        <td><i data-lucide="check" class="w-4 h-4 text-green-400"></i></td>
                    </tr>
                    <tr class="price-row">
                        <td><strong>Price</strong></td>
                        <td><strong>₹999/mo</strong></td>
                        <td class="highlight"><strong>₹2,499/mo</strong></td>
                        <td><strong>₹4,999/mo</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="faq-section mt-12">
        <h2 class="text-xl font-semibold text-white mb-6 text-center">
            <i data-lucide="help-circle" class="w-5 h-5 inline-block mr-2"></i>
            Frequently Asked Questions
        </h2>
        
        <div class="faq-grid">
            <div class="faq-item glass-card p-5">
                <h4 class="text-white font-medium mb-2">Can I change plans later?</h4>
                <p class="text-slate-400 text-sm">Yes! You can upgrade or downgrade anytime. Changes take effect immediately.</p>
            </div>
            <div class="faq-item glass-card p-5">
                <h4 class="text-white font-medium mb-2">What payment methods do you accept?</h4>
                <p class="text-slate-400 text-sm">We accept UPI, Credit/Debit Cards, Net Banking via Cashfree.</p>
            </div>
            <div class="faq-item glass-card p-5">
                <h4 class="text-white font-medium mb-2">Is there a setup fee?</h4>
                <p class="text-slate-400 text-sm">No setup fees! Start your 14-day free trial and upgrade when ready.</p>
            </div>
            <div class="faq-item glass-card p-5">
                <h4 class="text-white font-medium mb-2">Can I get a refund?</h4>
                <p class="text-slate-400 text-sm">Yes, full refund within 7 days if you're not satisfied.</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Pricing Page Styles */
.subscription-page {
    max-width: 1200px;
    margin: 0 auto;
}

.text-gradient {
    background: linear-gradient(135deg, #22d3ee, #a78bfa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Trial Banner */
.trial-banner {
    background: rgba(34, 211, 238, 0.1);
    border: 1px solid rgba(34, 211, 238, 0.2);
}

.trial-countdown {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: rgba(34, 211, 238, 0.15);
    border-radius: 0.75rem;
}

.countdown-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #22d3ee;
    line-height: 1;
}

.countdown-label {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Pricing Grid */
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    max-width: 1000px;
    margin: 0 auto;
}

/* Pricing Card */
.pricing-card {
    position: relative;
    background: rgba(30, 41, 59, 0.6);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 1.25rem;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.pricing-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.pricing-card--popular {
    border-color: rgba(34, 211, 238, 0.4);
    box-shadow: 0 0 30px rgba(34, 211, 238, 0.15);
}

.popular-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 1rem;
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    color: #0f172a;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 9999px;
    white-space: nowrap;
}

/* Pricing Header */
.pricing-header {
    text-align: center;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 1.5rem;
}

.plan-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin-bottom: 1rem;
}

.plan-price {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.25rem;
}

.plan-price .currency {
    font-size: 1.25rem;
    color: #94a3b8;
}

.plan-price .amount {
    font-size: 3rem;
    font-weight: 700;
    color: white;
    line-height: 1;
}

.plan-price .period {
    font-size: 0.875rem;
    color: #64748b;
}

.plan-rooms {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    color: #94a3b8;
    font-size: 0.875rem;
}

/* Feature List */
.feature-list {
    flex: 1;
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem 0;
}

.feature-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0;
    font-size: 0.875rem;
}

.feature-list li.enabled {
    color: #e2e8f0;
}

.feature-list li.disabled {
    color: #475569;
}

/* Pricing Footer */
.pricing-footer {
    margin-top: auto;
}

.btn-plan {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border-radius: 0.75rem;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    border: none;
}

.btn-upgrade {
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    color: #0f172a;
    box-shadow: 0 4px 15px rgba(34, 211, 238, 0.3);
}

.btn-upgrade:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34, 211, 238, 0.4);
}

.btn-contact {
    background: rgba(167, 139, 250, 0.15);
    color: #a78bfa;
    border: 1px solid rgba(167, 139, 250, 0.3);
}

.btn-contact:hover {
    background: rgba(167, 139, 250, 0.25);
}

.btn-current {
    background: rgba(52, 211, 153, 0.15);
    color: #34d399;
    border: 1px solid rgba(52, 211, 153, 0.3);
    cursor: default;
}

/* Comparison Table */
.comparison-table {
    width: 100%;
    border-collapse: collapse;
}

.comparison-table th,
.comparison-table td {
    padding: 1rem;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.comparison-table th {
    color: #94a3b8;
    font-weight: 500;
    font-size: 0.875rem;
}

.comparison-table th:first-child,
.comparison-table td:first-child {
    text-align: left;
    color: #e2e8f0;
}

.comparison-table td {
    color: #94a3b8;
    font-size: 0.875rem;
}

.comparison-table .highlight {
    background: rgba(34, 211, 238, 0.05);
}

.comparison-table th.highlight {
    color: #22d3ee;
}

.comparison-table .price-row td {
    padding-top: 1.5rem;
    color: white;
}

/* FAQ Grid */
.faq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .pricing-grid {
        grid-template-columns: 1fr;
        max-width: 360px;
    }
    
    .plan-price .amount {
        font-size: 2.5rem;
    }
    
    .comparison-section {
        padding: 1rem;
    }
    
    .comparison-table th,
    .comparison-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>

<script>
function selectPlan(plan) {
    if (plan === 'enterprise') {
        window.location.href = 'mailto:sales@hotelos.in?subject=Enterprise%20Plan%20Inquiry';
        return;
    }
    // Redirect to checkout
    window.location.href = '/subscription/checkout?plan=' + encodeURIComponent(plan);
}

// Initialize icons
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
