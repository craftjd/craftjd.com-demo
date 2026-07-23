# Maintenance

Solo-maintainer runbook. Ubuntu 24.04 VPS, nginx, PHP 8.3, deploy via `git pull` + `bin/*`.

---

## New client — full checklist

Work **top to bottom**. `CLIENT` = config basename (`bin/internal/provision/clients/CLIENT.conf`, e.g. `acme`).

### 1. GitHub repo

Create a new **private** repo for the client (empty, no README).

### 2. Clone the project

Replace `ORG/CLIENT-REPO` with the client repo. All later commands run from the project directory.

**Laragon (Windows)** — clone into Laragon’s web root:

```powershell
cd C:\laragon\www
git clone git@github.com:craftjd/laravel-starter.git CLIENT
cd CLIENT
git remote set-url origin git@github.com:ORG/CLIENT-REPO.git
git push -u origin main
```

Enable the pretty URL in Laragon (Menu → www → CLIENT, or auto-virtual-hosts). Confirm Apache serves the Laravel public directory — DocumentRoot must end in `\public` (check `laragon\etc\apache2\sites-enabled\*.conf`). A directory listing means the root is wrong.

**WSL** — Unix paths and shell only:

```bash
mkdir -p ~/projects/CLIENT
cd ~/projects/CLIENT
git clone git@github.com:craftjd/laravel-starter.git .
git remote set-url origin git@github.com:ORG/CLIENT-REPO.git
git push -u origin main
```

### 3. Local development

Pick **one** stack. Do not mix Sail npm with host npm.

#### Laragon (Windows)

Use Laragon’s PHP / Composer / Node from a **Windows** terminal (PowerShell or CMD). Do not run Sail, `printf`, `touch`, or `id` here.

```powershell
composer install
copy .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
npm ci
npm run build
```

Edit `.env`:

| Key | Value |
|-----|--------|
| `APP_NAME`, `CONTACT_EMAIL` | Site identity |
| `APP_URL` | Laragon pretty URL, e.g. `http://joes.test` (must match the vhost) |
| `MAIL_MAILER` | `log` (`.env.example` defaults to Sail/Mailhog) |

After any `.env` change:

```powershell
php artisan config:clear
```

| URL | Purpose |
|-----|---------|
| `http://CLIENT.test` | Site (Laragon) |
| — | Contact mail → `storage\logs\laravel.log` |

Day-to-day Vite: `npm run dev` in a separate terminal. Laragon serves PHP — you do not need `php artisan serve` or `composer run dev` unless you prefer them.

#### WSL + Sail

From the project root in a **WSL** shell:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

Set `APP_NAME`, `CONTACT_EMAIL`, and Sail IDs in `.env`:

```bash
printf 'WWWUSER=%s\nWWWGROUP=%s\n' "$(id -u)" "$(id -g)" >> .env
```

(`id -u` / `id -g` override the placeholder `1000` values in `.env.example`.)

```bash
./vendor/bin/sail up -d
./vendor/bin/sail npm ci && ./vendor/bin/sail npm run build
./vendor/bin/sail npm run dev    # Vite — separate terminal
```

Run **npm only through Sail** — do not run `npm` on the Windows host or WSL host first; mixed installs cause `vite: Permission denied` in the container.

| URL | Purpose |
|-----|---------|
| http://localhost | Site |
| http://localhost:8025 | Mailhog (contact form) |

Contact form mail uses `MAIL_HOST=mailhog` from `.env.example`. Stop: `./vendor/bin/sail down`.

#### WSL without Docker

```bash
npm ci && npm run build
composer run dev
# or: php artisan serve
```

Set `MAIL_MAILER=log` — submissions in `storage/logs/laravel.log`.

### 4. Customize the site

Edit and commit as needed:

- `config/client.php` — name, nav, SEO
- `resources/content/*.md` or `resources/views/pages/*.blade.php` — pages
- `resources/content/data/*.json` — brochure table data (ships with each deploy)

Push to the client repo when ready.

### 5. Client config

```bash
cp bin/internal/provision/clients/_example.conf bin/internal/provision/clients/CLIENT.conf
```

Edit `CLIENT.conf`:

