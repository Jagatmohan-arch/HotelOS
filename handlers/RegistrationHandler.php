<?php
/**
 * HotelOS - Registration Handler
 * 
 * Handles owner signup and tenant creation with 14-day trial
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Auth;
use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class RegistrationHandler
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Register new hotel owner and create tenant
     * 
     * @param array $data Registration form data
     * @return array ['success' => bool, 'message' => string, 'tenant_id' => ?int]
     */
    public function registerOwner(array $data): array
    {
        // Validate required fields
        $required = ['hotel_name', 'owner_first_name', 'owner_last_name', 'email', 'phone', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => "Field '{$field}' is required",
                    'tenant_id' => null
                ];
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address',
                'tenant_id' => null
            ];
        }

        // Validate password strength (min 8 chars)
        if (strlen($data['password']) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters',
                'tenant_id' => null
            ];
        }

        // Check if email already exists
        $existingUser = $this->db->queryOne(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $data['email']],
            enforceTenant: false
        );

        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Email already registered. Please login instead.',
                'tenant_id' => null
            ];
        }

        // Generate unique slug for tenant
        $slug = $this->generateUniqueSlug($data['hotel_name']);

        try {
            $this->db->beginTransaction();

            // 1. Create tenant (hotel)
            $tenantId = $this->createTenant([
                'name' => $data['hotel_name'],
                'slug' => $slug,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'city' => $data['city'] ?? 'Mumbai',
                'state' => $data['state'] ?? 'Maharashtra',
                'address_line1' => $data['address'] ?? 'Address',
                'plan' => 'trial',
                'status' => 'active',
                'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+14 days'))
            ]);

            if (!$tenantId) {
                throw new \Exception('Failed to create tenant');
            }

            // 2. Create owner user
            $userId = $this->createOwnerUser([
                'tenant_id' => $tenantId,
                'first_name' => $data['owner_first_name'],
                'last_name' => $data['owner_last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password']
            ]);

            if (!$userId) {
                throw new \Exception('Failed to create owner user');
            }

            // 3. Create default room types (optional - helps onboarding)
            $this->createDefaultRoomTypes($tenantId);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Registration successful! Welcome to HotelOS.',
                'tenant_id' => $tenantId,
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Registration failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'tenant_id' => null
            ];
        }
    }

    /**
     * Create tenant record
     */
    private function createTenant(array $data): ?int
    {
        $uuid = $this->generateUUID();

        $this->db->execute(
            "INSERT INTO tenants (
                uuid, name, slug, email, phone, 
                address_line1, city, state, pincode,
                plan, status, trial_ends_at,
                created_at
            ) VALUES (
                :uuid, :name, :slug, :email, :phone,
                :address, :city, :state, '400001',
                :plan, :status, :trial_ends_at,
                NOW()
            )",
            [
                'uuid' => $uuid,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address_line1'],
                'city' => $data['city'],
                'state' => $data['state'],
                'plan' => $data['plan'],
                'status' => $data['status'],
                'trial_ends_at' => $data['trial_ends_at']
            ],
            enforceTenant: false
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Create owner user account
     */
    private function createOwnerUser(array $data): ?int
    {
        $uuid = $this->generateUUID();
        $passwordHash = Auth::hashPassword($data['password']);
        
        // Generate secure 64-char token
        $verificationToken = bin2hex(random_bytes(32));

        $this->db->execute(
            "INSERT INTO users (
                tenant_id, uuid, email, password_hash, phone,
                first_name, last_name, role, is_active, 
                email_verified_at,
                created_at
            ) VALUES (
                :tenant_id, :uuid, :email, :password_hash, :phone,
                :first_name, :last_name, 'owner', 1, 
                NOW(),
                NOW()
            )",
            [
                'tenant_id' => $data['tenant_id'],
                'uuid' => $uuid,
                'email' => $data['email'],
                'password_hash' => $passwordHash,
                'phone' => $data['phone'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name']
            ],
            enforceTenant: false
        );

        // Send verification email
        try {
            \HotelOS\Core\EmailService::getInstance()->sendVerificationEmail(
                $data['email'], 
                $data['first_name'], 
                $verificationToken
            );
        } catch (\Exception $e) {
            error_log("Failed to send verification email: " . $e->getMessage());
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Create basic room types for new hotel (helps onboarding)
     */
    private function createDefaultRoomTypes(int $tenantId): void
    {
        $defaultTypes = [
            ['name' => 'Standard Room', 'code' => 'STD', 'rate' => 2000.00],
            ['name' => 'Deluxe Room', 'code' => 'DLX', 'rate' => 3500.00],
            ['name' => 'Suite', 'code' => 'STE', 'rate' => 6000.00]
        ];

        foreach ($defaultTypes as $type) {
            $this->db->execute(
                "INSERT INTO room_types (
                    tenant_id, name, code, base_rate, is_active, created_at
                ) VALUES (
                    :tenant_id, :name, :code, :rate, 1, NOW()
                )",
                [
                    'tenant_id' => $tenantId,
                    'name' => $type['name'],
                    'code' => $type['code'],
                    'rate' => $type['rate']
                ],
                enforceTenant: false
            );
        }
    }

    /**
     * Generate unique slug from hotel name
     */
    private function generateUniqueSlug(string $name): string
    {
        // Convert to lowercase, remove special chars, replace spaces with hyphens
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        
        // Check if slug exists
        $existing = $this->db->queryOne(
            "SELECT id FROM tenants WHERE slug = :slug",
            ['slug' => $slug],
            enforceTenant: false
        );

        // If exists, append random suffix
        if ($existing) {
            $slug .= '-' . substr(md5(uniqid()), 0, 6);
        }

        return $slug;
    }

    /**
     * Generate UUID v4
     */
    private function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
