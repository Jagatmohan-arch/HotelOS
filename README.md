# HotelOS - Enterprise Hotel Property Management System

> **Next-Gen SaaS PMS for Indian Hotels**
> Built with Native PHP 8.2 | MySQL 8.0 | Tailwind CSS | Alpine.js

![HotelOS](https://img.shields.io/badge/Version-1.0.0-cyan)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![License](https://img.shields.io/badge/License-Proprietary-red)

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `json`
- MySQL 8.0+
- Apache with `mod_rewrite` enabled

### Local Development

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-repo/HotelOS.git
   cd HotelOS
   ```

2. **Create the database**
   ```sql
   CREATE DATABASE hotelos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'hotelos_user'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON hotelos_db.* TO 'hotelos_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Import schema and seed data**
   ```bash
   mysql -u hotelos_user -p hotelos_db < database/schema.sql
   mysql -u hotelos_user -p hotelos_db < database/seed.sql
   ```

4. **Configure database credentials**
   Edit `config/database.php` or set environment variables:
   ```bash
   export DB_HOST=localhost
   export DB_NAME=hotelos_db
   export DB_USER=hotelos_user
   export DB_PASS=your_secure_password
   ```

5. **Start local server**
   ```bash
   cd public
   php -S localhost:8000
   ```

6. **Access the application**
   Open http://localhost:8000 in your browser.

---

## 🔐 Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Owner | owner@grandpalace.com | Demo@123 |
| Manager | manager@grandpalace.com | Demo@123 |
| Reception | reception@grandpalace.com | Demo@123 |

---

## 📁 Project Structure

```
HotelOS/
├── .github/workflows/     # GitHub Actions (FTP Deploy)
├── public/                # Web root (DocumentRoot)
│   ├── index.php          # Front controller
│   ├── assets/            # CSS, JS, Images
│   └── .htaccess          # URL rewriting
├── config/                # Configuration files (protected)
│   ├── app.php            # App settings
│   └── database.php       # DB credentials
├── core/                  # Core PHP classes (protected)
│   ├── Auth.php           # Authentication
│   ├── Database.php       # PDO singleton
│   ├── Router.php         # Request routing
│   └── TenantContext.php  # Multi-tenancy
├── views/                 # PHP view templates
│   ├── layouts/           # Base templates
│   ├── auth/              # Login, register
│   └── errors/            # 404, 500
├── database/              # SQL schemas
├── cache/                 # File cache
└── logs/                  # Error logs
```

---

## 🚢 Deployment (MilesWeb)

### GitHub Secrets Required

| Secret | Description |
|--------|-------------|
| `FTP_HOST` | FTP server hostname (e.g., ftp.needkit.in) |
| `FTP_USER` | FTP username |
| `FTP_PASSWORD` | FTP password |

### Automatic Deployment
Push to `main` branch triggers automatic FTP deployment via GitHub Actions.

### Manual Deployment
1. Upload all files to `/public_html/HotelOS/` on your server
2. Set `public/` as the DocumentRoot or configure `.htaccess` redirect
3. Import database schema via phpMyAdmin
4. Update `config/database.php` with production credentials

---

## 🇮🇳 GST Compliance

HotelOS handles Indian GST automatically:

| Room Rate | GST Rate | Calculation |
|-----------|----------|-------------|
| < ₹7,500 | 12% | 6% CGST + 6% SGST (Intra-state) |
| ≥ ₹7,500 | 18% | 9% CGST + 9% SGST (Intra-state) |
| Inter-state | Same | Full IGST |

---

## 🎨 Theme Modes

- **Cosmic** (Default): Deep space dark mode with neon cyan accents
- **Royal**: Light mode with professional aesthetics
- **Comfort**: Warm sepia tones for reduced eye strain

Toggle themes using the floating button on the login page.

---

## 🔒 Security Features

- ✅ Argon2ID password hashing
- ✅ CSRF token protection
- ✅ Prepared statements (SQL injection prevention)
- ✅ Multi-tenant data isolation
- ✅ Account lockout after failed attempts
- ✅ Secure session management
- ✅ Protected config directories

---

## 📋 Roadmap

### Phase 1 ✅
- [x] Project structure & security
- [x] Database schema
- [x] Authentication system
- [x] Login UI (Antigravity theme)
- [x] GitHub Actions deployment

### Phase 2 (Next)
- [ ] Dashboard UI
- [ ] Room management CRUD
- [ ] Room type configuration

### Phase 3
- [ ] Booking engine
- [ ] Calendar view
- [ ] Guest management

### Phase 4
- [ ] GST invoice generation
- [ ] Reports & analytics
- [ ] C-Form generation

---

## 📝 License

Proprietary software. All rights reserved.

---

Built with ❤️ for Indian Hospitality by **HotelOS Team**
