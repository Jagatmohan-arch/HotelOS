# HotelOS CRON Job Setup Guide

## Overview

HotelOS requires two CRON jobs for automated background tasks.

---

## 1. Trial Expiry Check

**Purpose:** Sends warning emails and locks accounts when trial expires.

**Script:** `scripts/cron_trial_expiry.php`

**Frequency:** Daily at 9:00 AM IST

### cPanel CRON Command:
```
0 9 * * * /usr/bin/php /home/YOUR_USERNAME/public_html/scripts/cron_trial_expiry.php >> /home/YOUR_USERNAME/logs/trial_expiry.log 2>&1
```

### What it does:
- Sends 3-day warning email to users whose trial expires in 3 days
- Sends 1-day warning email to users whose trial expires in 1 day
- Locks accounts whose trial has expired (status = 'suspended')

---

## 2. Session Cleanup

**Purpose:** Deletes old sessions and audit logs to keep database clean.

**Script:** `scripts/cron_cleanup.php`

**Frequency:** Daily at 2:00 AM IST

### cPanel CRON Command:
```
0 2 * * * /usr/bin/php /home/YOUR_USERNAME/public_html/scripts/cron_cleanup.php >> /home/YOUR_USERNAME/logs/cleanup.log 2>&1
```

### What it does:
- Deletes sessions older than 7 days
- Deletes audit logs older than 90 days
- Archives inactive tenants with status 'suspended'

---

## cPanel Setup Instructions

1. Login to cPanel
2. Go to **Cron Jobs** section
3. Add new cron job with:
   - **Common Settings:** Once per day
   - **Command:** Paste command from above (replace YOUR_USERNAME)
4. Click **Add New Cron Job**

---

## Verification

After setup, check:
1. `/home/YOUR_USERNAME/logs/trial_expiry.log`
2. `/home/YOUR_USERNAME/logs/cleanup.log`

Logs should show timestamps and success messages.

---

## Troubleshooting

If CRON fails:

1. **Check PHP path:** Run `which php` in terminal
2. **Check file permissions:** `chmod 755 scripts/*.php`
3. **Check log files:** Review error messages in log files
4. **Test manually:** Run `php scripts/cron_trial_expiry.php` via SSH
