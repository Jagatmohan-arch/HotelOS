<?php
/**
 * HotelOS - OTA Channel Manager Handler
 * 
 * Manages OTA integrations for MMT, Goibibo, Agoda, etc.
 * Handles booking sync, inventory updates, and rate management
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;

class OTAChannelHandler
{
    private Database $db;
    
    // Supported OTA platforms
    private const PLATFORMS = [
        'mmt' => ['name' => 'MakeMyTrip', 'commission' => 18.00],
        'goibibo' => ['name' => 'Goibibo', 'commission' => 18.00],
        'agoda' => ['name' => 'Agoda', 'commission' => 15.00],
        'booking' => ['name' => 'Booking.com', 'commission' => 15.00],
        'oyo' => ['name' => 'OYO', 'commission' => 22.00],
        'yatra' => ['name' => 'Yatra', 'commission' => 15.00]
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all connected OTA channels
     */
    public function getChannels(): array
    {
        return $this->db->query(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM ota_bookings WHERE ota_channel_id = c.id AND sync_status = 'synced') as total_bookings,
                    (SELECT COUNT(*) FROM ota_bookings WHERE ota_channel_id = c.id AND sync_status = 'pending') as pending_bookings
             FROM ota_channels c
             ORDER BY c.is_active DESC, c.platform_name"
        );
    }
    
    /**
     * Get channel by ID
     */
    public function getChannel(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM ota_channels WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Connect/Update OTA channel
     */
    public function connectChannel(string $platform, array $credentials): array
    {
        if (!isset(self::PLATFORMS[$platform])) {
            return ['success' => false, 'error' => 'Invalid platform'];
        }
        
        $existing = $this->db->queryOne(
            "SELECT id FROM ota_channels WHERE platform = :platform",
            ['platform' => $platform]
        );
        
        $data = [
            'platform' => $platform,
            'platform_name' => self::PLATFORMS[$platform]['name'],
            'api_key' => $credentials['api_key'] ?? null,
            'api_secret' => $credentials['api_secret'] ?? null,
            'property_id' => $credentials['property_id'] ?? null,
            'hotel_code' => $credentials['hotel_code'] ?? null,
            'commission_rate' => $credentials['commission_rate'] ?? self::PLATFORMS[$platform]['commission'],
            'is_active' => true,
            'sync_inventory' => $credentials['sync_inventory'] ?? true,
            'sync_rates' => $credentials['sync_rates'] ?? true
        ];
        
        if ($existing) {
            $this->db->execute(
                "UPDATE ota_channels SET 
                    api_key = :api_key,
                    api_secret = :api_secret,
                    property_id = :property_id,
                    hotel_code = :hotel_code,
                    commission_rate = :commission_rate,
                    is_active = :is_active,
                    sync_inventory = :sync_inventory,
                    sync_rates = :sync_rates
                 WHERE id = :id",
                array_merge($data, ['id' => $existing['id']])
            );
            return ['success' => true, 'channel_id' => $existing['id'], 'action' => 'updated'];
        } else {
            $id = $this->db->insert('ota_channels', $data);
            return ['success' => true, 'channel_id' => $id, 'action' => 'created'];
        }
    }
    
    /**
     * Disconnect OTA channel
     */
    public function disconnectChannel(int $channelId): bool
    {
        return $this->db->execute(
            "UPDATE ota_channels SET is_active = FALSE WHERE id = :id",
            ['id' => $channelId]
        );
    }
    
    /**
     * Import booking from OTA
     */
    public function importBooking(int $channelId, array $bookingData): array
    {
        $channel = $this->getChannel($channelId);
        if (!$channel) {
            return ['success' => false, 'error' => 'Channel not found'];
        }
        
        // Check if booking already exists
        $existing = $this->db->queryOne(
            "SELECT id, booking_id FROM ota_bookings 
             WHERE ota_channel_id = :channel_id AND ota_booking_ref = :ref",
            ['channel_id' => $channelId, 'ref' => $bookingData['ota_booking_ref']]
        );
        
        if ($existing) {
            return ['success' => false, 'error' => 'Booking already imported', 'booking_id' => $existing['booking_id']];
        }
        
        // Calculate commission
        $totalAmount = (float)$bookingData['total_amount'];
        $commissionRate = $channel['commission_rate'];
        $commissionAmount = $totalAmount * ($commissionRate / 100);
        $netAmount = $totalAmount - $commissionAmount;
        
        // Calculate nights
        $checkIn = new \DateTime($bookingData['check_in_date']);
        $checkOut = new \DateTime($bookingData['check_out_date']);
        $nights = $checkIn->diff($checkOut)->days;
        
        // Insert OTA booking record
        $otaBookingId = $this->db->insert('ota_bookings', [
            'ota_channel_id' => $channelId,
            'ota_booking_ref' => $bookingData['ota_booking_ref'],
            'ota_confirmation_no' => $bookingData['ota_confirmation_no'] ?? null,
            'guest_name' => $bookingData['guest_name'],
            'guest_email' => $bookingData['guest_email'] ?? null,
            'guest_phone' => $bookingData['guest_phone'] ?? null,
            'room_type_requested' => $bookingData['room_type'] ?? null,
            'check_in_date' => $bookingData['check_in_date'],
            'check_out_date' => $bookingData['check_out_date'],
            'nights' => $nights,
            'adults' => $bookingData['adults'] ?? 1,
            'children' => $bookingData['children'] ?? 0,
            'total_amount' => $totalAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'net_amount' => $netAmount,
            'payment_mode' => $bookingData['payment_mode'] ?? 'prepaid',
            'amount_paid' => $bookingData['amount_paid'] ?? 0,
            'special_requests' => $bookingData['special_requests'] ?? null,
            'raw_data' => json_encode($bookingData),
            'sync_status' => 'pending'
        ]);
        
        return [
            'success' => true,
            'ota_booking_id' => $otaBookingId,
            'commission' => $commissionAmount,
            'net_amount' => $netAmount
        ];
    }
    
    /**
     * Convert OTA booking to main booking
     */
    public function convertToBooking(int $otaBookingId, int $roomId): array
    {
        $otaBooking = $this->db->queryOne(
            "SELECT ob.*, oc.platform_name 
             FROM ota_bookings ob
             JOIN ota_channels oc ON ob.ota_channel_id = oc.id
             WHERE ob.id = :id",
            ['id' => $otaBookingId]
        );
        
        if (!$otaBooking) {
            return ['success' => false, 'error' => 'OTA booking not found'];
        }
        
        if ($otaBooking['booking_id']) {
            return ['success' => false, 'error' => 'Already converted to booking'];
        }
        
        // Create or find guest
        $guestHandler = new GuestHandler();
        $nameParts = explode(' ', $otaBooking['guest_name'], 2);
        
        $guestId = $guestHandler->findOrCreate([
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'phone' => $otaBooking['guest_phone'],
            'email' => $otaBooking['guest_email']
        ]);
        
        // Create booking
        $bookingHandler = new BookingHandler();
        $booking = $bookingHandler->createBooking([
            'guest_id' => $guestId,
            'room_id' => $roomId,
            'check_in_date' => $otaBooking['check_in_date'],
            'check_out_date' => $otaBooking['check_out_date'],
            'adults' => $otaBooking['adults'],
            'children' => $otaBooking['children'],
            'rate_per_night' => $otaBooking['total_amount'] / $otaBooking['nights'],
            'total_amount' => $otaBooking['total_amount'],
            'source' => $otaBooking['platform_name'],
            'special_requests' => $otaBooking['special_requests']
        ]);
        
        // Update OTA booking
        $this->db->execute(
            "UPDATE ota_bookings SET 
                booking_id = :booking_id,
                room_id = :room_id,
                sync_status = 'synced',
                synced_at = NOW()
             WHERE id = :id",
            ['id' => $otaBookingId, 'booking_id' => $booking['id'], 'room_id' => $roomId]
        );
        
        return [
            'success' => true,
            'booking_id' => $booking['id'],
            'booking_number' => $booking['booking_number']
        ];
    }
    
    /**
     * Get pending OTA bookings
     */
    public function getPendingBookings(): array
    {
        return $this->db->query(
            "SELECT ob.*, oc.platform_name, oc.platform
             FROM ota_bookings ob
             JOIN ota_channels oc ON ob.ota_channel_id = oc.id
             WHERE ob.sync_status = 'pending'
             ORDER BY ob.check_in_date ASC"
        );
    }
    
    /**
     * Get OTA bookings summary
     */
    public function getBookingsSummary(string $startDate, string $endDate): array
    {
        $byPlatform = $this->db->query(
            "SELECT oc.platform_name,
                    COUNT(*) as booking_count,
                    SUM(ob.total_amount) as total_revenue,
                    SUM(ob.commission_amount) as total_commission,
                    SUM(ob.net_amount) as net_revenue
             FROM ota_bookings ob
             JOIN ota_channels oc ON ob.ota_channel_id = oc.id
             WHERE ob.check_in_date BETWEEN :start AND :end
             AND ob.sync_status = 'synced'
             GROUP BY oc.id, oc.platform_name
             ORDER BY total_revenue DESC",
            ['start' => $startDate, 'end' => $endDate]
        );
        
        $totals = $this->db->queryOne(
            "SELECT 
                COUNT(*) as total_bookings,
                SUM(total_amount) as total_revenue,
                SUM(commission_amount) as total_commission,
                SUM(net_amount) as net_revenue
             FROM ota_bookings
             WHERE check_in_date BETWEEN :start AND :end
             AND sync_status = 'synced'",
            ['start' => $startDate, 'end' => $endDate]
        );
        
        return [
            'by_platform' => $byPlatform,
            'totals' => $totals ?? []
        ];
    }
    
    /**
     * Get today's OTA arrivals
     */
    public function getTodayArrivals(): array
    {
        return $this->db->query(
            "SELECT ob.*, oc.platform_name, oc.platform
             FROM ota_bookings ob
             JOIN ota_channels oc ON ob.ota_channel_id = oc.id
             WHERE ob.check_in_date = CURDATE()
             AND ob.sync_status IN ('synced', 'pending')
             ORDER BY ob.guest_name"
        );
    }
    
    /**
     * Log sync activity
     */
    public function logSync(int $channelId, string $type, string $direction, array $result): void
    {
        $this->db->insert('ota_sync_log', [
            'ota_channel_id' => $channelId,
            'sync_type' => $type,
            'direction' => $direction,
            'status' => $result['success'] ? 'success' : 'failed',
            'records_processed' => $result['processed'] ?? 0,
            'records_failed' => $result['failed'] ?? 0,
            'error_details' => $result['error'] ?? null,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
        
        // Update channel last sync
        $this->db->execute(
            "UPDATE ota_channels SET 
                last_sync = NOW(),
                last_error = :error
             WHERE id = :id",
            ['id' => $channelId, 'error' => $result['error'] ?? null]
        );
    }
    
    /**
     * Get available platforms
     */
    public function getAvailablePlatforms(): array
    {
        return self::PLATFORMS;
    }
}
