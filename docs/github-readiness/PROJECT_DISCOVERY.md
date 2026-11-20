# Project Discovery Report — Groo (`groo/core`)

**Audit Date:** 2026-11-20  
**Workspace Root:** `c:\Users\syntax\Desktop\groomain`  
**Git Repository Status:** Not initialized (`.git/` directory does not exist)

---

## 1. Project Identity & Purpose

- **Package Name:** `groo/core` (`composer.json`)
- **Declared Description:** *"Groo - Extensible Plugin/Module based CMS/CRM Core"*
- **Declared License (in `composer.json`):** `MIT` *(No root `LICENSE` file exists yet; copyright holder is not specified in `composer.json`.)*
- **Primary Language:** PHP (`>=8.0`, strict types enabled across core and plugins), with HTML/CSS/vanilla JavaScript views and Apache `.htaccess` routing.
- **Current Local Configuration:** The local workspace `.env` is configured for a specific Turkish client deployment (`APP_NAME=DualTesisat`, `ACTIVE_TEMPLATE=DualTesisat`, `ACTIVE_PLUGINS` including `dualcontact` and `install`), whereas `.env.example` targets a generic `Groo` installation (`ACTIVE_TEMPLATE=default`).

---

## 2. Technology Stack (Verified)

| Layer | Technology | Evidence |
|---|---|---|
| Runtime | PHP `>=8.0` (`strict_types=1`, `str_starts_with`, `str_contains`, `str_ends_with`, constructor property promotion, arrow functions) | `composer.json`, `app/Core/*`, `plugins/*` |
| HTTP Routing | `bramus/router` (`^1.6`, locked `1.6.1`) wrapped by `App\Core\Http\Router` | `composer.json`, `app/Core/Http/Router.php` |
| Environment Config | `vlucas/phpdotenv` (`^5.6`, locked `v5.6.4`) | `composer.json`, `app/Core/Config.php` |
| Database | MySQL / MariaDB via `PDO` (`pdo_mysql`), UTF-8 (`utf8mb4`), MySQL `JSON` columns | `app/Core/Database/Connection.php`, `DynamicEntityRepository.php`, `install/migrations/*` |
| Web Server | Apache HTTP Server (`mod_rewrite`, `mod_headers`, `mod_expires`) | `.htaccess`, `public/.htaccess` |
| Frontend Styling (Views) | Tailwind CSS via Play CDN (`https://cdn.tailwindcss.com`), custom admin CSS (`plugins/Admin/public/assets/css/admin.css`), Google Fonts (`Inter`) | `templates/default/index.php`, `templates/DualTesisat/Views/layouts/app.php`, `plugins/Admin/Views/layouts/admin.php` |
| Frontend Charts | Chart.js (`cdn.jsdelivr.net/npm/chart.js`) in Traffic plugin; ApexCharts (`cdn.jsdelivr.net/npm/apexcharts`) in CRM Finance | `plugins/Traffic/Views/admin-traffic.php`, `plugins/Crm/Views/admin/crm/finance/index.php` |
| Image Processing | PHP GD extension (`imagecreatefrom*`, `imagewebp`, `imagecopyresampled`) for WebP + responsive variants (`480w`, `768w`) | `plugins/Media/Controllers/MediaController.php` |
| Package Extraction | PHP `ZipArchive` (optional, guarded by `class_exists`) for ZIP theme/plugin uploads | `plugins/Admin/Services/ZipInstallerService.php` |
| XML Sitemap | PHP `XMLWriter` | `plugins/SEO/Controllers/Front/SitemapController.php` |

> **Note on Tailwind CSS Build Files:** Root files `input.css`, `tailwind.config.js`, and `public/assets/css/app.min.css` (2.80 MB unpurged Tailwind v2.2.19) were found in the repository without a `package.json` and with zero references anywhere in the codebase. All templates and installer views load Tailwind CSS via CDN at runtime.

---

## 3. Repository Structure (Initial Snapshot)

