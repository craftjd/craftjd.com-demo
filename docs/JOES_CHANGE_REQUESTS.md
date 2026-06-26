# Joe's change request pipeline

Joe's Cafe (`demo.craftjd.com`) is a fictional business site used on the CraftJD main site. Visitors submit change requests on the main site; you fulfill them on Joe's and notify the main site when done.

All code uses the **`Joes`** prefix (`JoesChangeRequest`, `JoesChangeFulfillment`, `config/joes.php`, etc.). There are no `Demo` or `Sample` types in this pipeline.

---

## Shared configuration

Both sites must use the **same webhook secret**:

| Main site (craftjd) | Joe's Cafe (demo repo) |
|---------------------|------------------------|
| `JOES_SITE_URL` | — (Joe's own `APP_URL`) |
| `JOES_WEBHOOK_SECRET` | `JOES_WEBHOOK_SECRET` |
| — | `MAIN_SITE_WEBHOOK_URL` → `https://craftjd.com/api/joes/requests` |

---

## End-to-end flow

### 1. Visitor submits a request (main site)

- Form: Livewire `JoesChangeRequestForm` on the homepage.
- Creates a row in `joes_change_requests`.
- Sends **`JoesChangeRequestSubmitted`** email to `CONTACT_EMAIL` with:
  - request body and requester email
  - **claim link** (signed URL on Joe's — no SSH required)

### 2. You start work (Joe's — claim)

**Preferred:** click the claim link in the email:

```
GET {JOES_SITE_URL}/joes/requests/{id}/claim?expires=...&signature=...
```

The link is HMAC-signed with `JOES_WEBHOOK_SECRET` and expires after 7 days (`JOES_CLAIM_LINK_TTL_DAYS` on the main site).

**Alternative:** `php artisan joes:claim {id}` on the Joe's server.

Claim creates (or reuses) a pending row in `joes_change_fulfillments` with:

- `main_request_id`
- `claimed_at`
- `claim_deploy_git_sha` — git SHA at claim time

Multiple requests can be claimed at once (e.g. #42 and #43 both pending).

### 3. You edit and deploy (Joe's)

1. Make changes on Joe's.
2. Deploy with the request ID tagged:

```bash
REQUEST_ID=42 ./bin/deploy
```

`./bin/deploy`:

- runs a normal deploy (pull, composer, npm, migrate, cache)
- still writes `storage/app/deploy-meta.json` for the public changelog / health check
- calls `php artisan joes:record-deploy 42 {git_sha} {deployed_at}`

Deploy metadata for **fulfillment** is stored on the **claim row**, not read from `deploy-meta.json`:

- `deploy_git_sha`
- `deploy_deployed_at`

### 4. You finalize (Joe's)

After verifying the live site:

```bash
./bin/fulfill 42 "Updated summer hours on the contact section."
```

This runs `joes:fulfill`, which validates the claim row and POSTs to the main site webhook.

### 5. Main site completes the request

`POST /api/joes/requests/{id}/fulfilled` with `Authorization: Bearer {JOES_WEBHOOK_SECRET}` and JSON `{ "summary": "..." }`.

The main site:

- verifies the token
- skips if already completed (idempotent)
- requires a summary
- saves `fulfillment_summary` and `completed_at`
- sends **`JoesChangeRequestFulfilled`** email (reply-to set to the requester)

You reply to that email to notify the visitor.

---

## Fulfillment rules (Joe's)

For request `{id}`, Joe's loads the **pending** `joes_change_fulfillments` row for that `main_request_id`.

| Rule | Why |
|------|-----|
| Pending claim exists | You must claim before deploy/finalize |
| `deploy_git_sha` and `deploy_deployed_at` set on the row | Deploy must be recorded with `REQUEST_ID=` |
| `deploy_deployed_at` > `claimed_at` | Deploy happened after claim |
| `deploy_git_sha` ≠ `claim_deploy_git_sha` | Something actually changed |
| `deploy_git_sha` is a **git descendant** of `claim_deploy_git_sha` | Rollback deploys cannot satisfy the claim |

### Rollback scenario

1. Site at commit **B**. You claim request #42 → `claim_deploy_git_sha = B`.
2. You roll back production to commit **A** (older) and run `REQUEST_ID=42 ./bin/deploy`.
3. **Rejected:** A is not a descendant of B. You must either:
   - claim again after rollback (new claim SHA = A), make new commits, deploy; or
   - deploy forward commits on top of B without rolling back.

If you roll back **before** claiming, claim captures the rolled-back SHA and normal forward deploys work.

---

## Local testing

**Main site** — simulate Joe's webhook:

```bash
./bin/fulfill-request 1 "Updated summer hours."
```

**Joe's** — run feature tests:

```bash
php artisan test --filter=JoesFulfillment
```

---

## Key files

### Main site (`craftjd`)

| File | Role |
|------|------|
| `app/Models/JoesChangeRequest.php` | Request storage |
| `app/Services/JoesChangeRequestService.php` | Fulfill + claim URL signing |
| `app/Http/Controllers/Api/FulfillJoesChangeRequestController.php` | Webhook receiver |
| `app/Livewire/JoesChangeRequestForm.php` | Public form |
| `config/joes.php` | URLs, secret, public copy |

### Joe's Cafe (`demo` repo)

| File | Role |
|------|------|
| `app/Models/JoesChangeFulfillment.php` | Claim + deploy meta per request |
| `app/Services/JoesFulfillmentService.php` | Claim, record deploy, fulfill |
| `app/Http/Controllers/ClaimJoesChangeRequestController.php` | Claim link handler |
| `bin/deploy` | Deploy + `joes:record-deploy` |
| `bin/fulfill` | Finalize + webhook |

---

## Database

**Main site:** `joes_change_requests` (renamed from `demo_change_requests`)

**Joe's:** `joes_change_fulfillments` (renamed from `sample_change_fulfillments`) with columns:

- `main_request_id`, `claimed_at`, `claim_deploy_git_sha`
- `deploy_git_sha`, `deploy_deployed_at` — set by deploy
- `fulfilled_at`, `fulfill_deploy_git_sha`, `summary` — set by fulfill
