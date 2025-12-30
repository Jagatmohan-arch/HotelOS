-- ==========================================
-- Phase B: Subscription System - Database Schema
-- ==========================================

-- Add subscription tracking to tenants table
ALTER TABLE tenants
ADD COLUMN plan_started_at TIMESTAMP NULL AFTER status,
ADD COLUMN trial_ends_at TIMESTAMP NULL AFTER plan_started_at,
ADD COLUMN subscription_id VARCHAR(255) NULL AFTER trial_ends_at COMMENT 'Razorpay subscription ID',
ADD COLUMN next_billing_date DATE NULL AFTER subscription_id,
ADD COLUMN billing_status ENUM('active', 'past_due', 'cancelled', 'trial') DEFAULT 'trial' AFTER next_billing_date;

-- Create subscription plans table
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL COMMENT 'starter, professional, enterprise',
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100) NOT NULL COMMENT 'Public facing name',
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2) NULL COMMENT 'Discounted yearly price',
    max_rooms INT NULL COMMENT 'NULL = unlimited',
    max_users INT NULL COMMENT 'NULL = unlimited',
    features JSON NULL COMMENT 'Feature flags',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subscription transactions table
CREATE TABLE IF NOT EXISTS subscription_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    payment_gateway VARCHAR(50) DEFAULT 'razorpay',
    gateway_transaction_id VARCHAR(255) NULL COMMENT 'Razorpay payment ID',
    gateway_order_id VARCHAR(255) NULL COMMENT 'Razorpay order ID',
    status ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
    type ENUM('trial', 'subscription', 'upgrade', 'renewal', 'downgrade') NOT NULL,
    billing_period ENUM('monthly', 'yearly') NULL,
    invoice_url VARCHAR(500) NULL,
    metadata JSON NULL COMMENT 'Additional payment details',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_gateway_txn (gateway_transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed subscription plans
INSERT INTO subscription_plans (slug, name, display_name, price_monthly, price_yearly, max_rooms, max_users, features, sort_order) VALUES
('starter', 'Starter', 'Starter Plan', 999.00, 9990.00, 10, 5, '{"email_notifications": true, "basic_reports": true, "police_reports": true, "pdf_invoices": true, "sms_notifications": false, "advanced_reports": false, "api_access": false, "whatsapp": false}', 1),
('professional', 'Professional', 'Professional Plan', 2499.00, 24990.00, 30, 15, '{"email_notifications": true, "basic_reports": true, "police_reports": true, "pdf_invoices": true, "sms_notifications": true, "advanced_reports": true, "api_access": true, "whatsapp": false, "priority_support": false}', 2),
('enterprise', 'Enterprise', 'Enterprise Plan', 4999.00, 49990.00, NULL, NULL, '{"email_notifications": true, "basic_reports": true, "police_reports": true, "pdf_invoices": true, "sms_notifications": true, "advanced_reports": true, "api_access": true, "whatsapp": true, "priority_support": true, "custom_branding": true}', 3);

SELECT '✅ Subscription system tables created and plans seeded' AS Status;
