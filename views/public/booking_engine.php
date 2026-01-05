<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book your stay at <?= htmlspecialchars($hotel['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    <!-- Navbar -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($hotel['name']) ?></h1>
            <a href="tel:<?= htmlspecialchars($hotel['phone']) ?>" class="text-sm font-medium text-emerald-600 flex items-center gap-2">
                <i data-lucide="phone" class="w-4 h-4"></i> Support
            </a>
        </div>
    </header>

    <!-- Hero / Date Selection -->
    <div class="bg-emerald-600 text-white py-12 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4">Book Your Stay with Direct Benefits</h2>
            <p class="text-emerald-100 mb-8">Best Rate Guarantee • Instant Confirmation</p>
            
            <div class="bg-white rounded-lg shadow-xl p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-gray-800">
                <div class="text-left">
                    <label class="block text-xs font-semibold text-gray-500 uppercase">Check In</label>
                    <input type="date" class="w-full mt-1 font-medium border-b border-gray-200 focus:outline-none focus:border-emerald-500" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="text-left">
                    <label class="block text-xs font-semibold text-gray-500 uppercase">Check Out</label>
                    <input type="date" class="w-full mt-1 font-medium border-b border-gray-200 focus:outline-none focus:border-emerald-500" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <div class="flex items-end">
                    <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded transition-colors">
                        Check Availability
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Room List -->
    <div class="max-w-4xl mx-auto px-4 py-12">
        <h3 class="text-xl font-bold mb-6">Available Rooms</h3>
        
        <div class="space-y-6">
            <?php foreach ($roomTypes as $room): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col md:flex-row">
                <div class="md:w-1/3 h-48 md:h-auto relative">
                    <!-- Real Placeholder Image -->
                    <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=800&q=80" 
                         alt="Hotel Room" 
                         class="absolute inset-0 w-full h-full object-cover">
                </div>
                <div class="p-6 md:w-2/3 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start">
                            <h4 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($room['name']) ?></h4>
                            <span class="bg-emerald-50 text-emerald-700 text-xs px-2 py-1 rounded font-medium">Available</span>
                        </div>
                        <p class="text-gray-500 text-sm mt-2 line-clamp-2"><?= htmlspecialchars($room['description'] ?? 'Comfortable stay with all amenities.') ?></p>
                        
                        <div class="flex flex-wrap gap-2 mt-4">
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded flex items-center gap-1">
                                <i data-lucide="users" class="w-3 h-3"></i> Max <?= $room['max_occupancy'] ?> Guests
                            </span>
                            <?php if(str_contains(strtolower($room['amenities'] ?? ''), 'wifi')): ?>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded flex items-center gap-1">
                                <i data-lucide="wifi" class="w-3 h-3"></i> WiFi
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex items-end justify-between mt-6">
                        <div>
                            <span class="text-2xl font-bold text-gray-900">₹<?= number_format($room['base_rate']) ?></span>
                            <span class="text-gray-500 text-sm">/ night</span>
                            <p class="text-xs text-gray-400 text-green-600 font-medium">Extra 10% OFF for direct booking</p>
                        </div>
                        <button onclick="openBookingModal(<?= htmlspecialchars(json_encode($room)) ?>)" class="bg-gray-900 hover:bg-black text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            Select Room
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <footer class="bg-white border-t border-gray-200 py-8 mt-12">
        <div class="max-w-4xl mx-auto px-4 text-center text-gray-500 text-sm">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($hotel['name']) ?>. Powered by HotelOS.</p>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden">
            <div class="bg-emerald-600 px-6 py-4 flex justify-between items-center text-white">
                <h3 class="font-bold text-lg">Complete Your Booking</h3>
                <button onclick="closeModal()" class="hover:bg-emerald-700 p-1 rounded"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <div class="p-6">
                <div class="mb-6 bg-emerald-50 text-emerald-800 p-3 rounded-lg text-sm flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span id="modalRoomName" class="font-bold"></span> - <span id="modalDates"></span>
                </div>

                <form id="bookingForm" onsubmit="submitBooking(event)" class="space-y-4">
                    <input type="hidden" name="room_type_id" id="inputRoomTypeId">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="first_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="last_name" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                        <input type="tel" name="phone" required pattern="[0-9]{10}" placeholder="10-digit number" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adults</label>
                            <select name="adults" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="1">1 Adult</option>
                                <option value="2" selected>2 Adults</option>
                                <option value="3">3 Adults</option>
                            </select>
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Children</label>
                            <select name="children" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="0">None</option>
                                <option value="1">1 Child</option>
                                <option value="2">2 Children</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" id="mainBookBtn" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-lg mt-4 transition-all flex justify-center items-center gap-2">
                        <span>Confirm Booking</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                    <p class="text-xs text-center text-gray-400">No payment required now. Pay heavily discounted rate at hotel.</p>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-8 text-center">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="check" class="w-8 h-8"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Booking Confirmed!</h2>
            <p class="text-gray-600 mb-6">Your booking ID is <span class="font-mono font-bold text-gray-900" id="successBookingId"></span>. We have sent a confirmation to your mobile.</p>
            <button onclick="location.reload()" class="w-full bg-gray-900 text-white font-bold py-2 rounded-lg">Done</button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Simple State
        const checkInEl = document.querySelector('input[type="date"]:nth-of-type(1)');
        const checkOutEl = document.querySelector('input[type="date"]:nth-of-type(2)');

        function openBookingModal(room) {
                        try {
                // Ensure room is an object if passed as string/encoded
                if (typeof room === 'string') room = JSON.parse(room);
            } catch(e) {}
            
            document.getElementById('inputRoomTypeId').value = room.id;
            document.getElementById('modalRoomName').innerText = room.name;
            document.getElementById('modalDates').innerText = checkInEl.value + ' to ' + checkOutEl.value;
            document.getElementById('bookingModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }

        async function submitBooking(e) {
            e.preventDefault();
            const btn = document.getElementById('mainBookBtn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            // Add dates manually from the date pickers outside the form
            data.check_in_date = checkInEl.value;
            data.check_out_date = checkOutEl.value;

            try {
                const res = await fetch(window.location.pathname + '/submit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const json = await res.json();
                
                if (json.success) {
                    closeModal();
                    document.getElementById('successBookingId').innerText = '#' + json.id;
                    document.getElementById('successModal').classList.remove('hidden');
                } else {
                    alert('Error: ' + (json.error || 'Booking failed'));
                }
            } catch (err) {
                alert('Connection error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
                lucide.createIcons(); 
            }
        }
    </script>
</body>
</html>
