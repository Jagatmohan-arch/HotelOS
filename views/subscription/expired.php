<?php
/**
 * HotelOS - Trial/Subscription Expired Page
 */

// Force layout to basic if not loaded
$pageTitle = 'Access Locked';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired | HotelOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #f1f5f9; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="glass max-w-lg w-full rounded-2xl p-8 text-center shadow-2xl">
        <div class="mb-6 inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-500/20 text-red-400">
            <i data-lucide="lock" class="w-8 h-8"></i>
        </div>
        
        <h1 class="text-2xl font-bold mb-2 text-white">Access Locked</h1>
        <p class="text-slate-400 mb-8">
            Your 14-day free trial (or subscription) has expired. <br>
            To continue managing your hotel, increased revenue, and happy guests, please upgrade your plan.
        </p>

        <div class="space-y-4">
            <a href="/subscription/plans" class="block w-full py-3 px-6 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-semibold rounded-lg transition transform hover:scale-[1.02]">
                View Upgrade Plans
            </a>
            
            <form action="/logout" method="POST">
                <button type="submit" class="text-sm text-slate-500 hover:text-slate-300 transition">
                    Logout and return later
                </button>
            </form>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-700/50">
            <p class="text-xs text-slate-500">
                Need help? Contact support at support@hotelos.in <br>
                Reference Tenant ID: <?= \HotelOS\Core\TenantContext::getId() ?? 'Unknown' ?>
            </p>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
