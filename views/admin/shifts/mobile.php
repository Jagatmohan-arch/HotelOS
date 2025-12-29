<?php
/**
 * HotelOS - Mobile Shift Verification View (Manager)
 * 
 * Optimized card layout for 320-375px screens.
 * Focuses on quick verification of pending shifts.
 */
?>
<div class="mobile-shifts md:hidden" x-data="{ showVerifyModal: false, selectedShiftId: null, selectedVariance: 0, showHistory: false }">
    
    <!-- Mobile Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-white">Shift Audit</h1>
        <button @click="showHistory = !showHistory" class="text-xs font-medium text-slate-400 bg-slate-800/50 px-3 py-1.5 rounded-lg border border-slate-700">
            <span x-text="showHistory ? 'Show Pending' : 'Show History'"></span>
        </button>
    </div>

    <!-- Pending Verification Cards -->
    <div x-show="!showHistory" class="space-y-4">
        <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-2">Pending Action</h2>
        
        <?php 
        $pendingShifts = array_filter($shifts, fn($s) => !$s['verified_by']);
        if (empty($pendingShifts)): 
        ?>
            <div class="glass-card p-8 flex flex-col items-center justify-center text-center">
                <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center mb-3">
                    <i data-lucide="check-check" class="w-6 h-6 text-emerald-400"></i>
                </div>
                <h3 class="text-white font-medium">All Caught Up!</h3>
                <p class="text-sm text-slate-500 mt-1">No pending shifts to verify.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pendingShifts as $shift): ?>
            <div class="glass-card p-4 border-l-4 <?= $shift['variance_amount'] != 0 ? 'border-amber-500' : 'border-slate-700' ?>">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center text-sm font-bold text-slate-300">
                            <?= strtoupper(substr($shift['first_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="font-bold text-white"><?= htmlspecialchars($shift['first_name']) ?></div>
                            <div class="text-xs text-slate-400">Ended: <?= date('h:i A', strtotime($shift['shift_end_at'])) ?></div>
                        </div>
                    </div>
                    <?php if ($shift['variance_amount'] != 0): ?>
                    <div class="px-2 py-1 rounded bg-amber-500/20 text-amber-400 text-xs font-bold border border-amber-500/30">
                        Variance
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="bg-slate-800/50 p-2 rounded-lg">
                        <div class="text-[10px] text-slate-500 uppercase">System Exp.</div>
                        <div class="text-sm font-medium text-slate-300">₹<?= number_format($shift['system_expected_cash'], 2) ?></div>
                    </div>
                    <div class="bg-slate-800/50 p-2 rounded-lg">
                        <div class="text-[10px] text-slate-500 uppercase">Actual Cash</div>
                        <div class="text-sm font-bold text-white">₹<?= number_format($shift['closing_cash'], 2) ?></div>
                    </div>
                </div>
                
                <?php if ($shift['variance_amount'] != 0): ?>
                <div class="mb-4 text-center">
                    <span class="text-sm font-bold <?= $shift['variance_amount'] < 0 ? 'text-red-400' : 'text-blue-400' ?>">
                        <?= $shift['variance_amount'] > 0 ? '+' : '' ?>₹<?= number_format($shift['variance_amount'], 2) ?>
                        Difference
                    </span>
                </div>
                <?php endif; ?>
                
                <button 
                    @click="showVerifyModal = true; selectedShiftId = <?= $shift['id'] ?>; selectedVariance = '<?= $shift['variance_amount'] ?>'"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-indigo-600 text-white font-bold shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-2 active:scale-95 transition-all"
                >
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    Verify Shift
                </button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- History Cards (Hidden by default) -->
    <div x-show="showHistory" class="space-y-4" style="display: none;">
        <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-2">Verified History</h2>
        
        <?php 
        $verifiedShifts = array_filter($shifts, fn($s) => $s['verified_by']);
        foreach ($verifiedShifts as $shift): 
        ?>
        <div class="glass-card p-4 opacity-75">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-white"><?= htmlspecialchars($shift['first_name']) ?></span>
                <span class="text-xs text-emerald-400 flex items-center gap-1">
                    <i data-lucide="check-circle" class="w-3 h-3"></i> Verified
                </span>
            </div>
            <div class="text-xs text-slate-500 space-y-1">
                <div class="flex justify-between">
                    <span>Date:</span>
                    <span><?= date('M d, h:i A', strtotime($shift['shift_end_at'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Cash:</span>
                    <span class="text-slate-300">₹<?= number_format($shift['closing_cash'], 2) ?></span>
                </div>
                <?php if ($shift['manager_note']): ?>
                <div class="mt-2 pt-2 border-t border-slate-700/50 italic text-slate-600">
                    "<?= htmlspecialchars($shift['manager_note']) ?>"
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Mobile Verify Modal -->
    <div x-show="showVerifyModal" style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto"
         x-cloak
    >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="showVerifyModal = false"></div>

        <!-- Modal Content -->
        <div class="relative min-h-screen flex items-end sm:items-center justify-center p-4">
            <div 
                x-show="showVerifyModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-full opacity-0"
                class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-t-2xl sm:rounded-2xl p-6 shadow-2xl"
                @click.stop
            >
                <div class="w-12 h-1.5 bg-slate-700 rounded-full mx-auto mb-6"></div>
                
                <h3 class="text-xl font-bold text-white mb-2">Verify Shift</h3>
                <p class="text-sm text-slate-400 mb-6">Confirm cash collection and variance acknowledgement.</p>
                
                <form action="/admin/shifts/verify" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="shift_id" :value="selectedShiftId">
                    
                    <div x-show="selectedVariance != 0" class="mb-6 bg-red-500/10 border border-red-500/20 p-4 rounded-xl">
                         <p class="text-sm text-red-400 font-bold flex items-center gap-2">
                             <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                             Variance Detected: ₹<span x-text="selectedVariance"></span>
                         </p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-xs font-medium text-slate-400 mb-2 uppercase tracking-wide">Manager Remarks (Optional)</label>
                        <input 
                            type="text" 
                            name="note" 
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 transition-colors"
                            placeholder="e.g. Approved shortage"
                        >
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="showVerifyModal = false" class="py-3 rounded-xl bg-slate-800 text-slate-300 font-medium active:scale-95 transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="py-3 rounded-xl bg-emerald-500 text-white font-bold active:scale-95 transition-all shadow-lg shadow-emerald-500/20">
                            Verify & Lock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
