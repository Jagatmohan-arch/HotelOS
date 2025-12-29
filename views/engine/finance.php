<?php
/**
 * HotelOS - Financial Override Engine View
 * Owner-Only Cash Adjustment & Report Locking
 */

$shifts = $shifts ?? [];
$hotelSetup = $hotelSetup ?? [];
?>

<div class="engine-page animate-fadeIn" x-data="financeEngine()">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="/engine" class="text-slate-400 hover:text-white">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
            <i data-lucide="wallet" class="w-5 h-5 text-amber-400"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-amber-300">Financial Override Engine ⚠️</h1>
            <p class="text-amber-400 text-sm">Cash Adjustments & Data Lock</p>
        </div>
    </div>
    
    <!-- Warning -->
    <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5"></i>
            <div>
                <h3 class="text-amber-300 font-semibold">Financial Override Zone</h3>
                <p class="text-amber-400/80 text-sm">
                    These actions affect financial records. All changes are permanently logged and cannot be undone.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Data Lock Section -->
    <div class="glass-card p-5 mb-6">
        <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
            <i data-lucide="lock" class="w-5 h-5 text-cyan-400"></i>
            Data Lock
        </h3>
        <p class="text-slate-400 text-sm mb-4">
            Lock all data before a specific date. No one can edit bookings, invoices, or transactions before this date.
        </p>
        
        <div class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="form-label">Lock Data Until</label>
                <input type="date" x-model="dataLockDate" class="form-input"
                       value="<?= htmlspecialchars($hotelSetup['data_locked_until'] ?? '') ?>">
            </div>
            <button @click="setDataLock()" class="btn btn--primary">
                <i data-lucide="lock" class="w-4 h-4"></i>
                Set Lock
            </button>
            <button @click="clearDataLock()" class="btn btn--secondary">
                <i data-lucide="unlock" class="w-4 h-4"></i>
                Clear Lock
            </button>
        </div>
        
        <?php if (!empty($hotelSetup['data_locked_until'])): ?>
        <div class="mt-4 p-3 bg-cyan-500/10 rounded border border-cyan-500/30">
            <span class="text-cyan-400">
                🔒 Currently locked until: <strong><?= date('d M Y', strtotime($hotelSetup['data_locked_until'])) ?></strong>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Maintenance Mode -->
    <div class="glass-card p-5 mb-6">
        <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
            <i data-lucide="construction" class="w-5 h-5 text-red-400"></i>
            Maintenance Mode
        </h3>
        <p class="text-slate-400 text-sm mb-4">
            Put the system in maintenance mode. Only owners can access during maintenance.
        </p>
        
        <div class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="form-label">Maintenance Message</label>
                <input type="text" x-model="maintenanceMessage" class="form-input" 
                       placeholder="System under maintenance. Please wait..."
                       value="<?= htmlspecialchars($hotelSetup['maintenance_message'] ?? '') ?>">
            </div>
            <?php if ($hotelSetup['maintenance_mode'] ?? false): ?>
            <button @click="toggleMaintenance(false)" class="btn bg-green-600 text-white hover:bg-green-700">
                <i data-lucide="play" class="w-4 h-4"></i>
                End Maintenance
            </button>
            <?php else: ?>
            <button @click="toggleMaintenance(true)" class="btn btn--danger">
                <i data-lucide="pause" class="w-4 h-4"></i>
                Enable Maintenance
            </button>
            <?php endif; ?>
        </div>
        
        <?php if ($hotelSetup['maintenance_mode'] ?? false): ?>
        <div class="mt-4 p-3 bg-red-500/10 rounded border border-red-500/30">
            <span class="text-red-400">
                🔴 Maintenance Mode is <strong>ACTIVE</strong>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Cash Adjustment -->
    <div class="glass-card p-5 border-red-500/20">
        <h3 class="text-red-300 font-semibold mb-4 flex items-center gap-2">
            <i data-lucide="banknote" class="w-5 h-5"></i>
            Emergency Cash Adjustment ⚠️
        </h3>
        <p class="text-slate-400 text-sm mb-4">
            Adjust the expected cash for a shift. Use only for genuine discrepancies.
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="form-label">Shift ID</label>
                <input type="number" x-model="cashAdjust.shiftId" class="form-input" placeholder="Enter shift ID">
            </div>
            <div>
                <label class="form-label">Adjustment Amount (+ or -)</label>
                <input type="number" x-model="cashAdjust.amount" class="form-input" step="0.01" placeholder="e.g., -500 or +200">
            </div>
            <div>
                <label class="form-label">Reason</label>
                <input type="text" x-model="cashAdjust.reason" class="form-input" placeholder="Reason for adjustment">
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label text-red-300">Confirm Password</label>
            <input type="password" x-model="cashAdjust.password" class="form-input border-red-500/30" 
                   placeholder="Enter your password to confirm">
        </div>
        
        <button @click="adjustCash()" class="btn btn--danger" :disabled="processing">
            <i data-lucide="credit-card" class="w-4 h-4"></i>
            <span x-text="processing ? 'Processing...' : 'Apply Adjustment'"></span>
        </button>
    </div>
