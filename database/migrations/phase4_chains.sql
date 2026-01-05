-- Phase 4: Enterprise Scale - Chain Management
-- Created: 2026-01-06

-- 1. Create Chains Table
CREATE TABLE IF NOT EXISTS chains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner_id INT NOT NULL, -- User who owns this chain account
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chain_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add Chain ID to Tenants
-- Nullable because some hotels are standalone
ALTER TABLE tenants ADD COLUMN chain_id INT NULL AFTER id;
ALTER TABLE tenants ADD INDEX idx_tenant_chain (chain_id);

-- 3. Add Chain ID to Users (for Super Admins who don't belong to a single tenant)
-- Users can now optionally belong to a chain context directly
ALTER TABLE users ADD COLUMN chain_id INT NULL AFTER tenant_id;
ALTER TABLE users ADD INDEX idx_user_chain (chain_id);

-- 4. Constraint (Optional but good for integrity)
-- ALTER TABLE tenants ADD CONSTRAINT fk_tenant_chain FOREIGN KEY (chain_id) REFERENCES chains(id) ON DELETE SET NULL;
