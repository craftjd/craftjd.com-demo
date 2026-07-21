#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
# shellcheck disable=SC1091
source "${ROOT}/bin/lib.sh"

cd "$ROOT"

require_artisan_runner

usage() {
    echo "Usage:" >&2
    echo "  ./bin/internal/abandon.sh PUBLIC_ID" >&2
    echo "  task claim:abandon PUBLIC_ID=…" >&2
    echo >&2
    echo "Releases the claim on the main site and deletes Joe's pending row." >&2
    echo "Use when you claimed the wrong request or want to start over." >&2
    echo "Run from the Joe's Cafe repo (not craftjd)." >&2
    exit 1
}

PUBLIC_ID="${1:-}"

if [[ -z "$PUBLIC_ID" ]]; then
    usage
fi

echo "==> abandon ${PUBLIC_ID}"
step "release claim on main site and remove Joe's pending row"

# Run directly so the confirmation prompt stays attached to the terminal.
artisan joes:abandon "$PUBLIC_ID"
