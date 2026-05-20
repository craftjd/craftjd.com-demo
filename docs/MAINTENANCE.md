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
3. Note the public IP — set it as `IP` in your client config (step 2)

### 2. Client config and server provisioning

Create a config per client site (gitignored; copy the example):

```bash
cp bin/provision/clients/_example.conf bin/provision/clients/starter.craftjd.conf
# edit IP, TIER, DOMAIN, REPO, SSL, CERT_EMAIL
```

Client configs live in `bin/provision/clients/*.conf` (gitignored except `_example.conf`). All `bin/remote` commands read from this file — you do not pass IP or tier on the command line.

Provision the VPS (once per server; uses `IP` and `TIER` from the config):

```bash
./bin/remote server starter.craftjd
```

Manual equivalent (run on the server as root):

```bash
./bin/provision/server --tier=flat-file
```

**`flat-file`** installs: nginx, PHP 8.3 (+ SQLite), Composer, Node 20, Certbot, UFW, `deploy` user, `/var/www`.

**`app`** adds: MySQL, Redis, `php-redis`.

The script is idempotent — safe to re-run if something fails mid-way.

### 2b. GitHub deploy key (private repos only)

Skip this if your repo is public. For **private** GitHub repos, run **once per server** before site provisioning:

```bash
./bin/remote github-key starter.craftjd
```

Copy the printed public key into each repo: **Settings → Deploy keys → Add deploy key** (read-only). Full details: **Private GitHub repos** below.

### 3. Provision a client site

```bash
./bin/remote site starter.craftjd
```

Use an **SSH repo URL** for private GitHub repos: `git@github.com:ORG/repo.git` (not HTTPS). Set up the deploy key in step 2b first.

`SSL=true` runs Certbot for **both** `DOMAIN` and `www.DOMAIN` (`@` and `www` A records required first).

CLI flags override the client config when needed:

```bash
./bin/remote site starter.craftjd --ssl --cert-email=you@example.com
```

**App tier** — set `DB_NAME`, `DB_USER`, `DB_PASS` in the client config, or pass `--db-name/user/pass` on the CLI.

Manual equivalent (on the server as `deploy`):

```bash
./bin/provision/site \
  --domain=client.com \
  --repo=git@github.com:YOU/client.com.git \
  --tier=flat-file
```

The script will:

1. Clone the repo to `/var/www/DOMAIN`
2. Copy `.env.flat-file.example` or `.env.app.example` → `.env`
3. Set `APP_URL`, generate `APP_KEY` and `MONITOR_KEY`
4. Create SQLite file (brochure) or MySQL DB (app, when flags provided)
5. Fix `storage/` / `bootstrap/cache` permissions
6. Write and enable an nginx vhost
7. Optionally run Certbot (`SSL=true` or `--ssl` — covers `@` and `www`; DNS must point at the server first)

It prints the generated `MONITOR_KEY`. **Set site and mail settings in your client config**, then:

```bash
./bin/remote env starter.craftjd push
```

- App tier without DB in config: set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in client config (or use `DB_NAME` / `DB_USER` / `DB_PASS`)

### Private GitHub repos

`provision/site` runs `git clone` on the server as the **deploy** user. Private repos need a **read-only deploy key** before you run `./bin/remote site`.

#### 1. One-time per server (deploy user)

From your laptop (uses `IP` from the client config):

```bash
./bin/remote github-key starter.craftjd
```

Manual equivalent:

```bash
scp bin/provision/github-key bin/lib.sh deploy@SERVER_IP:~/
ssh deploy@SERVER_IP 'bash ~/github-key'
```

The script will:

- Create `~/.ssh/github_deploy` (ed25519)
- Add a `github.com` entry to `~/.ssh/config`
- Print the **public key** to add in GitHub

#### 2. Add the deploy key to each private repo

For every client repo you deploy from:

1. GitHub → **repo** → **Settings** → **Deploy keys** → **Add deploy key**
2. Title: e.g. `Linode deploy` or hostname
3. Key: paste the public key from step 1
4. **Allow write access: OFF** (read-only is enough for `git pull` deploys)

The **same public key** can be added to multiple repos (fine for solo-maintained client sites on one VPS).

