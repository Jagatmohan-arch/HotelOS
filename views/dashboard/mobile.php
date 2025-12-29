<?php
/**
 * HotelOS - Mobile Owner Dashboard
 * 
 * Optimized for budget Indian phones (3" x 6-7" screens, ~320-375px width)
 * Design principles:
 * - Single column layout
 * - Large touch targets (min 48px)
 * - Thumb-reachable actions
 * - Key metrics at glance
 * - Alert-first design
 * 
 * Variables from controller:
 * - $stats: Revenue, occupancy, arrivals, etc
 * - $alerts: Pending actions needing attention
 * - $rooms: Room status summary
 */

$stats = $stats ?? [
    'todayRevenue' => 0,
    'occupancy' => 0,
    'todayArrivals' => 0,
    'todayDepartures' => 0,
    'pendingCheckouts' => 0,
    'totalRooms' => 0,
];

$alerts = $alerts ?? [];
$rooms = $rooms ?? [];
$statusSummary = $statusSummary ?? ['available' => 0, 'occupied' => 0, 'reserved' => 0, 'dirty' => 0];
?>

<!-- Mobile Owner Dashboard -->
<div class="mobile-dashboard" x-data="mobileDashboard()">
    
    <!-- Pull to Refresh Indicator -->
    <div class="pull-refresh" :class="{ 'active': refreshing }">
        <i data-lucide="refresh-cw" class="w-5 h-5 animate-spin"></i>
        <span>Refreshing...</span>
    </div>
    
    <!-- Revenue Hero Card -->
    <div class="hero-card">
        <div class="hero-label">Today's Revenue</div>
        <div class="hero-value">₹<?= number_format($stats['todayRevenue']) ?></div>
        <div class="hero-meta">
            <span class="occupancy-pill">
                <i data-lucide="trending-up" class="w-3 h-3"></i>
                <?= number_format($stats['occupancy'], 0) ?>% Occupancy
            </span>
            <span class="time-pill"><?= date('d M, h:i A') ?></span>
        </div>
    </div>
    
    <!-- Alerts Section (if any) -->
    <?php if (!empty($alerts)): ?>
    <div class="alerts-section">
        <h3 class="section-title">
            <i data-lucide="bell-ring" class="w-4 h-4 text-amber-400"></i>
            Needs Attention
        </h3>
        <div class="alerts-list">
            <?php foreach ($alerts as $alert): ?>
            <a href="<?= $alert['href'] ?? '#' ?>" class="alert-card alert-card--<?= $alert['type'] ?? 'warning' ?>">
                <div class="alert-icon">
                    <i data-lucide="<?= $alert['icon'] ?? 'alert-triangle' ?>" class="w-5 h-5"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title"><?= htmlspecialchars($alert['title']) ?></div>
                    <div class="alert-desc"><?= htmlspecialchars($alert['description']) ?></div>
                </div>
                <i data-lucide="chevron-right" class="w-5 h-5 opacity-50"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Stats Grid (2x2) -->
    <div class="stats-grid">
        <a href="/bookings?tab=arrivals" class="stat-card stat-card--green">
            <div class="stat-icon">
                <i data-lucide="log-in" class="w-6 h-6"></i>
            </div>
            <div class="stat-value"><?= $stats['todayArrivals'] ?></div>
            <div class="stat-label">Arrivals</div>
        </a>
        
        <a href="/bookings?tab=departures" class="stat-card stat-card--orange">
            <div class="stat-icon">
                <i data-lucide="log-out" class="w-6 h-6"></i>
            </div>
            <div class="stat-value"><?= $stats['todayDepartures'] ?></div>
            <div class="stat-label">Departures</div>
        </a>
        
        <a href="/rooms" class="stat-card stat-card--cyan">
            <div class="stat-icon">
                <i data-lucide="door-open" class="w-6 h-6"></i>
            </div>
            <div class="stat-value"><?= $statusSummary['available'] ?></div>
            <div class="stat-label">Available</div>
        </a>
        
        <a href="/housekeeping" class="stat-card stat-card--blue">
            <div class="stat-icon">
                <i data-lucide="sparkles" class="w-6 h-6"></i>
            </div>
            <div class="stat-value"><?= $statusSummary['dirty'] ?? 0 ?></div>
            <div class="stat-label">To Clean</div>
        </a>
    </div>
    
    <!-- Room Status Visual -->
    <div class="room-status-section">
        <h3 class="section-title">
            <i data-lucide="layout-grid" class="w-4 h-4 text-cyan-400"></i>
            Room Status
        </h3>
        <div class="room-status-bar">
            <?php 
            $total = max(1, $stats['totalRooms']);
            $occupiedPct = ($statusSummary['occupied'] / $total) * 100;
            $reservedPct = ($statusSummary['reserved'] / $total) * 100;
            $availablePct = 100 - $occupiedPct - $reservedPct;
            ?>
            <div class="status-segment status-segment--occupied" style="width: <?= $occupiedPct ?>%"></div>
            <div class="status-segment status-segment--reserved" style="width: <?= $reservedPct ?>%"></div>
            <div class="status-segment status-segment--available" style="width: <?= $availablePct ?>%"></div>
        </div>
        <div class="room-status-legend">
            <span class="legend-item">
                <span class="legend-dot legend-dot--occupied"></span>
                <?= $statusSummary['occupied'] ?> Occupied
            </span>
            <span class="legend-item">
                <span class="legend-dot legend-dot--reserved"></span>
                <?= $statusSummary['reserved'] ?> Reserved
            </span>
            <span class="legend-item">
                <span class="legend-dot legend-dot--available"></span>
                <?= $statusSummary['available'] ?> Free
            </span>
        </div>
    </div>
    
    <!-- Quick Actions (Large Touch Targets) -->
    <div class="quick-actions-section">
        <h3 class="section-title">
            <i data-lucide="zap" class="w-4 h-4 text-amber-400"></i>
            Quick Actions
        </h3>
        <div class="action-buttons">
            <button @click="$dispatch('open-quick-checkin')" class="action-btn action-btn--primary">
                <i data-lucide="log-in" class="w-5 h-5"></i>
                Quick Check-in
            </button>
            <a href="/bookings?tab=departures" class="action-btn action-btn--secondary">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                Pending Checkouts
            </a>
        </div>
    </div>
    
    <!-- Today's Summary -->
    <div class="summary-section">
        <h3 class="section-title">
            <i data-lucide="calendar-days" class="w-4 h-4 text-purple-400"></i>
            Today's Summary
        </h3>
        <div class="summary-list">
            <div class="summary-row">
                <span class="summary-label">In-House Guests</span>
                <span class="summary-value"><?= $statusSummary['occupied'] ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Expected Arrivals</span>
                <span class="summary-value text-emerald-400"><?= $stats['todayArrivals'] ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Expected Departures</span>
                <span class="summary-value text-orange-400"><?= $stats['todayDepartures'] ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Rooms to Clean</span>
                <span class="summary-value text-blue-400"><?= $statusSummary['dirty'] ?? 0 ?></span>
            </div>
        </div>
    </div>
    
    <!-- Bottom Spacer for Nav -->
    <div class="nav-spacer"></div>
