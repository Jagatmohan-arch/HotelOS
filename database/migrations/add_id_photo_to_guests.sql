-- HotelOS Migration: Add ID Photo to Guests
-- Version: 4.1
-- Date: 2024-12-31

-- Add id_photo_path column to guests table
ALTER TABLE guests ADD COLUMN IF NOT EXISTS id_photo_path VARCHAR(255) NULL AFTER id_number;

-- Update success message
SELECT 'Migration complete: Added id_photo_path column to guests table' AS Result;
