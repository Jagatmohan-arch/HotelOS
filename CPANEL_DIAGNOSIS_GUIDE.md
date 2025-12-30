# MilesWeb cPanel - Core Diagnosis Guide

**Goal:** Check server-side configuration and database status

---

## 📍 Step 1: Access cPanel

1. Go to: https://my.milesweb.com/clientarea.php
2. Login with your credentials
3. Click on **"My Services"**
4. Select your hosting package (#97212)
5. Click **"Login to cPanel"** button

---

## 🔍 Step 2: Check PHP Version

**Path:** cPanel → Software → **"Select PHP Version"**

**What to Check:**
- ✅ PHP Version should be **8.1** or **8.2**
- ✅ Extensions enabled:
  - `pdo`
  - `pdo_mysql`
  - `mbstring`
  - `json`
  - `openssl`
  - `fileinfo`

**If Wrong:**
- Select PHP 8.1 or 8.2
- Enable missing extensions

---

## 🗄️ Step 3: Database Diagnosis (phpMyAdmin)

**Path:** cPanel → Databases → **"phpMyAdmin"**

### A) Select Database
- Click on `uplfveim_hotelos` (left sidebar)

### B) Check Tables Exist

Run this query in SQL tab:
```sql
SHOW TABLES;
```

**Required Tables (Must Have 25+):**
1. tenants
2. users
3. sessions
4. room_types
5. rooms
6. guests
7. bookings
8. transactions
9. invoices
10. invoice_items
11. audit_logs
12. settings
13. **refund_requests** ← If missing, site crashes!
14. **engine_actions**
15. **invoice_snapshots**
16. **branding_assets**
17. **subscription_plans**
18. **subscription_transactions**
19. **login_attempts** ← New (rate limiting)
20. shifts
21. police_reports
22. ... more

**If Any Missing:** Run `EMERGENCY_FIX_LIVE.sql`

### C) Check `users.pin_hash` Column

```sql
DESCRIBE users;
```

Look for `pin_hash` column in results. If missing, run emergency fix.

### D) Check `tenants` Subscription Columns

```sql
DESCRIBE tenants;
```

Look for:
- `trial_ends_at`
- `billing_status`
- `next_billing_date`
- `plan`

If missing, run emergency fix.

---

## 📁 Step 4: File Structure Check

**Path:** cPanel → Files → **"File Manager"**

**Navigate to:** `public_html/` or your domain root

**Verify These Exist:**
```
/public_html/
├── .htaccess ✅
├── index.php ✅
├── core/ ✅
├── handlers/ ✅
├── views/ ✅
├── database/ ✅
├── .env ✅ (IMPORTANT!)
└── public/ ✅
```

### Check `.env` File

**CRITICAL:** Check if `.env` exists and has correct values

**How to Check:**
1. File Manager → Show hidden files (Settings button → tick "Show hidden files")
2. Look for `.env` file
3. Right-click → **Edit**

**Should contain:**
```ini
APP_NAME=HotelOS
APP_ENV=production
APP_DEBUG=false  ← MUST be false
APP_URL=https://hotelos.needkit.in

DB_HOST=localhost
DB_PORT=3306
DB_NAME=uplfveim_hotelos
DB_USER=uplfveim_user  ← Your actual DB user
DB_PASS=your_actual_password  ← Your actual password

APP_TIMEZONE=Asia/Kolkata
```

**If `.env` is MISSING:**
- Copy `.env.example`
- Rename to `.env`
- Edit with correct database credentials

---

## 🔒 Step 5: .htaccess Verification

**File:** `.htaccess` in root

**Check Contents:**
```apache
# URL Rewriting
RewriteEngine On
RewriteRule ^assets/(.*)$ public/assets/$1 [L,NC]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**If Missing/Corrupted:** Re-upload from local codebase

---

## 📊 Step 6: Error Logs

**Path:** cPanel → Metrics → **"Errors"**

OR File Manager → `/logs/php_errors.log`

**Check for:**
- Database connection errors
- Missing table errors  
- Permission errors
- PHP fatal errors

**Common Errors:**
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'uplfveim_hotelos.refund_requests' doesn't exist
```
↑ This = Run EMERGENCY_FIX_LIVE.sql

---

## 🎯 Step 7: Test Database Connection

**Run in phpMyAdmin SQL tab:**
```sql
SELECT * FROM tenants LIMIT 1;
SELECT * FROM users WHERE role = 'owner' LIMIT 1;
SELECT COUNT(*) as total FROM room_types;
```

**If ANY Query Fails:**
- Check `.env` database credentials
- Check database user permissions

---

## ⚡ Step 8: Permissions Check

**Path:** File Manager → Select root folder → **Permissions**

**Correct Permissions:**
- `/public/uploads/` → 755 (writable)
- `/cache/` → 755 (writable)
- `/logs/` → 755 (writable)
- All other files → 644
- All directories → 755

**Fix:** Select folder → Permissions → Set to 755

---

## 🚨 Common Issues & Fixes

### Issue 1: "500 Internal Server Error"
**Causes:**
- `.htaccess` syntax error
- PHP version incompatible
- Missing .env file

**Fix:**
- Re-upload .htaccess
- Switch to PHP 8.1
- Create .env from .env.example

### Issue 2: "Database Connection Failed"
**Causes:**
- Wrong DB credentials in .env
- Database doesn't exist
- User lacks permissions

**Fix:**
- Verify DB_NAME, DB_USER, DB_PASS in .env
- Create database in cPanel if missing
- Grant user full permissions

### Issue 3: "Table doesn't exist" Error
**Cause:** Migrations not run on live

**Fix:** Run `EMERGENCY_FIX_LIVE.sql` via phpMyAdmin

### Issue 4: "Class not found" Error
**Causes:**
- Autoloader issue
- Case-sensitive filenames (Linux)

**Fix:**
- Re-upload all files
- Check file name cases match code

---

## ✅ Success Checklist

After checks, you should have:
- [✅] PHP 8.1+ with required extensions
- [✅] `.env` file with correct credentials
- [✅] All 25+ database tables exist
- [✅] `users.pin_hash` column exists
- [✅] `tenants` has subscription columns
- [✅] `.htaccess` working (mod_rewrite)
- [✅] File permissions correct
- [✅] No errors in logs
- [✅] Site loads without crashes

---

## 📸 Screenshot Checklist

Take screenshots of:
1. PHP version page
2. phpMyAdmin table list (SHOW TABLES)
3. `.env` file (hide password!)
4. Error logs (if any)
5. Working site homepage

---

**Next:** Once all green, test live site registration!