</div>

<style>
/* Mobile Dashboard - Optimized for 320-375px width (3" x 6-7" phones) */
.mobile-dashboard {
    padding: 12px;
    padding-bottom: 100px; /* Space for bottom nav */
    max-width: 100%;
    overflow-x: hidden;
}

/* Pull to Refresh */
.pull-refresh {
    display: none;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    color: #64748b;
    font-size: 13px;
}
.pull-refresh.active {
    display: flex;
}

/* Hero Revenue Card */
.hero-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid rgba(34, 211, 238, 0.2);
    border-radius: 16px;
    padding: 20px 16px;
    margin-bottom: 16px;
    text-align: center;
}

.hero-label {
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 4px;
}

.hero-value {
    font-size: 36px;
    font-weight: 700;
    color: #22d3ee;
    line-height: 1.1;
    margin-bottom: 12px;
}

.hero-meta {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}

.occupancy-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(34, 211, 238, 0.1);
    color: #22d3ee;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.time-pill {
    color: #64748b;
    font-size: 11px;
    padding: 4px 8px;
}

/* Section Titles */
.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Alerts Section */
.alerts-section {
    margin-bottom: 16px;
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.alert-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 12px;
    text-decoration: none;
    transition: transform 0.2s;
    -webkit-tap-highlight-color: transparent;
}

