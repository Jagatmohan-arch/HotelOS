<?php
/**
 * HotelOS - Staff Shifts Management View
 * 
 * Features:
 * - Start/End shift
 * - Cash tracking
 * - Activity log
 * - Handover workflow
 */

$handler = new \HotelOS\Handlers\StaffShiftHandler();
$userId = $user['id'] ?? 0;
$activeShift = $handler->getActiveShift($userId);
$todaySummary = $handler->getTodayShiftsSummary();
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">👔 Staff Shifts</h1>
        <p class="page-subtitle">Manage your shift, track activities & handover</p>
    </div>
    <div class="page-header__right">
        <?php if (!$activeShift): ?>
        <button onclick="openStartShiftModal()" class="btn btn--success">
            <i data-lucide="play" class="w-4 h-4"></i>
            Start Shift
        </button>
        <?php else: ?>
        <button onclick="openEndShiftModal()" class="btn btn--warning">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            End Shift
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Active Shift Card -->
<?php if ($activeShift): ?>
<div class="glass-card glass-card--glow p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center">
                <i data-lucide="user-check" class="w-6 h-6 text-emerald-400"></i>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Active Shift</h2>
                <p class="text-emerald-400 text-sm">
                    Started: <?= date('h:i A', strtotime($activeShift['shift_start'])) ?>
                </p>
            </div>
        </div>
        <div class="text-right">
            <span class="badge badge--green animate-pulse">● LIVE</span>
            <p class="text-slate-400 text-xs mt-1" id="shift-duration">
                Duration: Calculating...
            </p>
        </div>
    </div>
    
    <!-- Shift Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-mini">
            <div class="stat-mini__value text-emerald-400">
                ₹<?= number_format($activeShift['cash_collected'] ?? 0) ?>
            </div>
            <div class="stat-mini__label">Cash</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini__value text-purple-400">
                ₹<?= number_format($activeShift['upi_collected'] ?? 0) ?>
            </div>
            <div class="stat-mini__label">UPI</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini__value text-cyan-400">
                ₹<?= number_format($activeShift['card_collected'] ?? 0) ?>
            </div>
            <div class="stat-mini__label">Card</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini__value text-amber-400">
                ₹<?= number_format($activeShift['total_collected'] ?? 0) ?>
            </div>
            <div class="stat-mini__label">Total</div>
        </div>
    </div>
    
    <div class="grid grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-700">
        <div class="text-center">
            <div class="text-2xl font-bold text-cyan-400"><?= $activeShift['checkins_count'] ?? 0 ?></div>
            <div class="text-xs text-slate-400">Check-ins</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-orange-400"><?= $activeShift['checkouts_count'] ?? 0 ?></div>
            <div class="text-xs text-slate-400">Check-outs</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-emerald-400">
                ₹<?= number_format(($activeShift['opening_cash'] ?? 0) + ($activeShift['cash_collected'] ?? 0)) ?>
            </div>
            <div class="text-xs text-slate-400">Cash in Hand</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Today's Shifts Overview -->
<div class="glass-card p-6 mb-6">
    <h3 class="text-lg font-semibold text-white mb-4">📊 Today's Summary</h3>
    
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="stat-mini">
            <div class="stat-mini__value"><?= $todaySummary['summary']['total_shifts'] ?? 0 ?></div>
            <div class="stat-mini__label">Total Shifts</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini__value text-emerald-400">
                ₹<?= number_format($todaySummary['summary']['total_cash'] ?? 0) ?>
            </div>
            <div class="stat-mini__label">Cash</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini__value text-purple-400">
                ₹<?= number_format($todaySummary['summary']['total_upi'] ?? 0) ?>
            </div>
            <div class="stat-mini__label">UPI</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini__value text-cyan-400">
                ₹<?= number_format($todaySummary['summary']['total_card'] ?? 0) ?>
            </div>
            <div class="stat-mini__label">Card</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini__value text-amber-400">
                ₹<?= number_format($todaySummary['summary']['grand_total'] ?? 0) ?>
            </div>
            <div class="stat-mini__label">Total</div>
        </div>
    </div>
