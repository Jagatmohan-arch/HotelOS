<?php
/**
 * HotelOS - Direct Booking Handler
 * 
 * MODULE 1: PUBLIC DIRECT BOOKING ENGINE
 * Handles public-facing requests for the booking widget.
 * Does NOT require authentication.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class DirectBookingHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Resolve hotel slug to Tenant and Switch Context
     * This is the entry point for all public booking routes.
     */
    public function resolveHotel(string $slug): ?array
    {
        $tenant = $this->db->queryOne(
            "SELECT id, uuid, name, slug, email, phone, address_line1, city, state, country, 
                    check_in_time, check_out_time, currency, settings 
             FROM tenants 
             WHERE slug = :slug AND status = 'active'",
            ['slug' => $slug],
            enforceTenant: false
        );

        if ($tenant) {
            // CRITICAL: Set the context for this request 
            // So that subsequent calls (RoomTypeHandler, etc.) work correctly
            TenantContext::setId((int)$tenant['id']);
        }

        return $tenant;
    }

    /**
     * Get branding assets for the public page
     */
    public function getBranding(int $tenantId): array
    {
        return $this->db->query(
            "SELECT asset_type, file_path 
             FROM branding_assets 
             WHERE tenant_id = :tid AND is_active = 1",
            ['tid' => $tenantId],
            enforceTenant: false
        );
    }

    /**
     * Process a public booking request
     */
    public function processBooking(array $data): array
    {
        // 1. Basic Sanitization
        if (empty($data['first_name']) || empty($data['phone']) || empty($data['room_type_id'])) {
            return ['success' => false, 'error' => 'Missing required fields'];
        }

        // Security: Ensure Room Type belongs to current Tenant
        $tenantId = TenantContext::getId();
        $validRT = $this->db->queryOne(
            "SELECT id FROM room_types WHERE id = :id AND tenant_id = :tid", 
            ['id' => $data['room_type_id'], 'tid' => $tenantId],
            enforceTenant: false
        );

        if (!$validRT) {
            return ['success' => false, 'error' => 'Invalid Room Type'];
        }

        // 2. Guest Handling (Find or Create)
        $guestHandler = new GuestHandler();
        
        // Try finding by phone first
        $guest = $guestHandler->findByPhone($data['phone']);
        
        if ($guest) {
            $guestId = $guest['id'];
        } else {
            // Create new guest
            try {
                $guestId = $guestHandler->create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'] ?? '',
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'category' => 'regular',
                    'notes' => 'Direct Booking'
                ]);
            } catch (\Exception $e) {
                return ['success' => false, 'error' => 'Invalid guest data: ' . $e->getMessage()];
            }
        }

        // 3. Prepare Internal Booking Data
        $bookingData = [
            'guest_id' => $guestId,
            'room_type_id' => $data['room_type_id'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'adults' => $data['adults'] ?? 1,
            'children' => $data['children'] ?? 0,
            // Security: Rate must be fetched from DB, not trusted from input
            'rate_per_night' => $this->getRateForRoomType((int)$data['room_type_id']),
            'source' => 'website',
            'discount_type' => 'percent',
            'discount_value' => 10, // Direct Booking Bonus
            // Payment (Mock for now, or 'unpaid')
            'advance_amount' => 0, 
            'payment_mode' => 'online'
        ];

        // 4. Create Booking using Core Handler
        $bookingHandler = new BookingHandler();
        $result = $bookingHandler->create($bookingData);

        return $result;
    }

    private function getRateForRoomType(int $id): float {
        $rt = $this->db->queryOne("SELECT base_rate FROM room_types WHERE id = :id", ['id' => $id], enforceTenant: false);
        return (float)($rt['base_rate'] ?? 0);
    }

    /**
     * Get available room types with simplified pricing for display
     */
    public function getPublicRoomTypes(int $tenantId): array
    {
        // Re-using core logic but manual query to ensure fields are safe for public
        return $this->db->query(
            "SELECT id, name, description, base_rate, max_occupancy, amenities, images 
             FROM room_types 
             WHERE tenant_id = :tid AND is_active = 1 
             ORDER BY sort_order ASC",
            ['tid' => $tenantId],
            enforceTenant: false
        );
    }
}
