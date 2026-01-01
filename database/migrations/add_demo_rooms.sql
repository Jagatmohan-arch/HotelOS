-- ================================================
-- HotelOS Demo Data - Room Types and Rooms
-- Run this ONLY for DEMO tenant (tenant_id = 1)
-- Date: 2024-12-31
-- ================================================

-- First, check which tenant ID you want to use:
-- SELECT id, name, email FROM tenants LIMIT 5;
-- Replace @DEMO_TENANT_ID with the actual tenant ID

SET @DEMO_TENANT_ID = 1;  -- Change this to your demo tenant ID

-- ================================================
-- STEP 1: Add Room Types
-- ================================================
INSERT INTO room_types (tenant_id, name, code, description, base_rate, base_adults, max_adults, base_children, max_children, max_occupancy, is_active, created_at) VALUES
(@DEMO_TENANT_ID, 'Standard Room', 'STD', 'Comfortable standard room with AC, TV, and WiFi', 2500.00, 2, 2, 0, 1, 3, 1, NOW()),
(@DEMO_TENANT_ID, 'Deluxe Room', 'DLX', 'Spacious deluxe room with premium furnishing and city view', 4500.00, 2, 3, 0, 2, 4, 1, NOW()),
(@DEMO_TENANT_ID, 'Super Deluxe', 'SDL', 'Luxury super deluxe room with balcony and garden view', 6500.00, 2, 3, 1, 2, 5, 1, NOW()),
(@DEMO_TENANT_ID, 'Executive Suite', 'STE', 'Executive suite with living area, mini bar, and premium amenities', 9500.00, 2, 4, 2, 2, 6, 1, NOW());

-- ================================================
-- STEP 2: Add Rooms (using subquery for room_type_id)
-- ================================================
INSERT INTO rooms (tenant_id, room_type_id, room_number, floor, status, housekeeping_status, is_active, created_at) VALUES
-- Standard Rooms (Ground Floor)
(@DEMO_TENANT_ID, (SELECT id FROM room_types WHERE tenant_id = @DEMO_TENANT_ID AND code = 'STD' LIMIT 1), '101', 'Ground Floor', 'available', 'clean', 1, NOW()),
(@DEMO_TENANT_ID, (SELECT id FROM room_types WHERE tenant_id = @DEMO_TENANT_ID AND code = 'STD' LIMIT 1), '102', 'Ground Floor', 'available', 'clean', 1, NOW()),
(@DEMO_TENANT_ID, (SELECT id FROM room_types WHERE tenant_id = @DEMO_TENANT_ID AND code = 'STD' LIMIT 1), '103', 'Ground Floor', 'available', 'clean', 1, NOW()),

-- Deluxe Rooms (First Floor)
(@DEMO_TENANT_ID, (SELECT id FROM room_types WHERE tenant_id = @DEMO_TENANT_ID AND code = 'DLX' LIMIT 1), '201', 'First Floor', 'available', 'clean', 1, NOW()),
(@DEMO_TENANT_ID, (SELECT id FROM room_types WHERE tenant_id = @DEMO_TENANT_ID AND code = 'DLX' LIMIT 1), '202', 'First Floor', 'available', 'clean', 1, NOW()),

-- Super Deluxe (Second Floor)
(@DEMO_TENANT_ID, (SELECT id FROM room_types WHERE tenant_id = @DEMO_TENANT_ID AND code = 'SDL' LIMIT 1), '301', 'Second Floor', 'available', 'clean', 1, NOW()),
(@DEMO_TENANT_ID, (SELECT id FROM room_types WHERE tenant_id = @DEMO_TENANT_ID AND code = 'SDL' LIMIT 1), '302', 'Second Floor', 'available', 'clean', 1, NOW()),

-- Executive Suite (Third Floor)
(@DEMO_TENANT_ID, (SELECT id FROM room_types WHERE tenant_id = @DEMO_TENANT_ID AND code = 'STE' LIMIT 1), '401', 'Third Floor', 'available', 'clean', 1, NOW());

-- ================================================
-- Verify Data
-- ================================================
SELECT 'Room Types Added:' as Status, COUNT(*) as Count FROM room_types WHERE tenant_id = @DEMO_TENANT_ID;
SELECT 'Rooms Added:' as Status, COUNT(*) as Count FROM rooms WHERE tenant_id = @DEMO_TENANT_ID;
