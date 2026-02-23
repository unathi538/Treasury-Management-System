# CommVault PHP Backend

## Inferred minimum frontend files
From this repo, the frontend appears to be:
- Landing page: `index.html`
- dApp features page: `frontend/features.html` (moved from original `new.html`)
- JS bundle used by features page: `app.js`
- Redirect stub: `new.html` now forwards to protected `/backend/app.php`

## Quick start (local)
1. Install dependencies:
   ```bash
   cd backend
   composer install
   cd ..
   ```
2. Create env file:
   ```bash
   cp backend/.env.example backend/.env
   ```
3. Create MySQL database and run migrations:
   ```bash
   mysql -u root -p -e "CREATE DATABASE community_pool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p community_pool < backend/migrations/001_create_users_and_pool_transactions.sql
   ```
   If MariaDB JSON is unsupported, also run:
   ```bash
   mysql -u root -p community_pool < backend/migrations/002_mariadb_json_fallback.sql
   ```
4. Configure Google OAuth credentials in `backend/.env`:
   - `GOOGLE_CLIENT_ID`
   - `GOOGLE_CLIENT_SECRET`
   - `GOOGLE_REDIRECT_URI=http://localhost:8000/backend/auth/google_callback.php`
   - In Google Console set Authorized Redirect URI to this exact URI.
5. Run app:
   ```bash
   php -S localhost:8000 -t .
   ```

## URLs
- Landing: `http://localhost:8000/index.html`
- Login: `http://localhost:8000/backend/login.php`
- Register: `http://localhost:8000/backend/register.php`
- Protected dApp: `http://localhost:8000/backend/app.php`
- Auth state API: `http://localhost:8000/backend/auth/me.php`

## Deployment notes (shared hosting)
- If shell/Composer is unavailable on hosting:
  - Run `composer install --no-dev -o` locally inside `backend/`.
  - Upload the entire `backend/vendor` directory with backend source.
- If `.env` is not supported:
  - set environment variables through control panel, or
  - hard-map `env_or_default()` fallbacks in `backend/config/config.php` using hosting secrets storage.
- Ensure site is served via HTTPS in production so secure session cookies are enabled.
- Keep backend at `/backend` path so OAuth redirect URI remains stable.
