-- Phase 4: Compliance & Audit Layer
-- Created: 2026-01-06

-- 1. Audit Checkpoints (WORM Implementation)
-- Stores the cryptographic hash of a block of audit logs to prove they haven't been tampered with.
CREATE TABLE IF NOT EXISTS audit_checkpoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    start_log_id INT NOT NULL, -- ID of the first audit_log in this block
    end_log_id INT NOT NULL,   -- ID of the last audit_log in this block
    record_count INT NOT NULL,
    block_hash VARCHAR(64) NOT NULL, -- SHA-256 Hash of the combined logs in this block
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_created (tenant_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Shift Locks
-- Ensures that once a shift is verified and locked, it cannot be modified.
CREATE TABLE IF NOT EXISTS shift_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_id INT NOT NULL,
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    locked_by INT NOT NULL,
    digital_signature VARCHAR(255) NOT NULL, -- Signature of the shift totals
    UNIQUE KEY (shift_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
