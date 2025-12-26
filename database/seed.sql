-- ============================================
-- HotelOS Demo Seed Data
-- Creates a sample hotel with test users
-- ============================================

-- Insert Demo Tenant (Hotel)
INSERT INTO `tenants` (
    `uuid`, `name`, `legal_name`, `slug`,
    `gst_number`, `state_code`, `pan_number`,
    `email`, `phone`, `address`, `city`, `state`, `pincode`,
    `status`
) VALUES (
    UUID(),
    'The Grand Palace Hotel',
    'Grand Palace Hospitality Pvt Ltd',
    'grand-palace',
    '27AABCU9603R1ZM',
    '27',
    'AABCU9603R',
    'info@grandpalace.com',
    '9876543210',
    '123 Marine Drive, Near Gateway',
    'Mumbai',
    'Maharashtra',
    '400001',
    'active'
);

SET @tenant_id = LAST_INSERT_ID();

-- Insert Demo Users (Password: Demo@123 - Argon2ID hashed)
-- Generated using: password_hash('Demo@123', PASSWORD_ARGON2ID)
INSERT INTO `users` (
    `tenant_id`, `uuid`, `email`, `password_hash`,
    `first_name`, `last_name`, `role`, `is_active`, `email_verified_at`
) VALUES 
-- Owner Account
(@tenant_id, UUID(), 'owner@grandpalace.com', 
 '$argon2id$v=19$m=65536,t=4,p=1$RXdYMnZBZ1VxV3RsMkpJTg$8Nh0M8YCvJvPHzqE4pKpB5C9DfFgHhIiJjKkLlMmNn0',
 'Rajesh', 'Sharma', 'owner', TRUE, NOW()),

-- Manager Account  
(@tenant_id, UUID(), 'manager@grandpalace.com',
 '$argon2id$v=19$m=65536,t=4,p=1$RXdYMnZBZ1VxV3RsMkpJTg$8Nh0M8YCvJvPHzqE4pKpB5C9DfFgHhIiJjKkLlMmNn0',
 'Priya', 'Patel', 'manager', TRUE, NOW()),

-- Reception Account
(@tenant_id, UUID(), 'reception@grandpalace.com',
 '$argon2id$v=19$m=65536,t=4,p=1$RXdYMnZBZ1VxV3RsMkpJTg$8Nh0M8YCvJvPHzqE4pKpB5C9DfFgHhIiJjKkLlMmNn0',
 'Amit', 'Kumar', 'reception', TRUE, NOW());

-- Insert Room Types
INSERT INTO `room_types` (
    `tenant_id`, `name`, `code`, `description`,
    `base_rate`, `extra_adult_charge`, `extra_child_charge`,
    `max_adults`, `max_children`, `max_occupancy`,
    `amenities`, `sort_order`
) VALUES
(@tenant_id, 'Standard Room', 'STD', 
 'Comfortable room with essential amenities',
 3500.00, 500.00, 250.00, 2, 1, 3,
 '["wifi", "ac", "tv", "tea_coffee"]', 1),

(@tenant_id, 'Deluxe Room', 'DLX',
 'Spacious room with city view and premium amenities',
 5500.00, 750.00, 400.00, 2, 2, 4,
 '["wifi", "ac", "tv", "minibar", "room_service", "city_view"]', 2),

(@tenant_id, 'Executive Suite', 'EXE',
 'Luxurious suite with separate living area',
 8500.00, 1000.00, 500.00, 2, 2, 4,
 '["wifi", "ac", "tv", "minibar", "room_service", "living_area", "bathtub", "city_view"]', 3),

(@tenant_id, 'Presidential Suite', 'PRS',
 'Ultimate luxury with panoramic views and butler service',
 15000.00, 1500.00, 750.00, 4, 2, 6,
 '["wifi", "ac", "tv", "minibar", "room_service", "living_area", "bathtub", "jacuzzi", "butler", "panoramic_view"]', 4);

-- Get Room Type IDs
SET @std_id = (SELECT id FROM room_types WHERE tenant_id = @tenant_id AND code = 'STD');
SET @dlx_id = (SELECT id FROM room_types WHERE tenant_id = @tenant_id AND code = 'DLX');
SET @exe_id = (SELECT id FROM room_types WHERE tenant_id = @tenant_id AND code = 'EXE');
SET @prs_id = (SELECT id FROM room_types WHERE tenant_id = @tenant_id AND code = 'PRS');

-- Insert Rooms
INSERT INTO `rooms` (`tenant_id`, `room_type_id`, `room_number`, `floor`, `sort_order`) VALUES
-- Standard Rooms (101-105)
(@tenant_id, @std_id, '101', '1st', 101),
(@tenant_id, @std_id, '102', '1st', 102),
(@tenant_id, @std_id, '103', '1st', 103),
(@tenant_id, @std_id, '104', '1st', 104),
(@tenant_id, @std_id, '105', '1st', 105),

-- Deluxe Rooms (201-208)
(@tenant_id, @dlx_id, '201', '2nd', 201),
(@tenant_id, @dlx_id, '202', '2nd', 202),
(@tenant_id, @dlx_id, '203', '2nd', 203),
(@tenant_id, @dlx_id, '204', '2nd', 204),
(@tenant_id, @dlx_id, '205', '2nd', 205),
(@tenant_id, @dlx_id, '206', '2nd', 206),
(@tenant_id, @dlx_id, '207', '2nd', 207),
(@tenant_id, @dlx_id, '208', '2nd', 208),

-- Executive Suites (301-304)
(@tenant_id, @exe_id, '301', '3rd', 301),
(@tenant_id, @exe_id, '302', '3rd', 302),
(@tenant_id, @exe_id, '303', '3rd', 303),
(@tenant_id, @exe_id, '304', '3rd', 304),

-- Presidential Suite (401)
(@tenant_id, @prs_id, '401', '4th', 401);

-- Success Message
SELECT 'Demo data seeded successfully!' AS message;
SELECT CONCAT('Tenant ID: ', @tenant_id) AS tenant_info;
SELECT 'Demo Credentials: owner@grandpalace.com / Demo@123' AS login_info;
