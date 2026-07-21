#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
# shellcheck disable=SC1091
source "${ROOT}/bin/lib.sh"

cd "$ROOT"

require_artisan_runner

usage() {
    echo "Usage:" >&2
    echo "  ./bin/internal/fulfill.sh PUBLIC_ID \"summary of changes\"" >&2
    echo "  task claim:fulfill PUBLIC_ID=… SUMMARY=\"…\"" >&2
    echo >&2
    echo "Workflow (run from the Joe's Cafe repo):" >&2
    echo "  1. Open the claim link from the main-site email (or: php artisan joes:claim PUBLIC_ID)" >&2
    echo "  2. Edit Joe's Cafe, then task claim:deploy-local PUBLIC_ID=… (local)" >&2
    echo "     or REQUEST_PUBLIC_ID=… ./bin/internal/deploy.sh (on Joe's server)" >&2
    echo "  3. Review the preview and choose Fulfill, Abandon, or Keep pending" >&2
    exit 1
}

PUBLIC_ID="${1:-}"
SUMMARY="${2:-}"

if [[ -z "$PUBLIC_ID" || -z "$SUMMARY" ]]; then
    usage
fi

echo "==> fulfill ${PUBLIC_ID}"
if use_sail_artisan; then
    step "via Sail — fetch preview, confirm, notify main site"
else
    step "fetch request preview from main site, confirm, then notify main site"
fi

# Run directly — do not capture output in $(). Command substitution breaks the interactive prompt.
artisan joes:fulfill "$PUBLIC_ID" "$SUMMARY"
