<?php
/**
 * HotelOS - Quick Check-in Modal Component
 * 
 * Fast check-in flow with:
 * - Guest search (phone/name)
 * - Available room selection
 * - ID capture (camera/upload)
 * - Minimal required fields
 */
?>

<!-- Quick Check-in Modal -->
<div 
    x-data="quickCheckin()"
    x-show="isOpen"
    x-cloak
    @open-quick-checkin.window="open()"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <!-- Backdrop -->
    <div 
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
        class="fixed inset-0 bg-black/70 backdrop-blur-sm"
    ></div>
    
    <!-- Modal Container -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div 
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
            class="quick-checkin-modal"
        >
            <!-- Header -->
            <div class="modal-header">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                        <i data-lucide="log-in" class="w-5 h-5 text-emerald-400"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Quick Check-in</h2>
                        <p class="text-xs text-slate-400">Fast guest check-in</p>
                    </div>
                </div>
                <button @click="close()" class="text-slate-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <!-- Steps Indicator -->
            <div class="steps-indicator">
                <template x-for="(stepName, index) in ['Guest', 'Room', 'Confirm']" :key="index">
                    <div 
                        class="step-item"
                        :class="{ 
                            'step-item--active': currentStep === index + 1,
                            'step-item--completed': currentStep > index + 1
                        }"
                    >
                        <span class="step-number" x-text="index + 1"></span>
                        <span class="step-label" x-text="stepName"></span>
                    </div>
                </template>
            </div>
            
            <!-- Step 1: Guest Search -->
            <div x-show="currentStep === 1" class="modal-body">
                <div class="form-group">
                    <label class="form-label">Search Guest</label>
                    <div class="search-input-wrapper">
                        <i data-lucide="search" class="search-icon"></i>
                        <input 
                            type="text" 
                            x-model="searchQuery"
                            @input.debounce.300ms="searchGuests()"
                            placeholder="Enter phone number or name..."
                            class="form-input form-input--search"
                            autofocus
                        >
                        <span x-show="isSearching" class="search-spinner">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        </span>
                    </div>
                </div>
                
                <!-- Search Results -->
                <div x-show="searchResults.length > 0" class="search-results">
                    <template x-for="guest in searchResults" :key="guest.id">
                        <div 
                            @click="selectGuest(guest)"
                            class="search-result-item"
                            :class="{ 'search-result-item--selected': selectedGuest?.id === guest.id }"
                        >
                            <div class="guest-avatar">
                                <span x-text="guest.first_name.charAt(0)"></span>
                            </div>
                            <div class="guest-info">
                                <p class="guest-name" x-text="guest.first_name + ' ' + (guest.last_name || '')"></p>
                                <p class="guest-phone" x-text="guest.phone"></p>
                            </div>
                            <div class="guest-stats">
                                <span class="badge badge--cyan" x-text="(guest.total_stays || 0) + ' stays'"></span>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- No Results / New Guest -->
                <div x-show="searchQuery.length >= 3 && searchResults.length === 0 && !isSearching" class="no-results">
                    <p class="text-slate-400 mb-3">No guest found</p>
                    <button @click="showNewGuestForm = true" class="btn btn--primary btn--sm">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        Add New Guest
                    </button>
                </div>
                
                <!-- New Guest Form (inline) -->
                <div x-show="showNewGuestForm" x-collapse class="new-guest-form">
                    <h3 class="text-sm font-semibold text-white mb-3">New Guest Details</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" x-model="newGuest.first_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" x-model="newGuest.last_name" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone *</label>
                            <input type="tel" x-model="newGuest.phone" class="form-input" maxlength="10" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ID Type</label>
                            <select x-model="newGuest.id_type" class="form-input">
                                <option value="">Select...</option>
                                <option value="aadhaar">Aadhaar</option>
                                <option value="passport">Passport</option>
                                <option value="driving_license">Driving License</option>
                                <option value="voter_id">Voter ID</option>
                            </select>
                        </div>
                        <div class="form-group col-span-2">
                            <label class="form-label">ID Number</label>
                            <input type="text" x-model="newGuest.id_number" class="form-input">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Room Selection -->
            <div x-show="currentStep === 2" class="modal-body">
                <div class="selected-guest-card" x-show="selectedGuest || showNewGuestForm">
                    <div class="guest-avatar guest-avatar--lg">
                        <span x-text="(selectedGuest?.first_name || newGuest.first_name || 'G').charAt(0)"></span>
                    </div>
                    <div>
                        <p class="text-white font-medium" x-text="(selectedGuest?.first_name || newGuest.first_name) + ' ' + (selectedGuest?.last_name || newGuest.last_name || '')"></p>
                        <p class="text-slate-400 text-sm" x-text="selectedGuest?.phone || newGuest.phone"></p>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Check-out Date *</label>
                        <input type="date" x-model="checkOutDate" class="form-input" :min="today">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nights</label>
                        <input type="number" x-model="nights" class="form-input" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Room</label>
                    <div x-show="isLoadingRooms" class="text-center py-4">
                        <i data-lucide="loader-2" class="w-6 h-6 text-cyan-400 animate-spin mx-auto"></i>
                        <p class="text-slate-400 text-sm mt-2">Loading available rooms...</p>
                    </div>
                    <div x-show="!isLoadingRooms" class="rooms-grid">
                        <template x-for="room in availableRooms" :key="room.id">
                            <div 
                                @click="selectRoom(room)"
                                class="room-card-mini"
                                :class="{ 'room-card-mini--selected': selectedRoom?.id === room.id }"
                            >
                                <span class="room-number" x-text="room.room_number"></span>
                                <span class="room-type" x-text="room.room_type_name"></span>
                                <span class="room-rate">₹<span x-text="parseFloat(room.base_rate).toLocaleString()"></span></span>
                            </div>
                        </template>
                    </div>
                    <div x-show="!isLoadingRooms && availableRooms.length === 0" class="text-center py-4">
                        <p class="text-slate-400">No rooms available for selected date</p>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Confirm -->
            <div x-show="currentStep === 3" class="modal-body">
                <div class="confirmation-summary">
                    <h3 class="text-sm font-semibold text-white mb-4">Booking Summary</h3>
                    
                    <div class="summary-row">
                        <span class="summary-label">Guest</span>
                        <span class="summary-value" x-text="(selectedGuest?.first_name || newGuest.first_name) + ' ' + (selectedGuest?.last_name || newGuest.last_name || '')"></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Phone</span>
                        <span class="summary-value" x-text="selectedGuest?.phone || newGuest.phone"></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Room</span>
                        <span class="summary-value" x-text="selectedRoom?.room_number + ' (' + selectedRoom?.room_type_name + ')'"></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Check-in</span>
                        <span class="summary-value" x-text="today"></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Check-out</span>
                        <span class="summary-value" x-text="checkOutDate"></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Nights</span>
                        <span class="summary-value" x-text="nights"></span>
                    </div>
                    <div class="summary-row summary-row--total">
                        <span class="summary-label">Estimated Total</span>
                        <span class="summary-value text-emerald-400">₹<span x-text="(nights * (selectedRoom?.base_rate || 0)).toLocaleString()"></span></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Advance Payment (Optional)</label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">₹</span>
                        <input type="number" x-model="advanceAmount" class="form-input" min="0" step="100">
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="modal-footer">
                <button 
                    x-show="currentStep > 1" 
                    @click="prevStep()" 
                    class="btn btn--secondary"
                >
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Back
                </button>
                <div class="flex-1"></div>
                <button @click="close()" class="btn btn--ghost">Cancel</button>
                <button 
                    x-show="currentStep < 3" 
                    @click="nextStep()" 
                    :disabled="!canProceed"
                    class="btn btn--primary"
                >
                    Next
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
                <button 
                    x-show="currentStep === 3" 
                    @click="confirmCheckin()"
                    :disabled="isSubmitting"
                    class="btn btn--success"
                >
                    <template x-if="isSubmitting">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    </template>
                    <template x-if="!isSubmitting">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </template>
                    Confirm Check-in
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.quick-checkin-modal {
    background: rgba(15, 23, 42, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
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
    flex: 1;
}

.modal-footer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
}

