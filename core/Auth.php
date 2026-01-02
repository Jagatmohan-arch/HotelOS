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

        if (defined('USE_DB_SESSIONS') && \USE_DB_SESSIONS) {
            $handler = new DatabaseSessionHandler($this->db);
            session_set_save_handler($handler, true);
        }

        session_start();
        
        // Load user from session if exists
        if (isset($_SESSION['user_id'], $_SESSION['tenant_id'])) {
            // Check for mobile session timeout (15 min inactivity)
            if ($this->isMobileSessionExpired()) {
                $this->logout();
                return;
            }
            
            // Update last activity timestamp
            $_SESSION['last_activity'] = time();
            
            $this->loadUserFromSession();
        }
    }
    
    /**
     * Check if mobile session has expired due to inactivity
     */
    private function isMobileSessionExpired(): bool
    {
        // Check if mobile device
        if (!$this->isMobileDevice()) {
            return false;
        }
        
        // Check last activity
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $sessionConfig = $this->config['session'];
        $mobileTimeout = ($sessionConfig['mobile_lifetime'] ?? 15) * 60; // Convert to seconds
        
        return (time() - $lastActivity) > $mobileTimeout;
    }
    
    /**
     * Detect if current request is from a mobile device
     */
    public function isMobileDevice(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $mobileAgents = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod',
            'BlackBerry', 'IEMobile', 'Opera Mini', 'webOS'
        ];
        
        foreach ($mobileAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }
        
        return false;
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

        // Fix #1: Check if email is verified
        if (empty($user['email_verified_at'])) {
            return [
                'success' => false,
                'message' => 'Please verify your email address before logging in.',
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
            $this->recordFailedAttempt((int) $user['id']);
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
     * Attempt to authenticate staff user with 4-digit PIN
     * 
     * @param string $pin 4-digit PIN
     * @return array ['success' => bool, 'message' => string, 'user' => ?array]
     */
    public function attemptPIN(string $pin): array
    {
        // Validate PIN format (exactly 4 digits)
        if (!preg_match('/^\d{4}$/', $pin)) {
            return [
                'success' => false,
                'message' => 'Invalid PIN format. Must be 4 digits.',
                'user'    => null,
            ];
        }

        // Find user by PIN hash (no tenant filter - PIN is unique)
        $user = $this->db->queryOne(
            "SELECT u.*, t.name as tenant_name, t.status as tenant_status 
             FROM users u 
             JOIN tenants t ON u.tenant_id = t.id 
             WHERE u.pin_hash IS NOT NULL",
            [],
            enforceTenant: false
        );

        // Try to find matching PIN by verifying all users with PIN
        // (We can't query by hash since we need to verify)
        $users = $this->db->query(
            "SELECT u.*, t.name as tenant_name, t.status as tenant_status 
             FROM users u 
             JOIN tenants t ON u.tenant_id = t.id 
             WHERE u.pin_hash IS NOT NULL 
             AND u.is_active = 1 
             AND t.status = 'active'",
            [],
            enforceTenant: false
        );

        $matchedUser = null;
        foreach ($users as $u) {
            if (password_verify($pin, $u['pin_hash'])) {
                $matchedUser = $u;
                break;
            }
        }

        if (!$matchedUser) {
            // Log failed attempt (for rate limiting)
            error_log("Failed PIN attempt: {$pin} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            
            return [
                'success' => false,
                'message' => 'Invalid PIN.',
                'user'    => null,
            ];
        }

        // Check if account is locked
        if ($this->isAccountLocked($matchedUser)) {
            return [
                'success' => false,
                'message' => 'Account is temporarily locked. Please try again later.',
                'user'    => null,
            ];
        }

        // Check if user is active
        if (!$matchedUser['is_active']) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated.',
                'user'    => null,
            ];
        }

        // Check if tenant is active
        if ($matchedUser['tenant_status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Your hotel subscription is inactive.',
                'user'    => null,
            ];
        }

        // Successful PIN login
        $this->loginUser($matchedUser);

        return [
            'success' => true,
            'message' => 'Login successful.',
            'user'    => $this->sanitizeUser($matchedUser),
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
        // Phase F1: HARD ENFORCEMENT - Prevent logout if shift is OPEN
        if ($this->user && defined('USE_DB_SESSIONS') && \USE_DB_SESSIONS) {
            // Check for open shift
            $openShift = $this->db->queryOne(
                "SELECT id, shift_start_at, opening_cash FROM shifts 
                 WHERE user_id = :uid 
                 AND status = 'OPEN' 
                 AND tenant_id = :tid",
                ['uid' => $this->user['id'], 'tid' => $this->user['tenant_id']],
                enforceTenant: false
            );
            
            if ($openShift) {
                // CRITICAL: Block logout completely to enforce cash accountability
                $shiftStart = date('d-M-Y H:i', strtotime($openShift['shift_start_at']));
                $message = "Cannot logout with open shift. Your shift started at {$shiftStart}. Please close your shift first.";
                
                // Log the failed logout attempt for audit
                $this->logAudit(
                    'logout_blocked_open_shift', 
                    'shift', 
                    $openShift['id'],
                    null,
                    ['shift_id' => $openShift['id'], 'reason' => 'Attempted logout with unclosed shift']
                );
                
                throw new \Exception($message);
            }
            
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
     * Force logout for all devices of a specific user
     * Phase C feature
     */
    public function logoutAllDevices(int $userId): void
    {
        if (defined('USE_DB_SESSIONS') && \USE_DB_SESSIONS) {
            $this->db->execute(
                "DELETE FROM sessions WHERE user_id = :user_id",
                ['user_id' => $userId],
                enforceTenant: false
            );
            
            $this->logAudit('logout_all_devices', 'user', $userId);
        }
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
     * Log audit event with optional diff tracking
     * 
     * @param string $action Action performed (e.g., 'booking_update', 'refund_approved')
     * @param string $entityType Entity type (e.g., 'bookings', 'transactions')
     * @param int|string|null $entityId Entity ID
     * @param array|null $oldValues Previous values (for diff tracking)
     * @param array|null $newValues New values (for diff tracking)
     */
    public function logAudit(
        string $action, 
        string $entityType, 
        int|string|null $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void
    {
        $entityId = $entityId !== null ? (int) $entityId : null;
        
        if (!TenantContext::isActive()) {
            return;
        }

        try {
            // Prepare JSON for old/new values (financial field tracking)
            $oldValuesJson = $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
            $newValuesJson = $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;
            
            // Build description from changes
            $description = null;
            if ($oldValues && $newValues) {
                $changes = [];
                foreach ($newValues as $key => $newVal) {
                    $oldVal = $oldValues[$key] ?? null;
                    if ($oldVal !== $newVal) {
                        $changes[] = "{$key}: {$oldVal} → {$newVal}";
                    }
                }
                if (!empty($changes)) {
                    $description = implode('; ', $changes);
                }
            }
            
            $this->db->execute(
                "INSERT INTO audit_logs (tenant_id, user_id, action, entity_type, entity_id, description, old_values, new_values, ip_address, user_agent) 
                 VALUES (:tenant_id, :user_id, :action, :entity_type, :entity_id, :description, :old_values, :new_values, :ip, :ua)",
                [
                    'tenant_id'   => TenantContext::getId(),
                    'user_id'     => $this->user['id'] ?? null,
                    'action'      => $action,
                    'entity_type' => $entityType,
                    'entity_id'   => $entityId,
                    'description' => $description,
                    'old_values'  => $oldValuesJson,
                    'new_values'  => $newValuesJson,
                    'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
                    'ua'          => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                ],
                enforceTenant: false
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

    /**
     * Generate password reset token and store in database
     * 
     * @param string $email User email
     * @return array ['success' => bool, 'token' => ?string, 'message' => string]
     */
    public function generatePasswordResetToken(string $email): array
    {
        // Find user by email
        $user = $this->db->queryOne(
            "SELECT id, email, first_name FROM users WHERE email = :email AND is_active = 1",
            ['email' => $email],
            enforceTenant: false
        );

        if (!$user) {
            // Don't reveal if email exists (security)
            return [
                'success' => true,
                'message' => 'If this email exists, a reset link has been sent.',
                'token' => null
            ];
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token in database
        $this->db->execute(
            "UPDATE users SET 
             reset_token = :token,
             reset_token_expires_at = :expires
             WHERE id = :id",
            [
                'token' => $token,
                'expires' => $expiresAt,
                'id' => $user['id']
            ],
            enforceTenant: false
        );

        // Log the action
        $this->db->execute(
            "INSERT INTO audit_logs (tenant_id, user_id, action, entity_type, entity_id, description, ip_address)
             SELECT tenant_id, id, 'password_reset_requested', 'user', id, 'Password reset token generated', :ip
             FROM users WHERE id = :id",
            [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'id' => $user['id']
            ],
            enforceTenant: false
        );

        return [
            'success' => true,
            'token' => $token,
            'user_name' => $user['first_name'],
            'message' => 'Reset token generated successfully.'
        ];
    }

    /**
     * Validate password reset token
     * 
     * @param string $token Reset token
     * @return array|null User data if valid, null if invalid/expired
     */
    public function validateResetToken(string $token): ?array
    {
        $user = $this->db->queryOne(
            "SELECT id, email, first_name, reset_token_expires_at 
             FROM users 
             WHERE reset_token = :token 
             AND reset_token_expires_at > NOW()
             AND is_active = 1",
            ['token' => $token],
            enforceTenant: false
        );

        return $user ?: null;
    }

    /**
     * Reset password using token
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        // Validate token first
        $user = $this->validateResetToken($token);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset token. Please request a new one.'
            ];
        }

        // Validate password strength
        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters long.'
            ];
        }

        // Hash new password
        $passwordHash = self::hashPassword($newPassword);

        // Update password and clear reset token
        $this->db->execute(
            "UPDATE users SET 
             password_hash = :hash,
             reset_token = NULL,
             reset_token_expires_at = NULL,
             failed_login_attempts = 0,
             locked_until = NULL
             WHERE id = :id",
            [
                'hash' => $passwordHash,
                'id' => $user['id']
            ],
            enforceTenant: false
        );

        // Log the reset
        $this->db->execute(
            "INSERT INTO audit_logs (tenant_id, user_id, action, entity_type, entity_id, description, ip_address)
             SELECT tenant_id, id, 'password_reset_completed', 'user', id, 'Password successfully reset', :ip
             FROM users WHERE id = :id",
            [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'id' => $user['id']
            ],
            enforceTenant: false
        );

        return [
            'success' => true,
            'message' => 'Password has been reset successfully. You can now log in.'
        ];
    }
}
