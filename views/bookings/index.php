<?php
/**
 * HotelOS - Bookings List View
 * Shows today's arrivals, departures, and in-house guests
 */



$pageTitle = 'Front Desk | HotelOS';
$activeNav = 'bookings';
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Front Desk</h1>
        <p class="page-subtitle">Manage arrivals, departures & in-house guests</p>
    </div>
    <div class="page-header__right">
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
    <button class="tab-btn active" data-tab="arrivals">🛬 Arrivals</button>
    <button class="tab-btn" data-tab="departures">🛫 Departures</button>
    <button class="tab-btn" data-tab="inhouse">🏨 In-House</button>
</div>

<!-- Tab Content -->
<div class="tab-content">
    <!-- Arrivals Table -->
    <div class="tab-pane active" id="arrivals-tab">
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
    <div class="tab-pane" id="departures-tab">
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
    <div class="tab-pane" id="inhouse-tab">
        <div class="table-container glass-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Guest</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
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
});

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
</script>
