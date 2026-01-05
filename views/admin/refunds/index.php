<?php
/**
 * HotelOS - Admin Refund Requests Page
 * 
 * Manager-only view for 2-person approval refund workflow
 * Shows pending requests and history
 * 
 * Variables:
 * - $pendingRefunds: Array of pending requests
 * - $allRefunds: Array of all refunds (history)
 * - $reasonCodes: Array of reason codes
 */

$pendingRefunds = $pendingRefunds ?? [];
$allRefunds = $allRefunds ?? [];
$reasonCodes = $reasonCodes ?? [];
?>

<div class="refunds-page animate-fadeIn hidden md:block" x-data="refundsPage()">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Refund Requests</h1>
            <p class="text-slate-400 text-sm mt-1">Review and approve refund requests (2-person approval)</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge badge--yellow">
                <span x-text="pendingCount"></span> Pending
            </span>
        </div>
    </div>
    
    <!-- Pending Requests Section -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i data-lucide="clock" class="w-5 h-5 text-amber-400"></i>
            Pending Approval
        </h2>
        
        <?php if (empty($pendingRefunds)): ?>
            <div class="glass-card p-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-500/20 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-8 h-8 text-emerald-400"></i>
                </div>
                <h3 class="text-white font-medium mb-1">All Clear!</h3>
                <p class="text-slate-400 text-sm">No refund requests pending approval.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-4">
                <?php foreach ($pendingRefunds as $refund): ?>
                    <div class="glass-card p-4" x-data="{ showDetails: false }">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <!-- Request Info -->
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-white font-bold text-lg">₹<?= number_format((float)$refund['requested_amount'], 2) ?></span>
                                    <span class="badge badge--amber"><?= htmlspecialchars($reasonCodes[$refund['reason_code']] ?? $refund['reason_code']) ?></span>
                                </div>
                                <div class="text-sm text-slate-400 space-y-1">
                                    <p>
                                        <span class="text-slate-500">Booking:</span> 
                                        <span class="text-cyan-400 font-mono"><?= htmlspecialchars($refund['booking_number']) ?></span>
                                        <?php if (!empty($refund['room_number'])): ?>
                                            • Room <?= htmlspecialchars($refund['room_number']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <p>
                                        <span class="text-slate-500">Guest:</span> 
                                        <?= htmlspecialchars($refund['guest_name']) ?>
                                    </p>
                                    <p>
                                        <span class="text-slate-500">Requested by:</span> 
                                        <?= htmlspecialchars($refund['requested_by_name']) ?> 
                                        <span class="text-xs text-slate-500">(<?= ucfirst($refund['requested_by_role']) ?>)</span>
                                        • <?= date('d M Y, h:i A', strtotime($refund['requested_at'])) ?>
                                    </p>
                                    <?php if (!empty($refund['reason_text'])): ?>
                                        <p class="text-slate-300 italic">"<?= htmlspecialchars($refund['reason_text']) ?>"</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button 
                                    @click="approveRefund(<?= $refund['id'] ?>)"
                                    class="btn btn--primary"
                                    :disabled="processing"
                                >
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    Approve
                                </button>
                                <button 
                                    @click="showRejectModal(<?= $refund['id'] ?>, '<?= htmlspecialchars($refund['booking_number']) ?>', <?= $refund['requested_amount'] ?>)"
                                    class="btn btn--danger"
                                    :disabled="processing"
                                >
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                    Reject
                                </button>
                            </div>
                        </div>
                        
                        <!-- Paid Amount Context -->
                        <div class="mt-3 pt-3 border-t border-slate-700/50 text-xs text-slate-500">
                            Max Refundable: ₹<?= number_format((float)$refund['max_refundable'], 2) ?>
                            • Invoice: <?= htmlspecialchars($refund['invoice_number']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- History Section -->
    <div>
        <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i data-lucide="history" class="w-5 h-5 text-slate-400"></i>
            Recent History
        </h2>
        
        <?php if (empty($allRefunds)): ?>
            <div class="glass-card p-6 text-center text-slate-400">
                No refund history yet.
            </div>
        <?php else: ?>
            <div class="glass-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Booking</th>
                                <th>Guest</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Requested By</th>
                                <th>Approved/Rejected By</th>
                                <th>Status</th>
                                <th>Credit Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allRefunds as $refund): ?>
                                <tr>
                                    <td class="text-slate-400 text-sm">
                                        <?= date('d M Y', strtotime($refund['requested_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="font-mono text-cyan-400"><?= htmlspecialchars($refund['booking_number']) ?></span>
                                    </td>
                                    <td class="text-white"><?= htmlspecialchars($refund['guest_name']) ?></td>
                                    <td class="text-white font-semibold">₹<?= number_format((float)$refund['requested_amount'], 2) ?></td>
                                    <td class="text-slate-400 text-sm"><?= htmlspecialchars($reasonCodes[$refund['reason_code']] ?? $refund['reason_code']) ?></td>
                                    <td class="text-slate-400 text-sm"><?= htmlspecialchars($refund['requested_by_name'] ?? '-') ?></td>
                                    <td class="text-slate-400 text-sm">
                                        <?php if ($refund['status'] === 'approved' && !empty($refund['approved_by_name'])): ?>
                                            <span class="text-emerald-400"><?= htmlspecialchars($refund['approved_by_name']) ?></span>
                                        <?php elseif ($refund['status'] === 'rejected' && !empty($refund['approved_by_name'])): ?>
                                            <span class="text-red-400"><?= htmlspecialchars($refund['approved_by_name']) ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadge = match($refund['status']) {
                                            'approved' => 'badge--green',
                                            'rejected' => 'badge--red',
                                            'pending' => 'badge--yellow',
                                            default => 'badge--gray'
                                        };
                                        ?>
                                        <span class="badge <?= $statusBadge ?>">
                                            <?= ucfirst($refund['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($refund['credit_note_number'])): ?>
                                            <span class="font-mono text-emerald-400 text-sm"><?= htmlspecialchars($refund['credit_note_number']) ?></span>
                                        <?php elseif ($refund['status'] === 'rejected'): ?>
                                            <span class="text-red-400 text-sm" title="<?= htmlspecialchars($refund['rejection_note'] ?? '') ?>">Rejected</span>
                                        <?php else: ?>
                                            <span class="text-slate-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Rejection Modal -->
    <div 
        x-show="rejectModal.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="rejectModal.show = false"
    >
        <div 
            x-show="rejectModal.show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="glass-card w-full max-w-md"
        >
            <div class="flex items-center justify-between p-4 border-b border-slate-700/50">
                <h2 class="text-lg font-semibold text-white">Reject Refund Request</h2>
                <button @click="rejectModal.show = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-4 space-y-4">
                <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-3">
                    <p class="text-red-300 text-sm">
                        Rejecting refund of <strong>₹<span x-text="rejectModal.amount"></span></strong> 
                        for booking <strong x-text="rejectModal.booking"></strong>
                    </p>
                </div>
                
                <div>
                    <label class="form-label">Rejection Reason *</label>
                    <textarea 
                        x-model="rejectModal.note"
                        class="form-input"
                        rows="3"
                        placeholder="Enter reason for rejection..."
                        required
                    ></textarea>
                </div>
                
                <div class="flex gap-3 pt-2">
                    <button 
                        type="button" 
                        @click="rejectModal.show = false" 
                        class="btn btn--secondary flex-1"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        @click="confirmReject()"
                        :disabled="!rejectModal.note || processing"
                        class="btn btn--danger flex-1"
                    >
                        <i data-lucide="x" class="w-4 h-4"></i>
                        Reject Refund
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refundsPage() {
    return {
        pendingCount: <?= count($pendingRefunds) ?>,
        processing: false,
        rejectModal: {
            show: false,
            id: null,
            booking: '',
            amount: 0,
            note: ''
        },
        
        async approveRefund(id) {
            if (!confirm('Approve this refund request? A Credit Note will be created.')) return;
            
            this.processing = true;
            try {
                const response = await fetch(`/api/refunds/${id}/approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ refund_mode: 'cash' })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Refund approved!\nCredit Note: ${data.credit_note}`);
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to approve'));
                }
            } catch (e) {
                alert('Network error');
            } finally {
                this.processing = false;
            }
        },
        
        showRejectModal(id, booking, amount) {
            this.rejectModal = {
                show: true,
                id: id,
                booking: booking,
                amount: amount.toFixed(2),
                note: ''
            };
            this.$nextTick(() => lucide.createIcons());
        },
        
        async confirmReject() {
            if (!this.rejectModal.note.trim()) {
                alert('Please enter a rejection reason');
                return;
            }
            
            this.processing = true;
            try {
                const response = await fetch(`/api/refunds/${this.rejectModal.id}/reject`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ note: this.rejectModal.note })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Refund request rejected.');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to reject'));
                }
            } catch (e) {
                alert('Network error');
            } finally {
                this.processing = false;
            }
        }
    };
}
</script>

<style>
.badge--amber {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.btn--danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
}

.btn--danger:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
}

.btn--danger:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<!-- Mobile View -->
<?php include __DIR__ . '/mobile.php'; ?>
