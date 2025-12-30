# Migration Automation - Usage Guide

## Quick Start

### Apply All Pending Migrations
```bash
cd c:\Users\HP\Documents\HotelOS
php migrate.php
```

### Preview What Will Run (Dry Run)
```bash
php migrate.php --dry-run
```

### Force Re-Run Specific Migration (Use with caution!)
```bash
php migrate.php --force-file=shift_immutability_trigger.sql
```

---

## How It Works

1. **Scans** `database/migrations/` for all `.sql` files
2. **Checks** `migrations_log` table to see which have been applied
3. **Applies** only new/pending migrations in alphabetical order
4. **Logs** each migration with timestamp, checksum, and execution time
5. **Prevents** duplicate runs automatically

---

## Migration File Naming Convention

**Format**: `descriptive_name.sql`

**Examples**:
- `shifts_table.sql`
- `cash_ledger_table.sql`
- `shift_immutability_trigger.sql`
- `add_tax_exemption_column.sql`

**Best Practice**: Use descriptive names that sort chronologically if needed (e.g., `01_create_users.sql`, `02_add_roles.sql`)

---

## Creating New Migrations

### Step 1: Create SQL File
```bash
# Create new file in migrations directory
cd database/migrations
touch add_booking_notes_column.sql
```

### Step 2: Write Migration SQL
```sql
-- Add notes column to bookings table
ALTER TABLE bookings 
ADD COLUMN special_notes TEXT NULL 
AFTER internal_notes;

SELECT '✅ Added special_notes column' AS Status;
```

### Step 3: Run Migration
```bash
php migrate.php
```

---

## Safety Features

### ✅ Duplicate Prevention
Migrations logged in `migrations_log` will never run again (unless forced)

### ✅ Transaction Support
Each migration runs in its own context

### ✅ Error Handling
If migration fails:
- Execution stops immediately
- Error is logged to `migrations_log`
- Clear error message displayed
- Database remains in last good state

### ✅ Checksum Verification
SHA256 hash of file content stored to detect modifications

---

## Deployment Workflow

### On Development
```bash
# 1. Create migration file
# 2. Test locally
php migrate.php --dry-run
php migrate.php

# 3. Commit to git
git add database/migrations/your_migration.sql
git commit -m "Add: your migration description"
git push
```

### On Production
```bash
# After git pull/deployment
cd /path/to/HotelOS
php migrate.php --dry-run  # Preview first
php migrate.php            # Apply if looks good
```

---

## Troubleshooting

### Migration Failed
```bash
# Check error in migrations_log
SELECT * FROM migrations_log WHERE status = 'failed' ORDER BY applied_at DESC LIMIT 1;

# Fix the SQL file
# Re-run (will skip successful ones, retry failed)
php migrate.php
```

### Need to Re-Run Migration
```bash
# Delete from log (allows re-run)
DELETE FROM migrations_log WHERE migration_file = 'your_migration.sql';

# OR force re-run
php migrate.php --force-file=your_migration.sql
```

### Check Applied Migrations
```bash
# Via SQL
SELECT migration_file, applied_at, execution_time_ms 
FROM migrations_log 
WHERE status = 'success' 
ORDER BY applied_at DESC;
```

---

## Integration with CI/CD

### GitHub Actions Example
```yaml
- name: Run Database Migrations
  run: php migrate.php
```

### Post-Deploy Hook
```bash
#!/bin/bash
cd /var/www/hotelos
git pull origin main
php migrate.php
```

---

## File Permissions

### Shared Hosting
Ensure `migrate.php` is executable:
```bash
chmod +x migrate.php
```

### Cron Job (Optional)
Not recommended - migrations should be manual/controlled

---

## Best Practices

1. **One change per migration** - easier to track and rollback if needed
2. **Test locally first** - always use `--dry-run` in production
3. **Descriptive names** - future you will thank you
4. **Commit migrations** - version control is your friend
5. **Never edit applied migrations** - create new migration to fix issues

---

**Phase E Status**: Migration system ready for use!
