<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Portal - <?= htmlspecialchars($data['hotel']['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800">

    <!-- Header -->
    <div class="bg-gray-900 text-white p-6 pb-12 rounded-b-3xl shadow-lg relative z-10">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold"><?= htmlspecialchars($data['hotel']['name']) ?></h1>
                <p class="text-gray-400 text-sm mt-1">Room <?= htmlspecialchars($data['booking']['room_number'] ?? 'N/A') ?></p>
            </div>
            <div class="bg-white/10 p-2 rounded-lg backdrop-blur-sm">
                <i data-lucide="moon" class="w-6 h-6 text-yellow-300"></i>
            </div>
        </div>
        
        <div class="mt-6 flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-emerald-500 flex items-center justify-center text-xl font-bold">
                <?= substr($data['booking']['first_name'], 0, 1) ?>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wider">Welcome Guest</p>
                <p class="font-bold text-lg"><?= htmlspecialchars($data['booking']['first_name']) ?> <?= htmlspecialchars($data['booking']['last_name']) ?></p>
            </div>
        </div>
    </div>

    <div class="px-4 -mt-8 relative z-20 space-y-4 mb-12">
        
        <!-- Quick Actions Card (WiFi) -->
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-blue-50 p-3 rounded-xl text-blue-600">
                    <i data-lucide="wifi" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 font-medium">WiFi Access</p>
                    <p class="font-bold text-lg"><?= htmlspecialchars($data['hotel']['wifi']['ssid']) ?></p>
                </div>
            </div>
            <button onclick="copyWifi()" class="text-blue-600 font-medium text-sm bg-blue-50 px-3 py-1.5 rounded-lg">
                <?= htmlspecialchars($data['hotel']['wifi']['password']) ?>
            </button>
        </div>

        <!-- Bill Summary Card -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <i data-lucide="receipt" class="w-5 h-5 text-gray-400"></i> Current Bill
                </h3>
            </div>
            <div class="p-5 space-y-3">
                <?php foreach($data['bill']['items'] as $label => $amount): ?>
                <?php if($amount != 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500"><?= htmlspecialchars($label) ?></span>
                    <span class="font-medium">₹<?= number_format(abs($amount), 2) ?></span>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="border-t border-dashed border-gray-200 my-3"></div>
                
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-900">Total Balance</span>
                    <span class="font-bold text-xl <?= $data['bill']['balance'] > 0 ? 'text-red-500' : 'text-emerald-600' ?>">
                        ₹<?= number_format($data['bill']['balance'], 2) ?>
                    </span>
                </div>
            </div>
            <?php if($data['bill']['balance'] > 0): ?>
            <div class="bg-red-50 p-4 text-center">
                <button class="bg-gray-900 text-white w-full py-3 rounded-xl font-bold hover:bg-black transition-colors">
                    Pay Now
                </button>
                <p class="text-xs text-red-400 mt-2">Secure payment via 3rd party gateway</p>
            </div>
            <?php else: ?>
                 <div class="bg-emerald-50 p-4 text-center text-emerald-700 font-medium text-sm">
                    <i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i> Bill Settled
                </div>
            <?php endif; ?>
        </div>

        <!-- Hotel Info -->
        <div class="bg-white rounded-2xl shadow-sm p-5 text-center">
            <i data-lucide="map-pin" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
            <h4 class="font-bold text-gray-900"><?= htmlspecialchars($data['hotel']['name']) ?></h4>
            <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($data['hotel']['address']) ?></p>
            <a href="tel:<?= htmlspecialchars($data['hotel']['support']) ?>" class="inline-block mt-4 text-emerald-600 font-bold bg-emerald-50 px-6 py-2 rounded-full border border-emerald-100">
                Call Reception
            </a>
        </div>
        
    </div>

    <script>
        lucide.createIcons();
        function copyWifi() {
            navigator.clipboard.writeText('<?= htmlspecialchars($data['hotel']['wifi']['password']) ?>');
            alert('WiFi Password Copied!');
        }
    </script>
</body>
</html>
