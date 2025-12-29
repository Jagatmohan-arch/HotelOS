<?php
/**
 * HotelOS - Mobile Refund Approval View
 * 
 * Optimized for quick manager approvals on mobile.
 */
?>
<div class="mobile-refunds md:hidden" x-data="mobileRefunds()">
    
    <!-- Mobile Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-white">Refund Approvals</h1>
        <div class="px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-xs font-bold text-slate-300">
             <?= count($pendingRefunds) ?> Pending
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 gap-3 mb-6">
         <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700/50">
            <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Total Pending</div>
            <div class="text-2xl font-bold text-white flex items-end gap-1">
                <span><?= count($pendingRefunds) ?></span>
                <span class="text-sm font-normal text-slate-500 mb-1">reqs</span>
            </div>
         </div>
         <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700/50">
            <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Value</div>
            <div class="text-2xl font-bold text-amber-400">
                ₹<?= number_format(array_reduce($pendingRefunds, fn($sum, $r) => $sum + $r['requested_amount'], 0) / 1000, 1) ?>k
            </div>
         </div>
    </div>

    <!-- Pending Cards -->
    <div class="space-y-4 mb-8">
        <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Pending Requests</h2>
        
        <?php if (empty($pendingRefunds)): ?>
             <div class="glass-card p-8 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center mb-4">
                    <i data-lucide="shield-check" class="w-8 h-8 text-emerald-400"></i>
                </div>
                <h3 class="text-white font-bold text-lg">All Reviewed</h3>
                <p class="text-sm text-slate-500 mt-2">No pending refund requests.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pendingRefunds as $refund): ?>
            <div class="glass-card p-5 relative overflow-hidden group">
                <!-- Status Stripe -->
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500"></div>
                
                <div class="flex justify-between items-start mb-4 pl-2">
                    <div>
                         <div class="flex items-center gap-2 mb-1">
                            <span class="text-2xl font-bold text-white">₹<?= number_format((float)$refund['requested_amount'], 2) ?></span>
                        </div>
                        <div class="text-xs font-bold text-amber-400 uppercase tracking-wide">
                            <?= htmlspecialchars($reasonCodes[$refund['reason_code']] ?? $refund['reason_code']) ?>
                        </div>
                    </div>
                     <div class="text-right">
                        <div class="text-xs text-slate-500"><?= date('d M', strtotime($refund['requested_at'])) ?></div>
                        <div class="text-xs text-slate-500"><?= date('h:i A', strtotime($refund['requested_at'])) ?></div>
                    </div>
                </div>
                
                <div class="bg-slate-800/50 rounded-lg p-3 mb-4 pl-3 border-l-2 border-slate-600">
                    <div class="text-sm text-slate-300">
                        <span class="text-slate-500 text-xs uppercase block mb-1">Reason</span>
                        "<?= htmlspecialchars($refund['reason_text'] ?? 'No note provided') ?>"
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-700/50 flex justify-between text-xs">
                        <span class="text-slate-500">By: <?= htmlspecialchars($refund['requested_by_name']) ?></span>
                        <span class="text-cyan-400 font-mono"><?= htmlspecialchars($refund['booking_number']) ?></span>
                    </div>
                </div>

                <!-- Swipe Handlers (Visual) -->
                <div class="grid grid-cols-2 gap-3">
                    <button 
                        @click="reject('<?= $refund['id'] ?>', '<?= $refund['booking_number'] ?>', <?= $refund['requested_amount'] ?>)"
                        class="py-3 rounded-xl bg-slate-800 border border-slate-700 text-slate-300 font-bold flex items-center justify-center gap-2 active:scale-95 transition-all"
                    >
                        <i data-lucide="x" class="w-4 h-4"></i> Reject
                    </button>
                    <button 
                         @click="approve('<?= $refund['id'] ?>')"
                         class="py-3 rounded-xl bg-emerald-500 text-slate-900 font-bold flex items-center justify-center gap-2 active:scale-95 transition-all shadow-lg shadow-emerald-500/20"
                    >
                         <i data-lucide="check" class="w-4 h-4"></i> Approve
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Reject Modal -->
    <div x-show="rejectModal.show" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak>
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="rejectModal.show = false"></div>
        
        <div class="glass-card w-full max-w-sm relative z-10 p-6 animate-fadeIn">
            <h3 class="text-lg font-bold text-white mb-4">Reject Refund</h3>
            
            <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-3 mb-4">
                 <p class="text-red-300 text-sm">
                    Rejection of <strong>₹<span x-text="rejectModal.amount"></span></strong>
                </p>
            </div>
            
            <textarea 
                x-model="rejectModal.note"
                class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white mb-4 focus:outline-none focus:border-red-500"
                rows="3"
                placeholder="Reason for rejection (Required)..."
            ></textarea>

            <div class="grid grid-cols-2 gap-3">
                <button @click="rejectModal.show = false" class="py-3 rounded-xl bg-slate-800 text-slate-400 font-medium">Cancel</button>
                <button 
                    @click="confirmReject()"
                    :disabled="!rejectModal.note.trim() || processing"
                    class="py-3 rounded-xl bg-red-500 text-white font-bold shadow-lg shadow-red-500/20 disabled:opacity-50"
                >
                    Confirm Reject
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function mobileRefunds() {
    return {
        processing: false,
        rejectModal: {
            show: false,
            id: null,
            booking: '',
            amount: 0,
            note: ''
        },

        async approve(id) {
            if (!confirm('Approve Refund?')) return;
            this.processing = true;

            try {
                const res = await fetch(`/api/refunds/${id}/approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ refund_mode: 'cash' })
                });
                const data = await res.json();
                if (data.success) {
                    alert('✅ Approved');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Connection Error');
            } finally {
                this.processing = false;
            }
        },

        reject(id, booking, amount) {
            this.rejectModal = {
                show: true,
                id: id,
                booking: booking,
                amount: amount,
                note: ''
            };
        },

        async confirmReject() {
            if (!this.rejectModal.note.trim()) return;
            this.processing = true;

            try {
                 const res = await fetch(`/api/refunds/${this.rejectModal.id}/reject`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ note: this.rejectModal.note })
                });
                const data = await res.json();
                if (data.success) {
                    alert('❌ Rejected');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Connection Error');
            } finally {
                this.processing = false;
                this.rejectModal.show = false;
            }
        }
    }
}
</script>
