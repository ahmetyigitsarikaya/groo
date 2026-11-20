# Comprehensive Repository Audit Report — Groo (`groo/core`)

**Audit Date:** 2026-11-20  
**Scope:** Full source, configuration, dependency, security, and hygiene inspection of all 343 files (24.44 MB) across `app/`, `install/`, `plugins/`, `public/`, `scratch/`, `storage/`, `templates/`, `vendor/`, and root configuration files.

---

## 1. Executive Summary

Groo (`groo/core`) is a lightweight, custom PHP 8.0+ plugin-and-template CMS/CRM framework with only 2 direct Composer dependencies (`bramus/router`, `vlucas/phpdotenv`). The core architecture (~649 KB across 173 PHP files) is cleanly structured around PSR-4 namespaces and modular plugins.

However, the audit uncovered **critical security vulnerabilities**, **client-specific production data mixed into the core repository**, **competing migration systems**, **23.76 MB (~97.2%) of non-source bloat/duplicates**, and **zero automated tests**. Public open-source release is **blocked** until the security and client-data findings are resolved.

---

## 2. Code Quality Audit

### 2.1 Critical Functional Bugs & Broken Logic

| ID | Location | Issue |
|---|---|---|
| CQ-01 | `plugins/Install/Migrations/006_create_contact_messages_table.php:7-9` | Duplicate `use App\Core\Database\Connection;` causes a **PHP fatal compile error** whenever loaded. *(Fixed during Phase 3 cleanup.)* |
| CQ-02 | `plugins/UserManagement/Controllers/AdminUserController.php:176` | Calls `$this->auth->refreshPermissions()`, which does not exist on `AdminAuthService` (fatal error when an admin updates their own account). |
| CQ-03 | `plugins/UserManagement/Controllers/ApiKeyController.php:98-138` | `ApiResponse::error(..., 'unauthorized')` passes a `string` where `int $status` is required (`TypeError`) and omits `->send()`, breaking the Bearer token `/api/me` endpoint. |
| CQ-04 | `plugins/ErrorPages/ErrorPagesPlugin.php:20-38` | `register()` never calls `View::addPath($this->path('Views'))` (only called inside the exception listener), causing the `/admin/error-pages` admin UI to fail view resolution. |
| CQ-05 | `plugins/SEO/Controllers/Admin/SEOController.php` & `Front/SitemapController.php:81` | `saveSettings()`/`saveRobots()` execute `UPDATE` without `INSERT ... ON DUPLICATE KEY UPDATE` (0 rows affected if keys are unseeded); sitemap queries nonexistent `content_posts` table instead of the `post` dynamic entity. |
| CQ-06 | `app/Core/Middleware/HTMLOptimizer.php:41,64-76,127,178-181` | CSS `url()` WebP replacement condition is logically unreachable (dead code); `file_exists()`/`getimagesize()` omit `/public` prefix; generated theme upload URLs hardcode `/public/uploads/...`. |
| CQ-07 | `app/Core/Middleware/PageCache.php:16-79` | Caches non-HTML GET responses (e.g., `/sitemap.xml`) and appends `<!-- Served from HTML Cache -->`; lacks TTL and size cap; couples Core directly to `Plugins\Admin\Services\AdminAuthService`. |
| CQ-08 | `app/Core/Template/TemplateManager.php:232-235` | `glob($themeUploadDir . '/' . $filename . '_*.webp')` selects `$matches[0]`, which can serve `_480w.webp` or `_768w.webp` thumbnails instead of the full-resolution WebP image. |
| CQ-09 | `plugins/Admin/Controllers/ThemeController.php:26` | Reads nonexistent `Config::get('env.ACTIVE_TEMPLATE', 'default')` (actual key is `templates.active`), always displaying `default` as active. |
| CQ-10 | `plugins/SEO/*`, `plugins/Traffic/*` | Read nonexistent `Config::get('app.admin_path', 'admin')` instead of `AdminPaths`, breaking custom `ADMIN_PATH` exclusions. Similarly, `Content`, `Crm`, `DualContact`, and `ErrorPages` hardcode `/admin/...` route prefixes. |
| CQ-11 | `plugins/Admin/public/assets/js/admin.js:24,33` | Hardcodes `fetch('/groo/admin/crm/notifications/...')`, breaking notification actions on any deployment not mounted at `/groo`. |
| CQ-12 | `templates/DualTesisat/Controllers/BlogController.php:40` | Missing post redirect concatenates `Config::get('app.base_path', '') . '/blog'`, generating `//blog` (protocol-relative URL) when `APP_BASE_PATH=/`. |
| CQ-13 | `app/Core/Database/DynamicEntityRepository.php:106-110,177-184` | `matchesType()` returns `true` for `null` even on `required` attributes; `updateRecord()` calls `validateData()` twice per update. |
| CQ-14 | `app/Core/Application.php:57-58` | `$this->templates->runMigrations()` runs outside the `try/catch` block wrapping `runMigrations()`, risking fatal boot failure on DB errors. |
| CQ-15 | `app/Core/Exceptions/Handler.php:59-68` | Does not match `TemplateException`; returns `$e->getMessage()` in JSON even when `app.debug=false`; risks double output if `core.exception.render` listener does not `exit`. |

