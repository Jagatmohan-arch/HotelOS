<?php
/**
 * HotelOS - Mobile Bookings View
 * 
 * Card-based layout for 3" x 6-7" phone screens (320-375px)
 * Shows arrivals, departures, in-house as swipeable cards
 * 
 * Design principles:
 * - Card layout instead of tables
 * - Large touch targets (48px min)
 * - Swipe tabs
 * - Quick action buttons
 */

$currentTab = $_GET['tab'] ?? 'arrivals';
$validTabs = ['arrivals', 'departures', 'inhouse'];
if (!in_array($currentTab, $validTabs)) $currentTab = 'arrivals';
?>

<!-- Mobile Bookings View -->
<div class="mobile-bookings md:hidden" x-data="mobileBookings()">
    
    <!-- Mobile Header -->
    <div class="mobile-header">
        <h1 class="mobile-title">Front Desk</h1>
        <button @click="$dispatch('open-quick-checkin')" class="mobile-checkin-btn">
            <i data-lucide="log-in" class="w-5 h-5"></i>
        </button>
    </div>
    
    <!-- Search Bar -->
    <div class="mobile-search">
        <div class="search-input-wrap">
            <i data-lucide="search" class="search-icon"></i>
            <input 
                type="text" 
                x-model="searchQuery"
                @input.debounce.300ms="searchGuests()"
                class="mobile-search-input" 
                placeholder="Search guest by phone..."
            >
            <button x-show="searchQuery.length > 0" @click="clearSearch()" class="search-clear">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <!-- Search Results -->
        <div x-show="searchResults.length > 0" class="search-results-mobile">
            <template x-for="guest in searchResults" :key="guest.id">
                <a :href="'/guests/' + guest.id" class="search-result-card">
                    <div class="result-avatar" x-text="guest.name.charAt(0)"></div>
                    <div class="result-info">
                        <div class="result-name" x-text="guest.name"></div>
                        <div class="result-phone" x-text="guest.phone"></div>
                    </div>
                    <i data-lucide="chevron-right" class="w-5 h-5 opacity-50"></i>
                </a>
            </template>
        </div>
    </div>
    
    <!-- Tab Pills -->
    <div class="tab-pills">
        <button 
            @click="switchTab('arrivals')"
            class="tab-pill"
            :class="{ 'tab-pill--active': activeTab === 'arrivals' }"
        >
            <i data-lucide="log-in" class="w-4 h-4"></i>
            <span>Arrivals</span>
            <span class="tab-count" x-text="counts.arrivals"></span>
        </button>
        <button 
            @click="switchTab('departures')"
            class="tab-pill"
            :class="{ 'tab-pill--active': activeTab === 'departures' }"
        >
            <i data-lucide="log-out" class="w-4 h-4"></i>
            <span>Departures</span>
            <span class="tab-count" x-text="counts.departures"></span>
        </button>
        <button 
            @click="switchTab('inhouse')"
            class="tab-pill"
            :class="{ 'tab-pill--active': activeTab === 'inhouse' }"
        >
            <i data-lucide="bed-double" class="w-4 h-4"></i>
            <span>In-House</span>
            <span class="tab-count" x-text="counts.inhouse"></span>
        </button>
    </div>
    
    <!-- Loading State -->
    <div x-show="loading" class="loading-state">
        <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-cyan-400"></i>
        <span>Loading...</span>
    </div>
    
    <!-- Booking Cards -->
    <div x-show="!loading" class="booking-cards">
        <template x-for="booking in filteredBookings" :key="booking.id">
            <div class="booking-card" :class="'booking-card--' + booking.status">
                <!-- Card Header -->
                <div class="card-header">
                    <div class="room-badge" x-text="booking.room_number || 'TBA'"></div>
                    <div class="guest-info">
                        <div class="guest-name" x-text="booking.guest_name"></div>
                        <div class="guest-phone" x-text="booking.guest_phone"></div>
                    </div>
                    <div class="status-badge" :class="'status--' + booking.status" x-text="booking.status"></div>
                </div>
                
                <!-- Card Body -->
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Check-in</span>
                        <span class="info-value" x-text="formatDate(booking.check_in)"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-out</span>
                        <span class="info-value" x-text="formatDate(booking.check_out)"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nights</span>
                        <span class="info-value" x-text="booking.nights + ' night(s)'"></span>
                    </div>
                    <div class="info-row info-row--highlight">
                        <span class="info-label">Amount</span>
                        <span class="info-value" x-text="'₹' + formatNumber(booking.grand_total || booking.room_total)"></span>
                    </div>
                </div>
                
                <!-- Card Actions -->
                <div class="card-actions">
                    <template x-if="activeTab === 'arrivals' && booking.status === 'confirmed'">
                        <button @click="checkIn(booking.id)" class="action-btn action-btn--success">
                            <i data-lucide="log-in" class="w-4 h-4"></i>
                            Check-in
                        </button>
                    </template>
                    <template x-if="activeTab === 'departures' || booking.status === 'checked_in'">
                        <button @click="checkOut(booking.id)" class="action-btn action-btn--warning">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                            Check-out
                        </button>
                    </template>
                    <a :href="'/bookings/' + booking.id" class="action-btn action-btn--secondary">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        View
                    </a>
                </div>
            </div>
        </template>
        
        <!-- Empty State -->
        <div x-show="filteredBookings.length === 0 && !loading" class="empty-state">
            <i data-lucide="inbox" class="w-12 h-12 text-slate-600"></i>
            <p>No bookings found</p>
        </div>
    </div>
    
    <!-- Bottom Spacer -->
    <div class="nav-spacer"></div>
