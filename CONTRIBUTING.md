# Contributing to Groo (`groo/core`)

Thank you for your interest in contributing to Groo. This document outlines how to set up a development environment, follow repository conventions, and submit changes.

---

## Code of Conduct

All contributors and participants are expected to follow our [Code of Conduct](CODE_OF_CONDUCT.md).

---

## Development Setup

1. Review the prerequisites and setup steps in [`docs/development/BUILDING.md`](docs/development/BUILDING.md).
2. Copy `.env.example` to `.env` and configure your local MySQL credentials.
3. Install dependencies with Composer:
   ```bash
   composer install
   ```
4. Never commit your local `.env`, `vendor/`, `composer.phar`, runtime caches (`storage/cache/*`), or user uploads (`public/uploads/*`).

---

## Coding Standards

- **PHP Version:** Maintain compatibility with PHP `>=8.0`.
- **Strict Typing:** Include `declare(strict_types=1);` at the top of all PHP files.
- **Formatting:** Follow [`.editorconfig`](.editorconfig) (UTF-8, LF line endings, 4-space indentation for PHP, 2-space indentation for YAML/JSON/JS/CSS) and PSR-12 style guidelines.
- **Autoloading:** Follow PSR-4 namespace-to-directory mapping (`App\`, `Plugins\`, `Templates\`).
- **Database Queries:** Always use parameterized PDO prepared statements (`?` or named placeholders). Never interpolate user input into SQL identifiers, table names, column names, or operators.
- **Output Escaping & CSRF:**
  - Escape all untrusted output in PHP views using `$this->e(...)` or `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
  - Protect all state-changing routes (`POST`, `PUT`, `DELETE`) with CSRF token validation (`Csrf::verify`) and appropriate authentication/authorization middleware (`AdminAuthMiddleware`).
- **Configuration Access:** Never hardcode machine-specific paths (e.g., `C:\xampp\...`), installation subdirectories (e.g., `/groo/`), or `/admin` literals; use `GROO_BASE_PATH`, `Config::get(...)`, and `AdminPaths`.
- **No Client Data in Core:** Keep core plugins and templates generic. Client-specific themes, domains, phone numbers, and assets must live outside `groo/core`.

---

## Verification Before Submitting a Pull Request

Run the available local validation checks (see [`docs/development/TESTING.md`](docs/development/TESTING.md)):

```bash
composer validate --strict
find app install plugins public templates -name '*.php' -print0 | xargs -0 -n1 php -l
```

If you add or modify database migrations, verify both fresh installation and idempotent re-runs.

---

## Pull Request Process

1. Create a focused branch from the default branch (e.g., `fix/media-upload-validation` or `feat/seo-redirects`).
2. Keep pull requests scoped to one logical change.
3. Update relevant documentation under `docs/` when changing architecture, configuration, routes, or setup steps.
4. Clearly describe what changed, why it changed, and how you validated it in your PR description.

---

## Reporting Security Issues

If you discover a security vulnerability, please follow [`SECURITY.md`](SECURITY.md) instead of opening a public issue or pull request.