/* Steps Indicator */
.steps-indicator {
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.2);
}

.step-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    opacity: 0.5;
}

.step-item--active,
.step-item--completed {
    opacity: 1;
}

.step-number {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(148, 163, 184, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: #94a3b8;
}

.step-item--active .step-number {
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    color: #0f172a;
}

.step-item--completed .step-number {
    background: #22c55e;
    color: white;
}

.step-label {
    font-size: 0.8rem;
    color: #94a3b8;
}

.step-item--active .step-label {
    color: #f1f5f9;
}

/* Search Input */
.search-input-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #64748b;
}

.form-input--search {
    padding-left: 40px;
    padding-right: 40px;
}

.search-spinner {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #22d3ee;
}

/* Search Results */
.search-results {
    margin-top: 1rem;
    max-height: 200px;
    overflow-y: auto;
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background 0.2s;
}

.search-result-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.search-result-item--selected {
    background: rgba(34, 211, 238, 0.1);
    border: 1px solid rgba(34, 211, 238, 0.3);
}

.guest-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #a78bfa, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
}

.guest-avatar--lg {
    width: 48px;
    height: 48px;
    font-size: 1.25rem;
}

.guest-name {
    color: #f1f5f9;
    font-weight: 500;
}

.guest-phone {
    color: #64748b;
    font-size: 0.8rem;
}

