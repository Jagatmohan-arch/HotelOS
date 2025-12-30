-- ============================================
-- DATABASE MIGRATION VERIFICATION SCRIPT
-- Run this in phpMyAdmin to check missing tables
-- ============================================

-- Check which tables exist
SELECT 'Checking existing tables...' AS Status;

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ EXISTS'
        ELSE '❌ MISSING'
    END AS Status,
    'tenants' AS TableName
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'tenants'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'users'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'users'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'sessions'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'sessions'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'room_types'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'room_types'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'rooms'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'rooms'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'guests'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'guests'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'bookings'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'bookings'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'transactions'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'transactions'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'invoices'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'invoices'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'invoice_items'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'invoice_items'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'refund_requests'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'refund_requests'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'engine_actions'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'engine_actions'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'invoice_snapshots'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'invoice_snapshots'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'branding_assets'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'branding_assets'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'subscription_plans'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'subscription_plans'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'subscription_transactions'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'subscription_transactions'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'login_attempts'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'login_attempts'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'shifts'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'shifts'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'police_reports'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'police_reports'

UNION ALL

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'audit_logs'
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'audit_logs';

-- Check for missing columns in users table
SELECT 'Checking users.pin_hash column...' AS Status;

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ EXISTS'
        ELSE '❌ MISSING - Need to add'
    END AS Status,
    'users.pin_hash' AS ColumnName
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'users' 
  AND column_name = 'pin_hash';

-- Check tenants subscription columns
SELECT 'Checking tenants subscription columns...' AS Status;

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ EXISTS'
        ELSE '❌ MISSING - Need to add'
    END AS Status,
    'tenants.trial_ends_at' AS ColumnName
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'tenants' 
  AND column_name = 'trial_ends_at';

SELECT '✅ Verification complete! Check results above.' AS Status;
