<?php
/**
 * HotelOS - New Booking Wizard
 * 3-Panel wizard: Guest → Room → Payment
 */



$pageTitle = 'New Booking | HotelOS';
$activeNav = 'bookings';

// Fetch room types for dropdown
$roomTypes = $roomTypes ?? [];
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/bookings" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Front Desk
        </a>
        <h1 class="page-title">New Booking</h1>
    </div>
</div>

<!-- Wizard Steps Indicator -->
<div class="wizard-steps">
    <div class="wizard-step active" data-step="1">
        <span class="wizard-step__number">1</span>
        <span class="wizard-step__label">Guest Details</span>
    </div>
    <div class="wizard-step" data-step="2">
        <span class="wizard-step__number">2</span>
        <span class="wizard-step__label">Room Selection</span>
    </div>
    <div class="wizard-step" data-step="3">
        <span class="wizard-step__number">3</span>
        <span class="wizard-step__label">Confirmation</span>
    </div>
</div>

<form id="booking-form" class="booking-wizard glass-card">
    <!-- Step 1: Guest Details -->
    <div class="wizard-panel active" id="panel-1">
        <h2 class="panel-title">Guest Information</h2>
        
        <div class="form-row">
            <div class="form-group form-group--full">
                <label for="phone">Mobile Number</label>
                <input type="tel" id="phone" name="phone" class="form-input form-input--lg" 
                       placeholder="Enter mobile number to search guest..." maxlength="10" required>
                <p class="form-hint">Type to search existing guests or enter new number</p>
            </div>
        </div>
        
        <!-- OCR Scan Section -->
        <div class="ocr-section">
            <div class="ocr-divider">
                <span>OR</span>
            </div>
            <div id="ocr-upload-container"></div>
        </div>
        
        <div id="guest-results" class="guest-results" style="display: none;"></div>
        
        <input type="hidden" id="guest_id" name="guest_id" value="">
        
        <div id="guest-form" class="guest-form" style="display: none;">
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Title</label>
                    <select id="title" name="title" class="form-input">
                        <option value="Mr">Mr</option>
                        <option value="Mrs">Mrs</option>
                        <option value="Ms">Ms</option>
                        <option value="Dr">Dr</option>
                    </select>
                </div>
                <div class="form-group form-group--grow">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-input" required>
                </div>
                <div class="form-group form-group--grow">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-input">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="guest@email.com">
                </div>
                <div class="form-group">
                    <label for="id_type">ID Type</label>
                    <select id="id_type" name="id_type" class="form-input">
                        <option value="">Select...</option>
                        <option value="aadhaar">Aadhaar</option>
                        <option value="passport">Passport</option>
                        <option value="driving_license">Driving License</option>
                        <option value="voter_id">Voter ID</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_number">ID Number</label>
                    <input type="text" id="id_number" name="id_number" class="form-input">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group--full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-input" rows="2"></textarea>
                </div>
            </div>
        </div>
        
        <div class="panel-actions">
            <button type="button" class="btn btn--secondary" disabled>Previous</button>
            <button type="button" class="btn btn--primary" onclick="nextStep(2)">
                Next: Room Selection
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Step 2: Room Selection -->
    <div class="wizard-panel" id="panel-2">
        <h2 class="panel-title">Room Selection</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label for="check_in_date">Check-in Date *</label>
                <input type="date" id="check_in_date" name="check_in_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="check_out_date">Check-out Date *</label>
                <input type="date" id="check_out_date" name="check_out_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="room_type_id">Room Type</label>
                <select id="room_type_id" name="room_type_id" class="form-input">
                    <option value="">All Types</option>
                </select>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn--secondary" onclick="searchAvailableRooms()">
                    Search Available
                </button>
            </div>
        </div>
        
        <div id="available-rooms" class="rooms-grid">
            <p class="text-center text-muted">Select dates and click "Search Available" to see rooms</p>
        </div>
        
        <input type="hidden" id="room_id" name="room_id" value="">
        <input type="hidden" id="rate_per_night" name="rate_per_night" value="">
        
        <div class="form-row">
            <div class="form-group">
                <label for="adults">Adults</label>
                <input type="number" id="adults" name="adults" class="form-input" value="1" min="1" max="6">
            </div>
            <div class="form-group">
                <label for="children">Children</label>
                <input type="number" id="children" name="children" class="form-input" value="0" min="0" max="4">
            </div>
            <div class="form-group">
                <label for="source">Booking Source</label>
                <select id="source" name="source" class="form-input">
                    <option value="walk_in">Walk-in</option>
                    <option value="phone">Phone</option>
                    <option value="website">Website</option>
                    <option value="booking_com">Booking.com</option>
                    <option value="makemytrip">MakeMyTrip</option>
                    <option value="goibibo">Goibibo</option>
                    <option value="agoda">Agoda</option>
                </select>
            </div>
        </div>
        
        <div class="panel-actions">
            <button type="button" class="btn btn--secondary" onclick="prevStep(1)">Previous</button>
            <button type="button" class="btn btn--primary" onclick="nextStep(3)">
                Next: Confirmation
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Step 3: Confirmation -->
    <div class="wizard-panel" id="panel-3">
        <h2 class="panel-title">Booking Confirmation</h2>
        
        <div class="summary-card">
            <h3>Booking Summary</h3>
            <div id="booking-summary">
                <!-- Filled by JS -->
            </div>
        </div>
        
        <!-- Visual Payment Selection -->
        <h3 class="section-subtitle">Payment Details</h3>
        <div class="payment-section">
            <div class="form-group mb-4">
                <label>Advance Payment (₹)</label>
                <input type="number" id="advance_amount" name="advance_amount" class="form-input form-input--lg" value="0" min="0" step="100">
            </div>
            
            <label class="mb-2 block text-sm font-medium text-slate-300">Payment Mode</label>
            <div class="payment-method-grid">
                <div class="payment-tile selected" onclick="selectPaymentMode('cash')">
                    <i data-lucide="banknote"></i>
                    <span>Cash</span>
                </div>
                <div class="payment-tile" onclick="selectPaymentMode('upi')">
                    <i data-lucide="qr-code"></i>
                    <span>UPI / Scan</span>
                </div>
                <div class="payment-tile" onclick="selectPaymentMode('card')">
                    <i data-lucide="credit-card"></i>
                    <span>Card</span>
                </div>
                <div class="payment-tile" onclick="selectPaymentMode('bank_transfer')">
                    <i data-lucide="building"></i>
                    <span>Bank / Net</span>
                </div>
                <div class="payment-tile" onclick="selectPaymentMode('ota_prepaid')">
                    <i data-lucide="globe"></i>
                    <span>OTA Prepaid</span>
                </div>
                <div class="payment-tile" onclick="selectPaymentMode('credit')">
                    <i data-lucide="briefcase"></i>
                    <span>Bill to Co.</span>
                </div>
            </div>
            
            <input type="hidden" id="payment_mode" name="payment_mode" value="cash">
            
            <div class="form-group mt-4">
                <label for="payment_reference">Reference / Transaction ID</label>
                <input type="text" id="payment_reference" name="payment_reference" class="form-input" placeholder="e.g. UPI Ref, Cheque No, Company Name">
            </div>
        </div>
        <script>
            function selectPaymentMode(mode) {
                document.querySelectorAll('.payment-tile').forEach(el => el.classList.remove('selected'));
                event.currentTarget.classList.add('selected');
                document.getElementById('payment_mode').value = mode;
                
                // Auto-focus reference if not cash
                if (mode !== 'cash') {
                    document.getElementById('payment_reference').focus();
                }
            }
        </script>
        
        <style>
            .payment-method-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            .payment-tile {
                background: rgba(15, 23, 42, 0.6);
                border: 1px solid rgba(148, 163, 184, 0.2);
                border-radius: 8px;
                padding: 15px 10px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                transition: all 0.2s;
                text-align: center;
                font-size: 0.9rem;
                color: #94a3b8;
            }
            .payment-tile:hover {
                background: rgba(15, 23, 42, 0.9);
                border-color: #22d3ee;
            }
            .payment-tile.selected {
                background: rgba(34, 211, 238, 0.15);
                border-color: #22d3ee;
                color: #22d3ee;
                box-shadow: 0 0 10px rgba(34, 211, 238, 0.1);
            }
            .payment-tile i {
                width: 24px;
                height: 24px;
            }
        </style>
        
        <div class="form-row">
            <div class="form-group form-group--full">
                <label for="special_requests">Special Requests</label>
                <textarea id="special_requests" name="special_requests" class="form-input" rows="2" placeholder="Early check-in, extra bed, etc."></textarea>
            </div>
        </div>
        
        <div class="panel-actions">
            <button type="button" class="btn btn--secondary" onclick="prevStep(2)">Previous</button>
            <button type="submit" class="btn btn--success btn--lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                Confirm Booking
            </button>
        </div>
    </div>
