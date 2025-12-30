<?php
/**
 * Trial Banner Component
 * Drop-in component to show trial countdown in header
 * 
 * Usage: Include this in your main layout header
 */

$auth = \HotelOS\Core\Auth::getInstance();
if (!$auth->check()) {
    return; // Don't show if not logged in
}

$middleware = \HotelOS\Core\SubscriptionMiddleware::getSubscriptionBanner();

if (!$middleware) {
    return; // No banner to show
}
?>

<div class="subscription-banner subscription-banner-<?= $middleware['urgency'] ?>" 
     data-banner-type="<?= $middleware['type'] ?>">
    <div class="banner-content">
        <div class="banner-icon">
            <?php if ($middleware['type'] === 'trial'): ?>
                ⏰
            <?php else: ?>
                ⚠️
            <?php endif; ?>
        </div>
        <div class="banner-message">
            <strong><?= htmlspecialchars($middleware['message']) ?></strong>
        </div>
        <div class="banner-action">
            <a href="<?= $middleware['action_url'] ?>" class="banner-btn">
                <?= htmlspecialchars($middleware['action_text']) ?>
            </a>
        </div>
    </div>
</div>

<style>
.subscription-banner {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.subscription-banner-normal {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #000;
}

.subscription-banner-high {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: white;
}

.subscription-banner-critical {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.9; }
}

.banner-content {
    display: flex;
    align-items: center;
    gap: 15px;
    max-width: 1200px;
    width: 100%;
}

.banner-icon {
    font-size: 24px;
}

.banner-message {
    flex: 1;
    font-size: 14px;
}

.banner-btn {
    display: inline-block;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.2);
    color: inherit;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: background 0.3s;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.banner-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.subscription-banner-critical .banner-btn {
    background: white;
    color: #dc2626;
    border: none;
}

.subscription-banner-critical .banner-btn:hover {
    background: #f8f8f8;
}

@media (max-width: 768px) {
    .banner-content {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .banner-message {
        font-size: 12px;
    }
}
</style>
