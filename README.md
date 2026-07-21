# Starter

Laravel brochure-site starter. Flat-file pages, optional markdown, JSON data, Livewire where needed.

## First-time setup (client clone)

See **[docs/MAINTENANCE.md](docs/MAINTENANCE.md)** — steps 2–3 (clone repo, local dev).

### Laragon (Windows)

Clone into Laragon’s web root (e.g. `C:\laragon\www\CLIENT`), then from that folder in PowerShell or CMD:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
npm ci
npm run dev
```

In `.env`:

- Set `APP_URL` to the Laragon pretty URL (e.g. `http://joes.test`)
- Set `MAIL_MAILER=log` (no Mailhog without Sail)
- Run `php artisan config:clear` after changing `.env`

Confirm the Apache vhost document root is `…\public` (Laragon: `etc\apache2\sites-enabled\*.conf`). A directory listing means the root is wrong.

| URL | Purpose |
|-----|---------|
| `http://CLIENT.test` | Site (Laragon pretty URL) |
| — | Contact mail → `storage\logs\laravel.log` |

Day-to-day assets: `npm run dev` (separate terminal). You do not need `php artisan serve` when Laragon is serving the vhost.

### WSL + Sail

Unix-only — use a WSL shell, not a Windows terminal. Full steps: **[docs/MAINTENANCE.md](docs/MAINTENANCE.md)** §3.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
printf 'WWWUSER=%s\nWWWGROUP=%s\n' "$(id -u)" "$(id -g)" >> .env
./vendor/bin/sail up -d
./vendor/bin/sail npm ci && ./vendor/bin/sail npm run build
```

Site: http://localhost · Mailhog: http://localhost:8025

## Maintainer docs

**[docs/MAINTENANCE.md](docs/MAINTENANCE.md)** — new client checklist, provisioning, deploy, reference.
