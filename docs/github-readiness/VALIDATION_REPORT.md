# Build, Test & Validation Report — Groo (`groo/core`)

**Validation Date:** 2026-11-20  
**Host Environment:** Windows (PowerShell), Node.js `v24.22.0`, npm `11.19.1`, Python `3.12`, Git `2.56.0.windows.2`

---

## 1. Environment & Toolchain Availability (Verified)

| Tool / Runtime | Status in Audit Environment | Evidence / Command Result |
|---|---|---|
| `php` (native Windows PATH & common directories) | **Not Installed** | `php -v` → command not found; `where.exe php` → not found; checked `C:\xampp`, `C:\laragon`, `C:\wamp64`, `C:\php`, Herd, Scoop, Chocolatey. |
| `php` (inside WSL) | **Not Installed** | `wsl php -v` → `/bin/sh: php: not found`. |
| `composer` | **Not Installed** | `where.exe composer` → not found (and `composer.phar` requires `php` to execute). |
| `docker` | **Installed, Daemon Stopped** | `docker info` → failed to connect to `dockerDesktopLinuxEngine` named pipe. |
| `python` | **Available (`3.12`)** | Used for static JSON, YAML, PHP hygiene, PSR-4 namespace, BOM, and Markdown link verification. |
| `node` / `npm` | **Available (`v24.22.0` / `11.19.1`)** | Verified installed (not required by project since no `package.json` exists). |
| `git` | **Available (`2.56.0`)** | `git status -sb` → confirmed workspace is not currently a Git repository. |

> **Important Evidence Constraint:** Because `php`, `composer`, Apache, and MySQL are not installed in this environment (and Docker Desktop is not running), **no PHP interpreter syntax check (`php -l`), Composer install/validate, database migration run, or live HTTP smoke test was executed locally**. All such runtime checks are recorded honestly as **Skipped (Environment Limitation)** below and automated via `.github/workflows/ci.yml` for execution in GitHub Actions (Ubuntu Linux, PHP 8.1 / 8.2 / 8.3).

---

## 2. Build & Configuration Validation

Groo is an interpreted PHP web application with no native compilation step and no Node.js asset build pipeline.

