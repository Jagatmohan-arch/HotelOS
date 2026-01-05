-- Phase 4: Role Governance & Permission Engine
-- Created: 2026-01-06

-- 1. Permissions Table (Master List)
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(64) NOT NULL UNIQUE, -- e.g. 'view_dashboard', 'refund_execution'
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'general', -- e.g. 'finance', 'operations'
    INDEX idx_perm_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Role Permissions (Mapping)
-- Maps roles (strings in users table) to specialized permissions
-- Note: 'role_name' corresponds to users.role enum/string
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL, -- Customize per hotel if needed (Enterprise Feature)
    role_name VARCHAR(50) NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_role_perm (tenant_id, role_name, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Seed Default Permissions (Core Lock - Do not delete these)
INSERT IGNORE INTO permissions (slug, name, category) VALUES 
('view_dashboard', 'View Dashboard', 'general'),
('manage_bookings', 'Create & Edit Bookings', 'operations'),
('manage_rooms', 'Manage Rooms', 'operations'),
('manage_users', 'Manage Staff', 'admin'),
('view_reports', 'View Reports', 'finance'),
('manage_billing', 'Manage Billing', 'finance'),
('delete_records', 'Delete Records', 'admin'),
('view_audit_logs', 'View Audit Logs', 'compliance'),
('approve_discounts', 'Approve Discounts', 'finance');