</form>

<style>
.back-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    text-decoration: none;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.back-link:hover { color: #22d3ee; }

.wizard-steps {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 2rem;
}

.wizard-step {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    opacity: 0.5;
}

.wizard-step.active { opacity: 1; }
.wizard-step.completed { opacity: 1; }

.wizard-step__number {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(148, 163, 184, 0.2);
    border-radius: 50%;
    font-weight: 600;
    color: #94a3b8;
}

.wizard-step.active .wizard-step__number {
    background: linear-gradient(135deg, #22d3ee, #06b6d4);
    color: #0f172a;
}

.wizard-step.completed .wizard-step__number {
    background: #22c55e;
    color: white;
}

.wizard-step__label {
    color: #94a3b8;
}

.wizard-step.active .wizard-step__label { color: #f1f5f9; }

.booking-wizard {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
}

@media (max-width: 640px) {
    .booking-wizard {
        padding: 1rem; /* Reduced from 2rem */
    }
    .wizard-steps {
        gap: 0.5rem;
    }
    .wizard-step__label {
        display: none; /* Hide labels on mobile to save space */
    }
}

.wizard-panel { display: none; }
.wizard-panel.active { display: block; }

.panel-title {
    font-size: 1.25rem;
    color: #f1f5f9;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.form-group { flex: 1; min-width: 150px; }
.form-group--full { flex: 100%; }
.form-group--grow { flex: 2; }

.form-group label {
    display: block;
    color: #e2e8f0;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 0.5rem;
    color: #f1f5f9;
    font-size: 0.95rem;
}

.form-input:focus {
    outline: none;
    border-color: #22d3ee;
    box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
}

.form-input--lg { font-size: 1.1rem; padding: 1rem; }

.form-hint {
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #64748b;
}

.guest-results {
    margin: 1rem 0;
    padding: 1rem;
    background: rgba(15, 23, 42, 0.5);
    border-radius: 0.5rem;
}

.guest-result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background 0.2s;
}

.guest-result-item:hover { background: rgba(34, 211, 238, 0.1); }

.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
    margin: 1.5rem 0;
}

.room-card {
    padding: 1rem;
    background: rgba(15, 23, 42, 0.5);
    border: 2px solid transparent;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}

.room-card:hover { border-color: rgba(34, 211, 238, 0.5); }
.room-card.selected { border-color: #22d3ee; background: rgba(34, 211, 238, 0.1); }

.room-card__number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #f1f5f9;
}

.room-card__type {
    font-size: 0.85rem;
    color: #94a3b8;
}

.room-card__rate {
    margin-top: 0.5rem;
    font-size: 1rem;
    color: #22c55e;
    font-weight: 600;
}

.summary-card {
    padding: 1.5rem;
    background: rgba(15, 23, 42, 0.5);
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
}

.summary-card h3 {
    color: #f1f5f9;
    margin-bottom: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.summary-row:last-child { border-bottom: none; font-weight: 600; }

.panel-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.btn--success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
}

.btn--lg { padding: 1rem 2rem; font-size: 1rem; }

.text-muted { color: #64748b; }

/* OCR Section */
.ocr-section {
    margin: 1.5rem 0;
}

.ocr-divider {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.ocr-divider::before,
.ocr-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.ocr-divider span {
    padding: 0 1rem;
    color: #64748b;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
</style>

<script>
let currentStep = 1;
let selectedGuest = null;
let selectedRoom = null;

document.addEventListener('DOMContentLoaded', function() {
    // Set default dates
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    document.getElementById('check_in_date').value = today.toISOString().split('T')[0];
    document.getElementById('check_out_date').value = tomorrow.toISOString().split('T')[0];
    
    // Load room types
    loadRoomTypes();
    
    // Initialize OCR Upload Component
    initOCRUpload();
    
    // Debounced phone search
    let debounceTimer;
    document.getElementById('phone').addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        const phone = e.target.value.replace(/\D/g, '');
        
        if (phone.length >= 4) {
            debounceTimer = setTimeout(() => searchGuests(phone), 500);
        } else {
            document.getElementById('guest-results').style.display = 'none';
        }
    });
    
    // Form submission
    document.getElementById('booking-form').addEventListener('submit', function(e) {
        e.preventDefault();
        createBooking();
    });
});

function loadRoomTypes() {
    fetch('/api/room-types')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('room_type_id');
                data.data.forEach(rt => {
                    select.innerHTML += `<option value="${rt.id}">${rt.name} (₹${rt.base_rate}/night)</option>`;
                });
            }
        });
}

