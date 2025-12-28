-- HotelOS Staff Shift & Handover System
-- Created: 2024-12-28

-- Staff Shifts Table
CREATE TABLE IF NOT EXISTS staff_shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    user_id INT NOT NULL,
    shift_date DATE NOT NULL,
    shift_start DATETIME NOT NULL,
    shift_end DATETIME NULL,
    opening_cash DECIMAL(10,2) DEFAULT 0.00,
    closing_cash DECIMAL(10,2) NULL,
    cash_collected DECIMAL(10,2) DEFAULT 0.00,
    upi_collected DECIMAL(10,2) DEFAULT 0.00,
    card_collected DECIMAL(10,2) DEFAULT 0.00,
    other_collected DECIMAL(10,2) DEFAULT 0.00,
    total_collected DECIMAL(10,2) DEFAULT 0.00,
    bookings_count INT DEFAULT 0,
    checkins_count INT DEFAULT 0,
    checkouts_count INT DEFAULT 0,
    expected_cash DECIMAL(10,2) DEFAULT 0.00,
    cash_difference DECIMAL(10,2) DEFAULT 0.00,
    handover_notes TEXT,
    handover_to_user_id INT NULL,
    status ENUM('active', 'ended', 'handover_pending', 'handover_complete') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tenant_date (tenant_id, shift_date),
    INDEX idx_user_status (user_id, status),
    INDEX idx_status (status)
);

-- Shift Activities Log (auto-tracked)
CREATE TABLE IF NOT EXISTS shift_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shift_id INT NOT NULL,
    activity_type ENUM('checkin', 'checkout', 'payment', 'refund', 'expense', 'pos_sale', 'adjustment') NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    amount DECIMAL(10,2) DEFAULT 0.00,
    payment_mode ENUM('cash', 'upi', 'card', 'other') DEFAULT 'cash',
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_shift (shift_id),
    INDEX idx_type (activity_type),
    FOREIGN KEY (shift_id) REFERENCES staff_shifts(id) ON DELETE CASCADE
);

-- Shift Handover Records
CREATE TABLE IF NOT EXISTS shift_handovers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_shift_id INT NOT NULL,
    to_shift_id INT NULL,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    handover_time DATETIME NOT NULL,
    cash_handed DECIMAL(10,2) NOT NULL,
    cash_verified DECIMAL(10,2) NULL,
    difference DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    verified_by_receiver BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'verified', 'disputed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_from_shift (from_shift_id),
    INDEX idx_status (status),
    FOREIGN KEY (from_shift_id) REFERENCES staff_shifts(id)
);
