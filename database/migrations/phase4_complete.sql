-- HOTELOS PHASE 4: ENTERPRISE & SCALABILITY MIGRATION
-- GENERATED: 2026-01-06
-- INCLUDES MODULES 1-6

SET FOREIGN_KEY_CHECKS=0;

-- ==========================================
-- MODULE 1: MULTI-PROPERTY & CHAIN MANAGEMENT
-- ==========================================

-- 1. Create Chains Table
CREATE TABLE IF NOT EXISTS chains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner_id INT NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chain_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add Chain ID to Tenants
ALTER TABLE tenants ADD COLUMN IF NOT EXISTS chain_id INT NULL AFTER id;
ALTER TABLE tenants ADD INDEX IF NOT EXISTS idx_tenant_chain (chain_id);

-- 3. Add Chain ID to Users
ALTER TABLE users ADD COLUMN IF NOT EXISTS chain_id INT NULL AFTER tenant_id;
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_user_chain (chain_id);


-- ==========================================
-- MODULE 2: COMPLIANCE & AUDIT LOCKS
-- ==========================================

-- 1. Audit Checkpoints (WORM Implementation)
CREATE TABLE IF NOT EXISTS audit_checkpoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    start_log_id INT NOT NULL,
    end_log_id INT NOT NULL,
    record_count INT NOT NULL,
    block_hash VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_created (tenant_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Shift Locks (Immutable Shifts)
CREATE TABLE IF NOT EXISTS shift_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_id INT NOT NULL,
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    locked_by INT NOT NULL,
    digital_signature VARCHAR(255) NOT NULL,
    UNIQUE KEY (shift_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================
-- MODULE 3: ROLE GOVERNANCE & PERMISSIONS
-- ==========================================

-- 1. Permissions Master List
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    INDEX idx_perm_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Role Permission Mapping
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    role_name VARCHAR(50) NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_role_perm (tenant_id, role_name, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Seed Default Permissions
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


-- ==========================================
-- MODULE 4: SLA, INCIDENT & SUPPORT
-- ==========================================

-- 1. System Health Logs
CREATE TABLE IF NOT EXISTS system_health_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL,
    metric VARCHAR(50) NOT NULL,
    value FLOAT NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_time (metric, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Support Incidents
CREATE TABLE IF NOT EXISTS support_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(20) DEFAULT 'open',
    subject VARCHAR(255) NOT NULL,
    description TEXT,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX idx_tenant_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SLA Breaches
CREATE TABLE IF NOT EXISTS sla_breaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    incident_id INT NOT NULL,
    expected_resolution_by TIMESTAMP NOT NULL,
    actual_resolution_at TIMESTAMP NULL,
    breach_duration_minutes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES support_incidents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================
-- MODULE 5: AI INSIGHTS (READ-ONLY)
-- ==========================================

-- 1. AI Insights
CREATE TABLE IF NOT EXISTS ai_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    confidence_score FLOAT DEFAULT 0.0,
    insight_text TEXT NOT NULL,
    action_data JSON NULL,
    status VARCHAR(20) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================
-- MODULE 6: SAAS BILLING & LICENSING
-- ==========================================

-- 1. SaaS Plans
CREATE TABLE IF NOT EXISTS saas_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    monthly_price DECIMAL(10,2) NOT NULL,
    max_rooms INT DEFAULT 10,
    features JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tenant Subscriptions
CREATE TABLE IF NOT EXISTS tenant_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    plan_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'trial',
    starts_at DATE NOT NULL,
    expires_at DATE NOT NULL,
    license_key VARCHAR(64) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_status (tenant_id, status),
    FOREIGN KEY (plan_id) REFERENCES saas_plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SaaS Invoices
CREATE TABLE IF NOT EXISTS saas_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    subscription_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'unpaid',
    due_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES tenant_subscriptions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- END OF MIGRATION
