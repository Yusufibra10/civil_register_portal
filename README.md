<<<<<<< HEAD
# Civil Registry Portal

A civil registry and ID-services E-Government system for recording citizens, issuing birth certificates and national IDs, and processing ID applications through a staffed workflow — with public pages for verifying a certificate or tracking an application by number.

Built as plain PHP 8, with no framework and no Composer dependencies, so it can run on almost any shared-hosting or on-premises server that offers PHP + MySQL.

## Features

- **Authentication** — session-based login with remember-me, idle-timeout auto-logout, and account lockout after repeated failed attempts.
- **Role & permission management** — three built-in roles (Admin, Officer, Viewer) plus a data-driven permission system (`roles/index.php`) for finer-grained access control.
- **Citizens registry** — create, search, filter, edit, soft-delete/restore citizen records, with a profile photo.
- **Birth certificates** — register, print, revoke; a public verification page confirms a certificate's authenticity from just its number.
- **National IDs** — issue, print, mark as collected.
- **ID applications** — a 7-step workflow (Submitted → Received → Under Review → Approved/Rejected → Card Printed → Ready for Collection), with a public tracking page.
- **Reports** — citizens, birth certificates, national IDs, and applications reports with CSV export and a print-friendly view.
- **User management** — create/edit/deactivate/restore staff accounts; assign roles and per-role permissions.
- **System settings** — system name, logo, theme color, office contact details.
- **Activity log** — every significant action (login, create, update, delete, status change) is recorded with who, what, and when.

## Technologies

| Layer      | Choice |
|------------|--------|
| Language   | PHP 8 (tested on 8.2), PDO for all database access |
| Database   | MySQL 8.0.16+ or MariaDB 10.2+ (a `CHECK` constraint requires this minimum) |
| Frontend   | Bootstrap 5, Font Awesome 6 (via CDN), vanilla JavaScript |
| Charts     | Chart.js 4 (via CDN) |
| Server     | Apache 2.4 with `mod_rewrite`, `mod_headers`, `mod_deflate`, `mod_expires`, `mod_filter` |

No Composer, no npm build step, no framework — every `.php` file under a module folder (`citizens/`, `birth/`, `applications/`, …) is a directly-requested page.

## Folder Structure

```
civil_register_portal/
├── auth/            Login, logout, session handler
├── dashboard/        Landing page after login: stats, charts, recent activity
├── citizens/         Citizens registry (CRUD, soft delete/restore)
├── birth/            Birth certificates (register, print, revoke, public verify)
├── national_id/      National ID cards (issue, print, mark collected)
├── applications/     ID application workflow + public tracking page
├── reports/          Report generation, CSV export, print view
├── users/            User account management
├── roles/            Roles & permission management
├── settings/         System settings (branding, contact info)
├── config/           Bootstrap (config.php), DB connection, environment detection
├── middleware/       auth / guest / role / permission gate scripts
├── helpers/          Repositories (data access) + cross-cutting helpers (CSRF, validation, uploads, …)
├── components/       Reusable view partials (status badges, pagination, avatars, …)
├── layouts/          Page shells (app.php, guest.php, print.php)
├── includes/         Shared chrome: header, sidebar, navbar, footer
├── assets/           css/, js/, fonts/, images/, and uploads/ (user-submitted photos)
├── storage/          Generated files not meant to be web-servable (CSV report exports)
├── database/         schema.sql, migration_phaseN.sql, seed.php, backup.sh, restore.sh
└── logs/             app.log (production error log)
```

## Installation

See **[INSTALLATION.md](INSTALLATION.md)** for full step-by-step setup. Quick version for local development:

1. Copy the project into your web server's document root (e.g. `C:\xampp\htdocs\civil_register_portal`).
2. Create a database and import `database/schema.sql`.
3. Run `php database/seed.php` to create the three demo accounts.
4. Visit `http://localhost/civil_register_portal/` and log in.

## Configuration

All configuration lives in `config/`:

- `config/config.php` — environment detection (auto: `localhost`/`127.0.0.1`/CLI → development, anything else → production), error reporting, session hardening, timezone, `BASE_URL`.
- `config/database.php` — database connection factory. Reads `config/database.local.php` if present (gitignored — copy it from `config/database.local.php.example` for a real deployment); otherwise falls back to local XAMPP defaults (`root` / no password).

Nothing else needs editing to run locally. See [DEPLOYMENT.md](DEPLOYMENT.md) for production configuration.

## Running the Project

- **Local (XAMPP):** start Apache + MySQL from the XAMPP control panel, then browse to `http://localhost/civil_register_portal/`.
- **Demo accounts** (created by `database/seed.php` — change these before any real deployment):

  | Role    | Email                        | Password       |
  |---------|-------------------------------|----------------|
  | Admin   | admin@civilregistry.gov       | Admin@12345    |
  | Officer | officer@civilregistry.gov     | Officer@12345  |
  | Viewer  | viewer@civilregistry.gov      | Viewer@12345   |

## Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| Blank page or "A system error occurred" | Check `logs/app.log`. In development, `ENVIRONMENT` auto-detects to `development` on `localhost`/CLI and shows the real error instead. |
| "A system error occurred" on the login page specifically | Usually a database connection failure — check `config/database.local.php` (or the defaults in `config/database.php`) match a database that actually exists. |
| CSS/JS look broken | Confirm `mod_deflate`, `mod_expires`, `mod_filter`, and `mod_headers` are enabled in Apache — a missing `mod_filter` will make `mod_deflate`'s directive fail and can 500 the whole site (see DEPLOYMENT.md). |
| Photo/logo upload fails | Check `assets/uploads/{citizens,users,settings}/` are writable by the web server user, and the file is under 2MB and a JPG/PNG/WEBP. |
| Rate-limit message ("Too many lookups"/"Too many failed login attempts") appears unexpectedly during testing | Intentional — see the rate limits in [DEPLOYMENT.md](DEPLOYMENT.md#security). Wait 15 minutes, or clear matching rows from `activity_logs` in a dev environment. |
| "Your session expired due to inactivity" | Idle timeout is 30 minutes (`SESSION_TIMEOUT_SECONDS` in `config/config.php`) — log in again. |

For deployment, admin usage, and database setup, see the other docs in this repository: [INSTALLATION.md](INSTALLATION.md), [DEPLOYMENT.md](DEPLOYMENT.md), [ADMIN_GUIDE.md](ADMIN_GUIDE.md), [USER_GUIDE.md](USER_GUIDE.md), [DATABASE_SETUP.md](DATABASE_SETUP.md).
=======
# civil_register_portal
>>>>>>> e29379ee26f9dc9972b0795ba0bdeaadf0e006cb
