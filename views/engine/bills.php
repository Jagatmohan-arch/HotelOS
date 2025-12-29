<?php
/**
 * HotelOS - Bill Modification Engine View
 * Owner-Only Invoice Editing (DANGER ZONE)
 */

$invoices = $invoices ?? [];
$searchQuery = $searchQuery ?? '';
?>

<div class="engine-page animate-fadeIn" x-data="billsEngine()">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="/engine" class="text-slate-400 hover:text-white">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
            <i data-lucide="file-warning" class="w-5 h-5 text-red-400"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-red-300">Bill Modification Engine ⚠️</h1>
            <p class="text-red-400 text-sm">DANGER: Invoice edits are permanent and audited</p>
        </div>
    </div>
    
    <!-- Warning -->
    <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5"></i>
            <div>
                <h3 class="text-red-300 font-semibold">Danger Zone</h3>
                <p class="text-red-400/80 text-sm">
                    Invoice modifications are <strong>permanent</strong>. Every change is logged with your ID, timestamp, and IP address.
                    A snapshot of the original invoice is preserved. Misuse may result in legal action.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Search -->
    <div class="glass-card p-4 mb-6">
        <form @submit.prevent="searchInvoice()" class="flex gap-4">
            <div class="flex-1">
                <input type="text" x-model="searchQuery" 
                       placeholder="Enter Booking Number (e.g., BK-241229-001)" 
                       class="form-input font-mono">
            </div>
            <button type="submit" class="btn btn--primary" :disabled="searching">
                <i data-lucide="search" class="w-4 h-4"></i>
                Search
            </button>
        </form>
    </div>
    
    <!-- Invoice Details -->
    <template x-if="invoice">
        <div class="glass-card p-5 border-red-500/20">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-white font-semibold text-lg" x-text="'Invoice: ' + invoice.invoice_number"></h3>
                    <p class="text-slate-400 text-sm">Booking: <span x-text="invoice.booking_number" class="font-mono"></span></p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-white">₹<span x-text="parseFloat(invoice.grand_total).toLocaleString()"></span></div>
                    <div class="text-slate-400 text-sm" x-text="invoice.status"></div>
                </div>
            </div>
            
            <!-- Guest Info -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 text-sm">
                <div>
                    <span class="text-slate-500">Guest</span>
                    <p class="text-white" x-text="invoice.guest_name"></p>
                </div>
                <div>
                    <span class="text-slate-500">Check-in</span>
                    <p class="text-white" x-text="invoice.check_in_date"></p>
                </div>
                <div>
                    <span class="text-slate-500">Check-out</span>
                    <p class="text-white" x-text="invoice.check_out_date"></p>
                </div>
                <div>
                    <span class="text-slate-500">Room</span>
                    <p class="text-white" x-text="invoice.room_number"></p>
                </div>
            </div>
            
            <!-- Modification Form -->
            <div class="border-t border-slate-700/50 pt-4 mt-4">
                <h4 class="text-amber-300 font-semibold mb-4">⚠️ Modify Invoice</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="form-label">Discount Amount</label>
                        <input type="number" x-model="modifications.discount_amount" 
                               class="form-input" step="0.01" min="0">
                    </div>
                    <div>
                        <label class="form-label">Extra Charges</label>
                        <input type="number" x-model="modifications.extra_charges" 
                               class="form-input" step="0.01" min="0">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Notes</label>
                        <textarea x-model="modifications.notes" class="form-input" rows="2"></textarea>
                    </div>
                </div>
                
                <!-- Reason (Mandatory) -->
                <div class="mb-4">
                    <label class="form-label text-red-300">Reason for Modification * (min 20 chars)</label>
                    <textarea x-model="reason" class="form-input border-red-500/30" rows="3" 
                              placeholder="Explain why you are modifying this invoice..." required></textarea>
                    <p class="text-slate-500 text-xs mt-1">
                        Characters: <span x-text="reason.length"></span>/20 minimum
                    </p>
                </div>
                
                <!-- Password Confirmation -->
                <div class="mb-4">
                    <label class="form-label text-red-300">Confirm Your Password *</label>
                    <input type="password" x-model="confirmPassword" class="form-input border-red-500/30" 
                           placeholder="Enter your password to confirm" required>
                </div>
                
                <!-- Actions -->
                <div class="flex gap-4">
                    <button @click="modifyInvoice()" class="btn btn--danger" :disabled="processing">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span x-text="processing ? 'Processing...' : 'Apply Changes'"></span>
                    </button>
                    <button @click="voidInvoice()" class="btn bg-red-600 text-white hover:bg-red-700" :disabled="processing">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                        Void Invoice
                    </button>
                </div>
            </div>
        </div>
    </template>
    
    <!-- No Invoice Selected -->
    <template x-if="!invoice && !searching">
        <div class="glass-card p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-500/20 flex items-center justify-center">
                <i data-lucide="file-search" class="w-8 h-8 text-slate-400"></i>
            </div>
            <h3 class="text-white font-medium mb-1">Search for an Invoice</h3>
            <p class="text-slate-400 text-sm">Enter a booking number above to load the invoice for modification.</p>
        </div>
    </template>
