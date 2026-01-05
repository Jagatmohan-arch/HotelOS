<?php
/**
 * HotelOS - WhatsApp Service
 * 
 * MODULE 2: COMMUNICATION ENGINE
 * Singleton service to manage transactional messaging.
 * Currently configured to LOG messages (Mock Mode) until API keys are provided.
 */

declare(strict_types=1);

namespace HotelOS\Core;

class WhatsAppService
{
    private static ?WhatsAppService $instance = null;
    private bool $mockMode = true;
    private string $logFile;

    private function __construct()
    {
        // In production, load from .env
        $this->mockMode = true; 
        $this->logFile = LOGS_PATH . '/whatsapp.log';
    }

    public static function getInstance(): WhatsAppService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Send Booking Confirmation
     */
    public function sendBookingConfirmation(array $booking): bool
    {
        $guestName = $booking['guest_name'] ?? 'Guest';
        $hotelName = $booking['hotel_name'] ?? 'Hotel';
        $dates = "{$booking['check_in_date']} to {$booking['check_out_date']}";
        
        $message = "🎉 Booking Confirmed! Dear {$guestName}, your stay at {$hotelName} is confirmed for {$dates}. Booking ID: #{$booking['id']}. See you soon!";
        
        return $this->sendMessage($booking['guest_phone'], $message, 'booking_confirmation');
    }

    /**
     * Send Welcome Message (Check-in)
     */
    public function sendWelcome(array $booking): bool
    {
        $guestName = $booking['guest_name'] ?? 'Guest';
        $wifiPass = '12345678'; // Placeholder
        
        // Generate Magic Link
        $portalHandler = new \HotelOS\Handlers\GuestPortalHandler();
        $token = $portalHandler->generateMagicToken((int)$booking['id'], (string)($booking['booking_number'] ?? '000'));
        $link = "https://hotelos.in/guest/portal?token=" . urlencode($token);

        $message = "👋 Welcome to {$booking['hotel_name']}! Dear {$guestName}, we are delighted to have you. Access your Smart Portal here: {$link} . Dial 9 for Reception.";
        
        return $this->sendMessage($booking['guest_phone'], $message, 'check_in_welcome');
    }

    /**
     * Send Thank You / Invoice (Check-out)
     */
    public function sendThankYou(array $booking): bool
    {
        $guestName = $booking['guest_name'] ?? 'Guest';
        
        $message = "✅ Thank You! Dear {$guestName}, hope you had a pleasant stay. Your invoice has been emailed. We hope to see you again!";
        
        return $this->sendMessage($booking['guest_phone'], $message, 'check_out_thanks');
    }

    /**
     * Core Sender Logic
     */
    /**
     * Core Sender Logic
     */
    private function sendMessage(string $to, string $text, string $template): bool
    {
        if (empty($to)) return false;

        // Check environment/config for mode
        // In shared hosting, you might not have access to real env vars easily in PHP < 8,
        // so we default to true unless explicitly 'false'.
        if (getenv('WHATSAPP_LIVE_MODE') !== 'true') {
            $timestamp = date('Y-m-d H:i:s');
            // Log to file for audit/debugging
            $logEntry = "[{$timestamp}] [MOCK] [TO: {$to}] [TEMPLATE: {$template}] {$text}" . PHP_EOL;
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
            return true;
        }

        // REAL API INTEGRATION (Interakt / Wati)
        // This is where you would make the cURL request.
        // Example:
        /*
        $apiKey = getenv('WHATSAPP_API_KEY');
        $endpoint = getenv('WHATSAPP_API_URL');
        $payload = [
            'to' => $to,
            'type' => 'template',
            'template' => ['name' => $template, 'language' => ['code' => 'en']],
            'text' => $text
        ];
        // ... curl_exec ...
        */
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [LIVE-API] [TO: {$to}] {$text}" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        
        return true;
    }
}