### 2.2 Architectural Duplication & Dead Code

- **Three competing migration pipelines:**
  1. `install/migrations/` (7 files, named classes, `scope='install'`, misses `media_library` & `dual_contact_messages`; `007_error_pages.php` declares class `Install_008_error_pages`).
  2. `plugins/Install/Migrations/` (9 files, anonymous classes, collides on `scope='install'`, creates legacy `admin_users` table, omits `crm_task_types.updated_at` required by `TaskService::updateTaskType()`).
  3. Per-plugin `Migrations/` (`DualContact`, `Media`). Additionally, `Application::runMigrations()` uses scope `$slug`, whereas `PluginsController::toggle()` uses `"plugin_" . $slug`.
- **Two parallel event/hook systems with opposite priority sorting:** `EventDispatcher` (`krsort`, descending) vs `Hook` (`ksort`, ascending, used only by `UserManagement`).
- **Dead / Unused Files & Classes:**
  - `plugins/Install/routes.php` — Never loaded by `PluginManager` (wrong directory casing and incompatible Bramus API). *(Removed during Phase 3 cleanup.)*
  - `plugins/Content/Services/ContentService.php` (173 lines) — Duplicate of `PostService.php`, 0 references.
  - `plugins/UserManagement/Repositories/AdminUserRepository.php` (171 lines) — Targets unused `admin_users` table, 0 references.
  - `plugins/UserManagement/Controllers/BaseController.php` — Duplicates `plugins/Admin/Controllers/BaseController.php`.
  - `plugins/Admin/Support/AdminMenu*` — Populated by plugins (often with wrong argument arity: 5–6 args passed to a 4-arg method), yet completely ignored by `admin-sidebar.php` which hardcodes all links.
  - `plugins/Crm/Routes/routes.php:27,48` — Registers `GET /task-types` twice.
  - `plugins/Media/MediaPlugin.php:34` — Listens to nonexistent `admin.menu.build` event.
  - Unused database tables: `crm_expenses`, `seo_redirects`, `admin_users`.
- **Debug Logging Left in Production Paths:** `DemoPlugin.php:33` (`error_log` on every booted request), `ErrorPagesPlugin.php:26`, `MediaController.php:105,109` (`GROO_DEBUG`).
- **Hardcoded Local Windows Paths:** `C:\xampp\htdocs\groo` in `test_process_assets.php`, `scratch/*`, and comment in `plugins/Install/Migrations/008_CreateRobotsTxt.php:9`.

---

## 3. Build System & Configuration Audit

