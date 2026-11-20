# Repository Cleanup Report — Groo (`groo/core`)

**Date:** 2026-11-20  
**Total Space Reclaimed from Removed Files:** 6,638,870 bytes (6.33 MB across 13 files and 2 empty directories)

---

## 1. Files Removed (Verified Safe & Unreferenced)

Every file below was verified via full-repository search to have **zero active code references** before removal:

| Removed Path | Size (Bytes) | Reason for Removal |
|---|---:|---|
| `public/clear_opcache.php` | 140 | Unauthenticated web-accessible OPcache flush utility (DoS risk); 0 references. |
| `test_process_assets.php` | 224 | Broken ad-hoc local debug script with hardcoded `C:\xampp\htdocs\groo` path and invalid `Config::load(__DIR__ . '/.env')` call; 0 references. |
| `scratch/test_glob.php` | 346 | Ad-hoc local scratch script with hardcoded `C:\xampp\htdocs\groo` path; 0 references. |
| `scratch/test_realpath.php` | 739 | Ad-hoc local scratch script with hardcoded `C:\xampp\htdocs\groo` path; 0 references. |
| `scratch/test_regex.php` | 283 | Ad-hoc local scratch script referencing `/groo/` and client filename; 0 references. |
| `scratch/test_regex2.php` | 2,108 | Ad-hoc local scratch script with hardcoded `C:\xampp\htdocs\groo` path; 0 references. |
| `storage/cache/html/8c666d45efa05c015cac41c972c744dc.html` | 18,425 | Stale generated HTML page cache from local `/groo/` run containing client phone numbers; regenerated automatically at runtime. |
| `public/assets/css/app.min.css` | 2,934,019 | Unpurged Tailwind CSS v2.2.19 output (2.80 MB); 0 references in any template, plugin, or installer file. |
| `input.css` | 59 | Orphaned Tailwind CSS entry file without `package.json`; 0 references. |
| `tailwind.config.js` | 206 | Orphaned Tailwind v3 config without `package.json`; mismatched with unpurged v2 CSS; 0 references. |
| `composer.phar` | 3,639,279 | Bundled 3.47 MB Composer binary; excluded via `.gitignore` and replaced by standard Composer installation (`composer install`). |
| `plugins/Install/routes.php` | 290 | Dead route file ignored by `PluginManager` (wrong directory path and incompatible raw Bramus `$router->mount()` call); 0 references. |
| `public/uploads/img_6a7cd444b14a0.webp` | 42,752 | Unreferenced runtime test upload image; 0 references. |

Empty directories removed after cleanup:
- `scratch/`
- `public/assets/css/`

---

## 2. Targeted Configuration & Safety Modifications

| Modified / Added File | Change Summary | Why It Changed |
|---|---|---|
| `plugins/Install/Migrations/006_create_contact_messages_table.php` | Removed duplicate `use App\Core\Database\Connection;` statement (lines 7–9). | Eliminated a fatal PHP compile error (`Cannot use ... because the name is already in use`) that broke PHP syntax validation (`php -l`) and migration execution. |
| `plugins/Install/Migrations/008_CreateRobotsTxt.php` | Replaced hardcoded local path comment `// c:\xampp\htdocs\groo` on line 9 with `// Project root directory`. | Removed the final machine-specific Windows path reference from PHP source files so CI hygiene checks pass cleanly. |
| `plugins/Admin/Views/pages/admin/plugins.php` | Stripped leading UTF-8 BOM bytes (`EF BB BF`). | Prevented accidental premature output before PHP HTTP headers and aligned file encoding with `.editorconfig` (`charset = utf-8`). |
| `.htaccess` | Added `composer.phar`, `.gitignore`, `.gitattributes`, `.editorconfig`, and `*.md` to `<FilesMatch>` deny rules; added `templates`, `docs`, `scratch`, `.git`, and `.github` to the `RewriteRule` deny list. | Closed direct HTTP exposure gaps for repository metadata, documentation, templates, and binaries on Apache deployments. |
| `public/uploads/.htaccess` *(new)* | Disabled directory indexing, CGI, and PHP script execution (`php_flag engine off` + `<FilesMatch>` deny for `.php`, `.phtml`, `.phar`, etc.). | Added defense-in-depth Apache mitigation against remote code execution via uploaded files inside `public/uploads/`. |
| `.env.example` | Added clear inline comments explaining local vs production settings, base path, `AUTO_MIGRATE`, and `ACTIVE_PLUGINS`; added `media` to default `ACTIVE_PLUGINS`. | Improved developer onboarding and prevented broken Media picker links on fresh installations. |
| `.gitignore` *(new)* | Excluded `.env`, `vendor/`, `composer.phar`, `storage/cache/*`, `storage/temp/*`, `public/uploads/*`, `scratch/`, IDE metadata, and OS junk while preserving `.gitkeep` and `.htaccess` files. | Prevents secrets, generated artifacts, and multi-megabyte binaries from entering Git history once initialized. |
| `.gitattributes` *(new)* | Standardized LF line endings for source/config files, CRLF for Windows scripts, and binary flags for images/archives. | Prevents cross-platform diff noise between Windows and Linux/macOS contributors. |
| `.editorconfig` *(new)* | Defined UTF-8, LF, final newline, 4-space PHP indentation, and 2-space YAML/JSON/JS/CSS indentation. | Enforces consistent editor formatting across contributors. |
| `storage/cache/html/.gitkeep`, `storage/temp/.gitkeep`, `public/uploads/.gitkeep` *(new)* | Added `.gitkeep` placeholders in runtime directories. | Guarantees required writable runtime directories exist after a fresh `git clone`. |

---

## 3. Intentionally Preserved Files (Requires Maintainer Action)

To strictly preserve local runnability and avoid deleting existing user data or changing untested application behavior, the following items were **intentionally preserved on disk** and documented as follow-up work packages:

1. **`.env`** — Preserved locally so the developer's environment config is intact; ignored by `.gitignore`.
2. **`vendor/`** — Preserved locally because PHP/Composer are not installed in this validation environment to regenerate `vendor/autoload.php`; ignored by `.gitignore`.
3. **`templates/DualTesisat/`, `plugins/DualContact/`, `public/assets/images/dual/`, and `public/uploads/themes/DualTesisat/`** — Preserved locally because `.env` currently activates `DualTesisat` and `dualcontact`, and deleting them would remove client project data without maintainer sign-off. **Must be extracted to a private repository before making `groo/core` public** (see [`ADDITIONAL_WORK_PACKAGES.md`](ADDITIONAL_WORK_PACKAGES.md)).
4. **Application PHP Logic (Auth, Media, ZipInstaller, SEO, etc.)** — Because PHP runtime testing is unavailable in this environment, behavioral fixes to security vulnerabilities and functional bugs were scoped into [`ADDITIONAL_WORK_PACKAGES.md`](ADDITIONAL_WORK_PACKAGES.md) rather than modified blindly without tests.

---

## 4. Post-Cleanup Validation Summary

- Verified all 13 removed files and 2 empty directories no longer exist on disk.
- Verified no remaining source file references any removed path (`clear_opcache.php`, `test_process_assets.php`, `scratch/`, `app.min.css`, `input.css`, `tailwind.config.js`, `img_6a7cd444b14a0.webp`, `plugins/Install/routes.php`).
- Verified `plugins/Install/Migrations/006_create_contact_messages_table.php` no longer has duplicate `use` declarations.
- See [`VALIDATION_REPORT.md`](VALIDATION_REPORT.md) for full static validation results and environment constraints.
