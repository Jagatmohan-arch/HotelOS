<?php
/**
 * HotelOS - Email Service
 * 
 * Handles sending transactional emails.
 * Currently simulates sending by logging to file until SMTP is configured.
 */

declare(strict_types=1);

namespace HotelOS\Core;

class EmailService
{
    private static ?EmailService $instance = null;
    private array $config;

    private function __construct() {
        $this->config = require __DIR__ . '/../config/app.php';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $templateName Name of template view
     * @param array $data Data to pass to template
     * @return bool Success
     */
    public function send(string $to, string $subject, string $templateName, array $data = []): bool
    {
        // 1. Render content (Simple placeholder for now)
        $body = "Subject: $subject\nTo: $to\n\n";
        foreach ($data as $key => $value) {
            $body .= "$key: $value\n";
        }
        
        // 2. In Production: Use PHPMailer or SwiftMailer here
        // $this->sendViaSMTP($to, $subject, $body);

        // 3. Current Simulation: Log to file
        $logEntry = sprintf(
            "[%s] SENDING EMAIL TO: %s | SUBJECT: %s | LINK: %s\n", 
            date('Y-m-d H:i:s'), 
            $to, 
            $subject,
            $data['link'] ?? 'N/A'
        );
        
        // Log to specific email log
        $logFile = __DIR__ . '/../logs/emails.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        // Also log to error log for visibility in this audit
        error_log("EMAIL SERVICE: " . trim($logEntry));
        
        return true;
    }

    public function sendVerificationEmail(string $to, string $name, string $token): bool
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';
        $link = "$appUrl/verify-email?token=$token";
        
        return $this->send($to, "Verify your HotelOS Account", 'emails/verify', [
            'name' => $name,
            'link' => $link
        ]);
    }

    public function sendPasswordResetEmail(string $to, string $name, string $token): bool
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';
        $link = "$appUrl/reset-password?token=$token";
        
        return $this->send($to, "Reset your Password", 'emails/reset', [
            'name' => $name,
            'link' => $link
        ]);
    }
}