</div>

<style>
/* Mobile Bookings - Optimized for 320-375px */
.mobile-bookings {
    padding: 12px;
    padding-bottom: 100px;
}

/* Header */
.mobile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.mobile-title {
    font-size: 20px;
    font-weight: 700;
    color: #f1f5f9;
}

.mobile-checkin-btn {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #34d399, #10b981);
    color: #0f172a;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Search */
.mobile-search {
    margin-bottom: 12px;
    position: relative;
}

.search-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 12px;
    width: 18px;
    height: 18px;
    color: #64748b;
}

.mobile-search-input {
    width: 100%;
    height: 48px;
    padding: 0 44px 0 40px;
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #f1f5f9;
    font-size: 15px;
}

.mobile-search-input::placeholder {
    color: #64748b;
}

.search-clear {
    position: absolute;
    right: 8px;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-results-mobile {
    position: absolute;
    top: 52px;
    left: 0;
    right: 0;
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 50;
}

.search-result-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: #f1f5f9;
    text-decoration: none;
}

.result-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
}

.result-info {
    flex: 1;
}

.result-name {
    font-size: 14px;
    font-weight: 500;
}

.result-phone {
    font-size: 12px;
    color: #64748b;
}

/* Tab Pills */
.tab-pills {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;
    margin-bottom: 12px;
    -webkit-overflow-scrolling: touch;
}

.tab-pills::-webkit-scrollbar {
    display: none;
}

.tab-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    border-radius: 20px;
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #94a3b8;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    transition: all 0.2s;
}

.tab-pill--active {
    background: rgba(34, 211, 238, 0.15);
    border-color: rgba(34, 211, 238, 0.3);
    color: #22d3ee;
}

.tab-count {
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.1);
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tab-pill--active .tab-count {
    background: rgba(34, 211, 238, 0.2);
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px;
    color: #64748b;
}