.alert-card:active {
    transform: scale(0.98);
}

.alert-card--warning {
    background: rgba(251, 191, 36, 0.1);
    border: 1px solid rgba(251, 191, 36, 0.2);
    color: #fbbf24;
}

.alert-card--danger {
    background: rgba(248, 113, 113, 0.1);
    border: 1px solid rgba(248, 113, 113, 0.2);
    color: #f87171;
}

.alert-card--info {
    background: rgba(96, 165, 250, 0.1);
    border: 1px solid rgba(96, 165, 250, 0.2);
    color: #60a5fa;
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

.alert-content {
    flex: 1;
    min-width: 0;
}

.alert-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 2px;
}

.alert-desc {
    font-size: 12px;
    opacity: 0.8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Stats Grid (2x2) */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

.stat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px 12px;
    border-radius: 14px;
    text-decoration: none;
    transition: transform 0.2s;
    -webkit-tap-highlight-color: transparent;
    min-height: 100px; /* Good touch target */
}

.stat-card:active {
    transform: scale(0.96);
}

.stat-card--green {
    background: rgba(52, 211, 153, 0.1);
    border: 1px solid rgba(52, 211, 153, 0.2);
    color: #34d399;
}

.stat-card--orange {
    background: rgba(251, 146, 60, 0.1);
    border: 1px solid rgba(251, 146, 60, 0.2);
    color: #fb923c;
}

.stat-card--cyan {
    background: rgba(34, 211, 238, 0.1);
    border: 1px solid rgba(34, 211, 238, 0.2);
    color: #22d3ee;
}

.stat-card--blue {
    background: rgba(96, 165, 250, 0.1);
    border: 1px solid rgba(96, 165, 250, 0.2);
    color: #60a5fa;
}

.stat-icon {
    margin-bottom: 8px;
    opacity: 0.9;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 11px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Room Status Section */
.room-status-section {
    margin-bottom: 16px;
}

.room-status-bar {
    display: flex;
    height: 12px;
    border-radius: 6px;
    overflow: hidden;
    background: #1e293b;
    margin-bottom: 10px;
}

.status-segment {
    height: 100%;
    transition: width 0.5s ease;
}

.status-segment--occupied {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.status-segment--reserved {
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
}

.status-segment--available {
    background: linear-gradient(90deg, #34d399, #10b981);
}

.room-status-legend {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #94a3b8;
}

.legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.legend-dot--occupied { background: #ef4444; }
.legend-dot--reserved { background: #fbbf24; }
.legend-dot--available { background: #34d399; }

/* Quick Actions */
.quick-actions-section {
    margin-bottom: 16px;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    -webkit-tap-highlight-color: transparent;
    min-height: 52px; /* Good touch target */
}

.action-btn:active {
    transform: scale(0.98);
}

.action-btn--primary {
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    color: #0f172a;
    border: none;
}

.action-btn--secondary {
    background: rgba(255, 255, 255, 0.05);
    color: #e2e8f0;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Summary Section */
.summary-section {
    margin-bottom: 16px;
}

.summary-list {
    background: rgba(30, 41, 59, 0.5);
    border-radius: 12px;
    overflow: hidden;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-label {
    font-size: 13px;
    color: #94a3b8;
}

.summary-value {
    font-size: 16px;
    font-weight: 600;
    color: #f1f5f9;
}

/* Nav Spacer */
.nav-spacer {
    height: 80px;
}

/* Extra small phones (< 320px) */
@media (max-width: 320px) {
    .hero-value {
        font-size: 30px;
    }
    
    .stat-value {
        font-size: 24px;
    }
    
    .stats-grid {
        gap: 8px;
    }
    
    .stat-card {
        padding: 12px 8px;
        min-height: 90px;
    }
}

/* Desktop hide */
@media (min-width: 768px) {
    .mobile-dashboard {
        display: none;
    }
}
</style>

<script>
function mobileDashboard() {
    return {
        refreshing: false,
        
        init() {
            // Pull to refresh (future implementation)
        },
        
        refresh() {
            this.refreshing = true;
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    }
}
</script>
