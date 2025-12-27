<?php
/**
 * HotelOS - Guest Handler
 * 
 * Manages guest CRUD operations and search functionality
 * All queries are tenant-isolated via Database class
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;

class GuestHandler
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Search guests by phone number or name
     * Used for debounced AJAX lookup
     * 
     * @param string $query Phone number or name fragment
     * @param int $limit Max results (default 5 for performance)
     * @return array Matching guests
     */
    public function search(string $query, int $limit = 5): array
    {
        $query = trim($query);
        
        if (strlen($query) < 3) {
            return [];
        }
        
        $tenantId = TenantContext::getId();
        
        // Search by phone (exact prefix match) or name (like)
        return $this->db->query(
            "SELECT 
                id, first_name, last_name, phone, email,
                company_name, category, total_stays
             FROM guests 
             WHERE tenant_id = :tenant_id
               AND (phone LIKE :phone_query OR 
                    CONCAT(first_name, ' ', last_name) LIKE :name_query)
             ORDER BY total_stays DESC, last_name ASC
             LIMIT :limit",
            [
                'tenant_id' => $tenantId,
                'phone_query' => $query . '%',
                'name_query' => '%' . $query . '%',
                'limit' => $limit
            ],
            enforceTenant: false  // Manual tenant filter for complex query
        );
    }
    
    /**
     * Search guests by phone number only
     * 
     * @param string $phone Phone number
     * @return array|null Guest or null
     */
    public function findByPhone(string $phone): ?array
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) < 10) {
            return null;
        }
        
        return $this->db->queryOne(
            "SELECT * FROM guests WHERE phone = :phone",
            ['phone' => $phone]
        );
    }
    
    /**
     * Get guest by ID
     */
    public function getById(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM guests WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Create new guest
     * 
     * @param array $data Guest data
     * @return int New guest ID
     */
    public function create(array $data): int
    {
        $tenantId = TenantContext::getId();
        
        // Generate UUID
        $uuid = $this->generateUuid();
        
        // Sanitize and prepare data
        $insertData = [
            'tenant_id' => $tenantId,
            'uuid' => $uuid,
            'title' => $data['title'] ?? 'Mr',
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name'] ?? ''),
            'phone' => preg_replace('/[^0-9]/', '', $data['phone']),
            'email' => !empty($data['email']) ? strtolower(trim($data['email'])) : null,
            'alt_phone' => !empty($data['alt_phone']) ? preg_replace('/[^0-9]/', '', $data['alt_phone']) : null,
            'nationality' => $data['nationality'] ?? 'Indian',
            'id_type' => $data['id_type'] ?? null,
            'id_number' => $data['id_number'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'country' => $data['country'] ?? 'India',
            'company_name' => $data['company_name'] ?? null,
            'company_gst' => $data['company_gst'] ?? null,
            'category' => $data['category'] ?? 'regular',
            'notes' => $data['notes'] ?? null,
        ];
        
        $columns = implode(', ', array_keys($insertData));
        $placeholders = ':' . implode(', :', array_keys($insertData));
        
        $this->db->execute(
            "INSERT INTO guests ({$columns}) VALUES ({$placeholders})",
            $insertData,
            enforceTenant: false  // Already included tenant_id
        );
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update existing guest
     * 
     * @param int $id Guest ID
     * @param array $data Updated data
     * @return bool Success
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'title', 'first_name', 'last_name', 'email', 'phone', 'alt_phone',
            'nationality', 'id_type', 'id_number', 'id_expiry',
            'address', 'city', 'state', 'pincode', 'country',
            'company_name', 'company_gst', 'category', 'notes'
        ];
        
        $updates = [];
        $params = ['id' => $id];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updateSql = implode(', ', $updates);
        
        return $this->db->execute(
            "UPDATE guests SET {$updateSql}, updated_at = NOW() WHERE id = :id",
            $params
        ) > 0;
    }
    
    /**
     * Update guest stats after checkout
     * 
     * @param int $id Guest ID
     * @param float $amountSpent Amount spent in this stay
     */
    public function updateStayStats(int $id, float $amountSpent): void
    {
        $this->db->execute(
            "UPDATE guests SET 
             total_stays = total_stays + 1,
             total_spent = total_spent + :amount,
             last_visit_at = NOW()
             WHERE id = :id",
            ['id' => $id, 'amount' => $amountSpent]
        );
    }
    
    /**
     * Get all guests with pagination
     */
    public function getAll(int $page = 1, int $perPage = 25, ?string $search = null): array
    {
        $offset = ($page - 1) * $perPage;
        $tenantId = TenantContext::getId();
        
        $whereClause = "tenant_id = :tenant_id";
        $params = ['tenant_id' => $tenantId];
        
        if ($search) {
            $whereClause .= " AND (phone LIKE :search OR CONCAT(first_name, ' ', last_name) LIKE :search2)";
            $params['search'] = $search . '%';
            $params['search2'] = '%' . $search . '%';
        }
        
        // Get total count
        $countResult = $this->db->queryOne(
            "SELECT COUNT(*) as total FROM guests WHERE {$whereClause}",
            $params,
            enforceTenant: false
        );
        
        // Get paginated results
        $guests = $this->db->query(
            "SELECT * FROM guests 
             WHERE {$whereClause}
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $perPage, 'offset' => $offset]),
            enforceTenant: false
        );
        
        return [
            'data' => $guests,
            'total' => (int)($countResult['total'] ?? 0),
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil(($countResult['total'] ?? 0) / $perPage)
        ];
    }
    
    /**
     * Generate UUID v4
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