| Key | Example |
|-----|---------|
| `IP` | VPS public IP |
| `TIER` | `flat-file` (brochure) or `app` |
| `DOMAIN` | `client.com` |
| `REPO` | `git@github.com:ORG/CLIENT-REPO.git` |
| `SSL` | `true` when DNS is ready |
| `CERT_EMAIL` | your email (Let's Encrypt) |
| `APP_NAME`, `CONTACT_EMAIL` | site identity |
| `MAIL_MAILER` | `log` until go-live, then `smtp` + `MAIL_*` |

Configs are gitignored. Never commit `*.conf` or `.env`.

### 6. DNS

Point **both** records at the VPS IP before `SSL=true`:

| Name | Type | Value |
|------|------|-------|
| `@` | A | `IP` |
| `www` | A or CNAME | `IP` or `@` |

If using Cloudflare (proxied): set SSL/TLS to **Flexible** until Certbot succeeds, then **Full (strict)**.

### 7. Provision server (new VPS only)

```powershell
task provision:server
```

Or: `bash ./bin/internal/remote.sh server CLIENT`

Installs nginx, PHP 8.3, Composer, Node, Certbot, `deploy` user. Idempotent — safe to re-run.

**App tier** adds MySQL and Redis. **Skip this step** when adding another site to an existing VPS.

### 8. GitHub deploy key (private repos only)

```powershell
task provision:github-key
```

Or: `bash ./bin/internal/remote.sh github-key CLIENT`

Add the printed public key to the client repo: **Settings → Deploy keys** (read-only). Same key can serve multiple repos on one VPS.

Skip if the repo is public.

### 9. Provision site

```powershell
CONFIRM=true task provision:site
```

Or: `bash ./bin/internal/remote.sh site CLIENT`

Clones to `/var/www/DOMAIN`, seeds `.env`, nginx vhost, optional Certbot (`SSL=true` covers `@` and `www`). Note the printed `MONITOR_KEY`.

**Second site on same VPS:** new `CLIENT.conf` (same `IP`, new `DOMAIN`/`REPO`), skip steps 6–7, run step 8 only.

### 10. Push env to server

```powershell
task provision:env
```

Syncs client config → server `.env`. Shows a diff and asks `[Y/n]` before applying. Refreshes config cache automatically.

```powershell
bash ./bin/internal/remote.sh env CLIENT check    # validate
bash ./bin/internal/remote.sh env CLIENT show     # redacted .env on server
```

### 11. Deploy and verify

Prefer Deployer from your laptop:

```powershell
task deploy
```

Legacy on-server in-place deploy (Joe's claim recording supported):

```bash
ssh deploy@IP
cd /var/www/DOMAIN/current
REQUEST_PUBLIC_ID=… ./bin/internal/deploy.sh   # omit REQUEST_PUBLIC_ID for a normal release
./bin/internal/check.sh
```

Contact form works with `MAIL_MAILER=log` (submissions in `storage/logs/laravel.log`).

### 12. Go-live

1. Set `MAIL_MAILER=smtp` and SMTP credentials in `CLIENT.conf`
2. `task provision:env` (or `./bin/internal/remote.sh env CLIENT push`)
3. `task deploy`
4. Submit `/contact` — confirm delivery to `CONTACT_EMAIL`
5. Cloudflare → **Full (strict)** if proxied

---

## Ongoing

### Deploy updates

```powershell
task deploy
```

On-server fallback: `./bin/internal/deploy.sh` then `./bin/internal/check.sh`. Does **not** edit `.env`.

**Joe's Cafe integration** (optional — in `CLIENT.conf`, synced via `env push`):

| Key | Purpose |
|-----|---------|
| `MAIN_SITE_DOMAIN` | Main site hostname → `MAIN_SITE_WEBHOOK_URL=https://{domain}/api/joes/requests` |
| `MAIN_SITE_WEBHOOK_URL` | Explicit override for main-site API base |
| `JOES_WEBHOOK_SECRET` | Shared bearer token (must match main site) |
| `JOES_LOCAL_DEPLOY_BYPASS_GIT` | `false` in production; local dev only |

### Change site or mail settings (laptop)

```powershell
# edit bin/internal/provision/clients/CLIENT.conf
task provision:env
# then: task site:check
```

### Merge starter platform updates (laptop)

```bash
git remote add starter git@github.com:craftjd/laravel-starter.git   # once
git fetch starter
git merge starter/main
# resolve conflicts; keep client-specific config/content
git push
```

Then deploy on the server as usual.

---

## Appendix

### Code layout

```
pages/{slug}.blade.php            → hand-built (wins)
content/{slug}.md                 → markdown fallback
content/data/{name}.json          → brochure JSON (release content)
storage/…                         → shared runtime (uploads, deploy-meta, logs)
config/client.php                 → nav, SEO, site name
app/Starter/                      → platform (merge from starter)
```

Contact form: `pages/contact` → `Livewire/ContactForm` → mail to `CONTACT_EMAIL`.

### Tiers

| Tier | VPS (approx.) | Stack |
|------|---------------|-------|
| `flat-file` | 1 GB | SQLite, file cache/session |
| `app` | 2 GB+ | MySQL, Redis |

### Mail — local dev

| Stack | Mail |
|-------|------|
| WSL + Sail | `.env.example` defaults (`MAIL_HOST=mailhog`). Submit `/contact`, read at http://localhost:8025 |
| Laragon / no Docker | Set `MAIL_MAILER=log` — submissions in `storage/logs/laravel.log` |

### Mail — production SMTP

Before go-live, `CLIENT.conf` needs:

```env
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_SCHEME=tls
```

`MAIL_FROM_ADDRESS` defaults to `noreply@DOMAIN` when omitted. Port 465: `MAIL_SCHEME=smtps`.

### Private GitHub repos

- `REPO` must be SSH: `git@github.com:ORG/repo.git`
- `task provision:github-key` once per VPS (or `bash ./bin/internal/remote.sh github-key CLIENT`)
- Add deploy key to each private repo (read-only)
- Verify on server: `git ls-remote git@github.com:ORG/repo.git HEAD`
- Rotate: `bash ./bin/internal/remote.sh github-key CLIENT --regenerate`

| Problem | Fix |
|---------|-----|
| `Permission denied (publickey)` | Deploy key missing on repo |
| HTTPS clone prompt | Use SSH URL |
| `git pull` fails after rotate | Remove old deploy key in GitHub, add new pubkey |

### DNS and TLS notes

- Certbot needs `@` and `www` A records first; re-run `task provision:site` if it failed earlier
- Error 521 (Cloudflare): origin has no cert yet — use **Flexible** until Certbot runs
- `APP_URL` is set from `DOMAIN` + `SSL` via env push — no manual edit

### Scripts

```
bin/internal/remote.sh server|github-key|site|env CLIENT   # laptop → VPS (or: task provision:*)
bin/internal/deploy.sh, bin/internal/check.sh              # on server (or: task deploy / site:check)
bin/internal/provision/clients/CLIENT.conf                 # source of truth (gitignored)

Claim workflow (Joe's repo): `task claim:fulfill|abandon` (prod) / `task dev:claim:deploy|fulfill|abandon` (local)
Hub ops (craftjd repo): `task hub:*` (prod www) / `task dev:hub:*` (local)
```

| Command | Purpose |
|---------|---------|
| `remote server CLIENT` | Once per VPS |
| `remote github-key CLIENT` | Deploy key (private repos) |
| `remote site CLIENT` | Clone, nginx, optional SSL |
| `remote env CLIENT push` | Conf → `.env` (diff + confirm) |
| `remote env CLIENT check` | Validate conf + `.env` |

Provisioned automatically: `APP_KEY`, `MONITOR_KEY`, `APP_URL`. Never overwritten: `APP_KEY`.

### Health endpoint

`GET /health` with header `X-Monitor-Key` (value from `.env`). Returns 404 when `MONITOR_KEY` unset. Returns 503 when a sub-check fails (`db`, `storage`, `mail`).

### bin/internal/check.sh

| Check | Verifies |
|-------|----------|
| `checks/web` | `/up`, `/`, `/sitemap.xml`, `/health` |
| `checks/php` | PHP 8.3+, extensions, php-fpm |
| `checks/db` | Database connection |
| `checks/mail` | `CONTACT_EMAIL`; warns if `MAIL_MAILER=log` |
| `checks/storage` | Writable storage paths |

### Troubleshooting

**`GET /` returns 500 after deploy but all infra checks pass.**
Likely missing `APP_KEY` in `shared/.env`. The Deployer recipe checks this
automatically now (`deploy:app_key` task), but can be fixed manually:
```bash
cd /var/www/DOMAIN/current
php artisan key:generate --force
php artisan config:clear && php artisan config:cache
```
This happens when `releases/` + `current/` are removed to force a fresh clone
but `shared/.env` survives without a valid `APP_KEY`. The `deploy:app_key` task
catches it before `deploy:check` runs on future deploys.
