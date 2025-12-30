-- ==========================================
-- Missing Table: engine_actions
-- For Owner super-control audit trail
-- ==========================================

CREATE TABLE IF NOT EXISTS `engine_actions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Owner who performed action',
    
    `action_type` VARCHAR(50) NOT NULL COMMENT 'modify_invoice, void_invoice, adjust_shift, etc.',
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED NULL,
    
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `reason` VARCHAR(500) NOT NULL COMMENT 'Mandatory justification',
    
    `risk_level` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    `password_confirmed` BOOLEAN DEFAULT FALSE,
    
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_engine_tenant` (`tenant_id`),
    INDEX `idx_engine_user` (`user_id`),
    INDEX `idx_engine_action` (`action_type`),
    INDEX `idx_engine_risk` (`risk_level`),
    INDEX `idx_engine_date` (`created_at`),
    
    CONSTRAINT `fk_engine_tenant` 
        FOREIGN KEY (`tenant_id`) 
        REFERENCES `tenants`(`id`) 
        ON DELETE CASCADE,
    CONSTRAINT `fk_engine_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) 
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✅ engine_actions table created' AS Status;
