<?php
/**
 * HotelOS - Engine Handler
 * 
 * Owner-Only Super Control System
 * DANGER: All methods require Owner verification
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;
use HotelOS\Core\Auth;

class EngineHandler
{
    private Database $db;
    private Auth $auth;
    private int $tenantId;
    private ?int $userId;
    
    // Risk level constants
    public const RISK_LOW = 'low';
    public const RISK_MEDIUM = 'medium';
    public const RISK_HIGH = 'high';
    public const RISK_CRITICAL = 'critical';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        $this->tenantId = TenantContext::getId();
        $this->userId = $this->auth->id();
        
        // Immediate Owner verification
        if (!$this->auth->isOwner()) {
            throw new \Exception('Access denied: Owner only');
        }
    }
    
    // ========================================
    // SECTION 1: HOTEL SETUP ENGINE
    // ========================================
    
    /**
     * Get current hotel setup data
     */
    public function getHotelSetup(): array
    {
        $tenant = $this->db->queryOne(
            "SELECT name, legal_name, email, phone, alt_phone, website,
                    address_line1, address_line2, city, state, pincode,
                    gst_number, pan_number, state_code,
                    check_in_time, check_out_time, timezone,
                    data_locked_until, maintenance_mode, maintenance_message,
                    settings
             FROM tenants WHERE id = :id",
            ['id' => $this->tenantId],
            enforceTenant: false
        );
        
        if ($tenant && !empty($tenant['settings'])) {
            $tenant['settings'] = json_decode($tenant['settings'], true) ?? [];
        }
        
        return $tenant ?? [];
    }
    
    /**
     * Update hotel setup (with audit)
     */
    public function updateHotelSetup(array $newData, string $reason): array
    {
        $oldData = $this->getHotelSetup();
        
        // Build update query
        $allowedFields = [
            'name', 'legal_name', 'email', 'phone', 'alt_phone', 'website',
            'address_line1', 'address_line2', 'city', 'state', 'pincode',
            'gst_number', 'pan_number', 'state_code', 
            'check_in_time', 'check_out_time', 'timezone'
        ];
        
        $updates = [];
        $params = ['id' => $this->tenantId];
        $changedFields = [];
        
        foreach ($allowedFields as $field) {
            if (isset($newData[$field]) && $newData[$field] !== ($oldData[$field] ?? null)) {
                $updates[] = "$field = :$field";
                $params[$field] = $newData[$field];
                $changedFields[$field] = [
                    'old' => $oldData[$field] ?? null,
                    'new' => $newData[$field]
                ];
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No changes detected'];
        }
        
        $this->db->execute(
            "UPDATE tenants SET " . implode(', ', $updates) . " WHERE id = :id",
            $params,
            enforceTenant: false
        );
        
        // Log to engine_actions
        $this->logAction(
            'hotel_setup_update',
            'tenant',
            $this->tenantId,
            $oldData,
            $newData,
            $reason,
            self::RISK_MEDIUM
        );
        
        return ['success' => true, 'changed_fields' => array_keys($changedFields)];
    }
    
    /**
     * Set data lock date
     */
    public function setDataLock(?string $lockUntilDate, string $reason): array
    {
        $oldLock = $this->db->queryOne(
            "SELECT data_locked_until FROM tenants WHERE id = :id",
            ['id' => $this->tenantId],
            enforceTenant: false
        );
        
        $this->db->execute(
            "UPDATE tenants SET data_locked_until = :lock_date WHERE id = :id",
            ['id' => $this->tenantId, 'lock_date' => $lockUntilDate],
            enforceTenant: false
        );
        
        $this->logAction(
            'data_lock_set',
            'tenant',
            $this->tenantId,
            ['data_locked_until' => $oldLock['data_locked_until'] ?? null],
            ['data_locked_until' => $lockUntilDate],
            $reason,
            self::RISK_HIGH
        );
        
        return ['success' => true];
    }
    
    /**
     * Toggle maintenance mode
     */
    public function setMaintenanceMode(bool $enabled, ?string $message, string $reason): array
    {
        $this->db->execute(
            "UPDATE tenants SET maintenance_mode = :mode, maintenance_message = :msg WHERE id = :id",
            [
                'id' => $this->tenantId,
                'mode' => $enabled ? 1 : 0,
                'msg' => $message
            ],
            enforceTenant: false
        );
        
        $this->logAction(
            $enabled ? 'maintenance_enabled' : 'maintenance_disabled',
            'tenant',
            $this->tenantId,
            null,
            ['maintenance_mode' => $enabled, 'message' => $message],
            $reason,
            self::RISK_HIGH
        );
        
        return ['success' => true];
    }
    
    // ========================================
    // SECTION 2: BRANDING ENGINE
    // ========================================
    
    /**
     * Get branding assets
     */
    public function getBrandingAssets(): array
    {
        return $this->db->query(
            "SELECT * FROM branding_assets WHERE tenant_id = :tid AND is_active = 1",
            ['tid' => $this->tenantId]
        );
    }
    
    /**
     * Upload branding asset
     */
    public function uploadBrandingAsset(
        string $assetType,
        array $fileData,
        string $reason
    ): array {
        $validTypes = ['logo', 'stamp', 'signature'];
        if (!in_array($assetType, $validTypes)) {
            return ['success' => false, 'error' => 'Invalid asset type'];
        }
        
        // Validate file
        $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!in_array($fileData['type'], $allowedMimes)) {
            return ['success' => false, 'error' => 'Only PNG/JPG allowed'];
        }
        
        $maxSize = 500 * 1024; // 500KB
        if ($fileData['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File too large (max 500KB)'];
        }
        
        // Create upload directory
        $uploadDir = PUBLIC_PATH . '/uploads/branding/' . $this->tenantId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate filename
        $ext = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $filename = $assetType . '_' . time() . '.' . $ext;
        $filePath = $uploadDir . '/' . $filename;
        $relativePath = '/uploads/branding/' . $this->tenantId . '/' . $filename;
        
        // Move file
        if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'Upload failed'];
        }
        
        // Deactivate old asset
        $this->db->execute(
            "UPDATE branding_assets SET is_active = 0 
             WHERE tenant_id = :tid AND asset_type = :type",
            ['tid' => $this->tenantId, 'type' => $assetType]
        );
        
        // Insert new
        $this->db->execute(
            "INSERT INTO branding_assets 
             (tenant_id, asset_type, file_path, file_name, mime_type, file_size, uploaded_by)
             VALUES (:tid, :type, :path, :name, :mime, :size, :user)",
            [
                'tid' => $this->tenantId,
                'type' => $assetType,
                'path' => $relativePath,
                'name' => $filename,
                'mime' => $fileData['type'],
                'size' => $fileData['size'],
                'user' => $this->userId
            ]
        );
        
        $this->logAction(
            'branding_upload',
            'branding_assets',
            null,
            null,
            ['asset_type' => $assetType, 'file' => $filename],
            $reason,
            self::RISK_LOW
        );
        
        return ['success' => true, 'path' => $relativePath];
    }
    
    // ========================================
    // SECTION 3: STAFF ENGINE
    // ========================================
    
    /**
     * Get all staff for this tenant
     */
    public function getStaffList(): array
    {
        return $this->db->query(
            "SELECT id, first_name, last_name, email, role, is_active,
                    last_login_at, last_login_ip, created_at,
                    CASE WHEN pin_hash IS NOT NULL THEN 1 ELSE 0 END as has_pin
             FROM users 
             WHERE tenant_id = :tid AND role != 'owner'
             ORDER BY role, first_name",
            ['tid' => $this->tenantId]
        );
    }
    
    /**
     * Generate new PIN for staff
     */
    public function generateStaffPin(int $staffId, string $reason): array
    {
        // Verify staff exists and belongs to this tenant
        $staff = $this->db->queryOne(
            "SELECT id, first_name, last_name, role FROM users 
             WHERE id = :id AND tenant_id = :tid",
            ['id' => $staffId, 'tid' => $this->tenantId]
        );
        
        if (!$staff) {
            return ['success' => false, 'error' => 'Staff not found'];
        }
        
        // Don't allow PIN for Owner
        if ($staff['role'] === 'owner') {
            return ['success' => false, 'error' => 'Cannot set PIN for owner'];
        }
        
        // Generate 4-digit PIN
        $pin = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $pinHash = password_hash($pin, PASSWORD_ARGON2ID);
        
        $this->db->execute(
            "UPDATE users SET pin_hash = :hash, pin_attempts = 0, pin_locked_until = NULL 
             WHERE id = :id",
            ['id' => $staffId, 'hash' => $pinHash]
        );
        
        $this->logAction(
            'staff_pin_generated',
            'user',
            $staffId,
            null,
            ['staff_name' => $staff['first_name'] . ' ' . $staff['last_name']],
            $reason,
            self::RISK_MEDIUM
        );
        
        return ['success' => true, 'pin' => $pin, 'staff' => $staff];
    }
    
    /**
     * Block/Unblock staff
     */
    public function setStaffActive(int $staffId, bool $active, string $reason): array
    {
        $staff = $this->db->queryOne(
            "SELECT id, first_name, last_name, role, is_active FROM users 
             WHERE id = :id AND tenant_id = :tid",
            ['id' => $staffId, 'tid' => $this->tenantId]
        );
        
        if (!$staff) {
            return ['success' => false, 'error' => 'Staff not found'];
        }
        
        if ($staff['role'] === 'owner') {
            return ['success' => false, 'error' => 'Cannot block owner'];
        }
        
        $this->db->execute(
            "UPDATE users SET is_active = :active WHERE id = :id",
            ['id' => $staffId, 'active' => $active ? 1 : 0]
        );
        
        // Force logout if blocking
        if (!$active) {
            $this->auth->logoutAllDevices($staffId);
        }
        
        $this->logAction(
            $active ? 'staff_unblocked' : 'staff_blocked',
            'user',
            $staffId,
            ['is_active' => $staff['is_active']],
            ['is_active' => $active],
            $reason,
            self::RISK_MEDIUM
        );
        
        return ['success' => true];
    }
    
    /**
     * Force logout staff from all devices
     */
    public function forceLogoutStaff(int $staffId, string $reason): array
    {
        $staff = $this->db->queryOne(
            "SELECT id, first_name, last_name FROM users 
             WHERE id = :id AND tenant_id = :tid",
            ['id' => $staffId, 'tid' => $this->tenantId]
        );
        
        if (!$staff) {
            return ['success' => false, 'error' => 'Staff not found'];
        }
        
        $this->auth->logoutAllDevices($staffId);
        
        $this->logAction(
            'staff_force_logout',
            'user',
            $staffId,
            null,
            ['staff_name' => $staff['first_name'] . ' ' . $staff['last_name']],
            $reason,
            self::RISK_MEDIUM
        );
        
        return ['success' => true];
    }
    
    // ========================================
    // SECTION 4: BILL MODIFICATION ENGINE
    // ========================================
    
    /**
     * Create invoice snapshot before modification
     */
    public function createInvoiceSnapshot(int $invoiceId, int $bookingId, string $snapshotReason): int
    {
        // Get full invoice data
        $invoiceHandler = new InvoiceHandler();
        $invoiceData = $invoiceHandler->getInvoiceData($bookingId);
        
        $this->db->execute(
            "INSERT INTO invoice_snapshots 
             (tenant_id, invoice_id, booking_id, snapshot_data, snapshot_reason, created_by)
             VALUES (:tid, :inv, :book, :data, :reason, :user)",
            [
                'tid' => $this->tenantId,
                'inv' => $invoiceId,
                'book' => $bookingId,
                'data' => json_encode($invoiceData),
                'reason' => $snapshotReason,
                'user' => $this->userId
            ]
        );
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Modify invoice (DANGER)
     */
    public function modifyInvoice(
        int $bookingId,
        array $modifications,
        string $reason,
        string $confirmPassword
    ): array {
        // Verify owner password
        if (!$this->verifyOwnerPassword($confirmPassword)) {
            return ['success' => false, 'error' => 'Invalid confirmation password'];
        }
        
        if (strlen($reason) < 20) {
            return ['success' => false, 'error' => 'Reason must be at least 20 characters'];
        }
        
        // Get current invoice
        $invoice = $this->db->queryOne(
            "SELECT * FROM invoices WHERE booking_id = :bid AND tenant_id = :tid",
            ['bid' => $bookingId, 'tid' => $this->tenantId]
        );
        
        if (!$invoice) {
            return ['success' => false, 'error' => 'Invoice not found'];
        }
        
        // Create snapshot
        $snapshotId = $this->createInvoiceSnapshot($invoice['id'], $bookingId, 'before_edit');
        
        // Build update
        $allowedFields = ['invoice_date', 'notes'];
        $updates = [];
        $params = ['id' => $invoice['id']];
        $oldValues = [];
        $newValues = [];
        
        foreach ($allowedFields as $field) {
            if (isset($modifications[$field])) {
                $updates[] = "$field = :$field";
                $params[$field] = $modifications[$field];
                $oldValues[$field] = $invoice[$field] ?? null;
                $newValues[$field] = $modifications[$field];
            }
        }
        
        if (!empty($updates)) {
            $this->db->execute(
                "UPDATE invoices SET " . implode(', ', $updates) . " WHERE id = :id",
                $params
            );
        }
        
        // Also update booking if needed
        $bookingUpdates = [];
        $bookingParams = ['id' => $bookingId];
        
        $bookingFields = ['discount_amount', 'extra_charges'];
        foreach ($bookingFields as $field) {
            if (isset($modifications[$field])) {
                $bookingUpdates[] = "$field = :$field";
                $bookingParams[$field] = $modifications[$field];
                $oldValues['booking_' . $field] = null; // We'd need to fetch old
                $newValues['booking_' . $field] = $modifications[$field];
            }
        }
        
        if (!empty($bookingUpdates)) {
            $this->db->execute(
                "UPDATE bookings SET " . implode(', ', $bookingUpdates) . " WHERE id = :id",
                $bookingParams
            );
        }
        
        $this->logAction(
            'invoice_modified',
            'invoice',
            $invoice['id'],
            $oldValues,
            $newValues,
            $reason,
            self::RISK_CRITICAL,
            true
        );
        
        return ['success' => true, 'snapshot_id' => $snapshotId];
    }
    
    /**
     * Void invoice (DANGER)
     */
    public function voidInvoice(int $bookingId, string $reason, string $confirmPassword): array
    {
        if (!$this->verifyOwnerPassword($confirmPassword)) {
            return ['success' => false, 'error' => 'Invalid confirmation password'];
        }
        
        if (strlen($reason) < 20) {
            return ['success' => false, 'error' => 'Reason must be at least 20 characters'];
        }
        
        $invoice = $this->db->queryOne(
            "SELECT * FROM invoices WHERE booking_id = :bid AND tenant_id = :tid",
            ['bid' => $bookingId, 'tid' => $this->tenantId]
        );
        
        if (!$invoice) {
            return ['success' => false, 'error' => 'Invoice not found'];
        }
        
        if ($invoice['status'] === 'void') {
            return ['success' => false, 'error' => 'Invoice already void'];
        }
        
        // Create snapshot
        $snapshotId = $this->createInvoiceSnapshot($invoice['id'], $bookingId, 'before_void');
        
        // Mark as void
        $this->db->execute(
            "UPDATE invoices SET status = 'void', void_reason = :reason, voided_at = NOW(), voided_by = :user 
             WHERE id = :id",
            [
                'id' => $invoice['id'],
                'reason' => $reason,
                'user' => $this->userId
            ]
        );
        
        $this->logAction(
            'invoice_voided',
            'invoice',
            $invoice['id'],
            ['status' => $invoice['status'], 'grand_total' => $invoice['grand_total'] ?? 0],
            ['status' => 'void', 'void_reason' => $reason],
            $reason,
            self::RISK_CRITICAL,
            true
        );
        
        return ['success' => true, 'snapshot_id' => $snapshotId];
    }
    
    // ========================================
    // SECTION 5: FINANCIAL OVERRIDE ENGINE
    // ========================================
    
    /**
     * Adjust shift cash (emergency)
     */
    public function adjustShiftCash(
        int $shiftId,
        float $adjustmentAmount,
        string $reason,
        string $confirmPassword
    ): array {
        if (!$this->verifyOwnerPassword($confirmPassword)) {
            return ['success' => false, 'error' => 'Invalid confirmation password'];
        }
        
        $shift = $this->db->queryOne(
            "SELECT * FROM shifts WHERE id = :id AND tenant_id = :tid",
            ['id' => $shiftId, 'tid' => $this->tenantId]
        );
        
        if (!$shift) {
            return ['success' => false, 'error' => 'Shift not found'];
        }
        
        $oldExpected = (float)$shift['system_expected_cash'];
        $newExpected = $oldExpected + $adjustmentAmount;
        
        $this->db->execute(
            "UPDATE shifts SET system_expected_cash = :new WHERE id = :id",
            ['id' => $shiftId, 'new' => $newExpected]
        );
        
        $this->logAction(
            'cash_adjustment',
            'shift',
            $shiftId,
            ['system_expected_cash' => $oldExpected],
            ['system_expected_cash' => $newExpected, 'adjustment' => $adjustmentAmount],
            $reason,
            self::RISK_CRITICAL,
            true
        );
        
        return ['success' => true, 'old' => $oldExpected, 'new' => $newExpected];
    }
    
    // ========================================
    // SECTION 7: AUDIT & FORENSICS ENGINE
    // ========================================
    
    /**
     * Get engine action logs
     */
    public function getEngineLogs(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $userId = null,
        ?string $actionType = null,
        ?string $riskLevel = null,
        int $limit = 100
    ): array {
        $where = ['ea.tenant_id = :tid'];
        $params = ['tid' => $this->tenantId];
        
        if ($fromDate) {
            $where[] = 'ea.created_at >= :from_date';
            $params['from_date'] = $fromDate . ' 00:00:00';
        }
        
        if ($toDate) {
            $where[] = 'ea.created_at <= :to_date';
            $params['to_date'] = $toDate . ' 23:59:59';
        }
        
        if ($userId) {
            $where[] = 'ea.user_id = :user_id';
            $params['user_id'] = $userId;
        }
        
        if ($actionType) {
            $where[] = 'ea.action_type = :action_type';
            $params['action_type'] = $actionType;
        }
        
        if ($riskLevel) {
            $where[] = 'ea.risk_level = :risk_level';
            $params['risk_level'] = $riskLevel;
        }
        
        return $this->db->query(
            "SELECT ea.*, u.first_name, u.last_name, u.role
             FROM engine_actions ea
             LEFT JOIN users u ON ea.user_id = u.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY ea.created_at DESC
             LIMIT $limit",
            $params
        );
    }
    
    /**
     * Get invoice snapshots
     */
    public function getInvoiceSnapshots(int $invoiceId): array
    {
        return $this->db->query(
            "SELECT iss.*, u.first_name, u.last_name
             FROM invoice_snapshots iss
             LEFT JOIN users u ON iss.created_by = u.id
             WHERE iss.invoice_id = :inv AND iss.tenant_id = :tid
             ORDER BY iss.created_at DESC",
            ['inv' => $invoiceId, 'tid' => $this->tenantId]
        );
    }
    
    // ========================================
    // INTERNAL HELPERS
    // ========================================
    
    /**
     * Verify owner password for dangerous actions
     */
    private function verifyOwnerPassword(string $password): bool
    {
        $owner = $this->db->queryOne(
            "SELECT password_hash FROM users WHERE id = :id",
            ['id' => $this->userId],
            enforceTenant: false
        );
        
        if (!$owner) {
            return false;
        }
        
        return password_verify($password, $owner['password_hash']);
    }
    
    /**
     * Log action to engine_actions table
     */
    private function logAction(
        string $actionType,
        string $entityType,
        ?int $entityId,
        ?array $oldValues,
        ?array $newValues,
        string $reason,
        string $riskLevel,
        bool $passwordConfirmed = false
    ): void {
        $this->db->execute(
            "INSERT INTO engine_actions 
             (tenant_id, user_id, action_type, entity_type, entity_id, 
              old_values, new_values, reason, risk_level, password_confirmed,
              ip_address, user_agent)
             VALUES 
             (:tid, :uid, :action, :entity, :eid,
              :old, :new, :reason, :risk, :confirmed,
              :ip, :ua)",
            [
                'tid' => $this->tenantId,
                'uid' => $this->userId,
                'action' => $actionType,
                'entity' => $entityType,
                'eid' => $entityId,
                'old' => $oldValues ? json_encode($oldValues) : null,
                'new' => $newValues ? json_encode($newValues) : null,
                'reason' => $reason,
                'risk' => $riskLevel,
                'confirmed' => $passwordConfirmed ? 1 : 0,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
            ],
            enforceTenant: false
        );
    }
    
    /**
     * Check if date is locked
     */
    public function isDateLocked(string $date): bool
    {
        $tenant = $this->db->queryOne(
            "SELECT data_locked_until FROM tenants WHERE id = :id",
            ['id' => $this->tenantId],
            enforceTenant: false
        );
        
        if (!$tenant || !$tenant['data_locked_until']) {
            return false;
        }
        
        return $date <= $tenant['data_locked_until'];
    }
}
