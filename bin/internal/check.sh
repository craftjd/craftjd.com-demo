#!/usr/bin/env bash
set -uo pipefail

BIN_ROOT="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "${BIN_ROOT}/.." && pwd)"
cd "$ROOT"

CHECKS=(web php db mail storage)
FAILED=0

echo "==> check"

for check in "${CHECKS[@]}"; do
    echo "--- ${check}"
    if "${BIN_ROOT}/checks/${check}"; then
        :
    else
        FAILED=1
    fi
    echo
done

if (( FAILED )); then
    echo "One or more checks failed." >&2
    exit 1
fi

echo "All checks passed."