/* New Guest Form */
.new-guest-form {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

/* Selected Guest Card */
.selected-guest-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(34, 211, 238, 0.05);
    border: 1px solid rgba(34, 211, 238, 0.2);
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

/* Rooms Grid */
.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 0.75rem;
    max-height: 200px;
    overflow-y: auto;
}

.room-card-mini {
    padding: 0.75rem;
    background: rgba(15, 23, 42, 0.5);
    border: 2px solid transparent;
    border-radius: 0.5rem;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
}

.room-card-mini:hover {
    border-color: rgba(34, 211, 238, 0.3);
}

.room-card-mini--selected {
    border-color: #22d3ee;
    background: rgba(34, 211, 238, 0.1);
}

.room-number {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: #f1f5f9;
}

.room-type {
    display: block;
    font-size: 0.7rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.room-rate {
    display: block;
    font-size: 0.8rem;
    color: #22c55e;
    font-weight: 600;
}

/* Confirmation Summary */
.confirmation-summary {
    padding: 1rem;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row--total {
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-weight: 600;
}

.summary-label {
    color: #94a3b8;
}

.summary-value {
    color: #f1f5f9;
}

/* Input with prefix */
.input-with-prefix {
    position: relative;
}

.input-prefix {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.input-with-prefix .form-input {
    padding-left: 32px;
}

/* Form Row */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 2rem 1rem;
}

/* Buttons */
.btn--ghost {
    background: transparent;
    color: #94a3b8;
}

.btn--ghost:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #e2e8f0;
}

.btn--success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
}

