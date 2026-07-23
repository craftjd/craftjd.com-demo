# Joe's change request pipeline

Joe's Cafe (`demo.craftjd.com`) is a fictional business site used on the CraftJD main site. Visitors submit change requests on the main site; you fulfill them on Joe's and notify the main site when done.

Cross-site identity uses a **`public_id` UUID** on each main-site request. Display numbers (`#42`) are for humans only — never used as the lookup key on Joe's.

Joe's stores **no client PII** (name, email, request body). That data is fetched from the main site at fulfill/abandon time and shown in the terminal only.

### Where to run Task commands

| You are in… | Namespace | Environment | Purpose |
|-------------|-----------|-------------|---------|
| **Joe's Cafe** (`joes`) | `claim:*` | **Production main site** | Fulfill/abandon → `www.craftjd.com` (`site.conf`) |
| **Joe's Cafe** (`joes`) | `dev:claim:*` | **Local only** | Deploy-record / fulfill / abandon / PHPUnit against local craftjd |
| **Main site** (`craftjd`) | `hub:*` | **Production only** | SSH / webhooks against www |
| **Main site** (`craftjd`) | `dev:hub:*` | **Local only** | Pipeline check + webhooks against `.env` `APP_URL` |

Laravel artisan commands stay `joes:*` on both apps (e.g. `php artisan joes:fulfill`). Do not confuse those with Task namespaces.

**Rule:** `claim:*` / `hub:*` never target local `.test` hosts. Local work is always under `dev:`.

**Before outreach (from craftjd):** `task hub:smoke CONFIRM=true` — see craftjd `docs/JOES_PROD_SMOKE.md`.

---

## Shared configuration

Both sites must use the **same webhook secret**:

