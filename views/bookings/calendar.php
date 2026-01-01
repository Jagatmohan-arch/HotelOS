<?php
/**
 * Reservation Calendar (Tape Chart)
 */

$today = date('Y-m-d');
$startDate = $_GET['start'] ?? $today;
$days = 14;

// Calculate prev/next keys
$prevDate = date('Y-m-d', strtotime($startDate . ' - 7 days')); // Shift by week
$nextDate = date('Y-m-d', strtotime($startDate . ' + 7 days'));

$bookingHandler = new \HotelOS\Handlers\BookingHandler();
$data = $bookingHandler->getCalendarData($startDate, $days);

$rooms = $data['rooms'];
$dates = $data['dates'];
$bookings = $data['bookings'];

// Organize bookings by Room ID for easier rendering
$bookingsByRoom = [];
foreach ($bookings as $b) {
    $bookingsByRoom[$b['room_id']][] = $b;
}

// Helper to calculate CSS positioning
function getBookingStyle($booking, $startDate, $days) {
    $start = new DateTime($startDate);
    $checkIn = new DateTime($booking['check_in_date']);
    $checkOut = new DateTime($booking['check_out_date']);
    
    // Calculate offset from start (in days)
    $diffStart = $start->diff($checkIn);
    $offsetDays = (int)$diffStart->format('%r%a'); // Can be negative if started before view
    
    // Calculate duration
    $diffDuration = $checkIn->diff($checkOut);
    $durationDays = (int)$diffDuration->format('%a');
    
    // Adjust logic for visual boundaries
    // If offset < 0, it means booking started before current view. 
    // Format width and position to clip left.
    $left = $offsetDays;
    $width = $durationDays;
    
    // CSS Grid Column calculation (1-based index)
    // We'll use absolute positioning logic relative to cells instead for finer control?
    // Actually, simple cell-based width is: width = (duration * 100) / days %
    // But fixed pixel width cells are better for scrolling.
    
    // Let's assume each day col is 60px wide.
    $colWidth = 60;
    
    $leftPx = $left * $colWidth;
    $widthPx = $width * $colWidth;
    
    // Visual adjustments for gaps
    $leftPx += 2; // margin
    $widthPx -= 4; // margin
    
    // If booking starts before view
    if ($left < 0) {
        $widthPx += ($leftPx); // reduce width effectively
        $leftPx = 2; // clamp to start
        $leftClass = 'rounded-l-none border-l-0'; // visual cue
    } else {
        $leftClass = 'rounded-l-md';
    }
    
    // If booking ends after view
    $totalWidthReq = $days * $colWidth;
    if (($leftPx + $widthPx) > $totalWidthReq) {
        $widthPx = $totalWidthReq - $leftPx;
        $rightClass = 'rounded-r-none border-r-0';
    } else {
        $rightClass = 'rounded-r-md';
    }
    
    return [
        'style' => "left: {$leftPx}px; width: {$widthPx}px;",
        'class' => "{$leftClass} {$rightClass}"
    ];
}

// Status Colors
$statusColors = [
    'confirmed' => 'bg-blue-500 border-blue-600',
    'checked_in' => 'bg-green-500 border-green-600',
    'checked_out' => 'bg-slate-500 border-slate-600',
    'pending' => 'bg-yellow-500 border-yellow-600'
];

?>