function searchGuests(phone) {
    fetch(`/api/guests/search?q=${phone}`)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('guest-results');
            
            if (data.success && data.data.length > 0) {
                container.innerHTML = data.data.map(g => `
                    <div class="guest-result-item" onclick="selectGuest(${JSON.stringify(g).replace(/"/g, '&quot;')})">
                        <div>
                            <strong>${g.first_name} ${g.last_name}</strong>
                            <br><small>${g.phone} | ${g.total_stays} stays</small>
                        </div>
                        <span class="badge">${g.category}</span>
                    </div>
                `).join('');
                container.innerHTML += `
                    <div class="guest-result-item" onclick="createNewGuest('${phone}')">
                        <strong>+ Add New Guest</strong>
                    </div>
                `;
                container.style.display = 'block';
            } else {
                container.innerHTML = `
                    <div class="guest-result-item" onclick="createNewGuest('${phone}')">
                        <strong>+ Add New Guest with ${phone}</strong>
                    </div>
                `;
                container.style.display = 'block';
            }
        });
}

function selectGuest(guest) {
    selectedGuest = guest;
    document.getElementById('guest_id').value = guest.id;
    document.getElementById('phone').value = guest.phone;
    document.getElementById('guest-results').style.display = 'none';
    document.getElementById('guest-form').style.display = 'block';
    
    // Fill form
    document.getElementById('first_name').value = guest.first_name;
    document.getElementById('last_name').value = guest.last_name || '';
    document.getElementById('email').value = guest.email || '';
    
    // Disable editing for existing guests
    document.getElementById('first_name').readOnly = true;
    document.getElementById('last_name').readOnly = true;
}

