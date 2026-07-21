#!/usr/bin/env bash
# Migrate critical data from an old domain directory to a new one.
# Run AFTER updating site.conf with the new domain and running task provision:site.
#
# Usage: ./bin/internal/site-migrate-domain.sh <old-domain>
#   e.g. ./bin/internal/site-migrate-domain.sh new.craftjd.com
set -euo pipefail

BIN_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck disable=SC1091
source "${BIN_ROOT}/lib.sh"

OLD_DOMAIN="${1:-}"
if [[ -z "$OLD_DOMAIN" ]]; then
    echo "Usage: $(basename "$0") <old-domain>" >&2
    echo "  e.g. $(basename "$0") new.craftjd.com" >&2
    exit 1
fi

# Load client config for IP and new DOMAIN
CLIENT_CONF="${BIN_ROOT}/internal/provision/clients/site.conf"
if [[ ! -f "$CLIENT_CONF" ]]; then
    CLIENT_CONF="${BIN_ROOT}/internal/provision/clients/${CLIENT:-site}.conf"
fi
[[ -f "$CLIENT_CONF" ]] || fail "missing site.conf"

load_client_config "$(basename "$CLIENT_CONF" .conf)"

NEW_DOMAIN="${DOMAIN:-}"
[[ -n "$NEW_DOMAIN" ]] || fail "DOMAIN is not set in site.conf"
[[ "$OLD_DOMAIN" != "$NEW_DOMAIN" ]] || fail "old and new domain are the same"

OLD_DIR="/var/www/${OLD_DOMAIN}"
NEW_DIR="/var/www/${NEW_DOMAIN}"

echo "==> migrate domain data: ${OLD_DOMAIN} → ${NEW_DOMAIN}"

# Verify both directories exist on the server
echo "  → Checking directories on server..."
if ! ssh "deploy@${IP}" "test -d '${OLD_DIR}/shared' && test -d '${NEW_DIR}/shared'" < /dev/null 2>/dev/null; then
    fail "Both ${OLD_DIR}/shared and ${NEW_DIR}/shared must exist on the server"
fi

ok "found old=${OLD_DIR}  new=${NEW_DIR}"

# Copy database
echo "  → Copying database..."
if ssh "deploy@${IP}" "test -f '${OLD_DIR}/shared/database/database.sqlite'" < /dev/null 2>/dev/null; then
    ssh "deploy@${IP}" "cp '${OLD_DIR}/shared/database/database.sqlite' '${NEW_DIR}/shared/database/'" < /dev/null
    ok "copied database/database.sqlite"
else
    warn "no database.sqlite found at ${OLD_DIR}/shared/database/ — skipping"
fi

# Copy storage
echo "  → Copying storage directory..."
ssh "deploy@${IP}" "cp -a '${OLD_DIR}/shared/storage/.' '${NEW_DIR}/shared/storage/'" < /dev/null
ok "copied storage/"

# Preserve APP_KEY
echo "  → Preserving APP_KEY..."
APP_KEY=$(ssh "deploy@${IP}" "grep '^APP_KEY=' '${OLD_DIR}/shared/.env' | cut -d= -f2-" < /dev/null || true)
if [[ -n "$APP_KEY" ]]; then
    ssh "deploy@${IP}" "sed -i 's|^APP_KEY=.*|APP_KEY=${APP_KEY}|' '${NEW_DIR}/shared/.env'" < /dev/null
    ok "preserved APP_KEY from old .env"
else
    warn "no APP_KEY found in old .env — new .env keeps its auto-generated key"
fi

echo
echo "Domain data migration complete."
echo "  Old: ${OLD_DOMAIN} → ${OLD_DIR}"
echo "  New: ${NEW_DOMAIN} → ${NEW_DIR}"
echo
echo "Next:"
echo "  task deploy           # deploy latest code to new site"
echo "  task site:migrate     # run pending migrations"