#### 3. Set REPO to SSH in client config

```bash
# bin/provision/clients/starter.craftjd.conf
REPO=git@github.com:craftjd/laravel-starter.git
```

Do **not** use `https://github.com/...` for private repos — it will prompt for credentials and fail non-interactively.

#### 4. Verify before provisioning

On the server as `deploy`:

```bash
ssh -T git@github.com
# expect: "Hi ORG/REPO! You've successfully authenticated..."

git ls-remote git@github.com:craftjd/laravel-starter.git HEAD
# prints a commit SHA when access works
```

Then provision:

```bash
./bin/remote site starter.craftjd
```

#### 5. Ongoing deploys (`./bin/deploy`)

`bin/deploy` runs `git pull` in the existing clone. The deploy key continues to work — no extra setup per deploy.

#### Troubleshooting

| Problem | Fix |
|---------|-----|
| `Permission denied (publickey)` on clone | Deploy key not added to repo, or wrong `REPO` URL |
| `git clone` asks for username/password | Use `git@github.com:ORG/repo.git`, not HTTPS |
| `Could not read from remote` after repo rename | Update `REPO` in client config; on server: `git remote set-url origin NEW_URL` |
| Multiple VPSes | Generate a separate deploy key per server; add each pubkey to the repos |

#### 6. Rotate a deploy key

Re-run with `--regenerate` when a key is compromised or you want a fresh key. The script backs up the old key pair, prints the **old** public key to remove from GitHub, then generates a new one.

```bash
./bin/remote github-key starter.craftjd --regenerate
# prompts for confirmation (uses ssh -t)

./bin/remote github-key starter.craftjd --regenerate --yes
# non-interactive
```

On the server, old keys are kept as `~/.ssh/github_deploy.bak.TIMESTAMP` (+ `.pub`).

After regenerating:

1. **Delete** the old deploy key from **every** private repo on that VPS (GitHub → Settings → Deploy keys)
2. **Add** the new public key printed by the script
3. Verify: `git ls-remote git@github.com:ORG/repo.git HEAD` on the server, then `./bin/deploy` per site

Until step 1–2 are done, `git pull` on private repos will fail.

#### Alternative: HTTPS + token (not recommended)

GitHub personal access tokens in clone URLs expire and end up in shell history. Use deploy keys instead.

### Configure `.env` (client config + env push)

Site and mail settings live in your **client config** on the laptop (`bin/provision/clients/CLIENT.conf`). The server `.env` is synced from there — no `nano .env` for routine changes.

**Written automatically by `provision/site`:**

| Variable | How |
|----------|-----|
| `APP_URL` | Set from `DOMAIN` + `SSL` |
| `APP_KEY` | `php artisan key:generate` (never overwritten by env push) |
| `MONITOR_KEY` | Random value generated and saved into `.env` — copy into client config if you poll `/health` from a hub |

**Set in client config** (see `_example.conf`), then push:

```bash
./bin/remote env starter.craftjd push
```

Shows a diff against the server `.env` and asks `[Y/n]` before applying. Use `--yes` to skip confirmation.

```bash
./bin/remote env starter.craftjd push --yes   # non-interactive
./bin/remote env starter.craftjd check        # validate config + server .env
./bin/remote env starter.craftjd show         # server .env with secrets redacted
```

**Synced from client config:**

| Variable | Notes |
|----------|-------|
| `APP_NAME`, `SITE_DESCRIPTION`, `CONTACT_EMAIL`, `GA_MEASUREMENT_ID` | Site identity |
| `MAIL_*` | SMTP settings |
| `MAIL_FROM_ADDRESS` | Defaults to `noreply@${DOMAIN}` |
| `APP_URL` | Derived from `DOMAIN` + `SSL` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | App tier (`DB_NAME` / `DB_USER` / `DB_PASS` aliases supported) |
| `MONITOR_KEY`, `DEPLOY_BRANCH`, `CHECK_URL` | Only when set in client config |

After push, refresh config cache on the server:

```bash
cd /var/www/client.com
php artisan config:clear && php artisan config:cache
./bin/check
```

