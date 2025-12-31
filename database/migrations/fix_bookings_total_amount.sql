-- ============================================
-- FIX 2: Add total_amount Column to bookings
-- For Occupancy Report and Financial Reporting
-- ============================================

-- Add column if not exists
ALTER TABLE `bookings` 
ADD COLUMN IF NOT EXISTS `total_amount` DECIMAL(10,2) NULL 
COMMENT 'Total bill amount (room charges + extras + services - discounts)' 
AFTER `extra_charges`;

-- Populate total_amount for existing bookings from invoice data
UPDATE `bookings` b
LEFT JOIN (
  SELECT 
    i.booking_id,
    SUM(ii.total) as invoice_total
  FROM `invoices` i
  JOIN `invoice_items` ii ON i.id = ii.invoice_id
  WHERE i.status != 'cancelled'
  GROUP BY i.booking_id
) inv ON b.id = inv.booking_id
SET b.total_amount = COALESCE(inv.invoice_total, 0)
WHERE b.total_amount IS NULL 
  AND b.status IN ('checked_out', 'cancelled');

-- For checked-in bookings without invoice yet, calculate expected amount
UPDATE `bookings` b
JOIN `room_types` rt ON b.room_type_id = rt.id
SET b.total_amount = (
  rt.base_price * 
  GREATEST(1, DATEDIFF(b.check_out_date, b.check_in_date)) * 
  (1 + COALESCE(b.tax_rate, 0) / 100)
) + COALESCE(b.extra_charges, 0)
WHERE b.total_amount IS NULL 
  AND b.status = 'checked_in'
  AND b.check_in_date IS NOT NULL
  AND b.check_out_date IS NOT NULL;

-- For confirmed bookings, calculate estimated amount
UPDATE `bookings` b
JOIN `room_types` rt ON b.room_type_id = rt.id
SET b.total_amount = (
  rt.base_price * 
  GREATEST(1, DATEDIFF(b.check_out_date, b.check_in_date)) * 
  (1 + COALESCE(b.tax_rate, 0) / 100)
)
WHERE b.total_amount IS NULL 
  AND b.status = 'confirmed'
  AND b.check_in_date IS NOT NULL
  AND b.check_out_date IS NOT NULL;

SELECT '✅ total_amount column added and populated' AS Status;
SELECT COUNT(*) as bookings_updated FROM `bookings` WHERE `total_amount` IS NOT NULL;
