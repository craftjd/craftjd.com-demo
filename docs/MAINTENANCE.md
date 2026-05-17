# Maintenance

Solo-maintainer runbook. Ctrl+F is your friend.

## Re-orient (30 seconds)

```
URL slug
  → resources/views/pages/{slug}.blade.php   (hand-built, wins)
  → resources/content/{slug}.md              (markdown fallback)
  → 404

Structured data  → storage/app/private/data/{name}.json  (FlatFileStore)
Client config    → config/client.php
Platform code    → app/Starter/  (merge from starter repo)
```

**Contact form:** `pages/contact.blade.php` → `Livewire/ContactForm` → `Mail/ContactFormSubmitted`  
**Requires:** `CONTACT_EMAIL` in `.env` (who receives submissions)

---

## Mail — local dev (Docker + Mailhog)

### 1. Add Mailhog to `compose.yaml`

Under `services:`:

```yaml
    mailhog:
        image: 'axllent/mailhog:latest'
        ports:
            - '${FORWARD_MAILHOG_SMTP_PORT:-1025}:1025'
            - '${FORWARD_MAILHOG_DASHBOARD_PORT:-8025}:8025'
        networks:
            - sail
```

On `laravel.test`, add to `depends_on:`:

```yaml
            - mailhog
```

Restart: `./vendor/bin/sail down && ./vendor/bin/sail up -d`

### 2. `.env` (local)

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@example.test"
MAIL_FROM_NAME="${APP_NAME}"

CONTACT_EMAIL=you@example.com
```

`MAIL_HOST=mailhog` is the Docker service name — works inside Sail.  
Do **not** use `127.0.0.1` from inside the container.

### 3. Verify

1. Open `/contact`, submit the form
2. Open Mailhog UI: **http://localhost:8025**
3. Email should appear (not in `storage/logs`)

### Local without Docker

```env
MAIL_MAILER=log
```

Submissions write to `storage/logs/laravel.log`. Fine for quick checks, not for reading real emails.

---

## Mail — production

### 1. Required `.env` keys (every client)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS="noreply@clientdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

CONTACT_EMAIL=owner@clientdomain.com
```

