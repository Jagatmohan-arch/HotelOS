<?php
/**
 * HotelOS - Front Desk Dashboard (Reception)
 * "The Operator" view - Check-ins, Check-outs, Guest Service
 */

// Fetch Operations Data
$todayArrivalsDetail = $handler->getTodayArrivalsDetail();
$todayDeparturesDetail = $handler->getTodayDeparturesDetail();
?>

<!-- Operational Overview Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <!-- Quick Check-in Action -->
    <a href="#" @click.prevent="$dispatch('open-quick-checkin')" class="col-span-2 md:col-span-1 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-4 shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] transition-all flex flex-col justify-center items-center text-center cursor-pointer group">
        <div class="bg-white/20 p-3 rounded-full mb-3 group-hover:rotate-12 transition-transform">
            <i data-lucide="user-plus" class="w-6 h-6 text-white"></i>
        </div>
        <h3 class="text-lg font-bold text-white">New Check-In</h3>
        <p class="text-emerald-100 text-xs mt-1">Walk-in or Reservation</p>
    </a>

    <!-- Arrivals Count -->
    <div class="bg-slate-800 rounded-xl p-4 border border-slate-700 flex flex-col justify-center">
        <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Expected Arrivals</div>
        <div class="flex items-end gap-2">
            <div class="text-3xl font-bold text-white"><?= count($todayArrivalsDetail) ?></div>
            <div class="text-xs text-slate-500 mb-1">today</div>
        </div>
    </div>
    
    <!-- Departures Count -->
    <div class="bg-slate-800 rounded-xl p-4 border border-slate-700 flex flex-col justify-center">
        <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Pending Checkouts</div>
        <div class="flex items-end gap-2">
            <div class="text-3xl font-bold text-rose-400"><?= count($todayDeparturesDetail) ?></div>
            <div class="text-xs text-slate-500 mb-1">today</div>
        </div>
    </div>
    
    <!-- In-House (Quick Link) -->
    <a href="/bookings?tab=inhouse" class="bg-slate-800 rounded-xl p-4 border border-slate-700 hover:bg-slate-750 transition-colors flex flex-col justify-center group text-decoration-none">
        <div class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1 group-hover:text-cyan-400">In-House Guests</div>
        <div class="flex items-center justify-between">
            <div class="text-2xl font-bold text-white"><?= $stats['occupancy'] ?>%</div>
            <i data-lucide="chevron-right" class="w-5 h-5 text-slate-600 group-hover:text-cyan-400"></i>
        </div>
    </a>
</div>

<!-- Main Operational Lists -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Today's ARRIVALS -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg flex flex-col h-[500px]">
        <div class="p-4 border-b border-slate-700 flex justify-between items-center bg-slate-800/50 rounded-t-xl">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <i data-lucide="log-in" class="w-4 h-4 text-emerald-400"></i>
                Arrivals
                <span class="bg-slate-700 text-slate-300 text-xs py-0.5 px-2 rounded-full"><?= count($todayArrivalsDetail) ?></span>
            </h2>
            <a href="/bookings?tab=arrivals" class="text-xs text-slate-400 hover:text-white">View All</a>
        </div>
        
        <div class="flex-1 overflow-y-auto p-2 space-y-2 custom-scrollbar">
            <?php if (empty($todayArrivalsDetail)): ?>
                <div class="h-full flex flex-col items-center justify-center text-slate-500">
                    <i data-lucide="check-circle-2" class="w-10 h-10 mb-2 opacity-20"></i>
                    <p class="text-sm">No pending arrivals</p>
                </div>
            <?php else: ?>
                <?php foreach($todayArrivalsDetail as $booking): ?>
                <div class="p-3 bg-slate-700/30 border border-slate-700 rounded-lg hover:border-emerald-500/50 transition-colors group">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-bold text-white text-sm"><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></div>
                            <div class="text-xs text-slate-400"><?= htmlspecialchars($booking['guest_phone']) ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-emerald-400 font-mono text-xs font-bold"><?= $booking['room_number'] ?? 'Unassigned' ?></div>
                            <div class="text-[10px] text-slate-500 uppercase"><?= htmlspecialchars($booking['room_type']) ?></div>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <a href="/bookings/checkin/<?= $booking['id'] ?>" class="flex-1 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-xs font-bold py-1.5 rounded text-center transition-colors">
                            Check In
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Today's DEPARTURES -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg flex flex-col h-[500px]">
        <div class="p-4 border-b border-slate-700 flex justify-between items-center bg-slate-800/50 rounded-t-xl">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <i data-lucide="log-out" class="w-4 h-4 text-rose-400"></i>
                Departures
                <span class="bg-slate-700 text-slate-300 text-xs py-0.5 px-2 rounded-full"><?= count($todayDeparturesDetail) ?></span>
            </h2>
            <a href="/bookings?tab=departures" class="text-xs text-slate-400 hover:text-white">View All</a>
        </div>
        
        <div class="flex-1 overflow-y-auto p-2 space-y-2 custom-scrollbar">
            <?php if (empty($todayDeparturesDetail)): ?>
                <div class="h-full flex flex-col items-center justify-center text-slate-500">
                    <i data-lucide="check-circle-2" class="w-10 h-10 mb-2 opacity-20"></i>
                    <p class="text-sm">No pending checkouts</p>
                </div>
            <?php else: ?>
                <?php foreach($todayDeparturesDetail as $booking): ?>
                <div class="p-3 bg-slate-700/30 border border-slate-700 rounded-lg hover:border-rose-500/50 transition-colors group">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-bold text-white text-sm"><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></div>
                            <div class="text-xs text-slate-400">Room <?= $booking['room_number'] ?></div>
                        </div>
                        <div class="text-right">
                            <?php if ($booking['balance_amount'] > 0): ?>
                                <div class="text-rose-400 font-bold text-xs">Due: ₹<?= number_format($booking['balance_amount']) ?></div>
                            <?php else: ?>
                                <div class="text-emerald-400 font-bold text-xs">Paid</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <a href="/bookings/checkout/<?= $booking['id'] ?>" class="flex-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-bold py-1.5 rounded text-center transition-colors">
                            Checkout
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
