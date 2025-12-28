<?php
/**
 * HotelOS - OTA Channel Manager View
 * 
 * Manage OTA connections, view bookings, and sync status
 */

$handler = new \HotelOS\Handlers\OTAChannelHandler();
$channels = $handler->getChannels();
$pendingBookings = $handler->getPendingBookings();
$todayArrivals = $handler->getTodayArrivals();
$platforms = $handler->getAvailablePlatforms();

// Get summary for this month
$startDate = date('Y-m-01');
$endDate = date('Y-m-t');
$summary = $handler->getBookingsSummary($startDate, $endDate);
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">🌐 OTA Channel Manager</h1>
        <p class="page-subtitle">Manage online travel agency bookings</p>
    </div>
</div>

<!-- Alert for pending bookings -->
<?php if (count($pendingBookings) > 0): ?>
<div class="alert alert--warning mb-6">
    <i data-lucide="alert-circle" class="w-5 h-5"></i>
    <span><strong><?= count($pendingBookings) ?></strong> OTA bookings pending room assignment</span>
    <a href="#pending" class="btn btn--sm btn--warning ml-auto">View Pending</a>
</div>
<?php endif; ?>

<!-- Month Summary -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stats-card stats-card--cyan">
        <div class="flex items-start justify-between">
            <div>
                <p class="stats-value"><?= $summary['totals']['total_bookings'] ?? 0 ?></p>
                <p class="stats-label">OTA Bookings</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                <i data-lucide="globe" class="w-5 h-5 text-cyan-400"></i>
            </div>
        </div>
    </div>
    <div class="stats-card stats-card--green">
        <div class="flex items-start justify-between">
            <div>
                <p class="stats-value">₹<?= number_format($summary['totals']['total_revenue'] ?? 0) ?></p>
                <p class="stats-label">Gross Revenue</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                <i data-lucide="indian-rupee" class="w-5 h-5 text-emerald-400"></i>
            </div>
        </div>
    </div>
    <div class="stats-card stats-card--red">
        <div class="flex items-start justify-between">
            <div>
                <p class="stats-value">₹<?= number_format($summary['totals']['total_commission'] ?? 0) ?></p>
                <p class="stats-label">Commissions</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                <i data-lucide="percent" class="w-5 h-5 text-red-400"></i>
            </div>
        </div>
    </div>
    <div class="stats-card stats-card--purple">
        <div class="flex items-start justify-between">
            <div>
                <p class="stats-value">₹<?= number_format($summary['totals']['net_revenue'] ?? 0) ?></p>
                <p class="stats-label">Net Revenue</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                <i data-lucide="wallet" class="w-5 h-5 text-purple-400"></i>
            </div>
        </div>
    </div>
</div>

<!-- Connected Channels -->
<div class="glass-card p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-white">Connected Channels</h2>
        <button onclick="openConnectModal()" class="btn btn--primary btn--sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Channel
        </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($channels as $channel): ?>
        <div class="ota-channel-card <?= $channel['is_active'] ? 'ota-channel-card--active' : '' ?>">
            <div class="flex items-center gap-3 mb-3">
                <div class="ota-logo ota-logo--<?= $channel['platform'] ?>">
                    <?= strtoupper(substr($channel['platform_name'], 0, 2)) ?>
                </div>
                <div>
                    <h3 class="font-medium text-white"><?= htmlspecialchars($channel['platform_name']) ?></h3>
                    <span class="badge <?= $channel['is_active'] ? 'badge--green' : 'badge--red' ?>">
                        <?= $channel['is_active'] ? 'Connected' : 'Disconnected' ?>
                    </span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                <div>
                    <span class="text-slate-500">Bookings</span>
                    <p class="text-white font-medium"><?= $channel['total_bookings'] ?? 0 ?></p>
                </div>
                <div>
                    <span class="text-slate-500">Commission</span>
                    <p class="text-amber-400 font-medium"><?= $channel['commission_rate'] ?>%</p>
                </div>
            </div>
            
            <?php if ($channel['pending_bookings'] > 0): ?>
            <div class="bg-amber-500/10 text-amber-400 text-xs px-2 py-1 rounded mb-3">
                <?= $channel['pending_bookings'] ?> pending
            </div>
            <?php endif; ?>
            
            <div class="flex gap-2">
                <?php if ($channel['is_active']): ?>
                <button onclick="syncChannel(<?= $channel['id'] ?>)" class="btn btn--sm btn--secondary flex-1">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                    Sync
                </button>
                <button onclick="configureChannel(<?= $channel['id'] ?>)" class="btn btn--sm btn--ghost">
                    <i data-lucide="settings" class="w-3 h-3"></i>
                </button>
                <?php else: ?>
                <button onclick="connectChannel('<?= $channel['platform'] ?>')" class="btn btn--sm btn--primary flex-1">
                    Connect
                </button>
                <?php endif; ?>
            </div>
            
            <?php if ($channel['last_sync']): ?>
            <p class="text-xs text-slate-500 mt-2">
                Last sync: <?= date('d M, H:i', strtotime($channel['last_sync'])) ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Pending Bookings -->
