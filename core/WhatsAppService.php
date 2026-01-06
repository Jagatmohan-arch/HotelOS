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

        // Check Mode
        $isLive = getenv('WHATSAPP_LIVE_MODE') === 'true';

        if (!$isLive) {
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[{$timestamp}] [MOCK] [TO: {$to}] [TEMPLATE: {$template}] {$text}" . PHP_EOL;
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
            return true;
        }

        // REAL API INTEGRATION (Generic Connector for Interakt/Wati)
        $apiKey = getenv('WHATSAPP_API_KEY');
        $apiUrl = getenv('WHATSAPP_API_URL'); // e.g., https://api.interakt.ai/v1/public/message/
        
        if (empty($apiKey) || empty($apiUrl)) {
            error_log("WhatsApp Service Error: Missing API Credentials");
            return false;
        }

        // Construct Payload (Interakt Style)
        // Adjust this payload structure based on the specific provider if needed
        $payload = [
            'countryCode' => '+91',
            'phoneNumber' => $to, // Ensure number is clean (no +91 prefix if provider requires split)
            'type' => 'Template',
            'template' => [
                'name' => $template,
                'languageCode' => 'en',
                // We are passing raw text for now as bodyValues. 
                // Enhanced version would parse variables.
                'bodyValues' => [
                    $text 
                ]
            ]
        ];

        // Init cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ':') // Wati/Interakt often use Basic Auth or Bearer
        ]);
        
        // Some providers use Bearer Token instead:
        // 'Authorization: Key ' . $apiKey

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $timestamp = date('Y-m-d H:i:s');
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $logEntry = "[{$timestamp}] [LIVE-SENT] [TO: {$to}] {$text}" . PHP_EOL;
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
            return true;
        } else {
            $logEntry = "[{$timestamp}] [LIVE-FAIL] [CODE: {$httpCode}] [ERR: {$error}] [RESP: {$response}]" . PHP_EOL;
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
            return false;
        }
    }
}
