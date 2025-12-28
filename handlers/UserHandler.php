<?php
/**
 * HotelOS - User Handler
 * 
 * Manages user CRUD operations for Staff Management.
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\Auth;
use HotelOS\Core\TenantContext;

class UserHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all users for the current tenant
     */
    public function getAllUsers(): array
    {
        return $this->db->query(
            "SELECT * FROM users 
             WHERE tenant_id = :tenant_id 
             ORDER BY role ASC, first_name ASC",
            ['tenant_id' => TenantContext::getId()],
            enforceTenant: false
        );
    }

    /**
     * Get user by ID
     */
    public function getById(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM users WHERE id = :id AND tenant_id = :tenant_id",
            [
                'id' => $id,
                'tenant_id' => TenantContext::getId()
            ],
            enforceTenant: false
        );
    }

    /**
     * Create a new user
     */
    public function create(array $data): array
    {
        // 1. Validate Email Uniqueness
        $existing = $this->db->queryOne(
            "SELECT id FROM users WHERE email = :email AND tenant_id = :tenant_id",
            [
                'email' => $data['email'],
                'tenant_id' => TenantContext::getId()
            ],
            enforceTenant: false
        );

        if ($existing) {
            return ['success' => false, 'error' => 'Email already exists'];
        }

        // 2. Hash Password
        $passwordHash = Auth::hashPassword($data['password']);

        // 3. Insert
        $id = $this->db->insert('users', [
            'tenant_id' => TenantContext::getId(),
            'role' => $data['role'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'is_active' => 1
        ]);

        return ['success' => true, 'user_id' => $id];
    }

    /**
     * Update user details
     */
    public function update(int $id, array $data): array
    {
        $fields = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'role' => $data['role'],
            'is_active' => isset($data['is_active']) ? 1 : 0
        ];

        // If password provided, hash and update
        if (!empty($data['password'])) {
            $fields['password_hash'] = Auth::hashPassword($data['password']);
        }

        $params = $fields;
        $params['id'] = $id;
        $params['tenant_id'] = TenantContext::getId();

        // Build SET clause
        $setClause = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));

        $this->db->execute(
            "UPDATE users SET $setClause WHERE id = :id AND tenant_id = :tenant_id",
            $params,
            enforceTenant: false
        );

        return ['success' => true];
    }
}