</div>

<!-- Shift History -->
<div class="glass-card p-6">
    <h3 class="text-lg font-semibold text-white mb-4">📋 Today's Shifts</h3>
    
    <?php if (empty($todaySummary['shifts'])): ?>
    <div class="text-center py-8">
        <i data-lucide="clock" class="w-12 h-12 text-slate-500 mx-auto"></i>
        <p class="text-slate-400 mt-3">No shifts recorded today</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Time</th>
                    <th>Duration</th>
                    <th>Collected</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($todaySummary['shifts'] as $shift): ?>
                <tr>
                    <td>
                        <span class="font-medium text-white">
                            <?= htmlspecialchars($shift['first_name'] . ' ' . ($shift['last_name'] ?? '')) ?>
                        </span>
                    </td>
                    <td>
                        <?= date('h:i A', strtotime($shift['shift_start'])) ?>
                        <?php if ($shift['shift_end']): ?>
                        - <?= date('h:i A', strtotime($shift['shift_end'])) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-slate-400">
                        <?php
                        $start = strtotime($shift['shift_start']);
                        $end = $shift['shift_end'] ? strtotime($shift['shift_end']) : time();
                        $hours = floor(($end - $start) / 3600);
                        $mins = floor((($end - $start) % 3600) / 60);
                        echo "{$hours}h {$mins}m";
                        ?>
                    </td>
                    <td class="text-emerald-400 font-medium">
                        ₹<?= number_format($shift['total_collected'] ?? 0) ?>
                    </td>
                    <td>
                        <?php
                        $statusClass = match($shift['status']) {
                            'active' => 'badge--green',
                            'handover_pending' => 'badge--yellow',
                            'handover_complete' => 'badge--cyan',
                            default => 'badge--slate'
                        };
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $shift['status'])) ?></span>
                    </td>
                    <td>
                        <button onclick="viewShiftDetails(<?= $shift['id'] ?>)" class="btn btn--sm btn--ghost">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Start Shift Modal -->
<div id="start-shift-modal" class="modal-backdrop" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-semibold text-white">🚀 Start Shift</h3>
            <button onclick="closeModal('start-shift-modal')" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="start-shift-form" onsubmit="startShift(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Opening Cash (₹)</label>
                    <input type="number" name="opening_cash" class="form-input" 
                           placeholder="Enter cash in drawer" min="0" step="0.01" required>
                    <p class="text-xs text-slate-500 mt-1">Count and enter the cash amount before starting</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('start-shift-modal')" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--success">
                    <i data-lucide="play" class="w-4 h-4"></i>
                    Start Shift
                </button>
            </div>
        </form>
    </div>
</div>

<!-- End Shift Modal -->
<div id="end-shift-modal" class="modal-backdrop" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-semibold text-white">🏁 End Shift</h3>
            <button onclick="closeModal('end-shift-modal')" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="end-shift-form" onsubmit="endShift(event)">
            <div class="modal-body">
                <div class="alert alert--info mb-4">
                    <i data-lucide="info" class="w-5 h-5"></i>
                    <div>
                        <p class="font-medium">Expected Cash: ₹<span id="expected-cash"><?= number_format(($activeShift['opening_cash'] ?? 0) + ($activeShift['cash_collected'] ?? 0)) ?></span></p>
                        <p class="text-sm opacity-80">Opening (₹<?= number_format($activeShift['opening_cash'] ?? 0) ?>) + Collected (₹<?= number_format($activeShift['cash_collected'] ?? 0) ?>)</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Actual Closing Cash (₹)</label>
                    <input type="number" name="closing_cash" class="form-input" 
                           placeholder="Count and enter actual cash" min="0" step="0.01" required
                           oninput="calculateDifference(this.value)">
                </div>
                
                <div id="cash-difference" class="mt-3" style="display: none;"></div>
                
                <div class="form-group mt-4">
                    <label class="form-label">Handover Notes (Optional)</label>
                    <textarea name="notes" class="form-input" rows="3" 
                              placeholder="Any notes for the next shift..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('end-shift-modal')" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--warning">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    End Shift
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.stat-mini {
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 0.75rem;
    padding: 1rem;
    text-align: center;
}