```text
groomain/
├── app/
│   ├── Config/             # Runtime configuration loaders (app, db, plugins, templates)
│   ├── Core/
│   │   ├── Application.php # Kernel bootstrap & HTTP lifecycle orchestrator
│   │   ├── Config.php      # Dotenv + PHP config loader with dot-notation access
│   │   ├── Hook.php        # WordPress-style action/filter hook registry (ascending priority)
│   │   ├── Database/       # Connection (PDO), QueryBuilder, DynamicEntityRepository, MigrationManager
│   │   ├── Events/         # EventDispatcher singleton (descending priority)
│   │   ├── Exceptions/     # Global Handler + typed exceptions (NotFound, Validation, Plugin, Template)
│   │   ├── Http/           # Router (Bramus wrapper) + ApiResponse (JSON envelope)
│   │   ├── Middleware/     # PageCache (HTML disk cache) + HTMLOptimizer (WebP/srcset/defer)
│   │   ├── Plugin/         # PluginInterface, AbstractPlugin, PluginManager
│   │   ├── Template/       # TemplateInterface, AbstractTemplate, TemplateManager
│   │   └── View/           # PHP View renderer (layouts, sections, partials, escaping, URL helpers)
│   └── Database/           # Empty directory (Core expects app/Database/Migrations/ which is absent)
├── install/
│   ├── index.php           # Standalone 4-step web installation wizard
│   └── migrations/         # 7 core/module database migrations (001..007)
├── plugins/                # 11 modular plugins (PSR-4: Plugins\<Name>\)
│   ├── Admin/              # Admin shell, login/auth, dashboard, entity CRUD, plugin/theme manager
│   ├── Content/            # Block-based blog/post CMS + public JSON API
│   ├── Crm/                # Contacts, notes/activity timeline, tasks/calendar, invoices, notifications
│   ├── Demo/               # Sample entity/record API & greeting endpoints (unauthenticated)
│   ├── DualContact/        # Client-specific contact message inbox for DualTesisat
│   ├── ErrorPages/         # Custom HTTP error page renderer & admin editor
│   ├── Install/            # Legacy plugin-based installer & 9 migrations (conflicts with /install)
│   ├── Media/              # Media library, WebP conversion, responsive theme asset optimizer
│   ├── SEO/                # Meta/OG/JSON-LD manager, robots.txt & sitemap.xml generator
│   ├── Traffic/            # Visitor/bot request logger, page quality scoring, Chart.js analytics
│   └── UserManagement/     # Users, roles, admin accounts, Bearer API keys
├── public/                 # Apache web document root (index.php, .htaccess, robots.txt, assets, uploads)
├── scratch/                # Local XAMPP debug scripts (unreferenced)
├── storage/
│   ├── cache/html/         # File-based HTML page cache (contains stale local cache file)
│   └── temp/               # Temporary directory for ZIP archive extraction
├── templates/              # Frontend themes (PSR-4: Templates\<Name>\)
│   ├── default/            # Minimal Groo welcome template
│   └── DualTesisat/        # Client corporate website template (Turkish plumbing/renovation site)
├── vendor/                 # Installed Composer packages (7 third-party packages)
├── .env / .env.example     # Environment configuration
├── .htaccess               # Root Apache rewrite rules forwarding to public/
├── composer.json / .lock   # Composer manifest and lockfile
├── composer.phar           # Bundled 3.47 MB Composer PHAR binary
├── input.css               # Orphaned Tailwind entry file
├── tailwind.config.js      # Orphaned Tailwind v3 config
└── test_process_assets.php # Broken local XAMPP debug script
```

---

## 4. Architecture & Request Lifecycle

1. **Apache Entry (`.htaccess` → `public/.htaccess` → `public/index.php`):**
   - Root `.htaccess` denies direct HTTP access to `app/`, `plugins/`, `vendor/`, `storage/`, `database/`, `.env*`, and `composer.json`/`composer.lock`, exempts `/install` and `/public/`, and rewrites other requests to `public/$1`.
   - `public/.htaccess` rewrites non-file paths to `public/index.php`.
