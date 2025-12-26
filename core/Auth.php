<?php
/**
 * HotelOS - Authentication Manager
 * 
 * Handles user authentication, session management, and RBAC
 * Uses Argon2ID for password hashing (PHP 8.2 native)
 */

declare(strict_types=1);

namespace HotelOS\Core;

class Auth
{
    // Role constants matching database ENUM
    public const ROLE_OWNER = 'owner';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_ACCOUNTANT = 'accountant';
    public const ROLE_RECEPTION = 'reception';
    public const ROLE_HOUSEKEEPING = 'housekeeping';

    // Role hierarchy levels
    private const ROLE_LEVELS = [
        self::ROLE_OWNER       => 100,
        self::ROLE_MANAGER     => 75,
        self::ROLE_ACCOUNTANT  => 50,
        self::ROLE_RECEPTION   => 25,
        self::ROLE_HOUSEKEEPING=> 10,
    ];

    private static ?Auth $instance = null;
    private ?array $user = null;
    private Database $db;
    private array $config;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/app.php';
        $this->initSession();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize secure session
     */
    private function initSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionConfig = $this->config['session'];

        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', $sessionConfig['samesite']);
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }

        session_name($sessionConfig['name']);
        session_set_cookie_params([
            'lifetime' => $sessionConfig['lifetime'] * 60,
            'path'     => '/',
            'secure'   => $sessionConfig['secure'],
            'httponly' => $sessionConfig['httponly'],
            'samesite' => $sessionConfig['samesite'],
        ]);

        session_start();
        
        // Load user from session if exists
        if (isset($_SESSION['user_id'], $_SESSION['tenant_id'])) {
            $this->loadUserFromSession();
        }
    }

    /**
     * Load authenticated user from session
     */
    private function loadUserFromSession(): void
    {
        $userId = $_SESSION['user_id'];
        $tenantId = $_SESSION['tenant_id'];

        // First set tenant context
        TenantContext::loadById($tenantId);

        // Then load user
        $user = $this->db->queryOne(
            "SELECT * FROM users WHERE id = :id AND is_active = 1",
            ['id' => $userId]
        );

        if ($user) {
            $this->user = $user;
        } else {
            // Invalid session, clear it
            $this->logout();
        }
    }

    /**
     * Attempt to authenticate user
     * 
     * @param string $email User email
     * @param string $password Plain text password
     * @return array ['success' => bool, 'message' => string, 'user' => ?array]
     */
    public function attempt(string $email, string $password): array
    {
        // Find user by email (no tenant filter - email is unique per tenant)
        $user = $this->db->queryOne(
            "SELECT u.*, t.name as tenant_name, t.status as tenant_status 
             FROM users u 
             JOIN tenants t ON u.tenant_id = t.id 
             WHERE u.email = :email",
            ['email' => $email],
            enforceTenant: false
        );

        if (!$user) {
            $this->logFailedAttempt($email);
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
                'user'    => null,
            ];
        }

        // Check if account is locked
        if ($this->isAccountLocked($user)) {
            return [
                'success' => false,
                'message' => 'Account is temporarily locked. Please try again later.',
                'user'    => null,
            ];
        }

        // Check if user is active
        if (!$user['is_active']) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated.',
                'user'    => null,
            ];
        }

        // Check if tenant is active
        if ($user['tenant_status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Your hotel subscription is inactive.',
                'user'    => null,
            ];
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($user['id']);
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
                'user'    => null,
            ];
        }

        // Successful login
        $this->loginUser($user);

        return [
            'success' => true,
            'message' => 'Login successful.',
            'user'    => $this->sanitizeUser($user),
        ];
    }

    /**
     * Process successful login
     */
    private function loginUser(array $user): void
    {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['csrf_token'] = $this->generateCsrfToken();
        $_SESSION['login_time'] = time();

        // Update user record
        $this->db->execute(
            "UPDATE users SET 
             last_login_at = NOW(), 
             last_login_ip = :ip, 
             failed_login_attempts = 0,
             locked_until = NULL
             WHERE id = :id",
            [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'id' => $user['id'],
            ],
            enforceTenant: false
        );

        // Set tenant context
        TenantContext::loadById($user['tenant_id']);

        // Log audit event
        $this->logAudit('login', 'user', $user['id']);

        $this->user = $user;
    }

    /**
     * Logout current user
     */
    public function logout(): void
    {
        if ($this->user) {
            $this->logAudit('logout', 'user', $this->user['id']);
        }

        // Clear session
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        
        // Clear internal state
        $this->user = null;
        TenantContext::clear();
    }

    /**
     * Check if user is authenticated
     */
    public function check(): bool
    {
        return $this->user !== null;
    }

    /**
     * Get current authenticated user
     */
    public function user(): ?array
    {
        return $this->user ? $this->sanitizeUser($this->user) : null;
    }

    /**
     * Get current user ID
     */
    public function id(): ?int
    {
        return $this->user['id'] ?? null;
    }

    /**
     * Get current user's role
     */
    public function role(): ?string
    {
        return $this->user['role'] ?? null;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->user && $this->user['role'] === $role;
    }

    /**
     * Check if user has role level >= required
     */
    public function hasRoleLevel(string $requiredRole): bool
    {
        if (!$this->user) {
            return false;
        }

        $userLevel = self::ROLE_LEVELS[$this->user['role']] ?? 0;
        $requiredLevel = self::ROLE_LEVELS[$requiredRole] ?? 100;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Check if user is owner
     */
    public function isOwner(): bool
    {
        return $this->hasRole(self::ROLE_OWNER);
    }

    /**
     * Check if user is manager or above
     */
    public function isManager(): bool
    {
        return $this->hasRoleLevel(self::ROLE_MANAGER);
    }

    /**
     * Check if user can perform action
     */
    public function can(string $permission): bool
    {
        // Define permissions by role
        $permissions = [
            'view_dashboard'    => [self::ROLE_OWNER, self::ROLE_MANAGER, self::ROLE_RECEPTION],
            'manage_bookings'   => [self::ROLE_OWNER, self::ROLE_MANAGER, self::ROLE_RECEPTION],
            'manage_rooms'      => [self::ROLE_OWNER, self::ROLE_MANAGER],
            'manage_users'      => [self::ROLE_OWNER],
            'view_reports'      => [self::ROLE_OWNER, self::ROLE_MANAGER, self::ROLE_ACCOUNTANT],
            'manage_billing'    => [self::ROLE_OWNER, self::ROLE_MANAGER, self::ROLE_ACCOUNTANT],
            'delete_records'    => [self::ROLE_OWNER],
            'view_audit_logs'   => [self::ROLE_OWNER],
            'approve_discounts' => [self::ROLE_OWNER, self::ROLE_MANAGER],
        ];

        $allowedRoles = $permissions[$permission] ?? [];
        return $this->user && in_array($this->user['role'], $allowedRoles, true);
    }

    /**
     * Generate CSRF token
     */
    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_time'] = time();
        return $token;
    }

    /**
     * Get current CSRF token
     */
    public function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            return $this->generateCsrfToken();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public function verifyCsrf(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'], $_SESSION['csrf_time'])) {
            return false;
        }

        $lifetime = $this->config['security']['csrf_token_lifetime'];
        if (time() - $_SESSION['csrf_time'] > $lifetime) {
            return false; // Token expired
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Hash password using Argon2ID
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost'   => PASSWORD_ARGON2_DEFAULT_TIME_COST,
            'threads'     => PASSWORD_ARGON2_DEFAULT_THREADS,
        ]);
    }

    /**
     * Check if account is locked
     */
    private function isAccountLocked(array $user): bool
    {
        if ($user['locked_until'] === null) {
            return false;
        }

        $lockedUntil = strtotime($user['locked_until']);
        return $lockedUntil > time();
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt(int $userId): void
    {
        $maxAttempts = $this->config['security']['max_login_attempts'];
        $lockoutMinutes = $this->config['security']['lockout_duration'];

        $this->db->execute(
            "UPDATE users SET 
             failed_login_attempts = failed_login_attempts + 1,
             locked_until = CASE 
                 WHEN failed_login_attempts + 1 >= :max_attempts 
                 THEN DATE_ADD(NOW(), INTERVAL :lockout MINUTE)
                 ELSE locked_until 
             END
             WHERE id = :id",
            [
                'max_attempts' => $maxAttempts,
                'lockout'      => $lockoutMinutes,
                'id'           => $userId,
            ],
            enforceTenant: false
        );
    }

    /**
     * Log failed attempt (for rate limiting)
     */
    private function logFailedAttempt(string $email): void
    {
        // Could implement IP-based rate limiting here
        error_log("Failed login attempt for email: {$email} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }

    /**
     * Log audit event
     */
    private function logAudit(string $action, string $entityType, ?int $entityId = null): void
    {
        if (!TenantContext::isActive()) {
            return;
        }

        try {
            $this->db->execute(
                "INSERT INTO audit_logs (tenant_id, user_id, action, entity_type, entity_id, ip_address, user_agent) 
                 VALUES (:tenant_id, :user_id, :action, :entity_type, :entity_id, :ip, :ua)",
                [
                    'tenant_id'   => TenantContext::getId(),
                    'user_id'     => $this->user['id'] ?? null,
                    'action'      => $action,
                    'entity_type' => $entityType,
                    'entity_id'   => $entityId,
                    'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
                    'ua'          => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                ],
                enforceTenant: false // We're manually setting tenant_id
            );
        } catch (\Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
        }
    }

    /**
     * Remove sensitive data from user array
     */
    private function sanitizeUser(array $user): array
    {
        unset(
            $user['password_hash'],
            $user['reset_token'],
            $user['reset_token_expires_at'],
            $user['failed_login_attempts'],
            $user['locked_until']
        );
        return $user;
    }
}