.stat-mini__value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #f1f5f9;
}

.stat-mini__label {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.25rem;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 60;
    padding: 1rem;
}

.modal-content {
    background: rgba(15, 23, 42, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    width: 100%;
    max-width: 450px;
    max-height: 90vh;
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
}

.modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
}

.alert--info {
    background: rgba(34, 211, 238, 0.1);
    border: 1px solid rgba(34, 211, 238, 0.2);
    border-radius: 0.5rem;
    padding: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: #22d3ee;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
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
// Shift duration timer
<?php if ($activeShift): ?>
const shiftStart = new Date('<?= $activeShift['shift_start'] ?>');
setInterval(() => {
    const now = new Date();
    const diff = now - shiftStart;
    const hours = Math.floor(diff / 3600000);
    const mins = Math.floor((diff % 3600000) / 60000);
    document.getElementById('shift-duration').textContent = `Duration: ${hours}h ${mins}m`;
}, 1000);
<?php endif; ?>

function openStartShiftModal() {
    document.getElementById('start-shift-modal').style.display = 'flex';
    lucide.createIcons();
}

function openEndShiftModal() {
    document.getElementById('end-shift-modal').style.display = 'flex';
    lucide.createIcons();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

async function startShift(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    
    const res = await fetch('/api/shifts/start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ opening_cash: parseFloat(data.get('opening_cash')) })
    });
    
    const result = await res.json();
    if (result.success) {
        alert('✅ Shift started successfully!');
        location.reload();
    } else {
        alert('❌ Error: ' + result.error);
    }
}

async function endShift(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    
    const res = await fetch('/api/shifts/end', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            closing_cash: parseFloat(data.get('closing_cash')),
            notes: data.get('notes')
        })
    });
    
    const result = await res.json();
    if (result.success) {
        let msg = '✅ Shift ended!\n\n';
        msg += `Expected: ₹${result.expected_cash}\n`;
        msg += `Actual: ₹${result.closing_cash}\n`;
        msg += `Difference: ₹${result.difference} (${result.status})`;
        alert(msg);
        location.reload();
    } else {
        alert('❌ Error: ' + result.error);
    }
}

const expectedCash = <?= ($activeShift['opening_cash'] ?? 0) + ($activeShift['cash_collected'] ?? 0) ?>;

function calculateDifference(value) {
    const actual = parseFloat(value) || 0;
    const diff = actual - expectedCash;
    const diffDiv = document.getElementById('cash-difference');
    
    if (diff !== 0) {
        diffDiv.style.display = 'block';
        if (diff > 0) {
            diffDiv.innerHTML = `<div class="alert" style="background: rgba(34, 197, 94, 0.15); border-color: rgba(34, 197, 94, 0.3); color: #22c55e;">
                <i data-lucide="trending-up" class="w-5 h-5"></i>
                <span>Excess: ₹${diff.toFixed(2)}</span>
            </div>`;
        } else {
            diffDiv.innerHTML = `<div class="alert" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #ef4444;">
                <i data-lucide="trending-down" class="w-5 h-5"></i>
                <span>Shortage: ₹${Math.abs(diff).toFixed(2)}</span>
            </div>`;
        }
        lucide.createIcons();
    } else {
        diffDiv.style.display = 'none';
    }
}

function viewShiftDetails(id) {
    window.location.href = '/shifts/' + id;
}
</script>
