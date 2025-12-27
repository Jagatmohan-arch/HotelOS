<?php
/**
 * HotelOS - Housekeeping Board
 * Visual room status board with quick toggle buttons
 */

$pageTitle = 'Housekeeping | HotelOS';
$activeNav = 'housekeeping';

// Variables from controller
$rooms = $rooms ?? [];
$statusCounts = $statusCounts ?? ['clean' => 0, 'dirty' => 0, 'inspected' => 0, 'out_of_order' => 0];
$floors = $floors ?? [];
$selectedFloor = $selectedFloor ?? '';
$selectedStatus = $selectedStatus ?? '';
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Housekeeping Board</h1>
        <p class="page-subtitle">Manage room cleaning status</p>
    </div>
</div>

<!-- Status Summary Cards -->
<div class="status-cards">
    <div class="status-card status-card--clean">
        <span class="status-card__count"><?= $statusCounts['clean'] ?></span>
        <span class="status-card__label">Clean</span>
    </div>
    <div class="status-card status-card--dirty">
        <span class="status-card__count"><?= $statusCounts['dirty'] ?></span>
        <span class="status-card__label">Dirty</span>
    </div>
    <div class="status-card status-card--inspected">
        <span class="status-card__count"><?= $statusCounts['inspected'] ?></span>
        <span class="status-card__label">Inspected</span>
    </div>
    <div class="status-card status-card--oos">
        <span class="status-card__count"><?= $statusCounts['out_of_order'] ?></span>
        <span class="status-card__label">Out of Order</span>
    </div>
</div>

<!-- Filters -->
<div class="filters glass-card">
    <form method="GET" action="/housekeeping" class="filters__form">
        <div class="filter-group">
            <label>Floor</label>
            <select name="floor" class="form-input" onchange="this.form.submit()">
                <option value="">All Floors</option>
                <?php foreach ($floors as $f): ?>
                    <option value="<?= htmlspecialchars($f['floor']) ?>" <?= $selectedFloor === $f['floor'] ? 'selected' : '' ?>>
                        Floor <?= htmlspecialchars($f['floor']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Status</label>
            <select name="status" class="form-input" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="clean" <?= $selectedStatus === 'clean' ? 'selected' : '' ?>>Clean</option>
                <option value="dirty" <?= $selectedStatus === 'dirty' ? 'selected' : '' ?>>Dirty</option>
                <option value="inspected" <?= $selectedStatus === 'inspected' ? 'selected' : '' ?>>Inspected</option>
                <option value="out_of_order" <?= $selectedStatus === 'out_of_order' ? 'selected' : '' ?>>Out of Order</option>
            </select>
        </div>
    </form>
</div>

<!-- Room Grid -->
<div class="room-board glass-card">
    <?php if (empty($rooms)): ?>
        <p class="text-center text-muted">No rooms found</p>
    <?php else: ?>
        <div class="room-grid">
            <?php foreach ($rooms as $room): ?>
                <div class="hk-room-card hk-room-card--<?= htmlspecialchars($room['housekeeping_status']) ?>" 
                     data-room-id="<?= $room['id'] ?>">
                    <div class="hk-room-card__header">
                        <span class="hk-room-card__number"><?= htmlspecialchars($room['room_number']) ?></span>
                        <span class="hk-room-card__type"><?= htmlspecialchars($room['room_type_code']) ?></span>
                    </div>
                    
                    <div class="hk-room-card__status">
                        <?php 
                        $statusLabels = [
                            'clean' => '✓ Clean',
                            'dirty' => '⚠ Dirty',
                            'inspected' => '★ Inspected',
                            'out_of_order' => '✕ Out of Order'
                        ];
                        echo $statusLabels[$room['housekeeping_status']] ?? $room['housekeeping_status'];
                        ?>
                    </div>
                    
                    <?php if ($room['guest_name']): ?>
                        <div class="hk-room-card__guest">
                            <small>👤 <?= htmlspecialchars($room['guest_name']) ?></small>
                            <small>📅 Out: <?= date('d M', strtotime($room['check_out_date'])) ?></small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hk-room-card__actions">
                        <?php if ($room['housekeeping_status'] === 'dirty'): ?>
                            <button class="btn btn--sm btn--success" onclick="updateStatus(<?= $room['id'] ?>, 'clean')">
                                Mark Clean
                            </button>
                        <?php elseif ($room['housekeeping_status'] === 'clean'): ?>
                            <button class="btn btn--sm btn--primary" onclick="updateStatus(<?= $room['id'] ?>, 'inspected')">
                                Inspect
                            </button>
                        <?php else: ?>
                            <button class="btn btn--sm btn--warning" onclick="updateStatus(<?= $room['id'] ?>, 'dirty')">
                                Mark Dirty
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.status-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.status-card {
    padding: 1.25rem;
    border-radius: 0.75rem;
    text-align: center;
    background: rgba(30, 41, 59, 0.8);
    border: 2px solid;
}

.status-card--clean { border-color: #22c55e; }
.status-card--dirty { border-color: #ef4444; }
.status-card--inspected { border-color: #3b82f6; }
.status-card--oos { border-color: #64748b; }

.status-card__count {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #f1f5f9;
}

.status-card__label {
    color: #94a3b8;
    font-size: 0.875rem;
}

.filters {
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.filters__form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group label {
    color: #94a3b8;
    font-size: 0.875rem;
}

.room-board {
    padding: 1.5rem;
}

.room-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
}

.hk-room-card {
    padding: 1rem;
    border-radius: 0.75rem;
    background: rgba(15, 23, 42, 0.6);
    border-left: 4px solid;
}

.hk-room-card--clean { border-left-color: #22c55e; }
.hk-room-card--dirty { border-left-color: #ef4444; background: rgba(239, 68, 68, 0.1); }
.hk-room-card--inspected { border-left-color: #3b82f6; }
.hk-room-card--out_of_order { border-left-color: #64748b; opacity: 0.6; }

.hk-room-card__header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.hk-room-card__number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #f1f5f9;
}

.hk-room-card__type {
    color: #64748b;
    font-size: 0.75rem;
}

.hk-room-card__status {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.hk-room-card--clean .hk-room-card__status { color: #22c55e; }
.hk-room-card--dirty .hk-room-card__status { color: #ef4444; }
.hk-room-card--inspected .hk-room-card__status { color: #3b82f6; }

.hk-room-card__guest {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-bottom: 0.75rem;
    color: #94a3b8;
}

.hk-room-card__actions {
    margin-top: auto;
}

.btn--sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.btn--success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
}

.btn--warning {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
}

@media (max-width: 640px) {
    .status-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function updateStatus(roomId, status) {
    fetch('/api/housekeeping/status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
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
}
</script>