function createNewGuest(phone) {
    selectedGuest = null;
    document.getElementById('guest_id').value = '';
    document.getElementById('phone').value = phone;
    document.getElementById('guest-results').style.display = 'none';
    document.getElementById('guest-form').style.display = 'block';
    
    // Enable editing
    document.getElementById('first_name').readOnly = false;
    document.getElementById('last_name').readOnly = false;
    document.getElementById('first_name').value = '';
    document.getElementById('last_name').value = '';
    document.getElementById('email').value = '';
}

function searchAvailableRooms() {
    const checkIn = document.getElementById('check_in_date').value;
    const checkOut = document.getElementById('check_out_date').value;
    const roomTypeId = document.getElementById('room_type_id').value;
    
    if (!checkIn || !checkOut) {
        alert('Please select check-in and check-out dates');
        return;
    }
    
    let url = `/api/rooms/available?check_in=${checkIn}&check_out=${checkOut}`;
    if (roomTypeId) url += `&room_type_id=${roomTypeId}`;
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('available-rooms');
            
            if (data.success && data.data.length > 0) {
                container.innerHTML = data.data.map(room => `
                    <div class="room-card" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type_name}', ${room.base_rate})">
                        <div class="room-card__number">${room.room_number}</div>
                        <div class="room-card__type">${room.room_type_name}</div>
                        <div class="room-card__rate">₹${parseFloat(room.base_rate).toLocaleString()}/night</div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-center text-muted">No rooms available for selected dates</p>';
            }
        });
}

function selectRoom(id, number, type, rate) {
    selectedRoom = { id, number, type, rate };
    document.getElementById('room_id').value = id;
    document.getElementById('rate_per_night').value = rate;
    
    // Update UI
    document.querySelectorAll('.room-card').forEach(card => card.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
}

function nextStep(step) {
    // Validation
    if (currentStep === 1) {
        const guestId = document.getElementById('guest_id').value;
        const firstName = document.getElementById('first_name').value;
        const phone = document.getElementById('phone').value;
        
        if (!guestId && !firstName) {
            alert('Please select or create a guest');
            return;
        }
        if (!phone) {
            alert('Phone number is required');
            return;
        }
    }
    
    if (currentStep === 2) {
        if (!selectedRoom) {
            alert('Please select a room');
            return;
        }
    }
    
    // Update summary if going to step 3
    if (step === 3) {
        updateSummary();
    }
    
    goToStep(step);
}

function prevStep(step) {
    goToStep(step);
}

function goToStep(step) {
    currentStep = step;
    
    // Update panels
    document.querySelectorAll('.wizard-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + step).classList.add('active');
    
    // Update steps indicator
    document.querySelectorAll('.wizard-step').forEach(s => {
        const stepNum = parseInt(s.dataset.step);
        s.classList.remove('active', 'completed');
        if (stepNum === step) s.classList.add('active');
        if (stepNum < step) s.classList.add('completed');
    });
}

function updateSummary() {
    const checkIn = document.getElementById('check_in_date').value;
    const checkOut = document.getElementById('check_out_date').value;
    const nights = Math.ceil((new Date(checkOut) - new Date(checkIn)) / (1000 * 60 * 60 * 24));
    const rate = parseFloat(document.getElementById('rate_per_night').value) || 0;
    const total = nights * rate;
    
    document.getElementById('booking-summary').innerHTML = `
        <div class="summary-row">
            <span>Guest</span>
            <span>${document.getElementById('first_name').value} ${document.getElementById('last_name').value}</span>
        </div>
        <div class="summary-row">
            <span>Phone</span>
            <span>${document.getElementById('phone').value}</span>
        </div>
        <div class="summary-row">
            <span>Room</span>
            <span>${selectedRoom?.number} (${selectedRoom?.type})</span>
        </div>
        <div class="summary-row">
            <span>Check-in</span>
            <span>${checkIn}</span>
        </div>
        <div class="summary-row">
            <span>Check-out</span>
            <span>${checkOut}</span>
        </div>
        <div class="summary-row">
            <span>Nights × Rate</span>
            <span>${nights} × ₹${rate.toLocaleString()}</span>
        </div>
        <div class="summary-row" style="font-size: 1.1rem;">
            <span>Estimated Total</span>
            <span style="color: #22c55e;">₹${total.toLocaleString()}</span>
        </div>
    `;
}

async function createBooking() {
    // Create guest if new
    let guestId = document.getElementById('guest_id').value;
    
    if (!guestId) {
        const guestRes = await fetch('/api/guests', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                id_type: document.getElementById('id_type').value,
                id_number: document.getElementById('id_number').value,
                address: document.getElementById('address').value,
            })
        });
        
        const guestData = await guestRes.json();
        if (!guestData.success) {
            alert('Failed to create guest: ' + (guestData.error || 'Unknown error'));
            return;
        }
        guestId = guestData.guest_id;
    }
    
    // Create booking
    const bookingData = {
        guest_id: guestId,
        room_id: document.getElementById('room_id').value,
        room_type_id: document.getElementById('room_type_id').value || selectedRoom?.type_id,
        check_in_date: document.getElementById('check_in_date').value,
        check_out_date: document.getElementById('check_out_date').value,
        rate_per_night: document.getElementById('rate_per_night').value,
        adults: document.getElementById('adults').value,
        children: document.getElementById('children').value,
        source: document.getElementById('source').value,
        advance_amount: document.getElementById('advance_amount').value,
        payment_mode: document.getElementById('payment_mode').value,
        payment_reference: document.getElementById('payment_reference').value,
        special_requests: document.getElementById('special_requests').value,
    };
    
    const res = await fetch('/api/bookings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(bookingData)
    });
    
    const data = await res.json();
    
    if (data.success) {
        alert(`Booking created successfully!\nBooking Number: ${data.booking_number}`);
        window.location.href = '/bookings';
    } else {
        alert('Failed to create booking: ' + (data.error || 'Unknown error'));
    }
}

// ============================================
// OCR Check-in Integration
// ============================================

function initOCRUpload() {
    // Check if container exists
    const container = document.getElementById('ocr-upload-container');
    if (!container) return;
    
    // Initialize OCR UI component
    const ocrUI = new OCRUploadUI('#ocr-upload-container', {
        onResult: function(result) {
            console.log('OCR Result:', result);
            
            if (result.extracted) {
                // Show guest form
                document.getElementById('guest-results').style.display = 'none';
                document.getElementById('guest-form').style.display = 'block';
                document.getElementById('guest_id').value = '';
                
                // Enable editing for new guest
                document.getElementById('first_name').readOnly = false;
                document.getElementById('last_name').readOnly = false;
                
                // Auto-fill fields from OCR
                const data = result.extracted;
                
                // Name (split first/last)
                if (data.name) {
                    const nameParts = data.name.split(' ');
                    document.getElementById('first_name').value = nameParts[0] || '';
                    document.getElementById('last_name').value = nameParts.slice(1).join(' ') || '';
                }
                
                // ID Type and Number
                if (data.id_type) {
                    const idTypeMap = {
                        'Aadhaar': 'aadhaar',
                        'PAN': 'pan',
                        'Passport': 'passport',
                    };
                    document.getElementById('id_type').value = idTypeMap[data.id_type] || '';
                }
                
                if (data.id_number) {
                    document.getElementById('id_number').value = data.id_number;
                }
                
                // Phone
                if (data.phone) {
                    document.getElementById('phone').value = data.phone;
                }
                
                // Address
                if (data.address) {
                    document.getElementById('address').value = data.address;
                }
                
                // Show success message
                showNotification('ID scanned successfully! Please verify the details.', 'success');
            } else {
                showNotification('Could not extract information. Please enter manually.', 'warning');
            }
        },
        onError: function(message) {
            showNotification(message, 'error');
        }
    });
}

function showNotification(message, type = 'info') {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">×</button>
    `;
    
    // Add styles if not present
    if (!document.getElementById('toast-styles')) {
        const styles = document.createElement('style');
        styles.id = 'toast-styles';
        styles.textContent = `
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 1rem;
                z-index: 1000;
                animation: slideIn 0.3s ease;
                max-width: 400px;
            }
            .toast--success { background: #22c55e; color: white; }
            .toast--error { background: #ef4444; color: white; }
            .toast--warning { background: #f59e0b; color: white; }
            .toast--info { background: #3b82f6; color: white; }
            .toast button { background: none; border: none; color: inherit; font-size: 1.5rem; cursor: pointer; }
            @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}
</script>

<!-- OCR Check-in Library -->
<script src="/assets/js/ocr-checkin.js"></script>
