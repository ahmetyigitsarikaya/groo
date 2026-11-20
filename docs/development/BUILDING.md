# Building & Local Setup Guide

This guide covers setting up **Groo (`groo/core`)** for local development.

> **Environment Note:** Groo is a PHP/MySQL/Apache web application. It has no native binary compilation step and no Node.js build pipeline (frontend views load Tailwind CSS, Chart.js, and ApexCharts via CDN).

---

## 1. Prerequisites

| Requirement | Version / Details | Status |
|---|---|---|
| **PHP** | `>= 8.0` (recommended `8.1`, `8.2`, or `8.3`) | Required |
| **Composer** | `2.x` | Required |
| **MySQL / MariaDB** | MySQL `8.0+` or MariaDB `10.4+` with `utf8mb4` and `JSON` support | Required |
| **Web Server** | Apache `2.4+` with `mod_rewrite`, `mod_headers`, `mod_expires` (or PHP built-in server for local testing) | Required |

### PHP Extensions

| Extension | Requirement | Used By |
|---|---|---|
| `pdo`, `pdo_mysql` | **Required** | Core database connection & all plugins |
| `json`, `session`, `filter` | **Required** | Core JSON API, sessions, config filtering |
| `fileinfo` | **Required** (for Media plugin) | `mime_content_type()` in `MediaController` |
| `xmlwriter` | **Required** (for SEO plugin) | `SitemapController` |
| `mbstring` | **Recommended** | Admin & Content string handling (polyfilled if absent) |
| `gd` | **Optional** | WebP conversion & responsive image resizing (`Media`) |
| `zip` | **Optional** | ZIP theme/plugin installer (`Admin`) |

---

## 2. Clone & Install Dependencies

```bash
git clone <repository-url> groo
cd groo
composer install
```

> **Note:** `vendor/` and `composer.phar` are excluded via `.gitignore`. Always use a globally installed Composer (`composer install`).

---

## 3. Configure Environment

Copy the example environment file and edit your database and application settings:

```bash
cp .env.example .env
```

Key `.env` variables:

```dotenv
APP_NAME=Groo
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Europe/Istanbul
APP_BASE_PATH=
APP_INSTALLED=false

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=groo
DB_USERNAME=root
DB_PASSWORD=your_local_password
DB_CHARSET=utf8mb4

AUTO_MIGRATE=true
ACTIVE_PLUGINS=admin,content,crm,usermanagement,traffic,seo,errorpages,media
ACTIVE_TEMPLATE=default
ADMIN_PATH=admin
```

> **Important:** Always keep `ACTIVE_PLUGINS` explicitly set. Leaving `ACTIVE_PLUGINS` empty causes `PluginManager` to auto-load every directory in `plugins/`, including `demo` and `install`.

---

## 4. Directory Permissions

Ensure the web server user has write access to:

- `storage/cache/html/`
- `storage/cache/seo/` (created on demand by `SEO`)
- `storage/temp/`
- `public/uploads/`
- `.env` (during installation and when toggling plugins/themes in Admin)

---

## 5. Run the Application

### Option A: Apache VirtualHost (Recommended)

Ensure `mod_rewrite`, `mod_headers`, and `mod_expires` are enabled with `AllowOverride All`.

- **During initial web installation (`APP_INSTALLED=false`):** Because `install/` resides alongside `public/` at `<project-root>/install/`, either:
  1. Point your local VirtualHost (or subdirectory, e.g. `APP_BASE_PATH=/groo`) to `<project-root>` so root `.htaccess` exposes `/install` while denying `app/`, `plugins/`, `templates/`, `vendor/`, and `storage/`, **or**
  2. Point `DocumentRoot` to `<project-root>/public` and temporarily configure `Alias /install "<project-root>/install"` inside your VirtualHost.
- **After installation completes (`APP_INSTALLED=true`):** Delete `install/` (and remove any temporary `Alias /install`) and point your production `DocumentRoot` directly to `<project-root>/public`.

### Option B: PHP Built-in Server *(Untested in Current Audit Environment)*

For quick local development without Apache:

```bash
php -S 127.0.0.1:8000 -t public
```

*(Note: PHP's built-in server does not evaluate `.htaccess` rules. To run the web installer under the built-in server, run `php -S 127.0.0.1:8000 -t .` only while installing on localhost, then restart with `-t public`.)*

---

## 6. First-Time Database Setup (Web Installer)

1. Create an empty MySQL database (e.g., `CREATE DATABASE groo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`), or let the installer create it if your DB user has `CREATE DATABASE` privileges.
2. With `install/` reachable per Step 5, visit `http://<your-host>/install/index.php` while `APP_INSTALLED=false`.
3. Follow the 4-step wizard to verify requirements, configure `.env`, run `install/migrations/001..007`, and create the initial admin user.
4. Once `APP_INSTALLED=true`, **delete the `install/` directory** on any non-local server (self-destruct is skipped automatically when `APP_ENV` is `local` or `development`) and serve exclusively from `public/`.

> **Known Installer Caveat:** `install/migrations/` does not currently include `media_library`. Keep `AUTO_MIGRATE=true` on first boot after installing (with `media` in `ACTIVE_PLUGINS`) so `plugins/Media/Migrations/001_create_media_table.php` executes automatically, then set `AUTO_MIGRATE=false` in production.
