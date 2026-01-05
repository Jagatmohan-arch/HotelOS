<?php
/**
 * HotelOS - Compliance Service
 * 
 * MODULE 2: COMPLIANCE & AUDIT LEYER
 * Enforces WORM (Write Once Read Many) policies and manages integrity hashing.
 */

declare(strict_types=1);

namespace HotelOS\Core;

use HotelOS\Core\Database;

class ComplianceService
{
    private Database $db;
    private string $salt = 'HOTELOS_ENT_SALT_v1'; // Validation Salt

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate WORM Checkpoint for a Tenant
     * Should be run Daily via Cron
     */
    public function generateDailyCheckpoint(int $tenantId): array
    {
        // 1. Get last checkpoint
        $lastCheckpoint = $this->db->queryOne(
            "SELECT end_log_id FROM audit_checkpoints WHERE tenant_id = :tid ORDER BY id DESC LIMIT 1",
            ['tid' => $tenantId],
            enforceTenant: false
        );

        $startId = ($lastCheckpoint['end_log_id'] ?? 0) + 1;

        // 2. Fetch new logs
        $logs = $this->db->query(
            "SELECT id, action, entity_type, entity_id, created_at, old_values, new_values 
             FROM audit_logs 
             WHERE tenant_id = :tid AND id >= :start
             ORDER BY id ASC",
            ['tid' => $tenantId, 'start' => $startId],
            enforceTenant: false
        );

        if (empty($logs)) {
            return ['status' => 'skipped', 'message' => 'No new logs to hash'];
        }

        // 3. Calculate Block Hash (Merkle-like Chain)
        $dataToHash = '';
        $endId = $startId;
        foreach ($logs as $log) {
            $dataToHash .= implode('|', [
                $log['id'], 
                $log['action'], 
                $log['entity_type'], 
                $log['entity_id'], 
                $log['created_at'],
                md5($log['old_values'] ?? ''), 
                md5($log['new_values'] ?? '')
            ]);
            $endId = $log['id'];
        }

        // Final Block Hash
        $blockHash = hash_hmac('sha256', $dataToHash, $this->salt);

        // 4. Store Checkpoint
        // Note: In a real DB-blocked scenario, this INSERT would fail if table doesn't exist.
        // We assume the schema exists or this is a "Code Only" implementation.
        try {
            $this->db->execute(
                "INSERT INTO audit_checkpoints (tenant_id, start_log_id, end_log_id, record_count, block_hash)
                 VALUES (:tid, :start, :end, :count, :hash)",
                [
                    'tid' => $tenantId,
                    'start' => $startId,
                    'end' => $endId,
                    'count' => count($logs),
                    'hash' => $blockHash
                ],
                enforceTenant: false
            );
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'DB Write Failed: ' . $e->getMessage()];
        }

        return [
            'status' => 'success',
            'range' => "$startId - $endId",
            'hash' => $blockHash
        ];
    }

    /**
     * Lock a Shift (Prevent future edits)
     */
    public function lockShift(int $shiftId, int $userId, float $declaredCash, float $calculatedCash): bool
    {
        // Create a signature of the totals
        $signature = hash_hmac('sha256', "SHIFT:$shiftId|DEC:$declaredCash|CALC:$calculatedCash", $this->salt);

        try {
            $this->db->execute(
                "INSERT INTO shift_locks (shift_id, locked_by, digital_signature)
                 VALUES (:sid, :uid, :sig)",
                ['sid' => $shiftId, 'uid' => $userId, 'sig' => $signature],
                enforceTenant: false
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verify if a Shift is Locked
     */
    public function isShiftLocked(int $shiftId): bool
    {
        $lock = $this->db->queryOne(
            "SELECT id FROM shift_locks WHERE shift_id = :sid",
            ['sid' => $shiftId],
            enforceTenant: false
        );
        return (bool) $lock;
    }
}