<div class="glass-card p-6 mb-6" id="pending">
    <h2 class="text-lg font-semibold text-white mb-4">⏳ Pending Room Assignment</h2>
    
    <?php if (empty($pendingBookings)): ?>
    <div class="text-center py-8">
        <i data-lucide="check-circle" class="w-12 h-12 text-emerald-400 mx-auto"></i>
        <p class="text-slate-400 mt-3">All OTA bookings are assigned</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>Guest</th>
                    <th>Check-in</th>
                    <th>Nights</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingBookings as $booking): ?>
                <tr>
                    <td>
                        <span class="badge badge--<?= $booking['platform'] ?>">
                            <?= htmlspecialchars($booking['platform_name']) ?>
                        </span>
                    </td>
                    <td>
                        <p class="text-white font-medium"><?= htmlspecialchars($booking['guest_name']) ?></p>
                        <p class="text-xs text-slate-500"><?= $booking['ota_booking_ref'] ?></p>
                    </td>
                    <td><?= date('d M', strtotime($booking['check_in_date'])) ?></td>
                    <td><?= $booking['nights'] ?></td>
                    <td class="text-emerald-400">₹<?= number_format($booking['total_amount']) ?></td>
                    <td>
                        <button onclick="assignRoom(<?= $booking['id'] ?>)" class="btn btn--sm btn--primary">
                            Assign Room
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Revenue by Platform -->
<?php if (!empty($summary['by_platform'])): ?>
<div class="glass-card p-6">
    <h2 class="text-lg font-semibold text-white mb-4">📊 Revenue by Platform (This Month)</h2>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>Bookings</th>
                    <th>Gross Revenue</th>
                    <th>Commission</th>
                    <th>Net Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summary['by_platform'] as $row): ?>
                <tr>
                    <td class="text-white font-medium"><?= htmlspecialchars($row['platform_name']) ?></td>
                    <td><?= $row['booking_count'] ?></td>
                    <td>₹<?= number_format($row['total_revenue']) ?></td>
                    <td class="text-red-400">-₹<?= number_format($row['total_commission']) ?></td>
                    <td class="text-emerald-400 font-medium">₹<?= number_format($row['net_revenue']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
.ota-channel-card {
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 0.75rem;
    padding: 1rem;
}

.ota-channel-card--active {
    border-color: rgba(34, 211, 238, 0.2);
}

.ota-logo {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    color: white;
}

.ota-logo--mmt { background: linear-gradient(135deg, #eb4f4d, #c23b3a); }
.ota-logo--goibibo { background: linear-gradient(135deg, #f26722, #d85612); }
.ota-logo--agoda { background: linear-gradient(135deg, #5392f9, #3b7de6); }
.ota-logo--booking { background: linear-gradient(135deg, #003580, #00264d); }
.ota-logo--oyo { background: linear-gradient(135deg, #ee2e24, #c91f16); }
.ota-logo--yatra { background: linear-gradient(135deg, #e53935, #c62828); }

.badge--mmt { background: rgba(235, 79, 77, 0.2); color: #eb4f4d; }
.badge--goibibo { background: rgba(242, 103, 34, 0.2); color: #f26722; }
.badge--agoda { background: rgba(83, 146, 249, 0.2); color: #5392f9; }
.badge--booking { background: rgba(0, 53, 128, 0.2); color: #5392f9; }

.alert--warning {
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 0.5rem;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #fbbf24;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #f1f5f9;
}

.page-subtitle {
    color: #94a3b8;
    font-size: 0.875rem;
}
</style>

<script>
function openConnectModal() {
    alert('OTA Connection wizard coming soon!\n\nSupported platforms:\n- MakeMyTrip\n- Goibibo\n- Agoda\n- Booking.com\n- OYO\n- Yatra');
}

function connectChannel(platform) {
    openConnectModal();
}

function syncChannel(channelId) {
    alert('Syncing OTA channel...\n\nThis will fetch latest bookings and update inventory.');
}

function configureChannel(channelId) {
    window.location.href = '/ota/settings/' + channelId;
}

function assignRoom(otaBookingId) {
    window.location.href = '/ota/assign/' + otaBookingId;
}
</script>
