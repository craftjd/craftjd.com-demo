#!/usr/bin/env bash

ok() { echo "  ok  $1"; }
fail() { echo "  FAIL  $1" >&2; exit 1; }
warn() { echo "  warn  $1"; }

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

set_env_var() {
    local key="$1"
    local value="$2"
    local file="${3:-.env}"
    local line="${key}=${value}"

    if [[ "$value" =~ [^a-zA-Z0-9._/@-] ]]; then
        line="${key}=\"${value//\"/\\\"}\""
    fi

    if grep -qE "^${key}=" "$file"; then
        sed -i "s|^${key}=.*|${line}|" "$file"
    else
        echo "$line" >>"$file"
    fi
}

validate_tier() {
    local tier="$1"

    case "$tier" in
        flat-file | app) ;;
        *) fail "unknown tier: ${tier} (use flat-file or app)" ;;
    esac
}

sudo_cmd() {
    if [[ "${EUID}" -eq 0 ]]; then
        env "$@"
    else
        sudo "$@"
    fi
}

# Copy SSH public keys onto the deploy user so "ssh deploy@..." works.
# deploy is created with --disabled-password, so key auth is required.
#
# Why keys were missing on first runs:
#   1. Original script only copied keys inside the "create deploy user" block —
#      re-runs skipped key sync entirely.
#   2. Copy was silent when /root/.ssh/authorized_keys was missing or empty.
#      That happens when root SSH uses a Linode password instead of an injected key.
#   3. Home/.ssh permissions were not verified (sshd rejects bad modes).
sync_deploy_ssh_keys() {
    local deploy_home="/home/deploy"
    local deploy_ssh="${deploy_home}/.ssh"
    local deploy_keys="${deploy_ssh}/authorized_keys"
    local root_keys="/root/.ssh/authorized_keys"
    local tmp
    local key_count=0

    if ! id deploy &>/dev/null; then
        fail "deploy user does not exist"
    fi

    if [[ "${EUID}" -ne 0 ]]; then
        fail "sync_deploy_ssh_keys must run as root"
    fi

    chmod 755 "$deploy_home"
    chown deploy:deploy "$deploy_home"
    install -d -m 700 -o deploy -g deploy "$deploy_ssh"

    tmp="$(mktemp)"
    {
        if [[ -f "$deploy_keys" ]]; then
            grep -v '^[[:space:]]*$' "$deploy_keys" || true
        fi
        if [[ -f "$root_keys" ]] && [[ -s "$root_keys" ]]; then
            grep -v '^[[:space:]]*$' "$root_keys" || true
        fi
    } | sort -u >"$tmp"

    key_count="$(wc -l <"$tmp" | tr -d ' ')"

    if [[ "$key_count" -eq 0 ]]; then
        rm -f "$tmp"
        warn "no SSH public keys found for deploy"
        echo "       checked: ${root_keys}" >&2
        echo "       deploy has no password — key auth only. If you log into root with a" >&2
        echo "       password, add your SSH key in Linode (Rebuild/Resize → SSH Keys) or:" >&2
        echo "       ssh-copy-id -i ~/.ssh/id_ed25519.pub root@SERVER_IP" >&2
        echo "       then re-run provision-server as root." >&2
        return 1
    fi

    install -m 600 -o deploy -g deploy "$tmp" "$deploy_keys"
    rm -f "$tmp"
    ok "deploy SSH keys (${key_count} key(s))"
}

# deploy has no login password, so provisioning scripts need NOPASSWD sudo.
configure_deploy_sudo() {
    local sudoers_file="/etc/sudoers.d/deploy"

    if [[ "${EUID}" -ne 0 ]] || ! id deploy &>/dev/null; then
        return 0
    fi

    echo 'deploy ALL=(ALL) NOPASSWD:ALL' >"$sudoers_file"
    chmod 440 "$sudoers_file"
    visudo -cf "$sudoers_file" >/dev/null || fail "invalid sudoers config for deploy"
    ok "deploy passwordless sudo"
}

require_sudo() {
    if [[ "${EUID}" -eq 0 ]]; then
        return 0
    fi

    if sudo -n true 2>/dev/null; then
        return 0
    fi

    fail "sudo access required — re-run provision-server as root to configure deploy NOPASSWD sudo"
}
