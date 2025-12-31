<?php
/**
 * HotelOS - Premium Room Status Board
 * Visual room grid with glass glow effects and quick actions
 */

$pageTitle = 'Room Status | HotelOS';
$activeNav = 'housekeeping';

// Variables from controller
$rooms = $rooms ?? [];
$statusCounts = $statusCounts ?? ['clean' => 0, 'dirty' => 0, 'inspected' => 0, 'out_of_order' => 0];
$floors = $floors ?? [];
$selectedFloor = $selectedFloor ?? '';
$selectedStatus = $selectedStatus ?? '';

// Calculate room status counts
$availableCount = 0;
$occupiedCount = 0;
$reservedCount = 0;
$maintenanceCount = 0;

foreach ($rooms as $room) {
    switch ($room['status'] ?? 'available') {
        case 'available': $availableCount++; break;
        case 'occupied': $occupiedCount++; break;
        case 'reserved': $reservedCount++; break;
        case 'maintenance': $maintenanceCount++; break;
    }
}
?>

<div class="room-status-page" x-data="roomBoard()">
    <!-- Premium Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl flex items-center justify-center">
                    <i data-lucide="layout-grid" class="w-5 h-5 text-white"></i>
                </div>
                Room Status Board
            </h1>
            <p class="text-slate-400 text-sm mt-1">Real-time room availability & housekeeping status</p>
        </div>
        <div class="flex gap-2">
            <button @click="refreshBoard()" class="btn btn--secondary flex items-center gap-2">
                <i data-lucide="refresh-cw" class="w-4 h-4" :class="{'animate-spin': isRefreshing}"></i>
                Refresh
            </button>
        </div>
    </div>

    <!-- Status Overview Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-6">
        <!-- Room Status -->
        <div class="premium-stat-card premium-stat-card--available">
            <div class="premium-stat-card__icon">🟢</div>
            <div class="premium-stat-card__value"><?= $availableCount ?></div>
            <div class="premium-stat-card__label">Available</div>
        </div>
        <div class="premium-stat-card premium-stat-card--occupied">
            <div class="premium-stat-card__icon">🔴</div>
            <div class="premium-stat-card__value"><?= $occupiedCount ?></div>
            <div class="premium-stat-card__label">Occupied</div>
        </div>
        <div class="premium-stat-card premium-stat-card--reserved">
            <div class="premium-stat-card__icon">🔵</div>
            <div class="premium-stat-card__value"><?= $reservedCount ?></div>
            <div class="premium-stat-card__label">Reserved</div>
        </div>
        <div class="premium-stat-card premium-stat-card--maintenance">
            <div class="premium-stat-card__icon">🔧</div>
            <div class="premium-stat-card__value"><?= $maintenanceCount ?></div>
            <div class="premium-stat-card__label">Maintenance</div>
        </div>
        
        <!-- Housekeeping Status -->
        <div class="premium-stat-card premium-stat-card--clean">
            <div class="premium-stat-card__icon">✨</div>
            <div class="premium-stat-card__value"><?= $statusCounts['clean'] ?></div>
            <div class="premium-stat-card__label">Clean</div>
        </div>
        <div class="premium-stat-card premium-stat-card--dirty">
            <div class="premium-stat-card__icon">🧹</div>
            <div class="premium-stat-card__value"><?= $statusCounts['dirty'] ?></div>
            <div class="premium-stat-card__label">Dirty</div>
        </div>
        <div class="premium-stat-card premium-stat-card--inspected">
            <div class="premium-stat-card__icon">⭐</div>
            <div class="premium-stat-card__value"><?= $statusCounts['inspected'] ?></div>
            <div class="premium-stat-card__label">Inspected</div>
        </div>
        <div class="premium-stat-card premium-stat-card--oos">
            <div class="premium-stat-card__icon">⛔</div>
            <div class="premium-stat-card__value"><?= $statusCounts['out_of_order'] ?></div>
            <div class="premium-stat-card__label">Out of Order</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-card p-4 mb-6">
        <form method="GET" action="/housekeeping" class="flex flex-wrap gap-4 items-center">
            <div class="flex items-center gap-2">
                <label class="text-slate-400 text-sm">Floor:</label>
                <select name="floor" class="form-input py-1.5 text-sm w-32" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($floors as $f): ?>
                        <option value="<?= htmlspecialchars($f['floor']) ?>" <?= $selectedFloor === $f['floor'] ? 'selected' : '' ?>>
                            Floor <?= htmlspecialchars($f['floor']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-slate-400 text-sm">Status:</label>
                <select name="status" class="form-input py-1.5 text-sm w-32" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="clean" <?= $selectedStatus === 'clean' ? 'selected' : '' ?>>Clean</option>
                    <option value="dirty" <?= $selectedStatus === 'dirty' ? 'selected' : '' ?>>Dirty</option>
                    <option value="inspected" <?= $selectedStatus === 'inspected' ? 'selected' : '' ?>>Inspected</option>
                </select>
            </div>
            <!-- Legend -->
            <div class="ml-auto flex gap-4 text-xs">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Available</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-500"></span> Occupied</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-blue-500"></span> Reserved</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-amber-500"></span> Dirty</span>
            </div>
        </form>
    </div>

    <!-- Premium Room Grid -->
    <?php if (empty($rooms)): ?>
        <div class="glass-card p-12 text-center">
            <i data-lucide="inbox" class="w-12 h-12 text-slate-600 mx-auto mb-4"></i>
            <p class="text-slate-400">No rooms found. Add rooms in Room Management.</p>
        </div>
    <?php else: ?>
        <div class="premium-room-grid">
            <?php foreach ($rooms as $room): 
                $roomStatus = $room['status'] ?? 'available';
                $hkStatus = $room['housekeeping_status'] ?? 'clean';
                $guestName = $room['guest_name'] ?? null;
                $checkOutDate = $room['check_out_date'] ?? null;
                
                // Determine card class
                $cardClass = 'premium-room-card';
                if ($roomStatus === 'occupied') $cardClass .= ' premium-room-card--occupied';
                elseif ($roomStatus === 'reserved') $cardClass .= ' premium-room-card--reserved';
                elseif ($roomStatus === 'maintenance') $cardClass .= ' premium-room-card--maintenance';
                elseif ($hkStatus === 'dirty') $cardClass .= ' premium-room-card--dirty';
                else $cardClass .= ' premium-room-card--available';
            ?>
                <div class="<?= $cardClass ?>" 
                     data-room-id="<?= $room['id'] ?>"
                     @click="openRoomModal(<?= htmlspecialchars(json_encode($room)) ?>)">
                    
                    <!-- Room Number Badge -->
                    <div class="premium-room-card__number">
                        <?= htmlspecialchars($room['room_number']) ?>
                    </div>
                    
                    <!-- Room Type -->
                    <div class="premium-room-card__type">
                        <?= htmlspecialchars($room['room_type_name'] ?? $room['room_type_code'] ?? 'Standard') ?>
                    </div>
                    
                    <!-- Status Indicator -->
                    <div class="premium-room-card__status">
                        <?php if ($roomStatus === 'occupied'): ?>
                            <span class="status-badge status-badge--occupied">OCCUPIED</span>
                        <?php elseif ($roomStatus === 'reserved'): ?>
                            <span class="status-badge status-badge--reserved">RESERVED</span>
                        <?php elseif ($roomStatus === 'maintenance'): ?>
                            <span class="status-badge status-badge--maintenance">MAINTENANCE</span>
                        <?php elseif ($hkStatus === 'dirty'): ?>
                            <span class="status-badge status-badge--dirty">NEEDS CLEANING</span>
                        <?php else: ?>
                            <span class="status-badge status-badge--available">AVAILABLE</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Guest Info (if occupied) -->
                    <?php if ($guestName): ?>
                        <div class="premium-room-card__guest">
                            <div class="guest-name">
                                <i data-lucide="user" class="w-3 h-3"></i>
                                <?= htmlspecialchars($guestName) ?>
                            </div>
                            <?php if ($checkOutDate): ?>
                                <div class="checkout-date">
                                    Out: <?= date('d M', strtotime($checkOutDate)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Housekeeping Badge -->
                    <div class="premium-room-card__hk-status">
                        <?php if ($hkStatus === 'clean'): ?>
                            <span class="hk-badge hk-badge--clean">✓ Clean</span>
                        <?php elseif ($hkStatus === 'dirty'): ?>
                            <span class="hk-badge hk-badge--dirty">⚠ Dirty</span>
                        <?php elseif ($hkStatus === 'inspected'): ?>
                            <span class="hk-badge hk-badge--inspected">★ Inspected</span>
                        <?php else: ?>
                            <span class="hk-badge hk-badge--oos">✕ OOS</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions (on hover) -->
                    <div class="premium-room-card__actions">
                        <?php if ($roomStatus === 'available' && $hkStatus !== 'dirty'): ?>
                            <a href="/bookings/create?room_id=<?= $room['id'] ?>" class="action-btn action-btn--book" @click.stop>
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                Quick Book
                            </a>
                        <?php elseif ($hkStatus === 'dirty'): ?>
                            <button class="action-btn action-btn--clean" @click.stop="updateHKStatus(<?= $room['id'] ?>, 'clean')">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                                Mark Clean
                            </button>
                        <?php elseif ($hkStatus === 'clean'): ?>
                            <button class="action-btn action-btn--inspect" @click.stop="updateHKStatus(<?= $room['id'] ?>, 'inspected')">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                Inspect
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Glow Effect -->
                    <div class="premium-room-card__glow"></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Premium Stat Cards */
.premium-stat-card {
    background: rgba(30, 41, 59, 0.6);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.05);
    transition: transform 0.2s, box-shadow 0.2s;
}

.premium-stat-card:hover {
    transform: translateY(-2px);
}

.premium-stat-card__icon {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.premium-stat-card__value {
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
}

.premium-stat-card__label {
    font-size: 0.7rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.premium-stat-card--available { border-bottom: 3px solid #22c55e; }
.premium-stat-card--occupied { border-bottom: 3px solid #ef4444; }
.premium-stat-card--reserved { border-bottom: 3px solid #3b82f6; }
.premium-stat-card--maintenance { border-bottom: 3px solid #64748b; }
.premium-stat-card--clean { border-bottom: 3px solid #34d399; }
.premium-stat-card--dirty { border-bottom: 3px solid #f97316; }
.premium-stat-card--inspected { border-bottom: 3px solid #a78bfa; }
.premium-stat-card--oos { border-bottom: 3px solid #475569; }

/* Premium Room Grid */
.premium-room-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
}

/* Premium Room Card */
.premium-room-card {
    position: relative;
    background: rgba(30, 41, 59, 0.8);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    padding: 1.25rem;
    border: 1px solid rgba(255,255,255,0.08);
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
    min-height: 180px;
    display: flex;
    flex-direction: column;
}

.premium-room-card:hover {
    transform: translateY(-4px) scale(1.02);
    border-color: rgba(255,255,255,0.2);
}

/* Room Card Variants */
.premium-room-card--available {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(30, 41, 59, 0.8));
}
.premium-room-card--available:hover {
    box-shadow: 0 0 30px rgba(34, 197, 94, 0.3), 0 8px 32px rgba(0,0,0,0.3);
}
.premium-room-card--available:hover .premium-room-card__glow {
    background: radial-gradient(circle at center, rgba(34, 197, 94, 0.4), transparent 70%);
    opacity: 1;
}

.premium-room-card--occupied {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(30, 41, 59, 0.8));
}
.premium-room-card--occupied:hover {
    box-shadow: 0 0 30px rgba(239, 68, 68, 0.3), 0 8px 32px rgba(0,0,0,0.3);
}
.premium-room-card--occupied:hover .premium-room-card__glow {
    background: radial-gradient(circle at center, rgba(239, 68, 68, 0.4), transparent 70%);
    opacity: 1;
}

.premium-room-card--reserved {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(30, 41, 59, 0.8));
}
.premium-room-card--reserved:hover {
    box-shadow: 0 0 30px rgba(59, 130, 246, 0.3), 0 8px 32px rgba(0,0,0,0.3);
}

.premium-room-card--dirty {
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.15), rgba(30, 41, 59, 0.8));
}
.premium-room-card--dirty:hover {
    box-shadow: 0 0 30px rgba(249, 115, 22, 0.3), 0 8px 32px rgba(0,0,0,0.3);
}

.premium-room-card--maintenance {
    background: linear-gradient(135deg, rgba(100, 116, 139, 0.2), rgba(30, 41, 59, 0.8));
    opacity: 0.7;
}

/* Room Number */
.premium-room-card__number {
    font-size: 2rem;
    font-weight: 800;
    color: white;
    line-height: 1;
    margin-bottom: 0.25rem;
}

/* Room Type */
.premium-room-card__type {
    font-size: 0.7rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.75rem;
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.6rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-badge--available { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.status-badge--occupied { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.status-badge--reserved { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.status-badge--dirty { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.status-badge--maintenance { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }

/* Guest Info */
.premium-room-card__guest {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.guest-name {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #e2e8f0;
    font-weight: 500;
}

.checkout-date {
    font-size: 0.65rem;
    color: #64748b;
    margin-top: 0.125rem;
}

/* HK Badge */
.premium-room-card__hk-status {
    margin-top: auto;
    padding-top: 0.5rem;
}

.hk-badge {
    font-size: 0.65rem;
    color: #94a3b8;
}

.hk-badge--clean { color: #34d399; }
.hk-badge--dirty { color: #f97316; }
.hk-badge--inspected { color: #a78bfa; }
.hk-badge--oos { color: #64748b; }

/* Quick Actions */
.premium-room-card__actions {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
}

.premium-room-card:hover .premium-room-card__actions {
    opacity: 1;
    transform: translateY(0);
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.5rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn--book {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
}
.action-btn--book:hover { box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4); }

.action-btn--clean {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.action-btn--inspect {
    background: linear-gradient(135deg, #a78bfa, #8b5cf6);
    color: white;
}

/* Glow Effect */
.premium-room-card__glow {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 150%;
    height: 150%;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}

/* Mobile */
@media (max-width: 640px) {
    .premium-room-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .premium-room-card {
        padding: 1rem;
        min-height: 140px;
    }
    
    .premium-room-card__number {
        font-size: 1.5rem;
    }
    
    .premium-room-card__actions {
        opacity: 1;
        transform: translateY(0);
        position: relative;
        background: transparent;
        padding: 0;
        padding-top: 0.5rem;
    }
}
</style>

<script>
function roomBoard() {
    return {
        isRefreshing: false,
        selectedRoom: null,
        
        refreshBoard() {
            this.isRefreshing = true;
            location.reload();
        },
        
        updateHKStatus(roomId, status) {
            fetch('/api/housekeeping/status', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ room_id: roomId, status: status })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to update'));
                }
            });
        },
        
        openRoomModal(room) {
            this.selectedRoom = room;
            // Can add modal logic here
            console.log('Room clicked:', room);
        }
    }
}
</script>
