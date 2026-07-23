#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
# shellcheck disable=SC1091
source "${ROOT}/bin/lib.sh"

cd "$ROOT"

require_artisan_runner

usage() {
    echo "Usage:" >&2
    echo "  JOES_ENV=prod ./bin/internal/abandon.sh PUBLIC_ID" >&2
    echo "  task claim:abandon PUBLIC_ID=…       # PRODUCTION main site" >&2
    echo "  task dev:claim:abandon PUBLIC_ID=…   # local craftjd.test only" >&2
    echo >&2
    echo "Releases the claim on the main site and deletes Joe's pending row." >&2
    exit 1
}

PUBLIC_ID="${1:-}"

if [[ -z "$PUBLIC_ID" ]]; then
    usage
fi

require_joes_claim_env
announce_joes_claim_env
export_joes_claim_main_site_env

echo "==> abandon ${PUBLIC_ID}"
step "release claim on main site and remove Joe's pending row"

artisan config:clear >/dev/null

# Run directly so the confirmation prompt stays attached to the terminal.
artisan joes:abandon "$PUBLIC_ID"
