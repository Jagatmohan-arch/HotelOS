-- Phase 4: SLA, Incident & Support System
-- Created: 2026-01-06

-- 1. System Health Logs (Uptime/Performance)
CREATE TABLE IF NOT EXISTS system_health_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL, -- System-wide logs have NULL tenant
    metric VARCHAR(50) NOT NULL, -- e.g., 'response_time', 'memory_usage', 'error_rate'
    value FLOAT NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_time (metric, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Support Incidents (Tickets)
CREATE TABLE IF NOT EXISTS support_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    user_id INT NOT NULL, -- Who reported it
    category VARCHAR(50) NOT NULL, -- 'bug', 'billing', 'feature_request'
    priority VARCHAR(20) DEFAULT 'medium', -- 'low', 'medium', 'high', 'critical'
    status VARCHAR(20) DEFAULT 'open', -- 'open', 'in_progress', 'resolved', 'closed'
    subject VARCHAR(255) NOT NULL,
    description TEXT,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX idx_tenant_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SLA Breaches
-- Auto-generated when a ticket exceeds SLA time limit
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
