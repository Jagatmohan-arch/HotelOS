<?php
/**
 * HotelOS - Owner Dashboard
 * "The Brain" view - Financials & Control
 */
?>

<!-- KPI Cards -->
<!-- KPI Cards -->
<div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-8">
    <!-- Revenue Card (Full Width on Mobile for Emphasis) -->
    <div class="col-span-2 md:col-span-1 bg-slate-800 rounded-xl p-4 md:p-6 border border-slate-700 shadow-lg">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-slate-400 text-xs md:text-sm font-medium">Today's Revenue</p>
                <h3 class="text-2xl md:text-3xl font-bold text-white mt-1 md:mt-2">₹<?= number_format($stats['todayRevenue'] ?? 0) ?></h3>
            </div>
            <div class="p-2 md:p-3 bg-emerald-500/10 rounded-lg">
                <i data-lucide="indian-rupee" class="w-5 h-5 md:w-6 md:h-6 text-emerald-400"></i>
            </div>
        </div>
        <div class="mt-2 md:mt-4 flex items-center text-xs md:text-sm">
            <span class="text-emerald-400 font-medium flex items-center">
                <i data-lucide="trending-up" class="w-3 h-3 md:w-4 md:h-4 mr-1"></i> Live
            </span>
        </div>
    </div>

    <!-- Occupancy Card -->
    <div class="bg-slate-800 rounded-xl p-4 md:p-6 border border-slate-700 shadow-lg">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-slate-400 text-xs md:text-sm font-medium">Occupancy</p>
                <h3 class="text-xl md:text-3xl font-bold text-white mt-1 md:mt-2"><?= $stats['occupancy'] ?? 0 ?>%</h3>
            </div>
            <div class="p-2 md:p-3 bg-blue-500/10 rounded-lg">
                <i data-lucide="pie-chart" class="w-5 h-5 md:w-6 md:h-6 text-blue-400"></i>
            </div>
        </div>
        <div class="mt-2 md:mt-4 w-full bg-slate-700 rounded-full h-1 md:h-1.5">
            <div class="bg-blue-500 h-1 md:h-1.5 rounded-full" style="width: <?= $stats['occupancy'] ?? 0 ?>%"></div>
        </div>
    </div>

    <!-- Arrivals Card -->
    <div class="bg-slate-800 rounded-xl p-4 md:p-6 border border-slate-700 shadow-lg">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-slate-400 text-xs md:text-sm font-medium">Today's Arrivals</p>
                <h3 class="text-xl md:text-3xl font-bold text-white mt-1 md:mt-2"><?= $stats['todayArrivals'] ?? 0 ?></h3>
            </div>
            <div class="p-2 md:p-3 bg-purple-500/10 rounded-lg">
                <i data-lucide="users" class="w-5 h-5 md:w-6 md:h-6 text-purple-400"></i>
            </div>
        </div>
        <!-- Hidden on small mobile to save vertical space -->
        <div class="mt-2 text-xs text-slate-400 hidden sm:block">
            Guests checking in
        </div>
    </div>
    
    <!-- Total Rooms -->
    <!-- Hidden on very small screens if needed, but kept for parity -->
    <div class="bg-slate-800 rounded-xl p-4 md:p-6 border border-slate-700 shadow-lg hidden xs:block">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-slate-400 text-xs md:text-sm font-medium">Total Rooms</p>
                <h3 class="text-xl md:text-3xl font-bold text-white mt-1 md:mt-2"><?= $stats['totalRooms'] ?? 0 ?></h3>
            </div>
            <div class="p-2 md:p-3 bg-slate-700 rounded-lg">
                <i data-lucide="bed-double" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Section: Owner Controls -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Left Column: Business Health -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Room Status Grid (Compact) -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-white">Property Status</h2>
                <a href="/rooms" class="text-sm text-cyan-400 hover:text-cyan-300">View All Rooms &rarr;</a>
            </div>
            
            <?php 
            $roomSummary = $handler->getRoomStatusSummary(); 
            ?>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-4 rounded-lg bg-slate-900 border border-slate-700/50">
                    <div class="text-2xl font-bold text-emerald-400 mb-1"><?= $roomSummary['available'] ?? 0 ?></div>
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Available</div>
                </div>
                <div class="p-4 rounded-lg bg-slate-900 border border-slate-700/50">
                    <div class="text-2xl font-bold text-blue-500 mb-1"><?= $roomSummary['occupied'] ?? 0 ?></div>
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Occupied</div>
                </div>
                <div class="p-4 rounded-lg bg-slate-900 border border-slate-700/50">
                    <div class="text-2xl font-bold text-amber-500 mb-1"><?= $roomSummary['reserved'] ?? 0 ?></div>
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Reserved</div>
                </div>
                <div class="p-4 rounded-lg bg-slate-900 border border-slate-700/50">
                    <div class="text-2xl font-bold text-rose-500 mb-1"><?= ($roomSummary['maintenance'] ?? 0) + ($roomSummary['blocked'] ?? 0) ?></div>
                    <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Blocked</div>
                </div>
            </div>
        </div>
        
        <!-- Pending Approvals (Mockup for Phase 1) -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg p-6">
            <h2 class="text-lg font-bold text-white mb-4">Pending Actions</h2>
            
            <?php if (true): // Mock check for pending items ?>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-4 bg-slate-700/30 border border-slate-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-500/10 rounded-lg text-amber-500">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="text-white font-medium">Refund Request #REF-1023</p>
                            <p class="text-sm text-slate-400">₹2,500 • Requested by Reception</p>
                        </div>
                    </div>
                    <a href="/admin/refunds" class="px-3 py-1.5 text-sm font-medium text-white bg-slate-700 hover:bg-slate-600 rounded-lg transition-colors">
                        Review
                    </a>
                </div>
                
                <!-- If empty -->
                <!-- <div class="text-center py-8 text-slate-500">
                    No pending actions. You're all caught up!
                </div> -->
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Column: Quick Links & System -->
    <div class="space-y-6">
        <!-- Quick System Access -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg p-6">
            <h2 class="text-lg font-bold text-white mb-4">System Control</h2>
            <div class="space-y-2">
                <a href="/settings" class="block w-full text-left p-3 rounded-lg hover:bg-slate-700 transition-colors flex items-center gap-3 group">
                    <div class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400 group-hover:text-indigo-300">
                        <i data-lucide="settings-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-white font-medium">Hotel Settings</div>
                        <div class="text-xs text-slate-400">Configure property details</div>
                    </div>
                </a>
                
                <a href="/settings?tab=users" class="block w-full text-left p-3 rounded-lg hover:bg-slate-700 transition-colors flex items-center gap-3 group">
                    <div class="p-2 bg-cyan-500/10 rounded-lg text-cyan-400 group-hover:text-cyan-300">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-white font-medium">Staff & Roles</div>
                        <div class="text-xs text-slate-400">Manage user access</div>
                    </div>
                </a>
                
                <a href="/settings?tab=tax" class="block w-full text-left p-3 rounded-lg hover:bg-slate-700 transition-colors flex items-center gap-3 group">
                    <div class="p-2 bg-pink-500/10 rounded-lg text-pink-400 group-hover:text-pink-300">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-white font-medium">Tax & GST</div>
                        <div class="text-xs text-slate-400">Update billing rules</div>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Live Occupancy Mini-Chart (Placeholder for Phase 2) -->
        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl shadow-lg p-6 text-white">
            <h3 class="font-bold text-lg mb-2">Pro Tip</h3>
            <p class="text-indigo-100 text-sm mb-4">
                Did you know? Setting up correct room types increases online booking conversion by 20%.
            </p>
            <a href="/room-types" class="inline-block bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                Check Room Types
            </a>
        </div>
    </div>
</div>