| Main site (craftjd) | Joe's Cafe (demo repo) |
|---------------------|------------------------|
| `JOES_SITE_URL` | — (Joe's own `APP_URL`) |
| `JOES_WEBHOOK_SECRET` | `JOES_WEBHOOK_SECRET` |
| — | `MAIN_SITE_WEBHOOK_URL` → `https://www.craftjd.com/api/joes/requests` |
| — | `JOES_LOCAL_DEPLOY_BYPASS_GIT=true` (local `.env` only; `dev:claim:*` forces this) |

---

## Main site API (craftjd must implement)

Base: `{MAIN_SITE_WEBHOOK_URL}` → `/api/joes/requests`

All endpoints require `Authorization: Bearer {JOES_WEBHOOK_SECRET}`.

### `GET /api/joes/requests/{public_id}`

Returns request preview for terminal confirmation. **Not stored on Joe's.**

```json
{
  "public_id": "01920e1a-7b3c-7d1a-9f0a-123456789abc",
  "number": 42,
  "client_name": "Alex Guest",
  "client_email": "alex@example.com",
  "request": "Please update summer hours.",
  "submitted_at": "2026-06-19T14:30:00Z",
  "completed_at": null,
  "fulfillment_summary": null
}
```

When `completed_at` is set, Joe's must not start a new claim — the main site is the source of truth for completion.

### `POST /api/joes/requests/{public_id}/fulfilled`

Body: `{ "summary": "Updated summer hours on the contact section." }`

Marks the request complete and emails the requester.

### `POST /api/joes/requests/{public_id}/abandon`

No body. Releases the claim on the main site so the request can be claimed again or left open.

---

## End-to-end flow

### 1. Visitor submits a request (main site)

- Creates `joes_change_requests` row with **`public_id` (UUID)** and display `number`.
- Email includes a signed claim link on Joe's.

### 2. You start work (Joe's — claim)

**Preferred:** click the claim link:

```
GET {JOES_SITE_URL}/joes/requests/{public_id}/claim?number=42&submitted_at=1718798400&expires=...&signature=...
```

Signature payload: `{public_id}|{submitted_at}|{expires}` (HMAC-SHA256 with `JOES_WEBHOOK_SECRET`).

If there is no pending claim on Joe's, the claim route fetches `GET /api/joes/requests/{public_id}` from the main site. When `completed_at` is set, it shows an "already fulfilled" page instead of inserting a local row.

**Alternative:** `php artisan joes:claim {public_id}` — same main-site preview check, then creates the local claim row.

Joe's stores only:

- `main_request_public_id` (unique)
- `main_request_id` (display number)
- `main_request_submitted_at`
- `claimed_at`, `claim_deploy_git_sha`
- deploy + fulfill metadata

### 3. You edit and deploy (Joe's Cafe repo)

On the Joe's **server** (after code is already released, or for legacy in-place deploy):

```bash
REQUEST_PUBLIC_ID=01920e1a-… ./bin/internal/deploy.sh
```

Local parallel testing (app already running):

```powershell
task dev:claim:deploy PUBLIC_ID=01920e1a-…
```

Optional `REQUEST_NUMBER=42` sets the display number in `deploy-meta.json` changelog only.

Fulfillment validates the **DB claim row**, not `deploy-meta.json` (footer changelog + `/health` only).

### 4. You finalize (Joe's Cafe repo)

**Production** (posts to www.craftjd.com):

```powershell
task claim:fulfill PUBLIC_ID={public_id} SUMMARY="Updated summer hours on the contact section."
```

**Local only** (posts to craftjd.test):

```powershell
task dev:claim:fulfill PUBLIC_ID={public_id} SUMMARY="Updated summer hours on the contact section."
```

1. Fetches request preview from main site (name, email, body, date)
2. Menu: **Fulfill** / **Abandon** / **Keep pending** (default — Enter does not abandon)
3. **Fulfill** → POST fulfilled webhook, mark Joe's row complete
4. **Abandon** → release on both sites
5. **Keep pending** → no changes

Skip menu: `php artisan joes:fulfill {public_id} "summary" --yes`

### 5. Abandon (wrong claim or changed mind)

**Production:**

```powershell
task claim:abandon PUBLIC_ID={public_id}
```

**Local only:**

```powershell
task dev:claim:abandon PUBLIC_ID={public_id}
```

Shows preview, confirms, then:

- `POST /api/joes/requests/{public_id}/abandon` on main site
- deletes Joe's pending claim row

Check claims on the Joe's server (no client PII):

```bash
php artisan joes:status
php artisan joes:status --pending
```

In production, run this on Joe's and `php artisan joes:status` on the main site separately — then match rows by `#` and `public_id`.

**step** values: `claimed` (edit + deploy next) → `deployed` (run `task claim:fulfill` or `task dev:claim:fulfill`) → `done`.

---

## Fulfillment rules (Joe's)

Lookup is always by **`main_request_public_id`**.

| Rule | Why |
|------|-----|
| Pending claim exists | Must claim before deploy/finalize |
| `deploy_git_sha` and `deploy_deployed_at` on row | Recorded via deploy script |
| `deploy_deployed_at` > `claimed_at` | Deploy after claim |
| Git rules (production) | Ensures real code shipped |
| Local bypass | `JOES_LOCAL_DEPLOY_BYPASS_GIT=true` or `--local` on record-deploy |

---

## Local testing (main site + Joe's in parallel)

```bash
# .env on Joe's (optional — dev:claim:* forces local URL + git bypass)
JOES_LOCAL_DEPLOY_BYPASS_GIT=true
MAIN_SITE_WEBHOOK_URL=http://craftjd.test/api/joes/requests

# When bin/* runs via Sail, localhost is rewritten to host.docker.internal so the
# container can reach the main site on your host (craftjd on port 80, Joe's on 8080).
# Production / PHP-only servers use host php (Sail not in vendor with --no-dev).
# Force host php locally: JOES_BIN_PHP=1 ./bin/internal/fulfill.sh …

# From the Joe's Cafe repo:
# 1. Submit on local main site, open claim link (or: php artisan joes:claim {public_id})
# 2. Edit files
task dev:claim:deploy PUBLIC_ID={public_id}
# 3. Real webhook with confirmation menu (LOCAL main site only)
task dev:claim:fulfill PUBLIC_ID={public_id} SUMMARY="Updated summer hours."
```

---

## Key files (Joe's demo repo)

| File | Role |
|------|------|
| `app/Data/JoesChangeRequestPreview.php` | Transient preview DTO (never persisted) |
| `app/Services/MainSiteJoesClient.php` | GET preview, POST fulfill/abandon |
| `app/Services/JoesFulfillmentService.php` | Claim, record deploy, fulfill, abandon |
| `app/Console/Commands/FulfillJoesChangeRequest.php` | Preview + confirm + fulfill/abandon |
| `app/Console/Commands/JoesFulfillmentStatus.php` | `php artisan joes:status` |
| `app/Console/Commands/AbandonJoesChangeRequest.php` | Explicit abandon |
| `bin/internal/deploy-local.sh` | Local deploy metadata only (`task dev:claim:deploy`) |
| `bin/internal/fulfill.sh` | Interactive finalize (`task claim:fulfill` / `dev:claim:fulfill`) |
| `bin/internal/abandon.sh` | Release claim on both sites (`task claim:abandon` / `dev:claim:abandon`) |
| `bin/internal/deploy.sh` | On-server deploy + optional claim recording |

---

## Database

**Main site:** `joes_change_requests` with `public_id` (UUID, unique), `number`, client fields, status.

**Joe's:** `joes_change_fulfillments` — no client PII; unique `main_request_public_id`.
