# Testing & Validation Guide

## Current Testing Status

> **Transparency Notice:** Groo (`groo/core`) does **not** currently include an automated unit or integration test suite (no `tests/` directory, no `phpunit.xml`, and no `require-dev` test dependencies in `composer.json`).

Adding a PHPUnit test suite is tracked in [`../github-readiness/ADDITIONAL_WORK_PACKAGES.md`](../github-readiness/ADDITIONAL_WORK_PACKAGES.md#wp-5-composer-manifest--automated-test-suite-priority-medium).

---

## Available Verification Commands (PHP 8.0+ Environment)

Run these commands locally before opening a pull request. The same checks run automatically in [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) and [`.github/workflows/code-quality.yml`](../../.github/workflows/code-quality.yml).

### 1. Validate Composer Manifest & Lockfile

```bash
composer validate --strict
```

### 2. Check Dependencies for Known Security Advisories

```bash
composer audit
```

### 3. PHP Syntax Lint Across All Source Files

**Linux / macOS (bash):**
```bash
find app install plugins public templates -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

**Windows (PowerShell):**
```powershell
Get-ChildItem -Path app,install,plugins,public,templates -Recurse -Filter '*.php' | ForEach-Object {
    php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
```

### 4. Core PSR-4 Autoload Smoke Check

```bash
php -r 'require "vendor/autoload.php"; var_dump(class_exists(App\Core\Application::class));'
```

---

## Manual Smoke Testing Checklist (Requires Apache + PHP 8.0+ + MySQL)

Until automated integration tests are added, verify the following flows manually after changes:

1. **Fresh Install:** Visit `/install/index.php`, complete Steps 1–4, verify `groo_migrations` and `um_users` are populated and `APP_INSTALLED=true`.
2. **Admin Auth:** Log in at `/<ADMIN_PATH>/login`, verify invalid passwords fail and rate-limiting triggers after repeated failures, verify logout clears session.
3. **Dynamic Entities & Content:** Create, edit, and delete a blog post in `/<ADMIN_PATH>/posts`; verify `GET /api/content/posts/` returns only published posts.
4. **CRM:** Create a contact, add a note, create/toggle a task, and create an invoice.
5. **Media & Uploads:** Upload a valid JPEG/PNG image and verify WebP generation; verify `public/uploads/.htaccess` blocks direct execution of `.php` files.
6. **Frontend & Error Handling:** Visit `/` (active template) and a nonexistent path (`/does-not-exist`) to verify custom 404 handling.
7. **CI Parity:** Confirm no `.env`, `composer.phar`, `scratch/`, `public/clear_opcache.php`, or hardcoded local paths exist in `git status`.
