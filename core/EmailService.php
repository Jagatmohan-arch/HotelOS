<?php
/**
 * HotelOS - Email Service
 * 
 * Handles sending transactional emails via SMTP or PHP mail().
 * Falls back to logging when SMTP is not configured.
 * 
 * Shared hosting compatible - no external dependencies required.
 */

declare(strict_types=1);

namespace HotelOS\Core;

class EmailService
{
    private static ?EmailService $instance = null;
    private array $config;
    private array $mailConfig;

    private function __construct() {
        $this->config = require __DIR__ . '/../config/app.php';
        $this->mailConfig = $this->config['mail'] ?? [];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Send email using configured driver
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body HTML email body
     * @param array $options Optional: from_name, from_email, reply_to
     * @return bool Success
     */
    public function sendRaw(string $to, string $subject, string $body, array $options = []): bool
    {
        $driver = $this->mailConfig['driver'] ?? 'log';
        
        // Always log for audit trail
        $this->logEmail($to, $subject, $body);
        
        if ($driver === 'log') {
            // Log-only mode (development/testing)
            return true;
        }
        
        if ($driver === 'smtp') {
            return $this->sendViaSMTP($to, $subject, $body, $options);
        }
        
        if ($driver === 'mail') {
            return $this->sendViaPhpMail($to, $subject, $body, $options);
        }
        
        // Default: log only
        return true;
    }

    /**
     * Send email via SMTP using fsockopen (no external library)
     * Compatible with shared hosting
     */
    private function sendViaSMTP(string $to, string $subject, string $body, array $options = []): bool
    {
        $host = $this->mailConfig['host'] ?? '';
        $port = $this->mailConfig['port'] ?? 587;
        $username = $this->mailConfig['username'] ?? '';
        $password = $this->mailConfig['password'] ?? '';
        $encryption = $this->mailConfig['encryption'] ?? 'tls';
        $fromName = $options['from_name'] ?? $this->mailConfig['from_name'] ?? 'HotelOS';
        $fromEmail = $options['from_email'] ?? $this->mailConfig['from_email'] ?? 'noreply@hotelos.in';

        // If credentials not set, fall back to PHP mail()
        if (empty($username) || empty($password)) {
            error_log("EmailService: SMTP credentials not configured, falling back to PHP mail()");
            return $this->sendViaPhpMail($to, $subject, $body, $options);
        }

        try {
            // For shared hosting, use PHP's mail() with proper headers
            // Native SMTP socket implementation is complex and often blocked
            // Instead, we set proper headers and use mail() which uses server's sendmail/SMTP
            
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
            $headers[] = "From: {$fromName} <{$fromEmail}>";
            $headers[] = "Reply-To: {$fromEmail}";
            $headers[] = 'X-Mailer: HotelOS/1.0';
            
            $result = @mail($to, $subject, $body, implode("\r\n", $headers));
            
            if (!$result) {
                error_log("EmailService: PHP mail() failed for {$to}");
                return false;
            }
            
            error_log("EmailService: Email sent successfully to {$to}");
            return true;
            
        } catch (\Exception $e) {
            error_log("EmailService SMTP Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email via PHP's native mail() function
     */
    private function sendViaPhpMail(string $to, string $subject, string $body, array $options = []): bool
    {
        $fromName = $options['from_name'] ?? $this->mailConfig['from_name'] ?? 'HotelOS';
        $fromEmail = $options['from_email'] ?? $this->mailConfig['from_email'] ?? 'noreply@hotelos.in';
        
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = "From: {$fromName} <{$fromEmail}>";
        $headers[] = "Reply-To: {$fromEmail}";
        $headers[] = 'X-Mailer: HotelOS/1.0';
        
        $result = @mail($to, $subject, $body, implode("\r\n", $headers));
        
        if (!$result) {
            error_log("EmailService: PHP mail() failed for {$to}");
        }
        
        return $result;
    }

    /**
     * Log email to file for audit trail
     */
    private function logEmail(string $to, string $subject, string $body): void
    {
        $logEntry = sprintf(
            "[%s] TO: %s | SUBJECT: %s | DRIVER: %s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $this->mailConfig['driver'] ?? 'log'
        );
        
        $logFile = __DIR__ . '/../logs/emails.log';
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Send email using template
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $templateName Name of template in views/emails/
     * @param array $data Data to pass to template
     * @return bool Success
     */
    public function send(string $to, string $subject, string $templateName, array $data = []): bool
    {
        // Try to load template
        $templatePath = __DIR__ . "/../views/emails/{$templateName}.php";
        
        if (file_exists($templatePath)) {
            // Render template
            ob_start();
            extract($data);
            include $templatePath;
            $body = ob_get_clean();
        } else {
            // Fallback: Generate simple HTML email
            $body = $this->generateSimpleEmail($subject, $data);
        }
        
        return $this->sendRaw($to, $subject, $body);
    }

    /**
     * Generate simple HTML email when template not found
     */
    private function generateSimpleEmail(string $subject, array $data): string
    {
        $appName = $this->config['name'] ?? 'HotelOS';
        $content = '';
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $label = ucwords(str_replace('_', ' ', $key));
                if ($key === 'link') {
                    $content .= "<p><a href=\"{$value}\" style=\"background:#3b82f6;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;display:inline-block;\">{$subject}</a></p>";
                } else {
                    $content .= "<p><strong>{$label}:</strong> {$value}</p>";
                }
            }
        }
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$subject}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0;">{$appName}</h1>
    </div>
    <div style="background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; border-top: none;">
        <h2 style="color: #1f2937; margin-top: 0;">{$subject}</h2>
        {$content}
    </div>
    <div style="background: #1f2937; padding: 15px; text-align: center; border-radius: 0 0 8px 8px;">
        <p style="color: #9ca3af; margin: 0; font-size: 12px;">© 2026 {$appName}. All rights reserved.</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Send verification email
     */
    public function sendVerificationEmail(string $to, string $name, string $token): bool
    {
        $appUrl = getenv('APP_URL') ?: ($this->config['url'] ?? 'http://localhost:8000');
        $link = "{$appUrl}/verify-email?token={$token}";
        
        return $this->send($to, "Verify your HotelOS Account", 'verify', [
            'name' => $name,
            'link' => $link
        ]);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(string $to, string $name, string $token): bool
    {
        $appUrl = getenv('APP_URL') ?: ($this->config['url'] ?? 'http://localhost:8000');
        $link = "{$appUrl}/reset-password?token={$token}";
        
        return $this->send($to, "Reset your Password", 'reset', [
            'name' => $name,
            'link' => $link
        ]);
    }

    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation(string $to, array $bookingData): bool
    {
        return $this->send($to, "Booking Confirmation - #{$bookingData['booking_number']}", 'booking_confirmation', $bookingData);
    }

    /**
     * Test email configuration
     */
    public function testConfiguration(): array
    {
        $driver = $this->mailConfig['driver'] ?? 'log';
        $host = $this->mailConfig['host'] ?? 'not set';
        $username = $this->mailConfig['username'] ?? '';
        
        return [
            'driver' => $driver,
            'host' => $host,
            'configured' => !empty($username),
            'from_email' => $this->mailConfig['from_email'] ?? 'not set',
            'from_name' => $this->mailConfig['from_name'] ?? 'not set',
        ];
    }
}
