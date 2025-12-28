# HotelOS Deployment Guide (Shared Hosting / cPanel)

This guide will help you deploy HotelOS to any standard PHP/MySQL hosting (Hostinger, GoDaddy, Namecheap, MilesWeb etc.).

## 1. Prerequisites
- **PHP Version**: 8.1 or 8.2 (Required)
- **MySQL Version**: 5.7+ or 8.0
- **Extensions**: `pdo_mysql`, `json`, `mbstring` (Standard on all hosts)

## 2. Prepare Files for Upload
1.  **Zip the Project**: Create a zip file of your `HotelOS` folder.
    *   Example: `HotelOS_v1.zip`
    *   **Exclude**: `.git` folder, `.vscode` folder (if any).

2.  **Upload**:
    *   Log in to **cPanel > File Manager**.
    *   Upload `HotelOS_v1.zip` to `public_html` (or your subdomain folder).
    *   **Extract** the zip file.
    *   Ensure all files (`index.php`, `.htaccess`, `core/`, `public/`, etc.) are in the root of your domain folder.
    *   *Note*: If you see a subfolder like `HotelOS/` after extraction, move all files **out** to the main folder.

## 3. Database Setup
1.  **Create Database**:
    *   Go to **cPanel > MySQL Databases**.
    *   Create a new Database (e.g., `u123_hotelos`).
    *   Create a new User (e.g., `u123_admin`) and Password.
    *   **Add User to Database** with **ALL PRIVILEGES**.

2.  **Import Schema**:
    *   Go to **phpMyAdmin**.
    *   Select your new database.
    *   Click **Import**.
    *   Upload the file: `database/schema.sql`.
    *   Click **Go**.

## 4. Configuration
1.  **Create .env File**:
    *   In File Manager, find `.env.example`.
    *   Rename it to `.env` (Rename functionality).
    *   Right-click `.env` and **Edit**.

2.  **Update Credentials**:
    ```ini
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=http://your-hotel-domain.com

    DB_HOST=localhost
    DB_NAME=u123_hotelos  <-- Your cPanel DB Name
    DB_USER=u123_admin    <-- Your cPanel DB User
    DB_PASS=secret123     <-- Your Password
    ```
    *   Save the file.

## 5. Directory Privacy (Security)
Shared hosting often exposes all folders. We need to secure `config`, `core`, and `.env`.
The `.htaccess` file included in the root handles this, but verify:
1.  Try accessing `http://your-domain.com/.env`. It should say **403 Forbidden**.
2.  Try accessing `http://your-domain.com/config/database.php`. It should say **403 Forbidden**.

## 6. Verification
1.  Visit your domain: `http://your-domain.com`
2.  You should see the **Login Page**.
3.  **Default Credentials** (if you imported demo data):
    *   Owner: `owner@example.com` / `password123`
    *   Reception: `reception@example.com` / `password123`

## Troubleshooting
*   **500 Internal Server Error**:
    *   Check `public/index.php` permissions (should be 644).
    *   Check `.htaccess` syntax.
*   **Database Error**:
    *   Double-check `.env` credentials.
    *   Ensure `DB_HOST` is `localhost`.
*   **CSS/JS Not Loading**:
    *   Ensure the `public/assets` folder exists.
    *   Check if `RewriteRule` in `.htaccess` is working.

## Done!
Your HotelOS is now live.
