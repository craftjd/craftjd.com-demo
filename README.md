# Starter

Laravel brochure-site starter. Flat-file pages, optional markdown, JSON data, Livewire where needed.

## First-time setup (client clone)

See **[docs/MAINTENANCE.md](docs/MAINTENANCE.md)** — steps 2–3 (clone repo, local dev).

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
