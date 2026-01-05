# HotelOS Deployment Guide

## Requirements

- **PHP**: 8.2+
- **MySQL**: 8.0+
- **Web Server**: Apache with mod_rewrite OR Nginx
- **Hosting**: cPanel shared hosting or VPS

---

## 1. Upload Files

Upload all files to your web root:
- For cPanel: `public_html/` or subdomain folder
- The `public/` folder should be your document root

---

## 2. Database Setup

### Create Database
```sql
CREATE DATABASE hotelos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Import Schema
```bash
mysql -u username -p hotelos_db < database/schema.sql
```

---

## 3. Configure Environment

Copy `.env.example` to `.env` and update:

```env
# App
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_HOST=localhost
DB_NAME=hotelos_db
DB_USER=your_db_user
DB_PASS=your_db_password

# Mail (Required for password reset)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM=noreply@your-domain.com
```

---

## 4. Set Permissions

```bash
chmod 755 public/
chmod 777 logs/
chmod 777 uploads/
```

---

## 5. Configure Apache

Ensure `.htaccess` exists in `public/`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

---

## 6. SSL Certificate

Enable HTTPS (required for production):
- Use cPanel's AutoSSL
- Or install Let's Encrypt

---

## 7. First Login

1. Visit your domain
2. Click **Register** to create owner account
3. Complete hotel setup wizard
4. Invite staff members

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Blank page | Check `logs/error.log` |
| 500 error | Enable `APP_DEBUG=true` temporarily |
| Login fails | Verify database connection |
| Email not sending | Check SMTP credentials |

---

## Backup

### Daily Backup (Recommended)
```bash
mysqldump -u user -p hotelos_db > backup_$(date +%Y%m%d).sql
```

### Restore
```bash
mysql -u user -p hotelos_db < backup_file.sql
```

---

## Support

- Email: support@hotelos.in
- Docs: docs.hotelos.in

*HotelOS v4.0*