</div>

<script>
function billsEngine() {
    return {
        searchQuery: '',
        searching: false,
        processing: false,
        invoice: null,
        modifications: {
            discount_amount: 0,
            extra_charges: 0,
            notes: ''
        },
        reason: '',
        confirmPassword: '',
        
        async searchInvoice() {
            if (!this.searchQuery.trim()) return;
            
            this.searching = true;
            try {
                const res = await fetch(`/api/bookings/search?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await res.json();
                
                if (data.bookings && data.bookings.length > 0) {
                    // Get invoice for the booking
                    const booking = data.bookings[0];
                    const invRes = await fetch(`/api/invoices/${booking.id}`);
                    const invData = await invRes.json();
                    
                    if (invData.success) {
                        this.invoice = {
                            ...invData.data,
                            booking_id: booking.id,
                            booking_number: booking.booking_number,
                            guest_name: booking.guest_name
                        };
                        this.modifications.discount_amount = this.invoice.discount_amount || 0;
                        this.modifications.extra_charges = this.invoice.extra_charges || 0;
                    } else {
                        alert('Invoice not found for this booking');
                    }
                } else {
                    alert('Booking not found');
                }
            } catch (e) {
                alert('Search error');
            }
            this.searching = false;
        },
        
        async modifyInvoice() {
            if (this.reason.length < 20) {
                alert('Reason must be at least 20 characters');
                return;
            }
            if (!this.confirmPassword) {
                alert('Please enter your password to confirm');
                return;
            }
            
            if (!confirm('⚠️ Are you absolutely sure you want to modify this invoice? This action is permanent and audited.')) {
                return;
            }
            
            this.processing = true;
            try {
                const res = await fetch(`/api/engine/invoice/${this.invoice.booking_id}/modify`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ...this.modifications,
                        reason: this.reason,
                        confirm_password: this.confirmPassword
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('✅ Invoice modified. Snapshot ID: ' + data.snapshot_id);
                    location.href = '/engine';
                } else {
                    alert('Error: ' + (data.error || 'Modification failed'));
                }
            } catch (e) {
                alert('Network error');
            }
            this.processing = false;
        },
        
        async voidInvoice() {
            if (this.reason.length < 20) {
                alert('Reason must be at least 20 characters');
                return;
            }
            if (!this.confirmPassword) {
                alert('Please enter your password to confirm');
                return;
            }
            
            if (!confirm('⚠️ VOID INVOICE?\n\nThis will permanently mark the invoice as VOID. This cannot be undone.')) {
                return;
            }
            
            this.processing = true;
            try {
                const res = await fetch(`/api/engine/invoice/${this.invoice.booking_id}/void`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        reason: this.reason,
                        confirm_password: this.confirmPassword
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('✅ Invoice voided. Snapshot ID: ' + data.snapshot_id);
                    location.href = '/engine';
                } else {
                    alert('Error: ' + (data.error || 'Void failed'));
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
