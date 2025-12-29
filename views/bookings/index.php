<?php
/**
 * HotelOS - Bookings List View
 * Shows today's arrivals, departures, and in-house guests
 */



$pageTitle = 'Front Desk | HotelOS';
$activeNav = 'bookings';
$currentTab = $_GET['tab'] ?? 'arrivals';
$validTabs = ['arrivals', 'departures', 'inhouse'];
if (!in_array($currentTab, $validTabs)) $currentTab = 'arrivals';
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Front Desk</h1>
        <p class="page-subtitle">Manage arrivals, departures & in-house guests</p>
    </div>
    <div class="page-header__right">
        <!-- Guest Search -->
        <div class="search-box">
            <input 
                type="text" 
                id="guest-search" 
                class="search-input" 
                placeholder="🔍 Search by phone or name..."
                autocomplete="off"
            >
            <div id="search-results" class="search-results"></div>
        </div>
        <a href="/bookings/create" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Booking
        </a>
    </div>
</div>

<!-- Quick Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--arrivals">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                <path d="m9 12 2 2 4-4"/>
            </svg>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__value" id="arrivals-count">--</span>
            <span class="stat-card__label">Today's Arrivals</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--departures">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                <line x1="6" y1="1" x2="6" y2="4"/>
                <line x1="10" y1="1" x2="10" y2="4"/>
            </svg>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__value" id="departures-count">--</span>
            <span class="stat-card__label">Today's Departures</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--inhouse">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div class="stat-card__content">
            <span class="stat-card__value" id="inhouse-count">--</span>
            <span class="stat-card__label">In-House Guests</span>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="tabs">
    <button class="tab-btn <?= $currentTab === 'arrivals' ? 'active' : '' ?>" data-tab="arrivals">🛬 Arrivals</button>
    <button class="tab-btn <?= $currentTab === 'departures' ? 'active' : '' ?>" data-tab="departures">🛫 Departures</button>
    <button class="tab-btn <?= $currentTab === 'inhouse' ? 'active' : '' ?>" data-tab="inhouse">🏨 In-House</button>
</div>

<!-- Tab Content -->
<div class="tab-content">
    <!-- Arrivals Table -->
    <div class="tab-pane <?= $currentTab === 'arrivals' ? 'active' : '' ?>" id="arrivals-tab">
        <div class="table-container glass-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Booking #</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Nights</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="arrivals-table">
                    <tr><td colspan="6" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Departures Table -->
    <div class="tab-pane <?= $currentTab === 'departures' ? 'active' : '' ?>" id="departures-tab">
        <div class="table-container glass-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Booking #</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="departures-table">
                    <tr><td colspan="5" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- In-House Table -->
    <div class="tab-pane <?= $currentTab === 'inhouse' ? 'active' : '' ?>" id="inhouse-tab">
        <div class="table-container glass-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Guest</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="inhouse-table">
                    <tr><td colspan="5" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: 0.25rem;
}

.page-subtitle {
    color: #94a3b8;
    font-size: 0.95rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
}

.stat-card__icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
}

.stat-card__icon--arrivals { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.stat-card__icon--departures { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.stat-card__icon--inhouse { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }

.stat-card__value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #f1f5f9;
}

.stat-card__label {
    color: #94a3b8;
    font-size: 0.85rem;
}

.tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 0.5rem;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 0.95rem;
    cursor: pointer;
    border-radius: 0.5rem 0.5rem 0 0;
    transition: all 0.2s;
}

.tab-btn:hover { color: #f1f5f9; }

.tab-btn.active {
    background: rgba(34, 211, 238, 0.1);
    color: #22d3ee;
    border-bottom: 2px solid #22d3ee;
}

.tab-pane { display: none; }
.tab-pane.active { display: block; }

.table-container {
    padding: 1.5rem;
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    font-weight: 600;
    color: #94a3b8;
    font-size: 0.85rem;
    text-transform: uppercase;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.data-table td {
    padding: 1rem;
    color: #e2e8f0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.data-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.btn--sm {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.btn--success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
}

.btn--warning {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff;
}

.text-center { text-align: center; }

/* Search Box */
.search-box {
    position: relative;
    margin-right: 1rem;
}

.search-input {
    width: 250px;
    padding: 0.625rem 1rem;
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 0.5rem;
    color: #f1f5f9;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #22d3ee;
    box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: rgba(30, 41, 59, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    margin-top: 0.25rem;
    max-height: 300px;
    overflow-y: auto;
    z-index: 100;
    display: none;
}

.search-results.active {
    display: block;
}

.search-result-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    transition: background 0.15s;
}

.search-result-item:hover {
    background: rgba(34, 211, 238, 0.1);
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-name {
    font-weight: 600;
    color: #f1f5f9;
}

.search-result-meta {
    font-size: 0.8rem;
    color: #94a3b8;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .page-header__right {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .search-box {
        margin-right: 0;
    }
    
    .search-input {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(this.dataset.tab + '-tab').classList.add('active');
        });
    });
    
    // Load data
    loadArrivals();
    loadDepartures();
    loadInHouse();
    
    // Guest Search
    initGuestSearch();
});

