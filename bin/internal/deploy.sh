#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

# shellcheck disable=SC1091
source "$ROOT/bin/lib.sh"

echo "==> deploy (production server)"
echo ""

require_command git
require_command php
require_command composer
require_command npm

BRANCH="${DEPLOY_BRANCH:-main}"

step "pull latest ${BRANCH}"
ok "pulling ${BRANCH}"
git pull origin "$BRANCH"

step "install PHP dependencies"
ok "composer install"
composer install --no-dev --optimize-autoloader --no-interaction

step "build frontend assets"
ok "npm build"
if [[ -f package-lock.json ]]; then
    npm ci
else
    warn "no package-lock.json — run npm install (commit the lockfile for reproducible deploys)"
    npm install
fi
npm run build

step "run database migrations"
ok "artisan migrate"
php artisan migrate --force

step "refresh Laravel caches"
ok "artisan cache"
php artisan config:cache
php artisan route:cache
php artisan view:cache

mkdir -p storage/app

REQUEST_PUBLIC_ID="${REQUEST_PUBLIC_ID:-}"
REQUEST_NUMBER="${REQUEST_NUMBER:-}"

step "capture git metadata for this deploy"
GIT_SHA="$(git rev-parse HEAD)"
GIT_COMMIT_MESSAGE="$(git log -1 --format=%B HEAD)"
DEPLOYED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

step "write storage/app/deploy-meta.json (public changelog + /health — not used for fulfillment)"
export GIT_SHA GIT_COMMIT_MESSAGE DEPLOYED_AT REQUEST_ID="${REQUEST_NUMBER}"
write_deploy_meta_json storage/app/deploy-meta.json

if [[ -n "$REQUEST_PUBLIC_ID" ]]; then
    step "record deploy on claim ${REQUEST_PUBLIC_ID} (fulfillment validates this row in the DB)"
    if ! RECORD_OUTPUT="$(php artisan joes:record-deploy "$REQUEST_PUBLIC_ID" "$GIT_SHA" "$DEPLOYED_AT" 2>&1)"; then
        echo "$RECORD_OUTPUT" >&2
        explain_joes_git_failure "$RECORD_OUTPUT"
        exit 1
    fi
    echo "$RECORD_OUTPUT"
    ok "deploy complete for ${REQUEST_PUBLIC_ID} (${GIT_SHA:0:7})"
else
    ok "deploy meta written (${GIT_SHA:0:7})"
fi

echo ""
echo "Deploy complete. Run: ./bin/internal/check.sh"
if [[ -n "$REQUEST_PUBLIC_ID" ]]; then
    echo "Next: task claim:fulfill PUBLIC_ID=${REQUEST_PUBLIC_ID} SUMMARY=\"summary of changes\""
    echo "  or: ./bin/internal/fulfill.sh ${REQUEST_PUBLIC_ID} \"summary of changes\""
fi