| Check | Command / Method | Result | Details |
|---|---|---|---|
| `composer.json` JSON validity | `python validate.py` (`json.loads`) | **PASSED** | Valid JSON (9 top-level keys); PSR-4 mappings (`App\`, `Plugins\`, `Templates\`) verified. |
| `composer.lock` JSON validity | `python validate.py` (`json.loads`) | **PASSED** | Valid JSON (12 top-level keys); 7 locked packages match `vendor/`. |
| GitHub Actions YAML validity | `python validate.py` (`yaml.safe_load`) | **PASSED** | `.github/workflows/ci.yml` (`php-validation`) and `.github/workflows/code-quality.yml` (`hygiene-and-audit`) parsed cleanly with PyYAML. |
| `composer validate --strict` | Requires PHP + Composer | **SKIPPED** | Environment lacks PHP/Composer; wired into `.github/workflows/ci.yml`. |
| `composer install` | Requires PHP + Composer | **SKIPPED** | Environment lacks PHP/Composer; existing `vendor/` preserved locally on disk and excluded via `.gitignore`. |
| `composer audit` | Requires PHP + Composer | **SKIPPED** | Environment lacks PHP/Composer; wired into `.github/workflows/code-quality.yml`. |

---

## 3. Static Source, Hygiene & Link Validation (Executed)

Executed post-cleanup static validation script (`validate.py`) across the entire workspace:

| Check | Scope | Result | Findings |
|---|---|---|---|
| PHP file inventory | `app/`, `install/`, `plugins/`, `public/`, `templates/` | **PASSED** | **173** PHP files scanned. |
| Duplicate `use` statement check | All 173 PHP files | **PASSED** | **0** duplicate `use` statements remaining (after fixing `plugins/Install/Migrations/006_create_contact_messages_table.php`). |
| UTF-8 BOM check | All 173 PHP files | **PASSED** | **0** files with UTF-8 BOM remaining (after stripping BOM from `plugins/Admin/Views/pages/admin/plugins.php`). |
| Hardcoded `C:\xampp` path check | All 173 PHP files | **PASSED** | **0** remaining matches (after removing `test_process_assets.php`, `scratch/*`, and cleaning comment in `plugins/Install/Migrations/008_CreateRobotsTxt.php:9`). |
| References to removed files | All 173 PHP files | **PASSED** | **0** dangling references to any of the 13 removed files. |
| PSR-4 namespace alignment | All namespaced PHP files in `app/`, `plugins/`, `templates/` | **PASSED** | **0** namespace-to-directory mismatches. |
| Required governance & hygiene files | Root, `public/uploads/`, `storage/`, `.github/workflows/` | **PASSED** | All 17 expected files present (`.gitignore`, `.gitattributes`, `.editorconfig`, `.env.example`, `.htaccess`, `public/.htaccess`, `public/uploads/.htaccess`, `.gitkeep`s, community docs, CI workflows). |
| Markdown internal relative links | All 16 Markdown files (excluding `vendor/`) | **PASSED** | **57** internal relative links checked; **0** broken links. |

---

## 4. Test Suite Validation

| Test Category | Status | Details |
|---|---|---|
| Unit Tests (PHPUnit / Pest) | **NOT PRESENT** | Repository contains no `tests/` directory, no `phpunit.xml`, and no `require-dev` test dependencies. |
| Integration / HTTP / DB Tests | **NOT PRESENT / SKIPPED** | No automated integration suite exists; live server testing not possible without PHP/Apache/MySQL. |
| PHP Interpreter Syntax Lint (`php -l`) | **SKIPPED (Local)** | Unavailable locally (no PHP binary); configured in `.github/workflows/ci.yml` across PHP `8.1`, `8.2`, `8.3`. |

---

## 5. Platform Support Verification

| Platform | Evidence in Repository | Verification Status |
|---|---|---|
| **Windows (XAMPP / Apache + PHP 8.0+ + MySQL)** | Original development environment evidenced by `.htaccess`, prior XAMPP scripts, and Windows path handling (`str_replace('\\', '/', ...)` in `TemplateManager.php` / `HTMLOptimizer.php`). | Static inspection only (PHP/Apache/MySQL not installed on current audit host). |
| **Linux (Ubuntu + Apache + PHP 8.1–8.3 + MySQL)** | Standard POSIX PHP code with `DIRECTORY_SEPARATOR` handling; targeted by `.github/workflows/ci.yml` (`ubuntu-latest`). | Untested locally; CI workflow ready to execute on GitHub Actions. |
| **macOS** | No macOS-specific assumptions or blockers found in PHP source. | Untested. |
| **Nginx / PHP-FPM** | No Nginx config shipped; security blocks currently rely on Apache `.htaccess` files. | **Not directly supported** without equivalent Nginx `location` deny rules. |

---

## 6. Known Limitations & Remaining Runtime Validation Steps

Before tagging any release or deploying to production, run the following in a PHP 8.0+ / MySQL / Apache environment (or via GitHub Actions):

1. `composer validate --strict && composer install && composer audit`
2. Full `php -l` lint across all 173 PHP files (`app/`, `install/`, `plugins/`, `public/`, `templates/`)
3. Fresh installation test via `/install/index.php` against a clean MySQL 8.0 / MariaDB 10.4 database
4. Manual smoke test of Admin login, Content posts, CRM, Media upload (confirming `public/uploads/.htaccess` blocks `.php` execution), SEO, and Traffic
5. Security remediation verification for all items in [`ADDITIONAL_WORK_PACKAGES.md`](ADDITIONAL_WORK_PACKAGES.md)
