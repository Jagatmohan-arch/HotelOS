<?php
/**
 * Subscription Plans Page
 * Shows available plans and pricing
 */

$auth = \HotelOS\Core\Auth::getInstance();
$auth->requireAuth();

$subscription = new \HotelOS\Handlers\SubscriptionHandler();
$currentPlan = $subscription->getCurrentPlan();
$plans = $subscription->getAvailablePlans();
$trialDaysLeft = $subscription->getTrialDaysRemaining();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Plan - HotelOS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        
        .header h1 {
            font-size: 42px;
            margin-bottom: 15px;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.8;
        }
        
        .trial-banner {
            background: #fbbf24;
            color: #000;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 30px;
            font-weight: 500;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .plan-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            position: relative;
            transition: transform 0.3s;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
        }
        
        .plan-card.popular {
            border: 3px solid #06b6d4;
        }
        
        .popular-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #06b6d4;
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .plan-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .plan-price {
            font-size: 48px;
            font-weight: bold;
            color: #0f172a;
            margin: 20px 0;
        }
        
        .plan-price small {
            font-size: 18px;
            color: #64748b;
        }
        
        .plan-features {
            list-style: none;
            margin: 30px 0;
        }
        
        .plan-features li {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
        }
        
        .plan-features li:before {
            content: "✓ ";
            color: #10b981;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #0f172a;
            color: white;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #1e293b;
        }
        
        .btn-primary {
            background: #06b6d4;
        }
        
        .btn-primary:hover {
            background: #0891b2;
        }
        
        .current-plan {
            background: #f1f5f9;
            color: #475569;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Choose Your Plan</h1>
            <p>Select the perfect plan for your hotel</p>
        </div>
        
        <?php if ($trialDaysLeft > 0): ?>
        <div class="trial-banner">
            ⏰ Your free trial ends in <?= $trialDaysLeft ?> days. Choose a plan to continue!
        </div>
        <?php endif; ?>
        
        <div class="plans-grid">
            <?php foreach ($plans as $index => $plan): ?>
            <div class="plan-card <?= $index === 1 ? 'popular' : '' ?>">
                <?php if ($index === 1): ?>
                <div class="popular-badge">MOST POPULAR</div>
                <?php endif; ?>
                
                <div class="plan-name"><?= htmlspecialchars($plan['display_name']) ?></div>
                
                <div class="plan-price">
                    ₹<?= number_format($plan['price_monthly'], 0) ?>
                    <small>/month</small>
                </div>
                
                <ul class="plan-features">
                    <li><?= $plan['max_rooms'] ? "Up to {$plan['max_rooms']} rooms" : 'Unlimited rooms' ?></li>
                    <li><?= $plan['max_users'] ? "Up to {$plan['max_users']} users" : 'Unlimited users' ?></li>
                    <li>Basic reports & analytics</li>
                    <li>Invoice & police report PDFs</li>
                    <?php
                    $features = json_decode($plan['features'], true);
                    if ($features['email_notifications']) echo '<li>Email notifications</li>';
                    if ($features['sms_notifications']) echo '<li>SMS notifications</li>';
                    if ($features['advanced_reports']) echo '<li>Advanced analytics</li>';
                    if ($features['api_access']) echo '<li>API access</li>';
                    if ($features['whatsapp']) echo '<li>WhatsApp integration</li>';
                    if ($features['priority_support']) echo '<li>Priority support</li>';
                    ?>
                </ul>
                
                <?php if ($currentPlan && $currentPlan['plan_slug'] === $plan['slug']): ?>
                <a href="#" class="btn current-plan">Current Plan</a>
                <?php else: ?>
                <a href="/subscription/checkout?plan=<?= $plan['slug'] ?>" class="btn <?= $index === 1 ? 'btn-primary' : '' ?>">
                    Select <?= $plan['name'] ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