2. **Bootstrap (`public/index.php`):**
   - Defines `GROO_BASE_PATH`, loads `vendor/autoload.php`, calls `Config::load(GROO_BASE_PATH)` (loading `.env` via `Dotenv::createUnsafeMutable()->safeLoad()` and all files in `app/Config/`).
   - Redirects to `/install` if `APP_INSTALLED` is false and the URI is not already `/install*`.
   - Registers `App\Core\Exceptions\Handler::register()`, then runs `Application::create(GROO_BASE_PATH)->boot()->run()`.
3. **Kernel Boot (`App\Core\Application::boot()`):**
   - Sets PHP timezone, resolves base path, instantiates `Router`, shares `base_path` with `View`.
   - Loads active plugins (`PluginManager::load()`), resolves dependency order (`requires`), calls `registerAll()`.
   - Loads the active template (`TemplateManager::load()`), checks plugin `requires`, sets `View` base path to `<template>/Views`, calls `register()`.
   - When `AUTO_MIGRATE=true`, executes `MigrationManager::run()` for core, active plugins, and active template.
   - Registers plugin routes (`registerRoutes()` + `Routes/routes.php`) and template routes (`/assets/{file:.+}`, `/media/view/{file:.+}`, and `<template>/routes.php`).
   - Calls `bootAll()` on plugins, `boot()` on template, and dispatches `app.booted`.
4. **HTTP Execution & Response Pipeline (`App\Core\Application::run()`):**
   - **PageCache (`App\Core\Middleware\PageCache`):** Serves cached HTML from `storage/cache/html/<md5(uri)>.html` on eligible GET requests (excluding admin, `/api`, `/install`, and logged-in admins).
   - **Router Dispatch (`App\Core\Http\Router`):** Captures output via `ob_start()`, matches route, runs route-group middleware chain via `array_reduce`, and executes controller/closure handler.
   - **HTMLOptimizer (`App\Core\Middleware\HTMLOptimizer`):** On non-admin/api/install HTML outputs (`>= 100` bytes containing `<html`), rewrites `<img>` tags (`srcset`, `loading="lazy"`, `decoding="async"`, dimensions), defers external scripts, and attempts CSS `url()` WebP rewrites.
   - **Cache Storage & Output:** Stores optimized HTML into `PageCache` when HTTP status `< 400`, then flushes output.
5. **Dual Extensibility Mechanism:**
   - `App\Core\Events\EventDispatcher`: Singleton pub/sub pipeline sorted by descending priority (`krsort`). Used by Core (`app.booted`, `core.exception.render`) and most plugins.
   - `App\Core\Hook`: Static action/filter registry sorted by ascending priority (`ksort`). Used exclusively by `UserManagement` (`user.*` actions, `user_profile_tabs` filter).

---

## 5. Database & Dynamic Entity Architecture

Groo combines traditional relational MySQL tables with a JSON-backed dynamic entity engine (`DynamicEntityRepository`):

- **Dynamic Entity Engine (`entities`, `attributes`, `entity_records`):** Stores flexible schemas (`post`, `crm_contact`, `contact`) with typed JSON payloads in `entity_records.data`, queryable via `JSON_UNQUOTE(JSON_EXTRACT(data, ?))`.
- **Relational Module Tables:**
  - Users & Auth: `um_users`, `um_roles`, `um_user_roles`, `um_api_keys`, `um_login_attempts`
  - CRM: `crm_task_types`, `crm_tasks`, `crm_invoices`, `crm_notifications`, `crm_notes`, `crm_expenses` *(unused)*
  - SEO: `seo_settings`, `seo_meta`, `seo_redirects` *(unused)*
  - Traffic: `traffic_logs`, `page_metrics`
  - Media: `media_library`
  - Error Pages: `error_pages`
  - DualContact (client plugin): `dual_contact_messages`
  - Migrations: `groo_migrations`

> **Architectural Conflict Discovered:** Two incompatible installation and migration pipelines coexist: the standalone wizard (`install/index.php` + `install/migrations/001..007`) and the legacy `Install` plugin (`plugins/Install/` + `plugins/Install/Migrations/001..009`). Both record versions under the same `'install'` migration scope in `groo_migrations`, while diverging on schemas (`um_users` vs `admin_users`, `crm_task_types.updated_at`, `dual_contact_messages.ip_address`).