<div class="calendar-wrapper flex flex-col h-full overflow-hidden">
    <!-- Toolbar -->
    <div class="flex items-center justify-between p-4 bg-slate-800 border-b border-slate-700">
        <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <i data-lucide="calendar" class="w-6 h-6 text-cyan-400"></i>
            Reservation Calendar
        </h1>
        
        <div class="flex items-center gap-2">
            <a href="?start=<?= $today ?>" class="btn btn--secondary btn--sm">Today</a>
            <div class="flex bg-slate-700 rounded-lg overflow-hidden">
                <a href="?start=<?= $prevDate ?>" class="p-2 hover:bg-slate-600 text-slate-300">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </a>
                <span class="p-2 px-3 border-x border-slate-600 text-slate-200 font-medium whitespace-nowrap">
                    <?= date('M d', strtotime($startDate)) ?> - <?= date('M d', strtotime($startDate . " + $days days")) ?>
                </span>
                <a href="?start=<?= $nextDate ?>" class="p-2 hover:bg-slate-600 text-slate-300">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </a>
            </div>
            
            <a href="/bookings/create" class="btn btn--primary btn--sm ml-4">
                <i data-lucide="plus" class="w-4 h-4"></i> New Booking
            </a>
        </div>
    </div>

    <!-- Calendar Grid Container -->
    <div class="flex-1 overflow-auto bg-slate-900 relative custom-scrollbar" id="calendarScroll">
        <div class="inline-block min-w-full relative">
            
            <!-- Header Row (Dates) -->
            <div class="sticky top-0 z-20 flex bg-slate-800 border-b border-slate-700 h-14 shadow-lg">
                <!-- Corner (Rooms Label) -->
                <div class="sticky left-0 z-30 w-48 bg-slate-800 border-r border-slate-700 flex items-center justify-center font-bold text-slate-400 shadow-[4px_0_10px_rgba(0,0,0,0.3)]">
                    Rooms
                </div>
                <!-- Date Cols -->
                <div class="flex">
                    <?php foreach ($dates as $d): ?>
                        <div class="w-[60px] h-full flex flex-col items-center justify-center border-r border-slate-700/50 <?= $d['is_weekend'] ? 'bg-slate-700/30' : '' ?>">
                            <span class="text-xs text-slate-400 uppercase"><?= $d['day'] ?></span>
                            <span class="text-sm font-bold text-white"><?= $d['day_num'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Body Rows (Rooms) -->
            <?php foreach ($rooms as $room): ?>
            <div class="flex h-16 border-b border-slate-700/50 relative group">
                <!-- Room Label (Sticky Left) -->
                <div class="sticky left-0 z-10 w-48 bg-slate-800 border-r border-slate-700 flex flex-col justify-center px-4 shadow-[4px_0_10px_rgba(0,0,0,0.3)] group-hover:bg-slate-750 transition-colors">
                    <div class="font-bold text-white text-sm"><?= $room['room_number'] ?></div>
                    <div class="text-xs text-slate-400 truncate"><?= $room['room_type_name'] ?></div>
                </div>

                <!-- Day Cells Background -->
                <div class="flex absolute inset-0 pl-48 z-0">
                    <?php foreach ($dates as $d): ?>
                        <div class="w-[60px] h-full border-r border-slate-700/30 <?= $d['is_weekend'] ? 'bg-slate-700/10' : '' ?>"></div>
                    <?php endforeach; ?>
                </div>

                <!-- Bookings Strip (Absolute) -->
                <div class="relative flex-1 z-0 h-full">
                    <?php if (isset($bookingsByRoom[$room['id']])): ?>
                        <?php foreach ($bookingsByRoom[$room['id']] as $booking): 
                            $props = getBookingStyle($booking, $startDate, $days);
                            $colorClass = $statusColors[$booking['status']] ?? 'bg-gray-500';
                        ?>
                        <a href="/bookings/<?= $booking['id'] ?>" 
                           class="absolute top-2 bottom-2 border shadow-md text-white text-xs p-1 flex flex-col justify-center overflow-hidden hover:z-20 hover:scale-[1.02] transition-transform cursor-pointer <?= $colorClass ?> <?= $props['class'] ?>"
                           style="<?= $props['style'] ?>"
                           onmouseenter="showTooltip(event, '<?= addslashes($booking['first_name'] . ' ' . $booking['last_name']) ?>', '<?= $booking['status'] ?>', '<?= $booking['check_in_date'] ?>', '<?= $booking['check_out_date'] ?>')"
                           onmouseleave="hideTooltip()"
                        >
                            <span class="font-bold truncate"><?= htmlspecialchars($booking['first_name']) ?></span>
                            <span class="truncate opacity-80 text-[10px]"><?= $booking['booking_number'] ?></span>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Empty State if no rooms -->
            <?php if (empty($rooms)): ?>
                <div class="p-8 text-center text-slate-400">No rooms found. Add rooms in Settings to see the calendar.</div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Tooltip -->
<div id="bookingTooltip" class="fixed hidden z-50 bg-slate-900 border border-slate-600 rounded-lg shadow-xl p-3 pointer-events-none transform -translate-x-1/2 -translate-y-full mt-[-10px]">
    <div class="text-sm font-bold text-white" id="ttName"></div>
    <div class="text-xs text-cyan-400 mt-1" id="ttDates"></div>
    <div class="mt-1 inline-block px-2 py-0.5 rounded text-[10px] font-medium uppercase" id="ttStatus"></div>
</div>

<script>
    const tooltip = document.getElementById('bookingTooltip');
    const ttName = document.getElementById('ttName');
    const ttDates = document.getElementById('ttDates');
    const ttStatus = document.getElementById('ttStatus');
    
    // Status color map for tooltip
    const statusColors = {
        'confirmed': 'bg-blue-500/20 text-blue-400',
        'checked_in': 'bg-green-500/20 text-green-400',
        'checked_out': 'bg-slate-500/20 text-slate-400',
        'pending': 'bg-yellow-500/20 text-yellow-400'
    };

    function showTooltip(e, name, status, inDate, outDate) {
        ttName.textContent = name;
        ttDates.textContent = `${inDate} -> ${outDate}`;
        ttStatus.textContent = status.replace('_', ' ');
        ttStatus.className = `mt-1 inline-block px-2 py-0.5 rounded text-[10px] font-medium uppercase ${statusColors[status] || 'bg-gray-500 text-white'}`;
        
        tooltip.style.left = e.clientX + 'px';
        tooltip.style.top = e.clientY + 'px';
        tooltip.classList.remove('hidden');
    }

    function hideTooltip() {
        tooltip.classList.add('hidden');
    }
    
    // Drag to scroll
    const slider = document.getElementById('calendarScroll');
    let isDown = false;
    let startX;
    let scrollLeft;

    slider.addEventListener('mousedown', (e) => {
        isDown = true;
        slider.classList.add('cursor-grabbing');
        slider.classList.remove('cursor-grab');
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
    });
    slider.addEventListener('mouseleave', () => {
        isDown = false;
        slider.classList.remove('cursor-grabbing');
    });
    slider.addEventListener('mouseup', () => {
        isDown = false;
        slider.classList.remove('cursor-grabbing');
    });
    slider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 2; //scroll-fast
        slider.scrollLeft = scrollLeft - walk;
    });
</script>

<style>
.custom-scrollbar::-webkit-scrollbar {
    height: 10px;
    width: 10px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #1e293b; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569; 
    border-radius: 5px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #64748b; 
}
</style>
