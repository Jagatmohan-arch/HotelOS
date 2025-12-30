<?php
/**
 * Billing Dashboard
 * Shows current subscription, usage, and payment history
 */

$auth = \HotelOS\Core\Auth::getInstance();
$auth->requireAuth();

$subscription = new \HotelOS\Handlers\SubscriptionHandler();
$tenantId = \HotelOS\Core\TenantContext::getId();

$currentPlan = $subscription->getCurrentPlan();
$trialDaysLeft = $subscription->getTrialDaysRemaining();
$isLocked = $subscription->isBillingLocked();

// Get usage stats
$db = \HotelOS\Core\Database::getInstance();
$stats = $db->queryOne(
    "SELECT 
        (SELECT COUNT(*) FROM rooms WHERE tenant_id = :tid) as rooms_used,
        (SELECT COUNT(*) FROM users WHERE tenant_id = :tid) as users_used",
    ['tid' => $tenantId],
    enforceTenant: false
);

// Get payment history
$transactions = $db->query(
    "SELECT * FROM subscription_transactions 
     WHERE tenant_id = :tid AND status = 'success'
     ORDER BY created_at DESC LIMIT 10",
    ['tid' => $tenantId],
    enforceTenant: false
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Subscription - HotelOS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 32px;
            color: #0f172a;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 14px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .card-value {
            font-size: 28px;
            font-weight: bold;
            color: #0f172a;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-trial { background: #fef3c7; color: #92400e; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-locked { background: #fee2e2; color: #991b1b; }
        
        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: #06b6d4;
            transition: width 0.3s;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #0f172a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 12px;
            background: #f8fafc;
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .alert {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Billing & Subscription</h1>
        </div>
        
        <?php if ($isLocked): ?>
        <div class="alert">
            <strong>⚠️ Subscription Expired</strong><br>
            Your subscription has expired. Please renew to continue using HotelOS.
            <br><br>
            <a href="/subscription/plans" class="btn btn-primary">Renew Now</a>
        </div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Current Plan -->
            <div class="card">
                <div class="card-title">Current Plan</div>
                <div class="card-value">
                    <?= htmlspecialchars($currentPlan['plan_name'] ?? 'Free Trial') ?>
                </div>
                <div style="margin-top: 10px;">
                    <?php if ($trialDaysLeft > 0): ?>
                        <span class="badge badge-trial">Trial: <?= $trialDaysLeft ?> days left</span>
                    <?php elseif ($currentPlan['billing_status'] === 'active'): ?>
                        <span class="badge badge-active">Active</span>
                    <?php else: ?>
                        <span class="badge badge-locked">Expired</span>
                    <?php endif; ?>
                </div>
                <?php if (!$isLocked && $trialDaysLeft === 0): ?>
                <p style="margin-top: 15px; color: #64748b;">
                    Next billing: <?= $currentPlan['next_billing_date'] ?? 'N/A' ?>
                </p>
                <?php endif; ?>
            </div>
            
            <!-- Rooms Usage -->
            <div class="card">
                <div class="card-title">Rooms</div>
                <div class="card-value">
                    <?= $stats['rooms_used'] ?> 
                    <?php if ($currentPlan['max_rooms']): ?>
                    <span style="font-size: 18px; color: #64748b;">/ <?= $currentPlan['max_rooms'] ?></span>
                    <?php else: ?>
                    <span style="font-size: 18px; color: #64748b;">/ ∞</span>
                    <?php endif; ?>
                </div>
                <?php if ($currentPlan['max_rooms']): ?>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= min(100, ($stats['rooms_used'] / $currentPlan['max_rooms']) * 100) ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Users Usage -->
            <div class="card">
                <div class="card-title">Users</div>
                <div class="card-value">
                    <?= $stats['users_used'] ?> 
                    <?php if ($currentPlan['max_users']): ?>
                    <span style="font-size: 18px; color: #64748b;">/ <?= $currentPlan['max_users'] ?></span>
                    <?php else: ?>
                    <span style="font-size: 18px; color: #64748b;">/ ∞</span>
                    <?php endif; ?>
                </div>
                <?php if ($currentPlan['max_users']): ?>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= min(100, ($stats['users_used'] / $currentPlan['max_users']) * 100) ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <a href="/subscription/plans" class="btn">View All Plans</a>
            <?php if (!$isLocked && $trialDaysLeft === 0): ?>
            <a href="/subscription/plans" class="btn btn-primary">Upgrade Plan</a>
            <?php endif; ?>
        </div>
        
        <!-- Payment History -->
        <div class="card">
            <h2 style="margin-bottom: 20px; font-size: 20px;">Payment History</h2>
            
            <?php if (empty($transactions)): ?>
                <p style="color: #64748b; text-align: center; padding: 20px;">No payment history yet</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Transaction ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($txn['created_at'])) ?></td>
                        <td style="text-transform: capitalize;"><?= str_replace('_', ' ', $txn['type']) ?></td>
                        <td><strong>₹<?= number_format($txn['amount'], 2) ?></strong></td>
                        <td>
                            <span class="badge badge-active">Paid</span>
                        </td>
                        <td style="font-family: monospace; font-size: 12px; color: #64748b;">
                            <?= substr($txn['gateway_transaction_id'] ?? 'N/A', 0, 20) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