---

## 6. Verified Capabilities Matrix

| Subsystem / Module | Verified Status | Summary of Actual Implementation |
|---|---|---|
| Core Kernel, Config, Router, View | **Implemented** | Functional MVC/front-controller kernel with PSR-4 autoloading, dot-config, Bramus router wrapper, layout/section PHP views. |
| Plugin & Template Managers | **Implemented** | Auto-discovery, `ACTIVE_PLUGINS`/`ACTIVE_TEMPLATE` selection, dependency ordering, asset streaming with hotlink checks. |
| Dynamic Entity Engine | **Partially Implemented** | Entity/attribute/JSON-record CRUD and JSON extraction work; `null` bypasses `required` validation; double-validates on update. |
| QueryBuilder & Migrations | **Partially Implemented** | Simple CRUD and forward migrations work; lacks joins/transactions/identifier quoting; `down()` unused; migration scope collision exists. |
| PageCache & HTMLOptimizer | **Experimental / Buggy** | Basic HTML caching and lazy-loading work; PageCache lacks TTL/size caps & Content-Type checks; HTMLOptimizer has broken `/public` path assumptions and dead CSS WebP code. |
| `Admin` Plugin | **Implemented (Defects)** | Auth, dashboard, entity CRUD, plugin toggle, ZIP theme/plugin upload work; broken RBAC permission resolution (`*` fallback), hardcoded sidebar ignores `AdminMenuRegistry`, `/groo/` hardcoded in `admin.js`. |
| `Content` Plugin | **Implemented** | Block-based post CMS (text, image, HTML blocks), public JSON API (`/api/content/posts`), `ContentHelper`. Includes unused `ContentService`. |
| `Crm` Plugin | **Implemented** | Contacts + activity timeline, tasks/calendar, task types, invoices/expenses, notifications, dashboard widgets. |
| `UserManagement` Plugin | **Partially Implemented** | Users, roles, and admin CRUD work; Bearer API `/api/me` has `ApiResponse::error` type mismatch & omits `->send()`; editing own admin account calls undefined `refreshPermissions()`. |
| `Media` Plugin | **Implemented (Security Risks)** | Media upload, library UI, GD WebP + responsive `480w`/`768w` generation, theme asset processor. Missing from `.env.example`; unsafe upload fallback & missing CSRF. |
| `SEO` Plugin | **Partially Implemented** | Meta/OG/JSON-LD helper and `/robots.txt` + `/sitemap.xml` routes exist; `saveSettings()`/`saveRobots()` update 0 rows if unseeded; sitemap queries nonexistent `content_posts` table; `seo_redirects` unimplemented. |
| `Traffic` Plugin | **Implemented (Perf/Config Issues)** | Request logging, bot/device detection, scoring, Chart.js dashboard. Recalculates all URL scores synchronously on dashboard load; reads nonexistent `app.admin_path`. |
| `ErrorPages` Plugin | **Partially Implemented** | Front-end custom error page rendering works via `core.exception.render`; admin editor route fails to locate view because `View::addPath` is omitted in `register()`. |
| `Demo` Plugin | **Demo / Experimental** | Sample greeting and unauthenticated dynamic entity read/write endpoints. Should remain disabled in production. |
| `DualContact` & `DualTesisat` | **Client-Specific** | Corporate Turkish plumbing site (`dualtesisat.com`) and contact inbox. Hardcodes client phone numbers, address, domain, and field photos; admin inbox lacks authentication middleware. |
| `Install` Plugin (`plugins/Install`) | **Deprecated / Conflicting** | Superseded by `install/index.php`; exposes unauthenticated `/system-install/`, conflicts with `install/migrations/`, and had a duplicate `use` compile error in migration `006`. |
| Standalone Installer (`install/`) | **Partially Implemented** | 4-step setup wizard with DB provisioning, `.env` write, admin creation, and optional self-destruct; omits `media_library` & `dual_contact_messages`, allows step-skipping to step 4. |
| Automated Test Suite | **Not Implemented** | No PHPUnit, Pest, integration, or CI test suite exists in the repository. |
