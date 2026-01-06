<?php
/**
 * HotelOS - Audit Lock Manager
 * 
 * MODULE 3: COMPLIANCE & AUDIT LOCKS
 * Ensures strict immutability for closed shifts and financial records.
 */

declare(strict_types=1);

namespace HotelOS\Core;

class AuditLock
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lock a Shift (Immutable)
     * To be called when Manager clicks "Verify & Close"
     */
    public function lockShift(int $shiftId, int $userId): bool
    {
        // 1. Get Shift Totals for Signature
        $shift = $this->db->queryOne(
            "SELECT opening_cash, closing_cash, total_cash_collected FROM shifts WHERE id = :id",
            ['id' => $shiftId]
        );

        if (!$shift) return false;

        // 2. Generate Digital Signature
        // Hash of ID + Totals + Secret Key
        $signaturePayload = "{$shiftId}|{$shift['opening_cash']}|{$shift['closing_cash']}|{$shift['total_cash_collected']}";
        // 3. Get Application Key (Critical Security)
        $appKey = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? null);
        
        if (empty($appKey)) {
            // Enterprise Compliance: Cannot lock without secure key
            // In dev, we might allow it, but for now we enforce it strictly for safety
            if ((getenv('APP_ENV') ?: 'production') !== 'local') {
                throw new \Exception("SECURITY CRITICAL: APP_KEY is missing. Cannot securely lock shift.");
            }
            $appKey = 'dev_only_unsafe_secret';
        }

        $signature = hash_hmac('sha256', $signaturePayload, $appKey);

        // 3. Store Lock
        try {
            $this->db->execute(
                "INSERT INTO shift_locks (shift_id, locked_by, digital_signature) 
                 VALUES (:shift_id, :user_id, :signature)",
                [
                    'shift_id' => $shiftId,
                    'user_id' => $userId,
                    'signature' => $signature
                ]
            );
            return true;
        } catch (\Exception $e) {
            // Likely already locked
            return false;
        }
    }

    /**
     * Check if a Shift is Locked
     */
    public function isShiftLocked(int $shiftId): bool
    {
        $lock = $this->db->queryOne(
            "SELECT id FROM shift_locks WHERE shift_id = :id",
            ['id' => $shiftId],
            enforceTenant: false
        );
        return (bool)$lock;
    }

    /**
     * Create WORM Checkpoint for Audit Logs
     * Stores a hash of the last N logs to prevent tampering
     */
    public function createCheckpoint(): array
    {
        $tenantId = TenantContext::getId();

        // 1. Find last checkpoint
        $lastCheckpoint = $this->db->queryOne(
            "SELECT end_log_id FROM audit_checkpoints WHERE tenant_id = :tid ORDER BY id DESC LIMIT 1",
            ['tid' => $tenantId]
        );
        $startFrom = ($lastCheckpoint['end_log_id'] ?? 0) + 1;

        // 2. Fetch new logs (limit 1000)
        $logs = $this->db->query(
            "SELECT id, action, created_at FROM audit_logs 
             WHERE id >= :start AND tenant_id = :tid 
             ORDER BY id ASC LIMIT 1000",
            ['start' => $startFrom, 'tid' => $tenantId]
        );

        if (empty($logs)) {
            return ['success' => false, 'message' => 'No new logs to checkpoint'];
        }

        $startLogId = $logs[0]['id'];
        $endLogId = end($logs)['id'];
        $count = count($logs);

        // 3. Generate Block Hash (Merkle Chain Concept)
        // Combine IDs + Timestamps + Previous Checkpoint Hash (if we wanted full blockchain style)
        // For now, just hashing the content of this block
        $blockData = json_encode($logs);
        $blockHash = hash('sha256', $blockData);

        // 4. Store Checkpoint
        $this->db->execute(
            "INSERT INTO audit_checkpoints (tenant_id, start_log_id, end_log_id, record_count, block_hash) 
             VALUES (:tid, :start, :end, :count, :hash)",
            [
                'tid' => $tenantId,
                'start' => $startLogId,
                'end' => $endLogId,
                'count' => $count,
                'hash' => $blockHash
            ]
        );

        return [
            'success' => true,
            'message' => "Checkpoint created for logs #{$startLogId}-#{$endLogId}",
            'hash' => $blockHash
        ];
    }
}
