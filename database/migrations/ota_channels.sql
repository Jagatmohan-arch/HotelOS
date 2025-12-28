-- HotelOS OTA Channel Manager
-- Created: 2024-12-28

-- OTA Channels (connected platforms)
CREATE TABLE IF NOT EXISTS ota_channels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    platform VARCHAR(50) NOT NULL,
    platform_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    property_id VARCHAR(100),
    hotel_code VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    sync_inventory BOOLEAN DEFAULT TRUE,
    sync_rates BOOLEAN DEFAULT TRUE,
    commission_rate DECIMAL(5,2) DEFAULT 15.00,
    last_sync TIMESTAMP NULL,
    last_error TEXT,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_tenant_platform (tenant_id, platform),
    INDEX idx_active (is_active)
);

-- OTA Bookings (bookings from OTA platforms)
CREATE TABLE IF NOT EXISTS ota_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    booking_id INT NULL,
    ota_channel_id INT NOT NULL,
    ota_booking_ref VARCHAR(100) NOT NULL,
    ota_confirmation_no VARCHAR(100),
    guest_name VARCHAR(255) NOT NULL,
    guest_email VARCHAR(255),
    guest_phone VARCHAR(20),
    room_type_requested VARCHAR(100),
    room_id INT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    nights INT NOT NULL,
    adults INT DEFAULT 1,
    children INT DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    commission_rate DECIMAL(5,2),
    commission_amount DECIMAL(10,2),
    net_amount DECIMAL(10,2),
    payment_mode ENUM('prepaid', 'pay_at_hotel', 'partial') DEFAULT 'prepaid',
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    special_requests TEXT,
    raw_data JSON,
    sync_status ENUM('pending', 'synced', 'failed', 'cancelled') DEFAULT 'pending',
    sync_error TEXT,
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_ota_ref (ota_channel_id, ota_booking_ref),
    INDEX idx_dates (check_in_date, check_out_date),
    INDEX idx_status (sync_status),
    FOREIGN KEY (ota_channel_id) REFERENCES ota_channels(id)
);

-- OTA Rate Plans (rate mapping)
CREATE TABLE IF NOT EXISTS ota_rate_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ota_channel_id INT NOT NULL,
    room_type_id INT NOT NULL,
    ota_room_code VARCHAR(50),
    ota_rate_code VARCHAR(50),
    rate_markup DECIMAL(5,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ota_channel_id) REFERENCES ota_channels(id),
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- OTA Inventory Sync Log
CREATE TABLE IF NOT EXISTS ota_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ota_channel_id INT NOT NULL,
    sync_type ENUM('inventory', 'rates', 'booking', 'full') NOT NULL,
    direction ENUM('inbound', 'outbound') NOT NULL,
    status ENUM('success', 'partial', 'failed') NOT NULL,
    records_processed INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    error_details TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_channel_date (ota_channel_id, started_at),
    FOREIGN KEY (ota_channel_id) REFERENCES ota_channels(id)
);

-- Insert default OTA platforms
INSERT IGNORE INTO ota_channels (tenant_id, platform, platform_name, commission_rate, is_active) VALUES
(1, 'mmt', 'MakeMyTrip', 18.00, FALSE),
(1, 'goibibo', 'Goibibo', 18.00, FALSE),
(1, 'agoda', 'Agoda', 15.00, FALSE),
(1, 'booking', 'Booking.com', 15.00, FALSE),
(1, 'oyo', 'OYO', 22.00, FALSE),
(1, 'yatra', 'Yatra', 15.00, FALSE);
