<?php
/**
 * HotelOS - Manager Dashboard
 * "The Controller" view - Ops Oversight + Approval Authority
 */

// Fetch Critical Data
// Shares some stats with Owner, some operational lists with Reception
$todayArrivalsDetail = $handler->getTodayArrivalsDetail();
?>

<!-- Manager KPI Summary -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
        <p class="text-slate-400 text-xs font-medium uppercase">Occupancy</p>
        <h3 class="text-2xl font-bold text-white mt-1"><?= $stats['occupancy'] ?>%</h3>
    </div>
    <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
        <p class="text-slate-400 text-xs font-medium uppercase">Revenue (Today)</p>
        <h3 class="text-2xl font-bold text-emerald-400 mt-1">₹<?= number_format($stats['todayRevenue'] ?? 0) ?></h3>
    </div>
    <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
        <p class="text-slate-400 text-xs font-medium uppercase">Arrivals</p>
        <h3 class="text-2xl font-bold text-white mt-1"><?= $stats['todayArrivals'] ?></h3>
    </div>
    <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
        <p class="text-slate-400 text-xs font-medium uppercase">Rooms Available</p>
        <h3 class="text-2xl font-bold text-blue-400 mt-1">
            <?php 
                $summary = $handler->getRoomStatusSummary();
                echo $summary['available'] ?? 0;
            ?>
        </h3>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Left Column: Approvals & Shifts (Manager Specific) -->
    <div class="space-y-6">
        <!-- Shift Oversight -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg p-5">
            <h3 class="font-bold text-white mb-4 flex items-center">
                <i data-lucide="clock" class="w-4 h-4 mr-2 text-cyan-400"></i> Active Shifts
            </h3>
            
            <div class="space-y-3">
                <!-- Mockup for Active Shifts - In Phase F replacement logic this will be dynamic -->
                <div class="p-3 bg-slate-700/40 rounded-lg flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-600 flex items-center justify-center text-xs font-bold text-white">R</div>
                        <div>
                            <p class="text-xs font-bold text-white">Rahul (Reception)</p>
                            <p class="text-[10px] text-emerald-400">Online • Started 09:00 AM</p>
                        </div>
                    </div>
                    <a href="/shifts" class="text-xs text-slate-400 hover:text-white px-2 py-1 rounded bg-slate-700">View</a>
                </div>
            </div>
            
            <a href="/shifts" class="block mt-4 text-center text-xs text-cyan-400 hover:text-cyan-300 font-medium">Manage All Shifts</a>
        </div>
        
        <!-- Pending Refund Approvals -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg p-5">
            <h3 class="font-bold text-white mb-4 flex items-center">
                <i data-lucide="receipt-refund" class="w-4 h-4 mr-2 text-amber-500"></i> Refund Approvals
            </h3>
            <!-- Placeholder for pending refunds -->
            <div class="text-center py-6 text-slate-500 text-sm bg-slate-700/20 rounded-lg border border-dashed border-slate-700">
                No pending requests
            </div>
            <a href="/admin/refunds" class="block mt-4 text-center text-xs text-amber-500 hover:text-amber-400 font-medium">View History</a>
        </div>
    </div>
    
    <!-- Right Column: Operations Feed (Similar to Reception but summarized) -->
    <div class="lg:col-span-2 bg-slate-800 rounded-xl border border-slate-700 shadow-lg flex flex-col h-[600px]">
        <div class="p-4 border-b border-slate-700 flex justify-between items-center">
            <h3 class="font-bold text-white">Today's Movement</h3>
            <div class="flex gap-2 text-xs">
                <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Arrivals</span>
                <span class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">Departures</span>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
            <!-- Combined list logic would go here, reusing Reception components -->
            <!-- For Phase 1 Manager View, we list Arrivals first as priority -->
            
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Expected Arrivals</h4>
             <?php if (empty($todayArrivalsDetail)): ?>
                <p class="text-sm text-slate-500 italic mb-6">No pending arrivals</p>
            <?php else: ?>
                <div class="space-y-2 mb-6">
                <?php foreach($todayArrivalsDetail as $booking): ?>
                    <div class="flex justify-between items-center p-3 bg-slate-700/30 rounded-lg border-l-2 border-emerald-500">
                        <div>
                            <div class="text-white font-bold text-sm"><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></div>
                            <div class="text-xs text-slate-400">Room <?= $booking['room_number'] ?? 'Unassigned' ?></div>
                        </div>
                        <div class="text-xs text-emerald-400 font-mono">CONFIRMED</div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="h-px bg-slate-700 my-4"></div>
            
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Checkout Status</h4>
            <!-- Departures content placeholder -->
            <p class="text-sm text-slate-400">View detailed checkout list in <a href="/bookings?tab=departures" class="text-cyan-400 hover:underline">Front Desk</a>.</p>
        </div>
    </div>

</div>
