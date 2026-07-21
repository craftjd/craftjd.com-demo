#!/usr/bin/env bash
# Run Deployer with the Git Bash ssh-agent loaded (via bin/lib.sh).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
# shellcheck disable=SC1091
source "${ROOT}/bin/lib.sh"

exec "${ROOT}/vendor/bin/dep" "$@"
