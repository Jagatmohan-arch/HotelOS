<?php
/**
 * HotelOS - File Upload Handler
 * 
 * Handles ID photo uploads for guests
 */

declare(strict_types=1);

namespace HotelOS\Handlers;

use HotelOS\Core\Database;
use HotelOS\Core\TenantContext;
use HotelOS\Core\Auth;

class UploadHandler
{
    private Database $db;
    private Auth $auth;
    private string $uploadDir;
    
    // Allowed image types
    private array $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    
    // Max file size (5MB)
    private int $maxSize = 5 * 1024 * 1024;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
        
        // Base upload directory
        $this->uploadDir = dirname(__DIR__) . '/uploads';
    }
    
    /**
     * Upload guest ID photo
     * 
     * @param int $guestId Guest ID
     * @param array $file $_FILES array element
     * @return array Success/error response
     */
    public function uploadGuestIdPhoto(int $guestId, array $file): array
    {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No file uploaded'];
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            return ['success' => false, 'error' => 'File too large. Max 5MB allowed.'];
        }
        
        // Check file type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WebP allowed.'];
        }
        
        // Verify guest exists and belongs to tenant
        $guest = $this->db->queryOne(
            "SELECT id, first_name, id_photo_path FROM guests WHERE id = :id",
            ['id' => $guestId]
        );
        
        if (!$guest) {
            return ['success' => false, 'error' => 'Guest not found'];
        }
        
        // Create tenant upload directory
        $tenantId = TenantContext::getId();
        $idDir = $this->uploadDir . '/id/' . $tenantId;
        
        if (!is_dir($idDir)) {
            mkdir($idDir, 0755, true);
        }
        
        // Generate unique filename
        $ext = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg'
        };
        
        $filename = 'guest_' . $guestId . '_' . date('Ymd_His') . '.' . $ext;
        $filepath = $idDir . '/' . $filename;
        $relativePath = '/uploads/id/' . $tenantId . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }
        
        // Delete old photo if exists
        if (!empty($guest['id_photo_path'])) {
            $oldPath = dirname(__DIR__) . $guest['id_photo_path'];
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }
        
        // Update database
        $this->db->execute(
            "UPDATE guests SET id_photo_path = :path, updated_at = NOW() WHERE id = :id",
            ['path' => $relativePath, 'id' => $guestId]
        );
        
        // Audit log
        $this->auth->logAudit(
            'guest_id_uploaded',
            'guest',
            $guestId,
            ['old_photo' => $guest['id_photo_path']],
            ['new_photo' => $relativePath]
        );
        
        return [
            'success' => true,
            'path' => $relativePath,
            'message' => 'ID photo uploaded successfully'
        ];
    }
    
    /**
     * Get guest ID photo
     */
    public function getGuestIdPhoto(int $guestId): ?string
    {
        $guest = $this->db->queryOne(
            "SELECT id_photo_path FROM guests WHERE id = :id",
            ['id' => $guestId]
        );
        
        return $guest['id_photo_path'] ?? null;
    }
    
    /**
     * Delete guest ID photo
     */
    public function deleteGuestIdPhoto(int $guestId): array
    {
        $guest = $this->db->queryOne(
            "SELECT id, id_photo_path FROM guests WHERE id = :id",
            ['id' => $guestId]
        );
        
        if (!$guest) {
            return ['success' => false, 'error' => 'Guest not found'];
        }
        
        if (!empty($guest['id_photo_path'])) {
            $filepath = dirname(__DIR__) . $guest['id_photo_path'];
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            
            $this->db->execute(
                "UPDATE guests SET id_photo_path = NULL, updated_at = NOW() WHERE id = :id",
                ['id' => $guestId]
            );
        }
        
        return ['success' => true, 'message' => 'ID photo deleted'];
    }
}