Never commit `.env` or `*.conf`. Routine `./bin/deploy` does not modify `.env`.

If you cloned manually instead of `provision/site`:

```bash
cd /var/www/client.com
cp .env.flat-file.example .env   # brochure — or .env.app.example for app tier
php artisan key:generate
# flat-file only:
touch database/database.sqlite
./bin/remote env CLIENT push
```

### 4. First deploy and verify

```bash
cd /var/www/client.com
./bin/deploy
sudo systemctl reload php8.3-fpm
./bin/check
```

Submit the live `/contact` form and confirm mail (see **Mail — production** above).

Add another brochure site on the same VPS: reuse the same `IP` in a new client config, skip server + github-key, run `./bin/remote site NEW_CLIENT` only.

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
CHECK_URL=https://starter.craftjd.com ./bin/checks/web
```

`bin/checks/web` reads `APP_URL` from `.env` when `CHECK_URL` is unset.

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

`bin/` layout:

```
bin/
  remote              # from laptop — server / github-key / site / env via client config
  deploy              # on server — pull, build, migrate, cache
  check               # on server — run all post-deploy checks
  lib.sh              # shared helpers
  provision/
    server            # on VPS as root — once per server
    site              # on VPS as deploy — per client
    github-key        # on VPS as deploy — once per server (private repos)
    env               # on VPS as deploy — sync .env from client config
    clients/          # saved client configs (gitignored)
  checks/
    web php db mail storage
```

| Script | Where | Purpose |
|--------|-------|---------|
| `bin/remote server CLIENT` | laptop | Load client config, run `provision/server` |
| `bin/remote github-key CLIENT [--regenerate] [--yes]` | laptop | Load client config, run `provision/github-key` |
| `bin/remote site CLIENT` | laptop | Load client config, run `provision/site` |
| `bin/remote env CLIENT push\|check\|show` | laptop | Sync or validate `.env` from client config |
| `bin/provision/server --tier=...` | VPS (root) | Packages, PHP, deploy user |
| `bin/provision/site --domain=...` | VPS (deploy) | Clone, `.env`, nginx, optional SSL |
| `bin/provision/github-key` | VPS (deploy) | Generate GitHub deploy key (private repos) |
| `bin/provision/env push\|check\|show` | VPS (deploy) | Apply or validate `.env` from client config |

`provision/site` options: `--ssl --cert-email=...`, `--db-name/user/pass` (app), `--deploy` (skip until `env push`).

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
| `bin/checks/web` | `GET /up`, `/`, `/sitemap.xml`, `/health` (if `MONITOR_KEY` set) |
| `bin/checks/php` | PHP 8.3+, required extensions, php-fpm (if systemd) |
| `bin/checks/db` | `php artisan db:show` |
| `bin/checks/mail` | `CONTACT_EMAIL`, SMTP host when `MAIL_MAILER=smtp` |
| `bin/checks/storage` | Writable `storage/`, `bootstrap/cache`, data dir |

Override web check base URL: `CHECK_URL=https://example.com ./bin/checks/web` (defaults to `APP_URL` from `.env`)

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
| First-time server setup | client config → `server` → `github-key` (private) → `site` → `env push` |
| Manage site/mail settings | Edit `bin/provision/clients/CLIENT.conf` → `./bin/remote env CLIENT push` |
| DNS, Cloudflare, and TLS | **Server provisioning** → step 6 |
| Private GitHub deploy key | **Private GitHub repos** |
| Deploy updates | `./bin/deploy` then `./bin/check` |
| Add a Blade page | `resources/views/pages/{slug}.blade.php` |
| Add markdown page | `resources/content/{slug}.md` |
| Nav links | `config/client.php` → `navigation` |
| Site name / SEO basics | `config/client.php` + `.env` (`site_url`, `seo.og_image`, `seo.twitter_handle`) |
| Sitemap | auto at `/sitemap.xml` — exclude slugs in `config/starter.php` → `sitemap.excluded` |
| JSON table data | `storage/app/private/data/{name}.json` |
| Contact form logic | `app/Livewire/ContactForm.php` |
| Email template | `resources/views/mail/contact-form-submitted.blade.php` |
