<?php
/**
 * HotelOS - Cloud Backup Handler
 * 
 * Server-light backup system using client-side processing
 * Supports incremental backups with JSON export
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;

class BackupHandler
{
    private Database $db;
    
    private const EXPORTABLE_TABLES = [
        'guests', 'rooms', 'room_types', 'bookings', 'transactions',
        'staff_shifts', 'ota_bookings', 'settings'
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Export data for backup (JSON format)
     */
    public function exportData(array $options = []): array
    {
        $tables = $options['tables'] ?? self::EXPORTABLE_TABLES;
        $fromDate = $options['from_date'] ?? null;
        
        $data = [
            'export_date' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'tables' => []
        ];
        
        foreach ($tables as $table) {
            if (!in_array($table, self::EXPORTABLE_TABLES)) continue;
            
            $query = "SELECT * FROM {$table}";
            $params = [];
            
            // Incremental backup by date
            if ($fromDate && $this->tableHasDateColumn($table)) {
                $query .= " WHERE created_at >= :from_date OR updated_at >= :from_date2";
                $params = ['from_date' => $fromDate, 'from_date2' => $fromDate];
            }
            
            $data['tables'][$table] = $this->db->query($query, $params);
        }
        
        $data['record_count'] = array_sum(array_map('count', $data['tables']));
        
        return $data;
    }
    
    /**
     * Get backup summary
     */
    public function getBackupSummary(): array
    {
        $summary = [];
        
        foreach (self::EXPORTABLE_TABLES as $table) {
            $count = $this->db->queryOne("SELECT COUNT(*) as cnt FROM {$table}");
            $summary[$table] = (int)($count['cnt'] ?? 0);
        }
        
        return [
            'tables' => $summary,
            'total_records' => array_sum($summary),
            'last_backup' => $this->getLastBackupTime()
        ];
    }
    
    /**
     * Log backup completion
     */
    public function logBackup(string $type, int $recordCount, ?string $destination = null): void
    {
        $this->db->execute(
            "INSERT INTO backup_log (backup_type, record_count, destination, created_at) 
             VALUES (:type, :count, :dest, NOW())",
            ['type' => $type, 'count' => $recordCount, 'dest' => $destination]
        );
    }
    
    /**
     * Get last backup time
     */
    private function getLastBackupTime(): ?string
    {
        $result = $this->db->queryOne(
            "SELECT created_at FROM backup_log ORDER BY created_at DESC LIMIT 1"
        );
        return $result['created_at'] ?? null;
    }
    
    /**
     * Check if table has date columns
     */
    private function tableHasDateColumn(string $table): bool
    {
        return in_array($table, ['bookings', 'transactions', 'guests', 'staff_shifts', 'ota_bookings']);
    }
    
    /**
     * Generate backup filename
     */
    public function generateFilename(): string
    {
        return 'hotelOS_backup_' . date('Y-m-d_His') . '.json';
    }
}