`CONTACT_EMAIL` = who receives contact form submissions  
`MAIL_FROM_ADDRESS` = what recipients see as sender (use a real address on the client's domain)

### 2. TLS

For port **587** (typical):

```env
MAIL_SCHEME=tls
```

For port **465**:

```env
MAIL_SCHEME=smtps
MAIL_PORT=465
```

### 3. Common providers

| Provider | Notes |
|----------|-------|
| **Client's cPanel/host SMTP** | Host, port, and credentials from hosting panel. Most common for mom-and-pop sites. |
| **Google Workspace** | App password required; `smtp.gmail.com`, port 587. Not ideal for form mail at scale. |
| **Postmark / Mailgun / SES** | Transactional — better deliverability. Use provider docs; often a dedicated Laravel mailer or SMTP credentials. |

### 4. Production verify

1. Deploy with `.env` set (never commit `.env`)
2. Submit `/contact` on the live site
3. Confirm delivery to `CONTACT_EMAIL`
4. Check spam folder if missing — fix SPF/DKIM on the domain if needed (hosting provider docs)

### 5. Prod checklist

- [ ] `CONTACT_EMAIL` is the client's real inbox
- [ ] `MAIL_FROM_ADDRESS` is on the client's domain (not `@gmail.com`)
- [ ] `APP_URL` matches the live URL
- [ ] `MAIL_MAILER` is **not** `log`
- [ ] `MONITOR_KEY` is set (random string; used by hub to poll `/health`)

---

## Server provisioning (first time)

One-time setup per VPS, then per client site. Assumes **Ubuntu 24.04 LTS** on Linode (or similar), **nginx + PHP-FPM**, deploy via **git pull + `bin/*`** over SSH.

| Tier | Linode plan (approx.) | Stack |
|------|----------------------|-------|
| **Brochure / flat-file** | Nanode 1 GB (~$5/mo) | SQLite, file cache/session, sync queue |
| **App** | 2 GB + MySQL (~$12+/mo) | MySQL, Redis, queue worker (add later if needed) |

Multiple brochure sites can share one VPS (separate vhosts + app dirs). Run **`provision-server` once per VPS**, then **`provision-site` per client**.

### 1. Create the VPS

1. Linode → **Create Linode** → Ubuntu 24.04 LTS, region near the client
2. Add your **SSH public key** at create time (or `ssh-copy-id` to root before provisioning). The `deploy` user has **no password** — it receives a copy of root's authorized keys. If root uses password-only login, `deploy` cannot SSH until you add a key to root and re-run `provision-server`.
3. Note the public IP — you'll need it for DNS and first SSH

### 2. Provision the server (`bin/provision-server`)

Copy the scripts from this repo to the fresh server and run as **root**:

```bash
scp bin/provision-server bin/lib.sh root@SERVER_IP:~/
ssh root@SERVER_IP 'bash provision-server --tier=flat-file'
# or: bash provision-server --tier=app
```

**`flat-file`** installs: nginx, PHP 8.3 (+ SQLite), Composer, Node 20, Certbot, UFW, `deploy` user, `/var/www`.

**`app`** adds: MySQL, Redis, `php-redis`.

The script is idempotent — safe to re-run if something fails mid-way.

### 3. Provision a client site (`bin/provision-site`)

SSH as `deploy`, copy `bin/provision-site` + `bin/lib.sh` (or use them from an already-cloned repo):

```bash
scp bin/provision-site bin/lib.sh deploy@SERVER_IP:~/
ssh deploy@SERVER_IP
```

**Brochure example:**

```bash
bash provision-site \
  --domain=client.com \
  --repo=git@github.com:YOU/client.com.git \
  --tier=flat-file
```

**App example** (creates MySQL database/user and requests TLS):

```bash
bash provision-site \
  --domain=client.com \
  --repo=git@github.com:YOU/client.com.git \
  --tier=app \
  --db-name=client_db --db-user=client_user --db-pass='strong-password' \
  --ssl --cert-email=you@example.com
```

The script will:

1. Clone the repo to `/var/www/DOMAIN`
2. Copy `.env.flat-file.example` or `.env.app.example` → `.env`
3. Set `APP_URL`, generate `APP_KEY` and `MONITOR_KEY`
4. Create SQLite file (brochure) or MySQL DB (app, when flags provided)
5. Fix `storage/` / `bootstrap/cache` permissions
6. Write and enable an nginx vhost
7. Optionally run Certbot (`--ssl` — DNS A records must already point at the server)

It prints the generated `MONITOR_KEY`. **Edit `.env`** before going live:

- `APP_NAME`, `CONTACT_EMAIL`, `MAIL_*` (and `GA_MEASUREMENT_ID` if used)
- App tier without `--db-*` flags: set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` manually

### Configure `.env` (before first deploy)

`provision-site` creates and seeds `.env` for you. If you cloned manually instead:

```bash
cd /var/www/client.com
cp .env.flat-file.example .env   # brochure — or .env.app.example for app tier
nano .env
php artisan key:generate
# brochure only:
touch database/database.sqlite
```

**Written automatically by `provision-site`:**

| Variable | How |
|----------|-----|
| `APP_URL` | Set from `--domain` (`https://` after `--ssl`) |
| `APP_KEY` | `php artisan key:generate` |
| `MONITOR_KEY` | Random value generated and **saved into `.env`** — also printed to the terminal; copy that line somewhere safe if you will poll `/health` from a hub |

**You must edit manually** (`nano /var/www/client.com/.env`):

```env
APP_NAME="Client Site Name"
CONTACT_EMAIL=owner@clientdomain.com

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="noreply@clientdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Optional: `GA_MEASUREMENT_ID`, `SITE_DESCRIPTION`. App tier: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (unless you passed `--db-name/user/pass`).

Never commit `.env`. Routine `./bin/deploy` does not modify it.

### 4. First deploy and verify

```bash
cd /var/www/client.com
./bin/deploy
sudo systemctl reload php8.3-fpm
./bin/check
```

Submit the live `/contact` form and confirm mail (see **Mail — production** above).

Add another brochure site on the same VPS: skip step 2, run `provision-site` again with a different `--domain` and `--repo`.

### 6. DNS, Cloudflare, and TLS

**DNS records** (Cloudflare or registrar):

| Name | Type | Value | Notes |
|------|------|-------|-------|
| `@` | A | `SERVER_IP` | Root domain |
| `www` | A or CNAME | `SERVER_IP` or `@` | Required before Certbot if you request `www` |

Add **both** before running Certbot with `-d domain.com -d www.domain.com`. If `www` is missing, Certbot fails — safe to re-run after adding the record.

**Cloudflare (proxied orange cloud):**

1. **Before origin TLS:** SSL/TLS → **Flexible** (Cloudflare HTTPS → origin HTTP on port 80).  
   Using **Full** without a cert on the server causes **Error 521**.
2. **After Certbot on the server:** SSL/TLS → **Full (strict)**.
3. Confirm A records point at your Linode IP.

**TLS with Certbot** (on the server, after DNS propagates):

```bash
sudo certbot --nginx \
  -d starter.craftjd.com \
  -d www.starter.craftjd.com
```

Failed runs are fine to retry once DNS is complete. Certbot updates nginx for HTTPS and HTTP→HTTPS redirect.

Then update `.env` and redeploy:

```bash
cd /var/www/starter.craftjd.com
nano .env   # APP_URL=https://starter.craftjd.com
./bin/deploy
sudo systemctl reload php8.3-fpm
```

**Verify from the server:**

```bash
./bin/check
# or against the public URL:
CHECK_URL=https://starter.craftjd.com ./bin/check-web
```

`bin/check-web` reads `APP_URL` from `.env` when `CHECK_URL` is unset. For local curls to `127.0.0.1`, it sends the `Host` header from `APP_URL` so nginx serves the correct vhost.

### 5. Provisioning checklist

- [ ] SSH key login works; `deploy` user has sudo
- [ ] `provision-server --tier=...` completed without errors
- [ ] UFW: SSH + Nginx allowed
- [ ] PHP 8.3-FPM running; extensions match tier (sqlite vs mysql/redis)
- [ ] `provision-site` completed; `.env` mail settings filled in
- [ ] `APP_KEY` and `MONITOR_KEY` set; `APP_URL` matches live URL
- [ ] `storage/`, `bootstrap/cache` (and SQLite file) writable by `www-data`
- [ ] nginx site enabled; `nginx -t` passes
- [ ] DNS A records for `@` and `www` point to server
- [ ] Cloudflare SSL mode matches origin (Flexible before cert, Full strict after)
- [ ] TLS certificate issued (Certbot or `provision-site --ssl`)
- [ ] `./bin/deploy` and `./bin/check` pass
- [ ] Contact form delivers to `CONTACT_EMAIL`

### Script reference

| Script | Run as | Purpose |
|--------|--------|---------|
| `bin/provision-server --tier=flat-file\|app` | root | Once per VPS — packages, PHP, deploy user |
| `bin/provision-site --domain=... --repo=... --tier=...` | deploy | Per client — clone, `.env`, nginx, optional SSL |

`provision-site` options: `--ssl --cert-email=...`, `--db-name/user/pass` (app), `--deploy` (skip until `.env` mail is set).

---

## Deploy (production)

Ongoing updates after the site is provisioned and `.env` is configured (see **Configure `.env`** above). `bin/deploy` does not create or edit `.env`.

SSH to the server, then from the app root:

```bash
./bin/deploy
./bin/check
```

### `bin/deploy`

1. `git pull` (branch `main`, override with `DEPLOY_BRANCH=...`)
2. `composer install --no-dev`
3. `npm ci` (or `npm install` if no lockfile) && `npm run build`
4. `php artisan migrate --force`
5. `php artisan config:cache`, `route:cache`, `view:cache`
6. Writes `storage/app/deploy-meta.json` (`git_sha`, `deployed_at`)

After deploy, reload PHP-FPM if your stack requires it (not automated — varies by host):

```bash
sudo systemctl reload php8.3-fpm
```

### `bin/check`

Runs all domain checks in one pass:

| Script | What it verifies |
|--------|------------------|
| `bin/check-web` | `GET /up`, `/`, `/sitemap.xml`, `/health` (if `MONITOR_KEY` set) |
| `bin/check-php` | PHP 8.3+, required extensions, php-fpm (if systemd) |
| `bin/check-db` | `php artisan db:show` |
| `bin/check-mail` | `CONTACT_EMAIL`, SMTP host when `MAIL_MAILER=smtp` |
| `bin/check-storage` | Writable `storage/`, `bootstrap/cache`, data dir |

Override web check base URL: `CHECK_URL=https://example.com ./bin/check-web` (defaults to `APP_URL` from `.env`)

### `/health` (hub monitoring)

When `MONITOR_KEY` is set in `.env`, `GET /health` returns JSON. Send the key in header `X-Monitor-Key`.

Without `MONITOR_KEY`, the endpoint returns 404 (hidden).

Example response:

```json
{
  "status": "ok",
  "client": "Acme Co",
  "env": "production",
  "deployed_at": "2026-06-12T14:30:00Z",
  "git_sha": "abc123...",
  "checks": { "db": true, "storage": true, "mail": true }
}
```

---

## Quick reference

| Task | Where |
|------|-------|
| First-time server setup | `bin/provision-server` then `bin/provision-site` — **Server provisioning** |
| DNS, Cloudflare, and TLS | **Server provisioning** → step 6 |
| Deploy updates | `./bin/deploy` then `./bin/check` |
| Add a Blade page | `resources/views/pages/{slug}.blade.php` |
| Add markdown page | `resources/content/{slug}.md` |
| Nav links | `config/client.php` → `navigation` |
| Site name / SEO basics | `config/client.php` + `.env` (`site_url`, `seo.og_image`, `seo.twitter_handle`) |
| Sitemap | auto at `/sitemap.xml` — exclude slugs in `config/starter.php` → `sitemap.excluded` |
| JSON table data | `storage/app/private/data/{name}.json` |
| Contact form logic | `app/Livewire/ContactForm.php` |
| Email template | `resources/views/mail/contact-form-submitted.blade.php` |
