# Security Policy

## Reporting a Vulnerability

Please do **not** report security vulnerabilities through public GitHub issues, pull requests, or discussions.

Instead, report vulnerabilities privately to the repository maintainers via GitHub Security Advisories (**Security → Advisories → Report a vulnerability**) once the repository is hosted on GitHub, or directly to the project maintainer through a private channel.

When reporting a vulnerability, please include:

- A clear description of the issue and potential impact.
- Affected file paths, routes, or plugin names.
- Minimal reproduction steps or proof-of-concept details.
- Any suggested remediation or mitigation, if known.

We aim to acknowledge reports promptly and coordinate disclosure once a fix is verified.

---

## Known Security Considerations (Pre-Release Status)

> **Important:** Groo (`groo/core`) is currently undergoing pre-release security hardening and is **not yet recommended for public internet-facing production deployments** without applying the remediations documented in [`docs/github-readiness/REPOSITORY_AUDIT.md`](docs/github-readiness/REPOSITORY_AUDIT.md) and [`docs/github-readiness/ADDITIONAL_WORK_PACKAGES.md`](docs/github-readiness/ADDITIONAL_WORK_PACKAGES.md).

### Mandatory Deployment Hardening Rules

1. **Never commit `.env` or leave `APP_DEBUG=true` in production.**
2. **Always set `ACTIVE_PLUGINS` explicitly** in `.env`. Leaving `ACTIVE_PLUGINS` empty causes `PluginManager` to auto-load every plugin in `plugins/`, including `demo` and `install`.
3. **Never enable `demo` or `install` plugins in production.** Both expose unauthenticated administrative or write endpoints.
4. **Remove or block `/install` after installation.** The standalone setup wizard in `install/` should be deleted once `APP_INSTALLED=true`.
5. **Serve `public/` as your web server document root** and disable PHP script execution inside `public/uploads/` (enforced for Apache via `public/uploads/.htaccess`; configure equivalent rules if using Nginx).
6. **Do not expose client-specific plugins or themes** (`plugins/DualContact`, `templates/DualTesisat`) in public builds; extract them into private deployment repositories.

---

## Supported Versions

Because Groo has not yet published a tagged stable release (`v1.0.0`), security fixes are applied directly to the default development branch.
