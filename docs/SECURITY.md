# Security

**English** · [Türkçe](SECURITY.tr.md)

## Supported versions

| Version | Supported |
|---|---|
| 1.0.x | ✔ |

## Reporting a vulnerability

Please **do not open a public issue** for security problems. Report them privately with GitHub's **Report a vulnerability** button (repository → *Security* → *Advisories*). Include the affected version, steps to reproduce and the impact you expect. You will get an answer as soon as possible; fixes are released as a new patch version and listed in [CHANGELOG.md](../CHANGELOG.md).

## Authentication

| Topic | Implementation |
|---|---|
| Passwords | bcrypt (12 rounds); policy: at least 10 characters (configurable), letters + digits, breached-password check in production (Have I Been Pwned k-anonymity) |
| Sign-in | 5 attempts per minute per e-mail+IP, 20 per IP; unknown user and wrong password get the same response with similar timing; session ID is regenerated on sign-in (fixation) |
| 2FA | RFC 6238 TOTP (covered by tests), reuse of the same time step is blocked, 8 single-use recovery codes (hashed), can be made mandatory for staff |
| Password reset | Laravel tokens (60 min), responses don't reveal whether an account exists, all sessions are signed out after a successful reset |
| Links | Every URL in e-mails is built from `APP_URL`; an unknown `Host` header never reaches the application (host header poisoning) |
| Google | Verified e-mail addresses only; accounts are matched by the Google `sub` ID; **no automatic merge** with a local account that has the same e-mail |
| Re-authentication | Data export and account deletion, 2FA changes, signing out other sessions, creating API tokens, settings changes, downloading/restoring backups, role assignment: 15-minute confirmation window |
| Suspension | Takes effect on the next request (including remember-me); sessions and API tokens are deleted |

## Authorization

- A superadmin can only be created by the installer or by another superadmin (password confirmation + audit log). The last superadmin can't be demoted or deleted.
- Admin routes are protected by permission middleware (`permission:*`). Every permission is also a Gate ability (`@can`).
- Object-level access (IDOR/BOLA): policies and ownership queries; the API answers **404** for other tenants' records.
- Plan features are enforced in services, routes (`feature:` middleware) and the API, not only hidden in the UI.

## Content security

- User content is escaped everywhere; block texts are plain text (no HTML/Markdown interpretation).
- URLs are validated by `SafeUrl`: only `http/https`; `javascript:`, `data:`, `vbscript:`, `file:`, protocol-relative `//`, URLs with credentials or control characters are rejected.
- Embeds come only from allow-listed hosts (`config/biofy.php → embed_hosts`) with addresses rebuilt by the application; iframes are sandboxed and load on click.
- Theme settings accept only `#RRGGBB` colors, bounded numbers or fixed list values, so CSS injection is not possible.
- CMS (admin) Markdown is rendered with raw HTML escaped and unsafe links disabled.
- **Content-Security-Policy:** `script-src 'self' 'nonce-…'`, `style-src 'self' 'nonce-…'`, `object-src 'none'`, `frame-ancestors 'self'`; on profile pages only the allow-listed player hosts are added to `frame-src`. Further headers: `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `COOP`, optional HSTS.
- CSRF protection on every web form (Laravel `PreventRequestForgery`); session cookies are `HttpOnly`, `Secure` over HTTPS and `SameSite=Lax`.

## File uploads

- JPEG/PNG/WebP/GIF only. The type is detected from the **content** (`finfo::buffer`), never from the file name. SVG is not accepted.
- 5 MB limit, 40-megapixel limit (decompression bombs), per-user storage quota.
- Every image is **re-encoded** with GD (metadata and appended payloads are dropped) and stored under a random 32-character name in `public/media/{purpose}/{year}/{month}/`.
- `public/media/.htaccess` disables PHP/CGI execution, serves image extensions only and adds a `sandbox` CSP.
- Files in use can't be deleted; another user's file can't be selected.

## Secrets and logs

- `.env` is never part of the release package or the repository; the installer writes it with `0640` permissions. In Docker/Coolify, configuration comes from environment variables only.
- The SMTP password, Google client secret and cron token are encrypted in the database with APP_KEY and never shown in the UI (only "saved").
- Fields such as `password`, `secret`, `token` and `key` are written as `[redacted]` in audit and security logs. Failed e-mail errors are sanitized before logging.
- Production runs with `APP_DEBUG=false`; users never see stack traces, API errors use a standard JSON envelope.

## Web root

- In the recommended setup the web root is `public/`, so application code is not reachable.
- If the whole package has to live inside `public_html`, the root `.htaccess` routes every request into `public/`. In addition, `app`, `bootstrap`, `config`, `database`, `install`, `lang`, `resources`, `routes`, `storage`, `vendor` and `docs` contain a `Require all denied` file and dot-files are refused. This is verified end-to-end in CI next to a WordPress site ([TESTING.md](TESTING.md)).
- **Check on your server:** Admin → System → "Sensitive file access test" makes real HTTP requests for `.env`, logs, `vendor`, `composer.json`, installer files and the lock file.
- Docker image: application code is owned by root and read-only for the web server user; only `storage/`, `bootstrap/cache/` and `public/media/` are writable.

## Privacy (KVKK / GDPR)

- No cookies for visitors; analytics store no IP addresses but a pseudonymous ID that changes daily. Country data comes only from the Cloudflare header when explicitly enabled; nothing is guessed from IPs.
- Fonts and icons are served locally; third-party players load only after the visitor's consent.
- Users can download their data as JSON and request account deletion (configurable grace period).
- Retention periods (analytics, security log) are configurable by the admin.

## Known limitations and recommendations

- HTTPS is not enforced by the installer but is **strongly recommended**; without HTTPS the `Secure` cookie flag can't be used.
- Behind Cloudflare or another proxy, set `TRUSTED_PROXIES` (the Coolify setup does this); otherwise rate limits apply to the proxy's IP.
- 2FA is not mandatory for admins by default; enable it after installation (Settings → Security).
- Keep up with new releases for dependency security updates.
