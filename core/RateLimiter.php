<?php
/**
 * HotelOS - Login Rate Limiter
 * Prevents brute force attacks on login endpoints
 */

declare(strict_types=1);

namespace HotelOS\Core;

class RateLimiter
{
    private Database $db;
    private const MAX_ATTEMPTS = 10;  // Max attempts per hour
    private const BAN_DURATION = 3600; // 1 hour in seconds
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check if IP is rate limited
     */
    public function isRateLimited(string $ip, string $action = 'login'): bool
    {
        $this->cleanupOldAttempts();
        
        $attempts = $this->db->queryOne(
            "SELECT COUNT(*) as count 
             FROM login_attempts 
             WHERE ip_address = :ip 
             AND action = :action 
             AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            ['ip' => $ip, 'action' => $action],
            enforceTenant: false
        );
        
        return ($attempts['count'] ?? 0) >= self::MAX_ATTEMPTS;
    }
    
    /**
     * Record login attempt
     */
    public function recordAttempt(string $ip, string $action = 'login', bool $success = false): void
    {
        $this->db->execute(
            "INSERT INTO login_attempts 
             (ip_address, action, success, attempted_at, user_agent) 
             VALUES (:ip, :action, :success, NOW(), :ua)",
            [
                'ip' => $ip,
                'action' => $action,
                'success' => $success ? 1 : 0,
                'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ],
            enforceTenant: false
        );
    }
    
    /**
     * Clear attempts for IP (after successful login)
     */
    public function clearAttempts(string $ip, string $action = 'login'): void
    {
        $this->db->execute(
            "DELETE FROM login_attempts 
             WHERE ip_address = :ip AND action = :action",
            ['ip' => $ip, 'action' => $action],
            enforceTenant: false
        );
    }
    
    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(string $ip, string $action = 'login'): int
    {
        $attempts = $this->db->queryOne(
            "SELECT COUNT(*) as count 
             FROM login_attempts 
             WHERE ip_address = :ip 
             AND action = :action 
             AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            ['ip' => $ip, 'action' => $action],
            enforceTenant: false
        );
        
        $used = $attempts['count'] ?? 0;
        return max(0, self::MAX_ATTEMPTS - $used);
    }
    
    /**
     * Cleanup old attempts (older than 24 hours)
     */
    private function cleanupOldAttempts(): void
    {
        // Run cleanup randomly (1% chance) to avoid overhead
        if (rand(1, 100) === 1) {
            $this->db->execute(
                "DELETE FROM login_attempts 
                 WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                [],
                enforceTenant: false
            );
        }
    }
    
    /**
     * Get client IP (handles proxies)
     */
    public static function getClientIP(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
              $_SERVER['HTTP_CLIENT_IP'] ?? 
              $_SERVER['REMOTE_ADDR'] ?? 
              '0.0.0.0';
        
        // If multiple IPs (proxy chain), get first one
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        return $ip;
    }
}