// Guest Search Functionality
function initGuestSearch() {
    const searchInput = document.getElementById('guest-search');
    const searchResults = document.getElementById('search-results');
    let debounceTimer;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();
        
        if (query.length < 3) {
            searchResults.classList.remove('active');
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetch(`/api/guests/search?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        searchResults.innerHTML = data.data.map(g => `
                            <div class="search-result-item" onclick="viewGuestBookings(${g.id}, '${g.first_name} ${g.last_name}')">
                                <div class="search-result-name">${g.first_name} ${g.last_name}</div>
                                <div class="search-result-meta">${g.phone} • ${g.total_stays || 0} stays</div>
                            </div>
                        `).join('');
                        searchResults.classList.add('active');
                    } else {
                        searchResults.innerHTML = `
                            <div class="search-result-item">
                                <div class="search-result-meta">No guests found</div>
                            </div>
                        `;
                        searchResults.classList.add('active');
                    }
                });
        }, 300);
    });
    
    // Hide results on blur
    searchInput.addEventListener('blur', function() {
        setTimeout(() => searchResults.classList.remove('active'), 200);
    });
    
    // Show results on focus if has value
    searchInput.addEventListener('focus', function() {
        if (this.value.length >= 3) {
            searchResults.classList.add('active');
        }
    });
}

function viewGuestBookings(guestId, guestName) {
    alert(`Guest: ${guestName}\n\nFeature coming soon: View guest history and create booking for this guest.`);
    // TODO: Open guest details modal or navigate to guest page
}

function loadArrivals() {
    fetch('/api/bookings/today-arrivals')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('arrivals-count').textContent = data.data.length;
                renderArrivalsTable(data.data);
            }
        });
}

function loadDepartures() {
    fetch('/api/bookings/today-departures')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('departures-count').textContent = data.data.length;
                renderDeparturesTable(data.data);
            }
        });
}

function loadInHouse() {
    fetch('/api/bookings/in-house')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('inhouse-count').textContent = data.data.length;
                renderInHouseTable(data.data);
            }
        });
}

function renderArrivalsTable(bookings) {
    const tbody = document.getElementById('arrivals-table');
    if (bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No arrivals today</td></tr>';
        return;
    }
    
    tbody.innerHTML = bookings.map(b => `
        <tr>
            <td><strong>${b.booking_number}</strong></td>
            <td>${b.first_name} ${b.last_name}<br><small>${b.guest_phone}</small></td>
            <td>${b.room_number || 'Unassigned'}<br><small>${b.room_type_name}</small></td>
            <td>${b.nights}</td>
            <td>₹${parseFloat(b.grand_total).toLocaleString()}</td>
            <td>
                <button class="btn btn--success btn--sm" onclick="checkIn(${b.id})">Check In</button>
            </td>
        </tr>
    `).join('');
}

function renderDeparturesTable(bookings) {
    const tbody = document.getElementById('departures-table');
    if (bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No departures today</td></tr>';
        return;
    }
    
    tbody.innerHTML = bookings.map(b => `
        <tr>
            <td><strong>${b.booking_number}</strong></td>
            <td>${b.first_name} ${b.last_name}</td>
            <td>${b.room_number}</td>
            <td>₹${parseFloat(b.balance_amount || 0).toLocaleString()}</td>
            <td>
                <button class="btn btn--warning btn--sm" onclick="checkOut(${b.id})">Check Out</button>
            </td>
        </tr>
    `).join('');
}

function renderInHouseTable(bookings) {
    const tbody = document.getElementById('inhouse-table');
    if (bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No guests in-house</td></tr>';
        return;
    }
    
    tbody.innerHTML = bookings.map(b => `
        <tr>
            <td><strong>${b.room_number}</strong></td>
            <td>${b.first_name} ${b.last_name}<br><small>${b.guest_phone}</small></td>
            <td>${b.check_in_date}</td>
            <td>${b.check_out_date}</td>
            <td><span class="badge badge--success">Checked In</span></td>
            <td>
                <button class="btn btn--sm" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.4);" onclick="openMoveModal(${b.id}, '${b.room_number}', '${b.first_name} ${b.last_name}')">Move</button>
            </td>
        </tr>
    `).join('');
}

function checkIn(bookingId) {
    if (!confirm('Check in this guest?')) return;
    
    fetch(`/api/bookings/${bookingId}/check-in`, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Guest checked in successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
}

function checkOut(bookingId) {
    if (!confirm('Check out this guest? This will generate the final bill.')) return;
    
    fetch(`/api/bookings/${bookingId}/check-out`, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(`Guest checked out!\nFinal Amount: ₹${data.grand_total}\nBalance Due: ₹${data.balance}`);
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
}
// Room Move Functionality
let currentMoveBookingId = null;

function openMoveModal(bookingId, currentRoom, guestName) {
    currentMoveBookingId = bookingId;
    document.getElementById('move-modal').classList.add('active');
    document.getElementById('move-guest-name').innerText = guestName;
    document.getElementById('move-current-room').innerText = currentRoom;
    
    // Load available rooms
    const select = document.getElementById('move-new-room');
    select.innerHTML = '<option>Loading...</option>';
    
    // Fetch rooms available for today
    fetch(`/api/rooms/available?check_in=${new Date().toISOString().split('T')[0]}&check_out=${new Date(Date.now() + 86400000).toISOString().split('T')[0]}`)
       .then(r => r.json())
       .then(data => {
           if(data.success && data.data.length > 0) {
               select.innerHTML = data.data.map(r => `<option value="${r.id}">${r.room_number} (${r.type_name} - ₹${r.price})</option>`).join('');
           } else {
               select.innerHTML = '<option value="">No rooms available</option>';
           }
       });
}

function closeMoveModal() {
    document.getElementById('move-modal').classList.remove('active');
    currentMoveBookingId = null;
}

function submitMove() {
    if (!currentMoveBookingId) return;
    
    const newRoomId = document.getElementById('move-new-room').value;
    const notes = document.getElementById('move-notes').value;
    const rateAction = document.getElementById('move-rate-action').value;
    
    if (!newRoomId) { alert('Please select a room'); return; }
    
    if (!confirm('Confirm room move? This will change room status and log the move.')) return;
    
    fetch(`/api/bookings/${currentMoveBookingId}/move-room`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            new_room_id: newRoomId,
            reason: 'requested',
            notes: notes,
            rate_action: rateAction
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Room moved successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to move room'));
        }
    });
}
</script>

<!-- Room Move Modal -->
<div id="move-modal" class="modal-overlay">
    <div class="modal-content glass-card">
        <div class="p-6">
            <h2 class="text-xl font-bold text-white mb-4">Move Room</h2>
            
            <div class="mb-4 text-sm text-slate-300 bg-slate-800 p-3 rounded-lg">
                Moving <strong id="move-guest-name" class="text-white"></strong> 
                from Room <strong id="move-current-room" class="text-cyan-400"></strong>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Select New Room</label>
                    <select id="move-new-room" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white">
                        <option>Select Room...</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Rate Adjustment</label>
                    <select id="move-rate-action" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white">
                        <option value="keep_original">Keep Original Rate (Free Upgrade/Swap)</option>
                        <option value="use_new_rate">Apply New Room Rate</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Reason / Notes</label>
                    <textarea id="move-notes" rows="2" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white" placeholder="e.g. AC issue, upgrade..."></textarea>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button onclick="closeMoveModal()" class="flex-1 py-2 bg-slate-700 rounded-lg text-slate-200">Cancel</button>
                    <button onclick="submitMove()" class="flex-1 py-2 bg-indigo-600 rounded-lg text-white font-bold hover:bg-indigo-500">Confirm Move</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
}
.modal-overlay.active { display: flex; }
.modal-content { width: 100%; max-width: 400px; }
</style>