</div>

<script>
function financeEngine() {
    return {
        processing: false,
        dataLockDate: <?= json_encode($hotelSetup['data_locked_until'] ?? '') ?>,
        maintenanceMessage: <?= json_encode($hotelSetup['maintenance_message'] ?? '') ?>,
        cashAdjust: {
            shiftId: '',
            amount: '',
            reason: '',
            password: ''
        },
        
        async setDataLock() {
            if (!this.dataLockDate) {
                alert('Please select a date');
                return;
            }
            
            const reason = prompt('Reason for setting data lock:');
            if (!reason) return;
            
            const res = await fetch('/api/engine/data-lock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lock_until: this.dataLockDate, reason })
            });
            const data = await res.json();
            
            if (data.success) {
                alert('✅ Data lock set successfully');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed'));
            }
        },
        
        async clearDataLock() {
            if (!confirm('Clear data lock? This will allow editing of historical data.')) return;
            
            const reason = prompt('Reason for clearing data lock:');
            if (!reason) return;
            
            const res = await fetch('/api/engine/data-lock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lock_until: null, reason })
            });
            const data = await res.json();
            
            if (data.success) {
                alert('✅ Data lock cleared');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed'));
            }
        },
        
        async toggleMaintenance(enable) {
            const action = enable ? 'enable' : 'disable';
            if (!confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} maintenance mode?`)) return;
            
            const reason = prompt(`Reason for ${action}ing maintenance:`);
            if (!reason) return;
            
            const res = await fetch('/api/engine/maintenance', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    enabled: enable, 
                    message: this.maintenanceMessage,
                    reason 
                })
            });
            const data = await res.json();
            
            if (data.success) {
                alert(`✅ Maintenance mode ${enable ? 'enabled' : 'disabled'}`);
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed'));
            }
        },
        
        async adjustCash() {
            if (!this.cashAdjust.shiftId || !this.cashAdjust.amount || !this.cashAdjust.reason || !this.cashAdjust.password) {
                alert('All fields are required');
                return;
            }
            
            if (!confirm(`⚠️ Adjust cash by ₹${this.cashAdjust.amount} for Shift #${this.cashAdjust.shiftId}?\n\nThis is permanent!`)) {
                return;
            }
            
            this.processing = true;
            try {
                const res = await fetch('/api/engine/cash-adjust', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        shift_id: parseInt(this.cashAdjust.shiftId),
                        amount: parseFloat(this.cashAdjust.amount),
                        reason: this.cashAdjust.reason,
                        confirm_password: this.cashAdjust.password
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    alert(`✅ Cash adjusted. Old: ₹${data.old}, New: ₹${data.new}`);
                    this.cashAdjust = { shiftId: '', amount: '', reason: '', password: '' };
                } else {
                    alert('Error: ' + (data.error || 'Adjustment failed'));
                }
            } catch (e) {
                alert('Network error');
            }
            this.processing = false;
        }
    };
}
</script>

<style>
.btn--danger {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.btn--danger:hover {
    background: rgba(239, 68, 68, 0.3);
}
</style>
