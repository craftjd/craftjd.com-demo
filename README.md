# Starter

Laravel brochure-site starter. Flat-file pages, optional markdown, JSON data, Livewire where needed.

## First-time setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install && npm run build
php artisan serve
```

With Sail: `./vendor/bin/sail up -d`

## Maintainer docs

**[docs/MAINTENANCE.md](docs/MAINTENANCE.md)** — architecture, mail, env, common tasks. Read this when revisiting a project.
