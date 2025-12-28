-- HotelOS Chat & AI System
-- Created: 2024-12-28

-- Chat Conversations
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    type ENUM('owner_staff', 'staff_staff', 'ai_assist', 'support') NOT NULL,
    title VARCHAR(255),
    participants JSON,
    last_message_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_tenant_type (tenant_id, type),
    INDEX idx_active (is_active)
);

-- Chat Messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    sender_id INT NULL,
    sender_type ENUM('user', 'ai', 'system') NOT NULL,
    message TEXT NOT NULL,
    attachments JSON,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_conversation (conversation_id),
    INDEX idx_unread (conversation_id, is_read),
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
);

-- AI Suggestions
CREATE TABLE IF NOT EXISTS ai_suggestions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    type ENUM('seasonal', 'rate', 'occupancy', 'expense', 'alert') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    title VARCHAR(255) NOT NULL,
    suggestion TEXT NOT NULL,
    expected_impact VARCHAR(100),
    action_type VARCHAR(50),
    action_data JSON,
    is_dismissed BOOLEAN DEFAULT FALSE,
    is_applied BOOLEAN DEFAULT FALSE,
    applied_at TIMESTAMP NULL,
    valid_until DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_tenant_active (tenant_id, is_dismissed),
    INDEX idx_type (type)
);

-- Backup Log
CREATE TABLE IF NOT EXISTS backup_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT,
    backup_type ENUM('full', 'incremental', 'manual') NOT NULL,
    record_count INT DEFAULT 0,
    file_size INT DEFAULT 0,
    destination VARCHAR(100),
    status ENUM('success', 'failed') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_date (created_at)
);
