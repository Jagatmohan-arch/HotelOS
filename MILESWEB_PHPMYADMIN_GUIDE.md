# 🔧 MilesWeb phpMyAdmin Access Guide

**Goal:** Check and fix incomplete database migrations

---

## 📍 Step-by-Step: Access phpMyAdmin

### Step 1: Login to MilesWeb
1. Go to: https://my.milesweb.com/clientarea.php
2. Login with your credentials
3. Navigate to **"My Services"**
4. Click on your **hosting package** (#97212)

### Step 2: Access cPanel
1. Look for **"cPanel Login"** button (usually on right side)
2. Click **"Login to cPanel"**
3. You'll be auto-logged into cPanel

### Step 3: Open phpMyAdmin
1. In cPanel, scroll down to **"Databases"** section
2. Click on **"phpMyAdmin"** icon
3. phpMyAdmin will open in new tab

### Step 4: Select Database
1. On left sidebar, click database: **`uplfveim_hotelos`**
2. Make sure it's selected (highlighted)

---

## 🔍 CHECK: What Tables Are Missing

### Run Verification Script

1. In phpMyAdmin, click **"SQL"** tab (top menu)
2. Copy-paste content from: `database/CHECK_MIGRATIONS.sql`
3. Click **"Go"** button
4. Review results:
   - ✅ EXISTS = Table is there
   - ❌ MISSING = Need to create

**Common Missing Tables:**
- `refund_requests`
- `engine_actions`
- `invoice_snapshots`
- `branding_assets`
- `subscription_plans`
- `subscription_transactions`
- `login_attempts`

---

## ✅ FIX: Run Emergency Migration

If you see **ANY** missing tables:

1. In phpMyAdmin SQL tab
2. **Clear** the text box
3. Copy-paste **ENTIRE** file: `database/EMERGENCY_FIX_LIVE.sql`
4. Click **"Go"**
5. Wait 10-15 seconds
6. You should see:
   ```
   ✅ ALL MIGRATIONS APPLIED SUCCESSFULLY!
   Live site should now work without errors
   ```

---

## 🧪 VERIFY: After Running Fix

### Test 1: Check Tables Again
Run `CHECK_MIGRATIONS.sql` again - all should show ✅ EXISTS

### Test 2: Visit Live Site
1. Open: https://hotelos.needkit.in/
2. Should see **login page** (not error)
3. Try logging in
4. Should work!

---

## 📊 Alternative: Quick Check

If you just want to see table list:

**In phpMyAdmin:**
1. Select database `uplfveim_hotelos`
2. Click **"Structure"** tab
3. You should see **25+ tables**

**Required Core Tables:**
- tenants
- users
- sessions
- room_types
- rooms
- guests
- bookings
- transactions
- invoices
- invoice_items
- refund_requests ← **if missing, site crashes**
- audit_logs

---

## ⚠️ If You Get Errors

### Error: "Access Denied"
- Make sure you're logged into MilesWeb
- Use cPanel login, not manual phpMyAdmin

### Error: "Database not selected"
- Click database name on left sidebar: `uplfveim_hotelos`

### Error: "Table already exists"
- **This is OK!** It means that table was already created
- Continue with the script

### Error: "Column already exists"
- **This is OK!** It means that column was already added
- Continue with the script

---

## 🎯 Expected Result

**After running EMERGENCY_FIX_LIVE.sql:**

1. ✅ All 25+ tables exist
2. ✅ `users.pin_hash` column exists
3. ✅ `tenants` has subscription columns
4. ✅ `subscription_plans` has 3 rows (Starter, Professional, Enterprise)
5. ✅ Site loads without errors

---

## 📸 Screenshot Checklist

**Before Fix:**
- [ ] phpMyAdmin showing database structure (missing tables marked)
- [ ] Site error page (if site is down)

**After Fix:**
- [ ] phpMyAdmin showing all tables ✅
- [ ] Site login page loading ✅
- [ ] Successful login ✅

---

## 🚨 Emergency Contact

If something goes wrong:

1. **Backup first!**
   - phpMyAdmin → Export → Go
   - Save .sql file

2. **Revert if needed:**
   - phpMyAdmin → Import
   - Choose backup file

3. **Ask for help:**
   - Share screenshot of error
   - Share table list from phpMyAdmin

---

## ⏱️ Estimated Time

- Access phpMyAdmin: **2 minutes**
- Run CHECK script: **30 seconds**
- Run EMERGENCY_FIX: **2 minutes**
- Verify site works: **1 minute**

**Total: ~5 minutes**

---

**Files You Need:**
1. `database/CHECK_MIGRATIONS.sql` - Verification
2. `database/EMERGENCY_FIX_LIVE.sql` - Fix

**Both files are in your HotelOS directory!** 📁
