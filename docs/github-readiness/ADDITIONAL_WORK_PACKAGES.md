# Additional Work Packages (Follow-Up Remediation Plan)

This document records work packages discovered during the audit that fall outside safe, non-behavioral GitHub-readiness cleanup and require **PHP runtime testing** and/or **explicit maintainer decisions** before public open-source release.

---

## WP-1: Public Release Blockers — Security Remediation (Priority: Critical)

These vulnerabilities must be fixed and verified in a PHP 8.0+ / MySQL runtime environment before exposing Groo on a public server or publishing a production release:

1. **Fix RBAC Fail-Open Privilege Escalation (`plugins/Admin/Services/AdminAuthService.php:198-213`):**
   - Change empty role pivot and `catch (\Throwable)` branches from `return ['*']` to `return []` (fail closed).
   - Decode and merge the JSON `permissions` column from `um_roles` instead of hardcoding `['admin']`.
   - Enforce granular permissions on privileged routes in `Admin`, `UserManagement`, `Content`, and `Crm`.
2. **Eliminate RCE Fallback in Media Upload (`plugins/Media/Controllers/MediaController.php:168-175`):**
   - Reject uploads if GD image decoding fails rather than falling back to `move_uploaded_file()` with the user-supplied extension.
   - Enforce a strict extension allowlist, maximum file size limit, and `Csrf::verify()`.
3. **Fix Zip Slip & Zip Bomb Risks (`plugins/Admin/Services/ZipInstallerService.php:39-57`):**
   - Inspect every archive entry *before* extraction; reject `..`, absolute paths, backslashes, symlinks, and excessive entry counts/uncompressed sizes.
   - Require `admin.themes` / Super Admin permission in `ThemeController::upload()`.
4. **Lock Down Unauthenticated Endpoints (`plugins/DualContact`, `plugins/Demo`, `plugins/Install`, `install/index.php`):**
   - Add `AdminAuthMiddleware` to `plugins/DualContact/Routes/routes.php` (if retained outside core).
   - Remove or strictly gate `plugins/Install` (`/system-install/`), remove hardcoded default credentials in `001_CreateAdminUsersTable.php`, and stop leaking DB DSN/username on error.
   - Prevent `PluginManager::load()` from auto-enabling `demo` or `install` when `ACTIVE_PLUGINS` is empty.
   - Prevent direct `?step=4` skipping, strip newlines before writing `.env`, and disable unconditional `display_errors=1` in `install/index.php`.
5. **Close CSRF, XSS, Session & Cache Vulnerabilities:**
   - Add CSRF validation to `MediaController` (`upload`, `delete`, `optimizeThemeAssets`), `SEOController::clearCache`, `CrmNotificationController`, and `ContactController::submit`; change logout to `POST`-only.
   - Configure `httponly`, `secure`, and `samesite=Lax` on session cookies (`Session.php`); hash `remember_token` with SHA-256 + DB expiry + rotation; invalidate sessions/tokens on user ban.
   - Escape untrusted output in `DualTesisat/Views/layouts/app.php:12,16`, `Crm` views (`contacts/show.php:134`, `tasks/index.php:160`), `Admin/Views/pages/admin/dashboard.php:49`, `Admin/Views/layouts/admin.php:72`, and sanitize post HTML blocks; add `JSON_HEX_TAG` to inline JSON blocks.
   - Pin canonical base URL in `SitemapController` (eliminate `HTTP_HOST` cache poisoning) and add TTL/size caps + `Content-Type: text/html` filtering to `PageCache`.

---

## WP-2: Extract Client-Specific Code & Assets (Priority: Critical for Public Repo)

Before pushing `groo/core` to a public GitHub repository, the maintainer must decide how to separate client deployment files:

- Extract `templates/DualTesisat/`, `plugins/DualContact/`, `public/assets/images/dual/` (6.89 MB duplicate), and `public/uploads/themes/DualTesisat/` (3.22 MB generated WebPs) into a separate private repository.
- Verify copyright/usage rights for `screencapture-sanrotech-plumbing-de-2026-08-11-22_27_06.png` (4.12 MB) and remove third-party/client personal phone numbers, street addresses, and WhatsApp photos from the public repo.

---

## WP-3: License Formalization (Priority: High — Maintainer Approval Required)

- `composer.json` declares `"license": "MIT"`, and all 7 third-party Composer dependencies (`bramus/router`, `vlucas/phpdotenv`, `graham-campbell/result-type`, `phpoption/phpoption`, `symfony/polyfill-*`) use MIT, BSD-3-Clause, or Apache-2.0 licenses compatible with MIT.
- **Action Required:** Maintainer must confirm legal ownership/copyright holder name, add a root `LICENSE` file with the approved copyright line, and optionally add `"authors"` to `composer.json`.

---

## WP-4: Unify Migrations & Fix Core/Plugin Functional Bugs (Priority: High)

- Consolidate the 3 migration systems (`install/migrations/`, `plugins/Install/Migrations/`, and per-plugin `Migrations/`) under one canonical `MigrationManager` contract without `'install'` scope collisions, including `media_library`.
- Fix verified functional defects:
  - `AdminUserController.php:176` undefined `$this->auth->refreshPermissions()`.
  - `ApiKeyController.php:98-138` `ApiResponse::error` type mismatch & missing `->send()`.
  - `ErrorPagesPlugin.php` missing `View::addPath()` in `register()`.
  - `SEOController` `UPDATE` vs `INSERT ... ON DUPLICATE KEY UPDATE`, and `SitemapController` querying nonexistent `content_posts`.
  - `ThemeController.php:26` `Config::get('env.ACTIVE_TEMPLATE')` → `Config::get('templates.active')`.
  - `Admin/public/assets/js/admin.js:24,33` hardcoded `/groo/` notification URLs.
  - `Content`, `Crm`, `ErrorPages`, `SEO`, `Traffic` hardcoded `/admin` / nonexistent `app.admin_path` references.
  - `HTMLOptimizer` `/public` path resolution and dead CSS `url()` WebP branch.
  - `TemplateManager::serveAsset()` WebP glob selecting `_480w`/`_768w` thumbnails.
  - Remove unused dead classes (`ContentService.php`, `AdminUserRepository.php`) and duplicate `GET /task-types` route once confirmed unused by external plugins.

---

## WP-5: Composer Manifest & Automated Test Suite (Priority: Medium)

- Declare required PHP extensions (`ext-pdo`, `ext-pdo_mysql`, `ext-json`, `ext-fileinfo`, `ext-xmlwriter`) and suggested extensions (`ext-gd`, `ext-zip`, `ext-mbstring`) in `composer.json` and regenerate `composer.lock` with Composer.
- Introduce PHPUnit (`phpunit/phpunit` in `require-dev`) with unit tests for `Config`, `Router`, `DynamicEntityRepository`, `AdminAuthService` RBAC, `ZipInstallerService` path validation, and `HTMLOptimizer`, then enable PHPUnit in `.github/workflows/ci.yml`.
