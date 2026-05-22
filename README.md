# Starter

Laravel brochure-site starter. Flat-file pages, optional markdown, JSON data, Livewire where needed.

## First-time setup (client clone)

See **[docs/MAINTENANCE.md](docs/MAINTENANCE.md)** — steps 2–3 (clone repo, local dev).

```bash
composer run setup
printf 'WWWUSER=%s\nWWWGROUP=%s\n' "$(id -u)" "$(id -g)" >> .env
./vendor/bin/sail up -d
```

Site: http://localhost · Mailhog: http://localhost:8025

## Maintainer docs

**[docs/MAINTENANCE.md](docs/MAINTENANCE.md)** — new client checklist, provisioning, deploy, reference.
