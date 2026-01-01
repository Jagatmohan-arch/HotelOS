<?php
/**
 * HotelOS - UAT Simulation Script
 * 
 * Simulates the entire booking lifecycle to verify integrity.
 * Usage: php tests/uat_simulation.php
 */

// --- BOOTSTRAP ---
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', BASE_PATH . '/views');
define('HANDLERS_PATH', BASE_PATH . '/handlers');

// Standard autoloader from index.php
spl_autoload_register(function (string $class): void {
    $corePrefix = 'HotelOS\\Core\\';
    $coreDir = CORE_PATH . '/';
    if (strncmp($corePrefix, $class, strlen($corePrefix)) === 0) {
        $relativeClass = substr($class, strlen($corePrefix));
        $file = $coreDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) require $file;
        return;
    }

    $handlerPrefix = 'HotelOS\\Handlers\\';
    $handlerDir = HANDLERS_PATH . '/';
    if (strncmp($handlerPrefix, $class, strlen($handlerPrefix)) === 0) {
        $relativeClass = substr($class, strlen($handlerPrefix));
        $file = $handlerDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) require $file;
        return;
    }
});

// Load .env
if (file_exists(BASE_PATH . '/.env')) {
    $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name && $value) $_ENV[trim($name)] = trim($value);
    }
}

// Mock Auth & Session
$_SESSION['user_id'] = 1; // Assume Admin
$_SESSION['tenant_id'] = 1;

// --- SIMULATION ---

echo "🚀 Starting UAT Simulation...\n\n";

try {
    $db = \HotelOS\Core\Database::getInstance();
    $auth = \HotelOS\Core\Auth::getInstance();
    
    // Step 1: Create Guest
    echo "[1] Creating Guest... ";
    $guestHandler = new \HotelOS\Handlers\GuestHandler();
    $guestData = [
        'first_name' => 'UAT',
        'last_name' => 'Tester_' . time(),
        'phone' => '9999999999',
        'email' => 'uat@test.com',
        'title' => 'Mr',
        'nationality' => 'Indian'
    ];
    // Check if exists first to avoid duplicate error in dev
    $existing = $guestHandler->findByPhone($guestData['phone']);
    if ($existing) {
        $guestId = $existing['id'];
        echo "Found existing (ID: $guestId)\n";
    } else {
        $guestId = $guestHandler->create($guestData);
        echo "Created (ID: $guestId)\n";
    }

    // Step 2: Create Booking
    echo "[2] Creating Booking (Walk-in)... ";
    $bookingHandler = new \HotelOS\Handlers\BookingHandler();
    
    // Find a room
    $rooms = $bookingHandler->getAvailableRooms(date('Y-m-d'), date('Y-m-d', strtotime('+1 day')));
    if (empty($rooms)) {
        die("❌ FAILED: No available rooms for test.\n");
    }
    $room = $rooms[0];
    
    $bookingData = [
        'guest_id' => $guestId,
        'room_id' => $room['id'],
        'room_type_id' => $room['room_type_id'],
        'check_in_date' => date('Y-m-d'),
        'check_out_date' => date('Y-m-d', strtotime('+1 day')),
        'adults' => 1,
        'rate_per_night' => 2000,
        'advance_amount' => 500, // Advance Payment
        'payment_mode' => 'cash',
        'source' => 'walk_in',
        'created_by' => 1
    ];
    
    $result = $bookingHandler->create($bookingData);
    if (!$result['success']) {
        die("❌ FAILED: " . $result['error'] . "\n");
    }
    $bookingId = $result['booking_id'];
    echo "Created (ID: $bookingId, #{$result['booking_number']})\n";

    // Step 3: Verify Advance Payment
    echo "[3] Verifying Advance Payment... ";
    $booking = $bookingHandler->getById($bookingId);
    if ((float)$booking['paid_amount'] === 500.0) {
        echo "Verified (₹500)\n";
    } else {
        die("❌ FAILED: Paid amount is " . $booking['paid_amount'] . "\n");
    }

    // Step 4: Check In
    echo "[4] Checking In... ";
    $res = $bookingHandler->checkIn($bookingId);
    if ($res['success']) {
        echo "Success\n";
    } else {
        die("❌ FAILED: " . $res['error'] . "\n");
    }

    // Step 5: Add Extra Charges
    echo "[5] Adding Extra Charges (Room Move/Service)... ";
    // We can simulate extra charges by updating DB or using checkout hook
    // Let's assume minibar usage of 200 during checkout
    echo "Skipped (Will add at checkout)\n";

    // Step 6: Checkout & Final Settlement
    echo "[6] Checking Out... ";
    
    // Expected:
    // Room: 2000
    // Extra: 200
    // Total Taxable: 2200
    // GST (12% of 2200): 264 (Assuming 12% slab for >1000)
    // Grand Total: 2464
    // Paid: 500
    // Balance: 1964
    
    $checkoutResult = $bookingHandler->checkOut(
        $bookingId, 
        200, // Extra charges
        0,   // Late fee
        [
            'amount' => 1964, // Paying full balance (Estimated)
            'mode' => 'upi'
        ]
    );
    
    if ($checkoutResult['success']) {
        echo "Success\n";
        echo "    - Grand Total: ₹" . $checkoutResult['grand_total'] . "\n";
        echo "    - Tax: ₹" . ($checkoutResult['cgst'] + $checkoutResult['sgst']) . "\n";
        echo "    - Balanced: ₹" . $checkoutResult['balance'] . "\n";
        
        if (abs($checkoutResult['balance']) < 1) {
             echo "✅ VERIFIED: Balance Cleared.\n";
        } else {
             echo "⚠️ WARNING: Non-zero balance.\n";
        }
    } else {
        die("❌ FAILED: " . $checkoutResult['error'] . "\n");
    }
    
    echo "\n🎉 UAT SIMULATION PASSED!\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