/* Booking Cards */
.booking-cards {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.booking-card {
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    overflow: hidden;
}

.booking-card--confirmed {
    border-left: 4px solid #22d3ee;
}

.booking-card--checked_in {
    border-left: 4px solid #34d399;
}

.booking-card--reserved {
    border-left: 4px solid #fbbf24;
}

/* Card Header */
.card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.room-badge {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
    color: white;
}

.guest-info {
    flex: 1;
    min-width: 0;
}

.guest-name {
    font-size: 15px;
    font-weight: 600;
    color: #f1f5f9;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.guest-phone {
    font-size: 12px;
    color: #64748b;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status--confirmed {
    background: rgba(34, 211, 238, 0.15);
    color: #22d3ee;
}

.status--checked_in {
    background: rgba(52, 211, 153, 0.15);
    color: #34d399;
}

.status--reserved {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

/* Card Body */
.card-body {
    padding: 12px 14px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.03);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 12px;
    color: #64748b;
}

.info-value {
    font-size: 13px;
    color: #e2e8f0;
    font-weight: 500;
}

.info-row--highlight .info-value {
    font-size: 16px;
    font-weight: 700;
    color: #22d3ee;
}

/* Card Actions */
.card-actions {
    display: flex;
    gap: 8px;
    padding: 12px 14px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    min-height: 44px;
}

.action-btn--success {
    background: linear-gradient(135deg, #34d399, #10b981);
    color: #0f172a;
}

.action-btn--warning {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #0f172a;
}

.action-btn--secondary {
    background: rgba(255, 255, 255, 0.05);
    color: #94a3b8;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 60px 20px;
    color: #64748b;
    text-align: center;
}

/* Nav Spacer */
.nav-spacer {
    height: 80px;
}
</style>

<script>
function mobileBookings() {
    return {
        activeTab: '<?= $currentTab ?>',
        loading: true,
        bookings: [],
        searchQuery: '',
        searchResults: [],
        counts: {
            arrivals: 0,
            departures: 0,
            inhouse: 0
        },
        
        get filteredBookings() {
            return this.bookings.filter(b => {
                if (this.activeTab === 'arrivals') {
                    return b.status === 'confirmed' || b.status === 'reserved' || b.status === 'pending';
                } else if (this.activeTab === 'departures') {
                    // Departures = Checked in AND checkout date is today
                    if (b.status !== 'checked_in') return false;
                    const today = new Date().toISOString().split('T')[0];
                    return b.check_out_date && b.check_out_date.startsWith(today);
                } else {
                    // In-house = All checked in
                    return b.status === 'checked_in';
                }
            });
        },
        
        init() {
            this.loadBookings();
        },
        
        async loadBookings() {
            this.loading = true;
            try {
                const response = await fetch('/api/bookings/today');
                const data = await response.json();
                this.bookings = data.bookings || [];
                this.counts = {
                    arrivals: data.arrivals_count || 0,
                    departures: data.departures_count || 0,
                    inhouse: data.inhouse_count || 0
                };
            } catch (e) {
                console.error('Failed to load bookings', e);
            } finally {
                this.loading = false;
            }
        },
        
        switchTab(tab) {
            this.activeTab = tab;
            // Haptic feedback
            if (navigator.vibrate) navigator.vibrate(10);
        },
        
        async searchGuests() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            
            try {
                const response = await fetch(`/api/guests/search?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();
                this.searchResults = data.data || [];
            } catch (e) {
                console.error('Search failed', e);
            }
        },
        
        clearSearch() {
            this.searchQuery = '';
            this.searchResults = [];
        },
        
        async checkIn(bookingId) {
            if (!confirm('Confirm check-in?')) return;
            
            try {
                const response = await fetch(`/api/bookings/${bookingId}/check-in`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                if (response.ok) {
                    // Show success
                    alert('Check-in successful!');
                    this.loadBookings();
                }
            } catch (e) {
                alert('Check-in failed. Please try again.');
            }
        },
        
        async checkOut(bookingId) {
            // Navigate to checkout page for bill review
            window.location.href = `/bookings/${bookingId}/checkout`;
        },
        
        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
        },
        
        formatNumber(num) {
            return num ? Number(num).toLocaleString('en-IN') : '0';
        }
    }
}
</script>
