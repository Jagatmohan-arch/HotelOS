<?php
/**
 * HotelOS - Guest Portal Handler
 * 
 * MODULE 3: GUEST SELF-SERVICE
 * Manages the guest-facing portal accessed via magic links (QR Code).
 * Uses stateless HMAC signature to verify access without DB changes.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class GuestPortalHandler
{
    private Database $db;
    // secret should be in .env, using fallback for now
    private string $appKey = 'HOTELOS_SECURE_KEY_2026'; 

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a secure access token for a booking
     * Format: base64(booking_id . '.' . hmac)
     */
    public function generateMagicToken(int $bookingId, string $bookingNumber): string
    {
        $payload = $bookingId . '|' . $bookingNumber;
        $signature = hash_hmac('sha256', $payload, $this->appKey);
        return base64_encode($payload . '.' . $signature);
    }

    /**
     * Validate and decode the token
     * Returns booking ID if valid, null if invalid
     */
    public function validateToken(string $token): ?int
    {
        try {
            $decoded = base64_decode($token);
            if (!$decoded || !str_contains($decoded, '.')) return null;

            [$payload, $signature] = explode('.', $decoded, 2);
            [$bookingId, $bookingNumber] = explode('|', $payload, 2);

            // Re-calculate signature
            $expectedSignature = hash_hmac('sha256', $payload, $this->appKey);

            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }

            return (int)$bookingId;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Fetch data for the portal dashboard
     */
    public function getPortalData(int $bookingId): ?array
    {
        // 1. Fetch Booking & Room Details
        $booking = $this->db->queryOne(
            "SELECT b.*, 
                    r.room_number, rt.name as room_type,
                    g.first_name, g.last_name, g.phone,
                    t.name as hotel_name, t.wifi_ssid, t.wifi_password, t.support_phone, t.address
             FROM bookings b
             JOIN guests g ON b.guest_id = g.id
             LEFT JOIN rooms r ON b.room_id = r.id
             LEFT JOIN room_types rt ON b.room_type_id = rt.id
             JOIN tenants t ON b.tenant_id = t.id
             WHERE b.id = :id",
             ['id' => $bookingId],
             enforceTenant: false // Token validates access, tenant is implicit
        );

        if (!$booking) return null;

        // 2. Set Tenant Context for subsequent queries (mocking context for this request)
        // This is important if we used other handlers, but here we read directly.

        // 3. Get Bill Summary (Live Calculation)
        // Re-using logic from BookingHandler would be ideal, but for read-only View:
        $charges = [
            'Room Rent' => (float)$booking['room_total'],
            'Extra Charges' => (float)$booking['extra_charges'],
            'Taxes (GST)' => (float)$booking['tax_total'],
            'Discount' => -(float)$booking['discount_amount']
        ];
        
        $total = array_sum($charges);
        $paid = (float)$booking['paid_amount'];
        $balance = $total - $paid;

        return [
            'booking' => $booking,
            'bill' => [
                'items' => $charges,
                'total' => $total,
                'paid' => $paid,
                'balance' => $balance
            ],
            'hotel' => [
                'name' => $booking['hotel_name'],
                'wifi' => [
                    'ssid' => $booking['wifi_ssid'] ?? 'HotelOS_Guest',
                    'password' => $booking['wifi_password'] ?? 'welcome123'
                ],
                'support' => $booking['support_phone'],
                'address' => $booking['address']
            ]
        ];
    }
}
