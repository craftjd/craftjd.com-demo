#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
# shellcheck disable=SC1091
source "${ROOT}/bin/lib.sh"

cd "$ROOT"

require_artisan_runner

usage() {
    echo "Usage:" >&2
    echo "  JOES_ENV=prod ./bin/internal/fulfill.sh PUBLIC_ID \"summary of changes\"" >&2
    echo "  task claim:fulfill PUBLIC_ID=… SUMMARY=\"…\"       # PRODUCTION main site" >&2
    echo "  task dev:claim:fulfill PUBLIC_ID=… SUMMARY=\"…\"   # local craftjd.test only" >&2
    echo >&2
    echo "Workflow:" >&2
    echo "  Production: claim on joes.craftjd.com → deploy.sh on server → claim:fulfill" >&2
    echo "  Local:      claim on joes.test → dev:claim:deploy → dev:claim:fulfill" >&2
    exit 1
}

PUBLIC_ID="${1:-}"
SUMMARY="${2:-}"

if [[ -z "$PUBLIC_ID" || -z "$SUMMARY" ]]; then
    usage
fi

require_joes_claim_env
announce_joes_claim_env
export_joes_claim_main_site_env

echo "==> fulfill ${PUBLIC_ID}"
if use_sail_artisan; then
    step "via Sail — fetch preview, confirm, notify main site"
else
    step "fetch request preview from main site, confirm, then notify main site"
fi

# Config is read at boot; clear so MAIN_SITE_WEBHOOK_URL export is honored.
artisan config:clear >/dev/null

# Run directly — do not capture output in $(). Command substitution breaks the interactive prompt.
artisan joes:fulfill "$PUBLIC_ID" "$SUMMARY"
