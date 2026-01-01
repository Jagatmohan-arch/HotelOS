<?php
/**
 * HotelOS - Payment Success Page
 */

// Force layout to basic if not loaded
$pageTitle = 'Payment Successful';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful | HotelOS</title>
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
        <div class="mb-6 inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-500/20 text-green-400">
            <i data-lucide="check-circle" class="w-10 h-10"></i>
        </div>
        
        <h1 class="text-3xl font-bold mb-2 text-white">Payment Successful!</h1>
        <p class="text-slate-400 mb-8">
            Thank you for upgrading. Your subscription is now active.
        </p>

        <div class="bg-slate-800/50 rounded-lg p-6 mb-8 text-left">
            <div class="flex justify-between mb-2">
                <span class="text-slate-400">Order ID</span>
                <span class="font-mono text-white"><?= htmlspecialchars($_GET['order_id'] ?? 'N/A') ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Status</span>
                <span class="text-green-400 font-medium">Completed</span>
            </div>
        </div>

        <a href="/dashboard" class="block w-full py-3 px-6 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 text-white font-semibold rounded-lg transition transform hover:scale-[1.02]">
            Go to Dashboard
        </a>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
