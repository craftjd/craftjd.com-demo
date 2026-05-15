#!/usr/bin/env bash

ok() { echo "  ok  $1"; }
fail() { echo "  FAIL  $1" >&2; exit 1; }

project_root() {
    cd "$(dirname "${BASH_SOURCE[1]}")/.." && pwd
}

load_env_var() {
    local key="$1"
    local file="${2:-.env}"

    if [[ -f "$file" ]]; then
        grep -E "^${key}=" "$file" | tail -n1 | cut -d= -f2- | tr -d '"' | tr -d "'"
    fi
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "missing command: $1"
}