/* Form elements matching existing style */
.form-label {
    display: block;
    color: #e2e8f0;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

@media (max-width: 640px) {
    .quick-checkin-modal {
        max-height: 100vh;
        border-radius: 0;
    }
    
    .steps-indicator {
        gap: 1rem;
    }
    
    .step-label {
        display: none;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function quickCheckin() {
    return {
        isOpen: false,
        currentStep: 1,
        searchQuery: '',
        searchResults: [],
        isSearching: false,
        selectedGuest: null,
        showNewGuestForm: false,
        newGuest: {
            first_name: '',
            last_name: '',
            phone: '',
            id_type: '',
            id_number: ''
        },
        today: new Date().toISOString().split('T')[0],
        checkOutDate: '',
        nights: 1,
        availableRooms: [],
        isLoadingRooms: false,
        selectedRoom: null,
        advanceAmount: 0,
        isSubmitting: false,
        
        init() {
            // Set default checkout to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.checkOutDate = tomorrow.toISOString().split('T')[0];
            
            this.$watch('checkOutDate', () => this.calculateNights());
        },
        
        open() {
            this.isOpen = true;
            this.reset();
            this.$nextTick(() => lucide.createIcons());
        },
        
        close() {
            this.isOpen = false;
        },
        
        reset() {
            this.currentStep = 1;
            this.searchQuery = '';
            this.searchResults = [];
            this.selectedGuest = null;
            this.showNewGuestForm = false;
            this.newGuest = { first_name: '', last_name: '', phone: '', id_type: '', id_number: '' };
            this.selectedRoom = null;
            this.advanceAmount = 0;
            
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.checkOutDate = tomorrow.toISOString().split('T')[0];
            this.nights = 1;
        },
        
        async searchGuests() {
            if (this.searchQuery.length < 3) {
                this.searchResults = [];
                return;
            }
            
            this.isSearching = true;
            
            try {
                const res = await fetch(`/api/guests/search?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await res.json();
                
                if (data.success) {
                    this.searchResults = data.data;
                }
            } catch (e) {
                console.error('Search failed:', e);
            } finally {
                this.isSearching = false;
            }
        },
        
        selectGuest(guest) {
            this.selectedGuest = guest;
            this.showNewGuestForm = false;
            this.newGuest.phone = guest.phone;
        },
        
        calculateNights() {
            const checkIn = new Date(this.today);
            const checkOut = new Date(this.checkOutDate);
            const diff = checkOut - checkIn;
            this.nights = Math.max(1, Math.ceil(diff / (1000 * 60 * 60 * 24)));
        },
        
        async loadAvailableRooms() {
            this.isLoadingRooms = true;
            
            try {
                const res = await fetch(`/api/rooms/available?check_in=${this.today}&check_out=${this.checkOutDate}`);
                const data = await res.json();
                
                if (data.success) {
                    this.availableRooms = data.data;
                }
            } catch (e) {
                console.error('Failed to load rooms:', e);
            } finally {
                this.isLoadingRooms = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },
        
        selectRoom(room) {
            this.selectedRoom = room;
        },
        
        get canProceed() {
            if (this.currentStep === 1) {
                return this.selectedGuest || (this.showNewGuestForm && this.newGuest.first_name && this.newGuest.phone);
            }
            if (this.currentStep === 2) {
                return this.selectedRoom && this.checkOutDate;
            }
            return true;
        },
        
        nextStep() {
            if (!this.canProceed) return;
            
            if (this.currentStep === 1) {
                this.loadAvailableRooms();
            }
            
            this.currentStep++;
            this.$nextTick(() => lucide.createIcons());
        },
        
        prevStep() {
            this.currentStep--;
            this.$nextTick(() => lucide.createIcons());
        },
        
        async confirmCheckin() {
            this.isSubmitting = true;
            
            try {
                let guestId = this.selectedGuest?.id;
                
                // Create new guest if needed
                if (!guestId && this.showNewGuestForm) {
                    const guestRes = await fetch('/api/guests', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.newGuest)
                    });
                    const guestData = await guestRes.json();
                    
                    if (!guestData.success) {
                        throw new Error(guestData.error || 'Failed to create guest');
                    }
                    guestId = guestData.guest_id;
                }
                
                // Create booking with immediate check-in
                const bookingData = {
                    guest_id: guestId,
                    room_id: this.selectedRoom.id,
                    check_in_date: this.today,
                    check_out_date: this.checkOutDate,
                    rate_per_night: this.selectedRoom.base_rate,
                    adults: 1,
                    source: 'walk_in',
                    advance_amount: this.advanceAmount,
                    immediate_checkin: true // Flag for immediate check-in
                };
                
                const res = await fetch('/api/bookings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(bookingData)
                });
                
                const data = await res.json();
                
                if (data.success) {
                    // Show success and close
                    alert(`✅ Guest checked in successfully!\n\nBooking #: ${data.booking_number}\nRoom: ${this.selectedRoom.room_number}`);
                    this.close();
                    window.location.reload();
                } else {
                    throw new Error(data.error || 'Failed to create booking');
                }
            } catch (e) {
                alert('❌ Error: ' + e.message);
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
