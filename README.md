# HotelOS - Enterprise Hotel Property Management System

> **Next-Gen SaaS PMS for Indian Hotels**
> Built with Native PHP 8.2 | MySQL 8.0 | Tailwind CSS | Alpine.js

![HotelOS](https://img.shields.io/badge/Version-4.0.0_Beta-orange)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![License](https://img.shields.io/badge/License-Proprietary-red)

---

## 🚀 Current Status: BETA (v4.0)

HotelOS is currently in **Beta / Pre-Sale** stage. 
Core functionalities are fully implemented and production-ready. 

### ✅ Verified Working Features
- **Authentication**: Secure Login, 4-Digit Staff PIN, RBAC (6 Roles), Mobile Session Management
- **Front Office**: Interactive Dashboard, Room Grid, Quick Check-in/Check-out
- **Booking Engine**: Availability Check, Room Moves, Advance Payments, Cancellations
- **Billing & Finance**: 
  - 🇮🇳 **GST Compliance** (Auto 12%/18% calculation)
  - Invoice Generation (PDF ready)
  - B2B/Corporate Billing
  - Refund Approval Workflow (2-Level Security)
- **Shift Management**: 
  - Cash Drawer Tracking
  - **Immutable Shift Reports** (Anti-theft)
  - Expense Recording
- **Housekeeping**: Mobile-first room status updates
- **Security**: Audit Logging, Account Locking, CSRF Protection

---

## 🛠️ Quick Start

### Prerequisites
- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `json`, `curl`, `gd`
- MySQL 8.0+ (Strict Mode)
- Apache with `mod_rewrite` enabled
- **CRON Jobs**: Required for trial expiry and session cleanup

### Local Development
1. **Clone & Setup**:
   ```bash
   git clone https://github.com/your-repo/HotelOS.git
   cd HotelOS
   ```
2. **Database Setup**:
   - Create database `hotelos_db`
   - Import `database/schema.sql` (Base structure)
   - Import `database/migrations/*` (Apply all verified migrations)
3. **Configuration**:
   - Copy `.env.example` to `.env` (if using env loader) OR edit `config/app.php`
   - Set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
4. **Run Server**:
   ```bash
   cd public
   php -S localhost:8000
   ```

### 🚢 Production Deployment (cPanel/Shared Hosting)
1. Upload files to `public_html` (or subdirectory)
2. Point domain DocumentRoot to `/public` folder
3. Import SQL files via phpMyAdmin
4. **Secure Directories**: Ensure `/core`, `/config`, and `/logs` are NOT accessible via browser (.htaccess is provided)
5. **Cron Jobs**:
   - `0 0 * * * php /path/to/hotelos/scripts/cron_trial_check.php` (Trial Expiry)
   - `0 3 * * * php /path/to/hotelos/scripts/cron_cleanup.php` (Session Cleanup)

---

## 🔐 Demo Credentials

| Role | Email | Password | PIN (Quick Login) |
|------|-------|----------|-------------------|
| **Owner** | owner@grandpalace.com | Demo@123 | - |
| **Manager** | manager@grandpalace.com | Demo@123 | 1234 |
| **Reception** | reception@grandpalace.com | Demo@123 | 5678 |
| **Housekeeping** | hk@grandpalace.com | Demo@123 | 9090 |

---

## ⚠️ Known Issues / Configuration

1. **Email Configuration**: 
   - Uses `HotelOS\Core\EmailService`.
   - Currently logs to `logs/emails.log` by default.
   - **Action**: Configure SMTP in `config/app.php` before production.

2. **Trial Expiry**:
   - System enforces 14-day trial automatically.
   - Middleware redirects expired tenants to upgrade page.

3. **Payment Gateways**:
   - Cashfree integration files present (`handlers/CashfreeHandler.php`).
   - Sandbox mode active. Switch to Production in config/env.

---

## 🇮🇳 GST Logic

HotelOS handles Indian GST automatically based on room base rate:

| Room Rate | GST Rate | Calculation |
|-----------|----------|-------------|
| < ₹7,500 | 12% | 6% CGST + 6% SGST (Intra-state) |
| ≥ ₹7,500 | 18% | 9% CGST + 9% SGST (Intra-state) |
| Inter-state | Same | Full IGST |

---

## 📝 License
Proprietary software. All rights reserved.

Built with ❤️ for Indian Hospitality by **HotelOS Team**
