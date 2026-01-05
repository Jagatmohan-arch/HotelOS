-- Phase 4: AI Insights (Read-Only)
-- Created: 2026-01-06

-- 1. AI Insights Table
-- Stores generated suggestions. Does NOT auto-apply provided actions.
CREATE TABLE IF NOT EXISTS ai_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    category VARCHAR(50) NOT NULL, -- 'occupancy', 'revenue', 'staffing'
    confidence_score FLOAT DEFAULT 0.0, -- 0 to 1
    insight_text TEXT NOT NULL,
    action_data JSON NULL, -- Proposed action data (for manual approval)
    status VARCHAR(20) DEFAULT 'new', -- 'new', 'acknowledged', 'dismissed'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
