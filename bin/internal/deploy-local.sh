#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

# shellcheck disable=SC1091
source "$ROOT/bin/lib.sh"

echo "==> deploy-local (LOCAL DEV ONLY)"
echo "    Skips pull, build, migrate, and cache — records deploy metadata only."
if use_sail_artisan; then
    echo "    Using Sail for artisan (vendor/bin/sail detected)."
fi
echo ""

require_command git
require_artisan_runner

REQUEST_PUBLIC_ID="${REQUEST_PUBLIC_ID:-}"

if [[ -z "$REQUEST_PUBLIC_ID" ]]; then
    fail "REQUEST_PUBLIC_ID is required. Example: task dev:claim:deploy PUBLIC_ID=…"
fi

step "read git HEAD (stand-in deploy SHA for this run)"
GIT_SHA="$(git rev-parse HEAD)"
GIT_COMMIT_MESSAGE="$(git log -1 --format=%B HEAD)"
DEPLOYED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

step "write storage/app/deploy-meta.json (public changelog + /health — not used for fulfillment)"
mkdir -p storage/app
export GIT_SHA GIT_COMMIT_MESSAGE DEPLOYED_AT REQUEST_ID="${REQUEST_NUMBER:-}"
write_deploy_meta_json storage/app/deploy-meta.json
ok "deploy-meta.json updated (${GIT_SHA:0:7})"

step "record deploy on claim row ${REQUEST_PUBLIC_ID} (fulfillment uses the DB row, not deploy-meta.json)"
if ! RECORD_OUTPUT="$(artisan joes:record-deploy "$REQUEST_PUBLIC_ID" "$GIT_SHA" "$DEPLOYED_AT" --local 2>&1)"; then
    echo "$RECORD_OUTPUT" >&2
    explain_joes_git_failure "$RECORD_OUTPUT"
    exit 1
fi

echo "$RECORD_OUTPUT"
ok "claim row updated (${GIT_SHA:0:7}, local git bypass)"

echo ""
echo "Deploy-local complete."
echo "Next: task dev:claim:fulfill PUBLIC_ID=${REQUEST_PUBLIC_ID} SUMMARY=\"summary of changes\""
echo "  or: task claim:fulfill … if the main site target is production"
