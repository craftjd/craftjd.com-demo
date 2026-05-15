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

## Deploy (production)

SSH to the server, then from the app root:

```bash
./bin/deploy
./bin/check
```

### `bin/deploy`

1. `git pull` (branch `main`, override with `DEPLOY_BRANCH=...`)
2. `composer install --no-dev`
3. `npm ci && npm run build`
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

Override web check base URL: `CHECK_URL=https://example.com ./bin/check-web`

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
| Add a Blade page | `resources/views/pages/{slug}.blade.php` |
| Add markdown page | `resources/content/{slug}.md` |
| Nav links | `config/client.php` → `navigation` |
| Site name / SEO basics | `config/client.php` + `.env` (`site_url`, `seo.og_image`, `seo.twitter_handle`) |
| Sitemap | auto at `/sitemap.xml` — exclude slugs in `config/starter.php` → `sitemap.excluded` |
| JSON table data | `storage/app/private/data/{name}.json` |
| Contact form logic | `app/Livewire/ContactForm.php` |
| Email template | `resources/views/mail/contact-form-submitted.blade.php` |
