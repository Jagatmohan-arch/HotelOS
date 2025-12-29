<?php
/**
 * HotelOS - Dashboard Index View
 * 
 * Main cockpit with stats cards, room grid, and quick actions
 * 
 * Variables from controller:
 * - $stats: Array with totalRooms, occupancy, todayArrivals, todayRevenue
 * - $rooms: Array of rooms for grid display
 * - $statusSummary: Room status counts
 */



// Default values if not set
$stats = $stats ?? [
    'totalRooms' => 0,
    'occupancy' => 0,
    'todayArrivals' => 0,
    'todayRevenue' => 0,
];

$rooms = $rooms ?? [];
$statusSummary = $statusSummary ?? [
    'available' => 0,
    'occupied' => 0,
    'reserved' => 0,
    'maintenance' => 0,
];

// New variables from controller
$todayDepartures = $todayDepartures ?? 0;
$dirtyRooms = $dirtyRooms ?? 0;
$arrivalsDetail = $arrivalsDetail ?? [];
$departuresDetail = $departuresDetail ?? [];
?>

<!-- Dashboard Content (Desktop Only) -->
<div class="dashboard-page animate-fadeIn hidden md:block">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Dashboard</h1>
            <p class="text-slate-400 text-sm mt-1">Welcome back! Here's what's happening today.</p>
        </div>
        <div class="flex gap-2 sm:gap-3 flex-wrap">
            <a href="/bookings?action=checkin" class="btn btn--success">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <span class="hidden xs:inline">Quick</span> Check-in
            </a>
            <button class="btn btn--secondary hidden sm:flex">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Export</span>
            </button>
            <a href="/bookings/create" class="btn btn--primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span class="hidden sm:inline">New</span> Booking
            </a>
        </div>
    </div>
    
    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Rooms -->
        <div class="stats-card stats-card--cyan">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stats-value"><?= number_format($stats['totalRooms']) ?></p>
                    <p class="stats-label">Total Rooms</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                    <i data-lucide="bed-double" class="w-5 h-5 text-cyan-400"></i>
                </div>
            </div>
            <a href="/rooms?view=grid" class="mt-3 flex items-center gap-2 text-xs hover:bg-white/5 p-1 rounded -ml-1 transition-colors">
                <span class="text-emerald-400">
                    <i data-lucide="circle-check" class="w-3 h-3 inline"></i>
                    <?= $statusSummary['available'] ?? 0 ?> Available
                </span>
                <i data-lucide="chevron-right" class="w-3 h-3 text-slate-500 ml-auto"></i>
            </a>
        </div>
        
        <!-- Occupancy Rate -->
        <div class="stats-card stats-card--purple">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stats-value"><?= number_format($stats['occupancy'], 1) ?>%</p>
                    <p class="stats-label">Occupancy Rate</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-5 h-5 text-purple-400"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="h-1.5 bg-slate-700 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-gradient-to-r from-purple-500 to-purple-400 rounded-full transition-all duration-500"
                        style="width: <?= min($stats['occupancy'], 100) ?>%"
                    ></div>
                </div>
            </div>
        </div>
        
        <!-- Today's Arrivals -->
        <div class="stats-card stats-card--green">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stats-value"><?= number_format($stats['todayArrivals']) ?></p>
                    <p class="stats-label">Today's Arrivals</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <i data-lucide="log-in" class="w-5 h-5 text-emerald-400"></i>
                </div>
            </div>
            <a href="/bookings?tab=arrivals" class="mt-3 text-xs text-slate-400 hover:text-emerald-400 flex items-center transition-colors">
                <i data-lucide="clock" class="w-3 h-3 inline -mt-0.5 mr-1"></i>
                <span><?= date('d M Y') ?></span>
                <span class="ml-auto underline">View List →</span>
            </a>
        </div>
        
        <!-- Today's Revenue -->
        <div class="stats-card stats-card--gold">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stats-value">₹<?= number_format($stats['todayRevenue']) ?></p>
                    <p class="stats-label">Today's Revenue</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <i data-lucide="indian-rupee" class="w-5 h-5 text-amber-400"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-slate-400">
                <i data-lucide="wallet" class="w-3 h-3 inline -mt-0.5"></i>
                Collections today
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="glass-card p-4 mb-6">
        <h2 class="text-sm font-semibold text-slate-300 mb-3">Quick Actions</h2>
        <div class="flex flex-wrap gap-2">
            <a href="/bookings?action=checkin" class="btn btn--secondary">
                <i data-lucide="log-in" class="w-4 h-4 text-emerald-400"></i>
                Check-in Guest
            </a>
            <a href="/bookings?tab=departures" class="btn btn--secondary">
                <i data-lucide="log-out" class="w-4 h-4 text-orange-400"></i>
                Check-out
            </a>
            <a href="/rooms" class="btn btn--secondary">
                <i data-lucide="sparkles" class="w-4 h-4 text-blue-400"></i>
                Housekeeping
            </a>
            <a href="/room-types" class="btn btn--secondary">
                <i data-lucide="settings" class="w-4 h-4 text-slate-400"></i>
                Manage Room Types
            </a>
            <a href="/rooms" class="btn btn--secondary">
                <i data-lucide="bed-double" class="w-4 h-4 text-cyan-400"></i>
                Manage Rooms
            </a>
        </div>
    </div>
    
    <!-- Room Status Grid -->
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Room Grid -->
        <div class="lg:col-span-2 glass-card p-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-white">Room Status</h2>
                <div class="flex gap-3 text-xs">
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        Available (<?= $statusSummary['available'] ?>)
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-red-400"></span>
                        Occupied (<?= $statusSummary['occupied'] ?>)
                    </span>
                    <span class="flex items-center gap-1 hidden sm:flex">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        Reserved (<?= $statusSummary['reserved'] ?>)
                    </span>
                </div>
            </div>
            
            <?php if (empty($rooms)): ?>
                <!-- Empty State -->
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-700/50 flex items-center justify-center">
                        <i data-lucide="bed-double" class="w-8 h-8 text-slate-500"></i>
                    </div>
                    <h3 class="text-white font-medium mb-1">No Rooms Yet</h3>
                    <p class="text-slate-400 text-sm mb-4">Start by adding room types and rooms</p>
                    <a href="/room-types" class="btn btn--primary">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add Room Type
                    </a>
                </div>
            <?php else: ?>
                <!-- Room Grid -->
                <div class="room-grid">
                    <?php foreach ($rooms as $room): ?>
                        <div 
                            class="room-box room-box--<?= htmlspecialchars($room['status']) ?>"
                            title="<?= htmlspecialchars($room['room_type']) ?>"
                            style="cursor: pointer;"
                            onclick="window.location.href='/rooms'"
                        >
                            <span class="text-base font-bold"><?= htmlspecialchars($room['room_number']) ?></span>
                            <span class="text-[10px] opacity-70"><?= htmlspecialchars($room['room_type_code']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Today's Activity -->
        <div class="glass-card p-4">
            <h2 class="text-sm font-semibold text-white mb-4">Today's Activity</h2>
            
            <div class="space-y-4">
                <!-- Arrivals Section -->
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="log-in" class="w-4 h-4 text-emerald-400"></i>
                        <span class="text-xs font-medium text-slate-300">Expected Arrivals</span>
                        <span class="ml-auto badge badge--green"><?= $stats['todayArrivals'] ?></span>
                    </div>
                    <?php if ($stats['todayArrivals'] === 0): ?>
                        <p class="text-xs text-slate-500 pl-6">No arrivals scheduled</p>
                    <?php endif; ?>
                </div>
                
                <!-- Departures Section -->
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="log-out" class="w-4 h-4 text-orange-400"></i>
                        <span class="text-xs font-medium text-slate-300">Expected Departures</span>
                        <span class="ml-auto badge badge--yellow"><?= $todayDepartures ?></span>
                    </div>
                    <?php if ($todayDepartures === 0): ?>
                        <p class="text-xs text-slate-500 pl-6">No departures today</p>
                    <?php else: ?>
                         <a href="/bookings?tab=departures" class="text-xs text-orange-400 pl-6 hover:underline">View departures →</a>
                    <?php endif; ?>
                </div>
                
                <!-- Housekeeping -->
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="sparkles" class="w-4 h-4 text-blue-400"></i>
                        <span class="text-xs font-medium text-slate-300">Rooms to Clean</span>
                        <span class="ml-auto badge badge--<?= $dirtyRooms > 0 ? 'red' : 'blue' ?>"><?= $dirtyRooms ?></span>
                    </div>
                    <?php if ($dirtyRooms === 0): ?>
                        <p class="text-xs text-slate-500 pl-6">All rooms clean</p>
                    <?php else: ?>
                        <a href="/rooms" class="text-xs text-blue-400 pl-6 hover:underline"><?= $dirtyRooms ?> room(s) need cleaning →</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr class="border-slate-700/50 my-4">
            
            <!-- Quick Links -->
            <div class="space-y-2">
                <a href="/reports/daily" class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-700/30 text-slate-400 hover:text-white text-sm transition-colors">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    View Daily Report
                    <i data-lucide="chevron-right" class="w-4 h-4 ml-auto"></i>
                </a>
                <a href="/bookings" class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-700/30 text-slate-400 hover:text-white text-sm transition-colors">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    All Bookings
                    <i data-lucide="chevron-right" class="w-4 h-4 ml-auto"></i>
                </a>
            </div>
        </div>
    </div>
</div>
