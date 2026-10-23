# Biofy.TR

[Türkçe](README.tr.md) · **English**

A multi-tenant **link-in-bio SaaS** platform. Users sign up, pick a ready-made theme, add their links and content blocks, publish a mobile-friendly page at `https://biofy.tr/username` or on their own domain, and follow their visit and click statistics.

- **Stack:** Laravel 13 · PHP 8.3+ · MySQL 5.7+ / MariaDB 10.3+ · Blade · Tailwind CSS 4 (precompiled) · dependency-free JavaScript
- **Languages:** Turkish (default) and English
- **Deployment:** (1) shared WordPress hosting — FTP + web installer, no SSH/Composer/Node needed on the server; (2) Coolify — automatic Docker deployment on every push to GitHub
- **Version:** 1.0.3

## Highlights

| Area | Summary |
|---|---|
| Accounts | E-mail/password, e-mail verification, password reset, Google sign-in (OAuth/OIDC), TOTP two-factor authentication + recovery codes, session management, security history, personal data export (KVKK/GDPR) and account deletion |
| Editor | 20 block types, drag-and-drop and keyboard reordering, live mobile/desktop preview, draft ↔ published separation, version history, discard changes, multi-tab/conflict protection, scheduled blocks |
| Themes | 12 distinct themes (layout, typography, button and card styles), safe customization (colors, gradients, image background, font, corners, shadows, alignment, animation) |
| Plans | Basic / Pro / Premium; central entitlement engine (platform limit → account status → per-user override → plan → default), quotas, time-limited assignments |
| Analytics | Cookie-less view/click counting without storing IP addresses, bot and repeat filtering, daily aggregation, referrer/device/browser/country breakdowns, CSV export |
| Custom domains | Ownership check via DNS TXT, CNAME/A check, HTTPS verification, unknown hosts rejected |
| Administration | Dashboard from real data, user/page moderation, plan and feature catalogue, CMS, menus, e-mail templates, SMTP/Google settings, media, backup/restore, system health, updates, audit logs |
| API | `/api/v1` REST API, Sanctum tokens (hashed), abilities, per-token/per-user rate limits, OpenAPI 3 document |

## Quick start

**Shared (WordPress) hosting**

1. Download **`biofy-tr-<version>.zip`** from the **Assets** of the [latest release](https://github.com/itsbykus/Biofy.TR/releases/latest) and check it against its `.sha256` file. Do not use GitHub's automatic "Source code" archives (`Biofy.TR-<version>.zip`): they contain neither `vendor/` nor `public/build/`.
2. Extract it and upload it over FTP for a subdomain (layouts: [docs/SHARED_HOSTING.md](docs/SHARED_HOSTING.md)).
3. In your hosting panel, create an **empty** MySQL/MariaDB database, separate from WordPress.
4. Open the subdomain; the installer starts. Steps: [docs/INSTALLATION.md](docs/INSTALLATION.md).

No terminal, Composer, npm or Node.js is needed on the hosting account; those tools are only used on the machine (or in GitHub Actions) that builds the package.

**Coolify**

1. In Coolify, add this repository with the "Docker Compose" build pack and assign a domain to the `app` service.
2. Set `APP_URL`, `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD` and, for the first superadmin, the `BIOFY_ADMIN_*` variables, then press Deploy.
3. Details: [docs/COOLIFY.md](docs/COOLIFY.md).

## Development

```bash
composer install
npm ci && npm run build        # or npm run dev
cp .env.example .env && php artisan key:generate
# in .env: APP_ENV=local, APP_DEBUG=true and your database settings
php artisan migrate --seed
touch storage/app/private/installed.lock   # skip the web installer
php artisan serve
php artisan test                # 135 tests
php scripts/build-release.php   # FTP package -> dist/, verified by scripts/verify-release.php
```

## Documentation

[SECURITY](docs/SECURITY.md) is available in English; the other detailed documents are currently in Turkish.

| Document | Contents |
|---|---|
| [ARCHITECTURE](docs/ARCHITECTURE.md) | Modules, request flow, key decisions |
| [DATABASE](docs/DATABASE.md) | Tables, relations, indexes, tenant isolation |
| [SECURITY](docs/SECURITY.md) | Threat model and implemented measures |
| [DEPLOYMENT](docs/DEPLOYMENT.md) | Comparison of the two deployment methods, building a release package |
| [SHARED_HOSTING](docs/SHARED_HOSTING.md) | WordPress hosting requirements, layouts, file permissions |
| [COOLIFY](docs/COOLIFY.md) | Coolify setup, environment variables, automatic deployment, migrations, persistent data |
| [INSTALLATION](docs/INSTALLATION.md) · [UPDATE](docs/UPDATE.md) | Installation and update guides |
| [OPERATIONS](docs/OPERATIONS.md) | Cron, e-mail, backup/restore, domains, production checklist, known limitations |
| [API](docs/API.md) · [openapi.yaml](docs/openapi.yaml) | REST API |
| [TESTING](docs/TESTING.md) | Test suite, deployment tests and what is not covered |
| [CHANGELOG](CHANGELOG.md) | Release notes |
| [ROADMAP](docs/ROADMAP.md) | Payment readiness and next steps |

> The legal pages (Privacy, Terms of Use, Cookies, KVKK) are editable **starting drafts**; have them reviewed by your legal counsel before going live.

## License

[MIT](LICENSE)