- **Composer (`composer.json` / `composer.lock`):**
  - Clean PSR-4 autoloading (`App\`, `Plugins\`, `Templates\`) with `sort-packages` and `optimize-autoloader` enabled.
  - **Missing extension declarations:** Declares `"php": ">=8.0"` only, omitting required runtime extensions `ext-pdo`, `ext-pdo_mysql`, `ext-json`, `ext-fileinfo` (unguarded `mime_content_type` in Media), `ext-xmlwriter` (unguarded `XMLWriter` in SEO), and optional suggested extensions `ext-gd` (WebP/resizing), `ext-zip` (`ZipArchive`), and `ext-mbstring`.
  - **Missing directory:** `exclude-from-classmap` references `app/Database/Migrations/`, which does not exist (`app/Database/` is empty).
- **Orphaned Frontend Build Artifacts:**
  - `input.css` + `tailwind.config.js` (Tailwind v3 syntax) + `public/assets/css/app.min.css` (2.80 MB unpurged Tailwind v2.2.19) exist with **no `package.json`** and **zero references** in any template, plugin, or installer file.
- **Apache Configuration (`.htaccess`, `public/.htaccess`):**
  - Root `.htaccess` lacked explicit deny rules for `composer.phar`, `scratch/`, `templates/`, `docs/`, `.git/`, and markdown/config dotfiles.
  - `public/uploads/` lacked an `.htaccess` disabling PHP execution. *(Both hardened during Phase 3 cleanup.)*
- **`.env.example` Gaps:**
  - `ACTIVE_PLUGINS` omits `media` (even though `admin-sidebar.php`, `layouts/admin.php`, and `Content` post editor link to `/admin/media` and `/media/upload`).

---

## 4. Dependency & Licensing Audit

### 4.1 Direct & Transitive Composer Packages (`composer.lock` / `vendor/`)

| Package | Locked Version | Relationship | Verified License | Compatible with MIT? |
|---|---|---|---|---|
| `bramus/router` | `1.6.1` | Direct (used in `Router.php`) | MIT | Yes |
| `vlucas/phpdotenv` | `v5.6.4` | Direct (used in `Config.php`) | BSD-3-Clause | Yes |
| `graham-campbell/result-type` | `v1.1.4` | Transitive (`phpdotenv`) | MIT | Yes |
| `phpoption/phpoption` | `1.9.5` | Transitive (`phpdotenv`) | Apache-2.0 | Yes |
| `symfony/polyfill-ctype` | `v1.37.0` | Transitive (`phpdotenv`) | MIT | Yes |
| `symfony/polyfill-mbstring` | `v1.38.2` | Transitive (`phpdotenv`) | MIT | Yes |
| `symfony/polyfill-php80` | `v1.37.0` | Transitive (`phpdotenv`) | MIT | Yes |

- **Usage Verification:** Both direct dependencies are actively used. Zero unused Composer dependencies were found.
- **Runtime External CDN Dependencies:** Tailwind Play CDN (`cdn.tailwindcss.com`), Google Fonts (`fonts.googleapis.com`), Chart.js (`cdn.jsdelivr.net`), ApexCharts (`cdn.jsdelivr.net`).
- **Project License Status:** `composer.json` declares `"license": "MIT"`, and all 7 third-party packages use permissive MIT/BSD-3-Clause/Apache-2.0 licenses compatible with MIT. **However, no root `LICENSE` file exists and no copyright holder is declared anywhere in the repository.** Per open-source best practices, publishing a root `LICENSE` file requires explicit maintainer confirmation of legal ownership/copyright holder name.

---

## 5. Security Audit Summary

> **Secret Disclosure Policy:** No secret, password, or credential literals are reproduced in this report.

### 5.1 Secrets & Sensitive Data Inspection
- **`.env`:** Present in the workspace root (must remain gitignored). All `DB_*` credential fields are currently empty, and no API keys or private keys exist in `.env`. However, `.env` contains client deployment configuration (`DualTesisat`, `dualcontact`, `install`).
- **Hardcoded Credentials in Source:** `plugins/Install/Migrations/001_CreateAdminUsersTable.php:29-31` seeds a default admin user (`admin@groo.test`) with a hardcoded plaintext password literal.
- **Client-Identifying Production Data (Public Release Blocker):**
  - `templates/DualTesisat/` and `plugins/DualContact/` embed a real business's branding (`DUAL Tesisat & Dekorasyon`), production domain (`dualtesisat.com`), Turkish mobile phone numbers / WhatsApp URLs, physical street address and GPS coordinates (Ankara), 7 field WhatsApp photos, and a 4.12 MB third-party website screenshot (`screencapture-sanrotech-plumbing-de-2026-08-11-22_27_06.png` — potential copyright/trademark concern).
  - `storage/cache/html/8c666d45efa05c015cac41c972c744dc.html` contained cached output with client phone numbers and local `/groo/` URLs. *(Removed during Phase 3 cleanup.)*

### 5.2 Application Vulnerabilities Identified

| Severity | ID | Component | Summary |
|---|---|---|---|
| **Critical** | SEC-01 | `plugins/Media/Controllers/MediaController.php:168-175` | **RCE via Polyglot Upload Fallback:** If GD fails to parse a file that passed `mime_content_type()`, it preserves the user-supplied file extension and moves it into `public/uploads/`. *(Mitigated at Apache layer via `public/uploads/.htaccess` in Phase 3; PHP fix still required.)* |
| **Critical** | SEC-02 | `plugins/Admin/Services/ZipInstallerService.php:39-57` | **Zip Slip & Arbitrary Code Upload:** `extractTo()` runs before path validation and lacks entry-path traversal (`../`), symlink, and size/count checks; `ThemeController::upload()` lacks RBAC permission checks. |
| **Critical** | SEC-03 | `plugins/Admin/Services/AdminAuthService.php:198-213` | **RBAC Privilege Escalation (Fail-Open to Super Admin):** Users with zero roles (or any DB error during role lookup) receive `["*"]` wildcard Super Admin permissions. Revoking an admin's roles promotes them to Super Admin. |
| **Critical** | SEC-04 | `plugins/DualContact/Routes/routes.php:7-11` | **Unauthenticated Admin Inbox:** `/admin/contact-messages` has no `AdminAuthMiddleware`, exposing all customer PII messages and allowing anonymous deletion. |
| **Critical** | SEC-05 | `plugins/Demo/Routes/routes.php:9-14` | **Unauthenticated Dynamic Entity Read/Write:** Exposes all entities (including CRM contacts and draft posts) to anonymous read/write if `Demo` is enabled (which happens automatically if `ACTIVE_PLUGINS` is blank). |
| **Critical** | SEC-06 | `plugins/Install/Controllers/InstallController.php:17-49` | **Unauthenticated Migration Runner & DSN Leak:** `GET/POST /system-install/` has no auth, CSRF, or `APP_INSTALLED` guard, and outputs DB host/database/username on error. |
| **High** | SEC-07 | `public/clear_opcache.php` | **Unauthenticated OPcache Flush (DoS):** Directly web-accessible in `public/`. *(Removed during Phase 3 cleanup.)* |
| **High** | SEC-08 | `MediaController`, `SEOController`, `CrmNotificationController`, `ContactController`, `AuthController` (`GET /logout`) | **Missing CSRF Protection** on state-changing POST endpoints and GET logout route. |
| **High** | SEC-09 | `plugins/Admin/Support/Session.php` & `AdminAuthService.php:122-167` | **Weak Session/Remember-Me Security:** Missing `httponly`/`secure`/`samesite` session cookie params; `remember_token` stored in plaintext without DB expiry or rotation; banning a user does not invalidate sessions/tokens. |
| **High** | SEC-10 | `templates/DualTesisat/Views/layouts/app.php:12,16`, `Crm` views, `Admin` views, `ContentHelper.php` | **Reflected, Stored & DOM XSS Vectors:** Unescaped `REQUEST_URI` in canonical/OG tags; unescaped task titles, invoice numbers, unvalidated task colors, `innerHTML` usage, and raw post HTML blocks. |
| **High** | SEC-11 | `install/index.php` & `.htaccess:14` | **Exposed Standalone Installer:** Direct access allowed via `.htaccess`; step 4 reachable directly via `?step=4`; `display_errors=1` hardcoded; unquoted `.env` writes allow newline injection. |
| **Medium** | SEC-12 | `plugins/SEO/Controllers/Front/SitemapController.php` & `SEOEngine.php` | **Host Header Cache Poisoning & Disk Exhaustion:** Caches `$_SERVER['HTTP_HOST']` in global `sitemap_xml` cache for 6 hours; writes per-URL PHP cache files (`var_export` + `include`) for arbitrary request paths. |
| **Medium** | SEC-13 | `app/Core/Middleware/PageCache.php` & `TrafficPlugin.php` | **Unbounded Disk / DB Exhaustion:** Caches every unique `REQUEST_URI` query string forever; Traffic logs every 404 and recomputes all URLs (`2N+1` queries) on dashboard load. |
| **Medium** | SEC-14 | `TemplateManager.php:216-265`, `AssetController.php:36` | **Path Traversal Prefix Quirk:** `strpos($target, $root) !== 0` omits trailing `DIRECTORY_SEPARATOR`. |
| **Low / Info** | SEC-15 | `app/Core/Database/QueryBuilder.php` | All current plugin queries use parameterized values (no live SQLi found), but `QueryBuilder` does not quote or validate table/column/operator identifiers. |

---

## 6. Repository Hygiene Audit (Initial State)

Total initial footprint: **343 files / 25,629,662 bytes (24.44 MB)**.

| Category | Path(s) | Files | Size | Classification |
|---|---|---:|---:|---|
| Bundled binary | `composer.phar` | 1 | 3.47 MB | Unnecessary binary — remove & gitignore |
| Vendored dependencies | `vendor/` | 110 | 0.43 MB | Generated by Composer — gitignore (keep locally for runtime) |
| Unused compiled CSS | `public/assets/css/app.min.css` | 1 | 2.80 MB | Unpurged Tailwind v2, 0 references — remove |
| Orphaned CSS config | `input.css`, `tailwind.config.js` | 2 | 265 B | Unreferenced, no `package.json` — remove |
| Dangerous debug endpoint | `public/clear_opcache.php` | 1 | 140 B | Security risk, 0 references — remove |
| Dev scratch scripts | `test_process_assets.php`, `scratch/*` | 5 | 3.7 KB | Hardcoded XAMPP paths, 0 references — remove |
| Dead route file | `plugins/Install/routes.php` | 1 | 290 B | Unloaded legacy file — remove |
| Stale HTML cache | `storage/cache/html/8c666d45...html` | 1 | 18.4 KB | Generated runtime cache — remove & gitignore |
| Unreferenced upload | `public/uploads/img_6a7cd444b14a0.webp` | 1 | 42.8 KB | Test upload, 0 references — remove & gitignore `public/uploads/*` |
| Duplicate client assets | `public/assets/images/dual/*` ≡ `templates/DualTesisat/public/assets/images/dual/*` | 16 | 13.79 MB | Byte-for-byte SHA-256 duplicates (6.89 MB × 2) — preserve locally; extract client theme before public release |
| Generated theme WebPs | `public/uploads/themes/DualTesisat/*` | 22 | 3.22 MB | Runtime GD derivatives — gitignore `public/uploads/*`; preserve locally |
| Local env file | `.env` | 1 | 364 B | Machine/deployment config — gitignore (never delete user's local `.env`) |
| Missing community files | `.gitignore`, `.gitattributes`, `.editorconfig`, `README.md`, `SECURITY.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SUPPORT.md`, `.github/workflows/*` | — | — | Created in Phases 2, 5, 6, 7 |
