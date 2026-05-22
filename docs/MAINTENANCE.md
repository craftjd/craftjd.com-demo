# Maintenance

Solo-maintainer runbook. Ubuntu 24.04 VPS, nginx, PHP 8.3, deploy via `git pull` + `bin/*`.

---

## New client — full checklist

Work **top to bottom**. `CLIENT` = config basename (`bin/provision/clients/CLIENT.conf`, e.g. `acme`).

### 1. GitHub repo

Create a new **private** repo for the client (empty, no README).

### 2. Project on WSL

```bash
mkdir -p ~/projects/CLIENT
cd ~/projects/CLIENT
git clone git@github.com:craftjd/laravel-starter.git .
git remote set-url origin git@github.com:ORG/CLIENT-REPO.git
git push -u origin main
```

Replace `ORG/CLIENT-REPO` with the client repo. All later commands run from this directory.

### 3. Customize the site

Edit and commit as needed:

- `config/client.php` — name, nav, SEO
- `resources/content/*.md` or `resources/views/pages/*.blade.php` — pages
- `storage/app/private/data/*.json` — table data (brochure tier)

Push to the client repo when ready.

### 4. Client config

```bash
cp bin/provision/clients/_example.conf bin/provision/clients/CLIENT.conf
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

### 5. DNS

Point **both** records at the VPS IP before `SSL=true`:

| Name | Type | Value |
|------|------|-------|
| `@` | A | `IP` |
| `www` | A or CNAME | `IP` or `@` |

If using Cloudflare (proxied): set SSL/TLS to **Flexible** until Certbot succeeds, then **Full (strict)**.

### 6. Provision server (new VPS only)

```bash
./bin/remote server CLIENT
```

Installs nginx, PHP 8.3, Composer, Node, Certbot, `deploy` user. Idempotent — safe to re-run.

**App tier** adds MySQL and Redis. **Skip this step** when adding another site to an existing VPS.

### 7. GitHub deploy key (private repos only)

```bash
./bin/remote github-key CLIENT
```

Add the printed public key to the client repo: **Settings → Deploy keys** (read-only). Same key can serve multiple repos on one VPS.

Skip if the repo is public.

### 8. Provision site

```bash
./bin/remote site CLIENT
```

Clones to `/var/www/DOMAIN`, seeds `.env`, nginx vhost, optional Certbot (`SSL=true` covers `@` and `www`). Note the printed `MONITOR_KEY`.

**Second site on same VPS:** new `CLIENT.conf` (same `IP`, new `DOMAIN`/`REPO`), skip steps 6–7, run step 8 only.

### 9. Push env to server

```bash
./bin/remote env CLIENT push
```

Syncs client config → server `.env`. Shows a diff and asks `[Y/n]` before applying. Refreshes config cache automatically.

```bash
./bin/remote env CLIENT check    # validate
./bin/remote env CLIENT show     # redacted .env on server
```

### 10. Deploy and verify (on server)

```bash
ssh deploy@IP
cd /var/www/DOMAIN
./bin/deploy
sudo systemctl reload php8.3-fpm
./bin/check
```

Contact form works with `MAIL_MAILER=log` (submissions in `storage/logs/laravel.log`).

### 11. Go-live

1. Set `MAIL_MAILER=smtp` and SMTP credentials in `CLIENT.conf`
2. `./bin/remote env CLIENT push`
3. On server: `./bin/deploy && ./bin/check`
4. Submit `/contact` — confirm delivery to `CONTACT_EMAIL`
5. Cloudflare → **Full (strict)** if proxied

---

## Ongoing

### Deploy updates (server)

```bash
cd /var/www/DOMAIN
./bin/deploy
sudo systemctl reload php8.3-fpm
./bin/check
```

`bin/deploy` pulls `main`, runs composer, npm build, migrate, caches config. It does **not** edit `.env`.

### Change site or mail settings (laptop)

```bash
# edit bin/provision/clients/CLIENT.conf
./bin/remote env CLIENT push
# on server: ./bin/check
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
pages/{slug}.blade.php   → hand-built (wins)
content/{slug}.md        → markdown fallback
storage/app/private/data/{name}.json
config/client.php        → nav, SEO, site name
app/Starter/             → platform (merge from starter)
```

Contact form: `pages/contact` → `Livewire/ContactForm` → mail to `CONTACT_EMAIL`.

### Tiers

| Tier | VPS (approx.) | Stack |
|------|---------------|-------|
| `flat-file` | 1 GB | SQLite, file cache/session |
| `app` | 2 GB+ | MySQL, Redis |

### Mail — local dev

Mailhog via Sail — see `compose.yaml` + `.env` with `MAIL_HOST=mailhog`. Without Docker, `MAIL_MAILER=log` is fine.

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
- `./bin/remote github-key CLIENT` once per VPS
- Add deploy key to each private repo (read-only)
- Verify on server: `git ls-remote git@github.com:ORG/repo.git HEAD`
- Rotate: `./bin/remote github-key CLIENT --regenerate`

| Problem | Fix |
|---------|-----|
| `Permission denied (publickey)` | Deploy key missing on repo |
| HTTPS clone prompt | Use SSH URL |
| `git pull` fails after rotate | Remove old deploy key in GitHub, add new pubkey |

### DNS and TLS notes

- Certbot needs `@` and `www` A records first; re-run `./bin/remote site CLIENT` if it failed earlier
- Error 521 (Cloudflare): origin has no cert yet — use **Flexible** until Certbot runs
- `APP_URL` is set from `DOMAIN` + `SSL` via env push — no manual edit

### Scripts

```
bin/remote server|github-key|site|env CLIENT   # laptop → VPS
bin/deploy, bin/check                            # on server
bin/provision/clients/CLIENT.conf                # source of truth (gitignored)
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

### bin/check

| Check | Verifies |
|-------|----------|
| `checks/web` | `/up`, `/`, `/sitemap.xml`, `/health` |
| `checks/php` | PHP 8.3+, extensions, php-fpm |
| `checks/db` | Database connection |
| `checks/mail` | `CONTACT_EMAIL`; warns if `MAIL_MAILER=log` |
| `checks/storage` | Writable storage paths |
