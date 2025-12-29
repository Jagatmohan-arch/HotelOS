<?php
/**
 * HotelOS - Mobile-First Checkout Page
 * 
 * Shows final bill summary and collects payment before check-out.
 * Optimized for mobile devices (320-375px)
 */

$booking = $invoice['booking'];
$totals = $invoice['charges']; // Fixed: Use charges array from InvoiceHandler
$guest = $invoice['guest'];

$pendingAmount = $totals['balance']; // Fixed: Use balance from charges
$hasPending = $pendingAmount > 0;
?>

<div class="checkout-page" x-data="checkoutFlow()">
    
    <!-- Mobile Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="/bookings" class="p-2 -ml-2 text-slate-400 hover:text-white">
            <i data-lucide="arrow-left" class="w-6 h-6"></i>
        </a>
        <h1 class="text-xl font-bold text-white">Guest Checkout</h1>
    </div>
    
    <div class="grid lg:grid-cols-2 gap-6">
        
        <!-- Left Col: Bill Summary -->
        <div class="space-y-6">
            
            <!-- Room & Guest Card -->
            <div class="glass-card p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-500/20 flex items-center justify-center text-lg font-bold text-indigo-400">
                            <?= $booking['room_number'] ?>
                        </div>
                        <div>
                            <div class="font-bold text-white"><?= htmlspecialchars($guest['name']) ?></div>
                            <div class="text-sm text-slate-400"><?= htmlspecialchars($guest['phone'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-slate-500 uppercase">Nights</div>
                        <div class="font-bold text-white"><?= $invoice['stay']['nights'] ?></div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="bg-slate-800/50 p-3 rounded-lg">
                        <div class="text-slate-500 text-xs">Check-in</div>
                        <div class="text-white font-medium mt-1"><?= date('d M, h:i A', strtotime($booking['check_in_date'])) ?></div>
                    </div>
                    <div class="bg-slate-800/50 p-3 rounded-lg">
                        <div class="text-slate-500 text-xs">Check-out</div>
                        <div class="text-white font-medium mt-1"><?= date('d M', strtotime($booking['check_out_date'])) ?> (Today)</div>
                    </div>
                </div>
            </div>
            
            <!-- Bill Breakdown -->
            <div class="glass-card p-4">
                <h3 class="text-sm font-semibold text-slate-300 mb-4 uppercase tracking-wider">Bill Summary</h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-slate-400">
                        <span>Room Charges</span>
                        <span>₹<?= number_format($totals['room_total'], 2) ?></span>
                    </div>
                    
                    <?php if ($totals['total_tax'] > 0): ?>
                    <div class="flex justify-between text-slate-400">
                        <span>GST Tax</span>
                        <span>₹<?= number_format($totals['total_tax'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($totals['extra'] > 0): ?>
                    <div class="flex justify-between text-slate-400">
                        <span>Extra Services</span>
                        <span>₹<?= number_format($totals['extra'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($totals['discount'] > 0): ?>
                    <div class="flex justify-between text-emerald-400">
                        <span>Discount</span>
                        <span>-₹<?= number_format($totals['discount'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="border-t border-slate-700/50 my-2"></div>
                    
                    <div class="flex justify-between text-white font-semibold">
                        <span>Grand Total</span>
                        <span>₹<?= number_format($totals['grand_total'], 2) ?></span>
                    </div>
                    
                    <div class="flex justify-between text-emerald-400">
                        <span>Already Paid</span>
                        <span>₹<?= number_format($totals['paid'], 2) ?></span>
                    </div>
                    
                    <?php if ($booking['advance_amount'] > 0): ?>
                    <div class="text-xs text-right text-slate-500">
                        (Includes ₹<?= number_format($booking['advance_amount']) ?> advance)
                    </div>
                    <?php endif; ?>
                    
                    <div class="border-t border-slate-700/50 my-2"></div>
                    
                    <div class="flex justify-between items-center bg-slate-800/80 p-3 rounded-lg">
                        <span class="text-slate-300 font-medium">Pending Amount</span>
                        <span class="text-xl font-bold <?= $hasPending ? 'text-red-400' : 'text-emerald-400' ?>">
                            ₹<?= number_format($pendingAmount, 2) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Col: Payment & Action -->
        <div class="space-y-6">
            
            <?php if ($hasPending): ?>
            <!-- Payment Collection -->
            <div class="glass-card p-4 border-l-4 border-amber-500">
                <h3 class="flex items-center gap-2 text-lg font-bold text-white mb-4">
                    <i data-lucide="wallet" class="w-5 h-5 text-amber-400"></i>
                    Collect Payment
                </h3>
                
                <div class="space-y-4">
                    <!-- Amount Input -->
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Amount to Collect</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₹</span>
                            <input 
                                type="number" 
                                x-model.number="paymentAmount"
                                class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl py-3 pl-8 pr-4 text-white font-bold focus:outline-none focus:border-amber-400/50 focus:ring-1 focus:ring-amber-400/50"
                            >
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-2">Payment Method</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button 
                                @click="paymentMethod = 'cash'"
                                class="p-3 rounded-xl border transition-all text-sm font-medium flex flex-col items-center gap-1"
                                :class="paymentMethod === 'cash' ? 'bg-amber-400/10 border-amber-400 text-amber-400' : 'bg-slate-800/50 border-transparent text-slate-400 hover:bg-slate-800'"
                            >
                                <i data-lucide="banknote" class="w-5 h-5"></i>
                                Cash
                            </button>
                            <button 
                                @click="paymentMethod = 'upi'"
                                class="p-3 rounded-xl border transition-all text-sm font-medium flex flex-col items-center gap-1"
                                :class="paymentMethod === 'upi' ? 'bg-emerald-400/10 border-emerald-400 text-emerald-400' : 'bg-slate-800/50 border-transparent text-slate-400 hover:bg-slate-800'"
                            >
                                <i data-lucide="smartphone" class="w-5 h-5"></i>
                                UPI / Online
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                             <button 
                                @click="paymentMethod = 'card'"
                                class="p-3 rounded-xl border transition-all text-sm font-medium flex flex-col items-center gap-1"
                                :class="paymentMethod === 'card' ? 'bg-blue-400/10 border-blue-400 text-blue-400' : 'bg-slate-800/50 border-transparent text-slate-400 hover:bg-slate-800'"
                            >
                                <i data-lucide="credit-card" class="w-5 h-5"></i>
                                Card
                            </button>
                            <button 
                                @click="paymentMethod = 'transfer'"
                                class="p-3 rounded-xl border transition-all text-sm font-medium flex flex-col items-center gap-1"
                                :class="paymentMethod === 'transfer' ? 'bg-purple-400/10 border-purple-400 text-purple-400' : 'bg-slate-800/50 border-transparent text-slate-400 hover:bg-slate-800'"
                            >
                                <i data-lucide="building-2" class="w-5 h-5"></i>
                                Transfer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                    <i data-lucide="check" class="w-5 h-5 text-emerald-400"></i>
                </div>
                <div>
                    <h4 class="font-bold text-emerald-400">No Pending Dues</h4>
                    <p class="text-xs text-emerald-400/70">Bill is fully paid.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Checkout Actions -->
            <div class="glass-card p-4 space-y-3">
                <button 
                    @click="processCheckout()"
                    class="w-full py-4 rounded-xl text-slate-900 font-bold text-lg shadow-lg flex items-center justify-center gap-2 transition-all"
                    :class="canCheckout ? 'bg-gradient-to-r from-emerald-400 to-emerald-500 hover:shadow-emerald-500/25 active:scale-95' : 'bg-slate-700 text-slate-400 cursor-not-allowed'"
                    :disabled="!canCheckout || isProcessing"
                >
                    <span x-show="!isProcessing" class="flex items-center gap-2">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                        Complete Checkout
                    </span>
                    <span x-show="isProcessing" class="flex items-center gap-2">
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                        Processing...
                    </span>
                </button>
                
                <div class="grid grid-cols-2 gap-3">
                    <a href="/bookings/<?= $booking['id'] ?>/invoice" target="_blank" class="btn btn--secondary w-full justify-center">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        View Invoice
                    </a>
                    <a href="/bookings/<?= $booking['id'] ?>" class="btn btn--secondary w-full justify-center">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        View Booking
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkoutFlow() {
    return {
        paymentAmount: <?= $pendingAmount ?>,
        paymentMethod: 'cash',
        pendingAmount: <?= $pendingAmount ?>,
        isProcessing: false,
        
        get canCheckout() {
            if (this.pendingAmount <= 0) return true;
            return this.paymentAmount >= this.pendingAmount; // Allow settling
        },
        
        async processCheckout() {
            if (!this.canCheckout) return;
            if (!confirm('Confirm checkout and finalize bill? This action cannot be undone.')) return;
            
            this.isProcessing = true;
            
            try {
                const res = await fetch('/api/bookings/<?= $booking['id'] ?>/check-out', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        payment_method: this.paymentMethod,
                        amount_paid: this.paymentAmount
                    })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    alert('✅ Checkout Complete!');
                    window.location.href = '/bookings/<?= $booking['id'] ?>/invoice?mode=thermal';
                } else {
                    alert('Error: ' + (data.error || 'Checkout failed'));
                }
            } catch (e) {
                alert('Connection error');
            } finally {
                this.isProcessing = false;
            }
        }
    }
}
</script>
