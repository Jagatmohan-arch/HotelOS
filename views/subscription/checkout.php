<?php
/**
 * Cashfree Checkout Page
 * Handles payment for subscription plans
 */

$auth = \HotelOS\Core\Auth::getInstance();
$auth->requireAuth();

$subscription = new \HotelOS\Handlers\SubscriptionHandler();
$cashfree = new \HotelOS\Handlers\CashfreeHandler();

$planSlug = $_GET['plan'] ?? 'starter';
$tenantId = \HotelOS\Core\TenantContext::getId();

// Get plan details
$plans = $subscription->getAvailablePlans();
$selectedPlan = null;

foreach ($plans as $plan) {
    if ($plan['slug'] === $planSlug) {
        $selectedPlan = $plan;
        break;
    }
}

if (!$selectedPlan) {
    die('Invalid plan selected');
}

// Create payment order
$amount = (float)$selectedPlan['price_monthly'];
$orderResult = $cashfree->createOrder($tenantId, $planSlug, $amount);

if (!$orderResult['success']) {
    die('Failed to create payment order: ' . ($orderResult['error'] ?? 'Unknown error'));
}

$paymentSessionId = $orderResult['payment_session_id'];
$orderId = $orderResult['order_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - HotelOS</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .checkout-container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            color: #0f172a;
            margin-bottom: 10px;
        }
        
        .order-summary {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .order-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .order-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            padding-top: 15px;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: #06b6d4;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0891b2;
        }
        
        .btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }
        
        .secure-badge {
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            font-size: 14px;
        }
        
        .secure-badge::before {
            content: "🔒 ";
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="header">
            <h1>Complete Your Purchase</h1>
            <p style="color: #64748b;">Secure payment powered by Cashfree</p>
        </div>
        
        <div class="order-summary">
            <div class="order-row">
                <span>Plan</span>
                <strong><?= htmlspecialchars($selectedPlan['display_name']) ?></strong>
            </div>
            <div class="order-row">
                <span>Billing Cycle</span>
                <span>Monthly</span>
            </div>
            <div class="order-row">
                <span>Amount</span>
                <span>₹<?= number_format($amount, 2) ?></span>
            </div>
            <div class="order-row">
                <span>Total</span>
                <span>₹<?= number_format($amount, 2) ?></span>
            </div>
        </div>
        
        <button id="payButton" class="btn">Pay ₹<?= number_format($amount, 0) ?></button>
        
        <div id="loading" class="loading" style="display: none;">
            Processing payment...
        </div>
        
        <div class="secure-badge">
            Secure payment via Cashfree
        </div>
    </div>
    
    <script>
        const cashfree = Cashfree({
            mode: "<?= ($_ENV['APP_ENV'] ?? 'development') === 'production' ? 'production' : 'sandbox' ?>"
        });
        
        const payButton = document.getElementById('payButton');
        const loading = document.getElementById('loading');
        
        payButton.addEventListener('click', async () => {
            payButton.disabled = true;
            loading.style.display = 'block';
            
            try {
                const checkoutOptions = {
                    paymentSessionId: "<?= $paymentSessionId ?>",
                    returnUrl: "<?= $_ENV['APP_URL'] ?? 'https://hotelos.needkit.in' ?>/subscription/payment-success?order_id=<?= $orderId ?>"
                };
                
                cashfree.checkout(checkoutOptions).then((result) => {
                    if (result.error) {
                        alert('Payment failed: ' + result.error.message);
                        payButton.disabled = false;
                        loading.style.display = 'none';
                    }
                    if (result.paymentDetails) {
                        // Redirect handled by returnUrl
                        console.log('Payment successful');
                    }
                });
            } catch (error) {
                alert('Payment error: ' + error.message);
                payButton.disabled = false;
                loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>
