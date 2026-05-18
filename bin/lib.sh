#!/usr/bin/env bash

# Shared by all bin scripts. BIN_ROOT is set by callers in subdirs.
BIN_ROOT="${BIN_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"

ok() { echo "  ok  $1"; }
fail() { echo "  FAIL  $1" >&2; exit 1; }
warn() { echo "  warn  $1"; }

project_root() {
    cd "${BIN_ROOT}/.." && pwd
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

load_client_config() {
    local name="$1"
    local mode="${2:-site}"
    local file="${BIN_ROOT}/provision/clients/${name}.conf"

    [[ -f "$file" ]] || fail "missing client config: ${file} (copy provision/clients/_example.conf)"

    # shellcheck disable=SC1090
    source "$file"

    [[ -n "${IP:-}" && -n "${TIER:-}" ]] \
        || fail "client config must set IP and TIER"

    validate_tier "$TIER"

    case "$mode" in
        server) ;;
        site)
            [[ -n "${DOMAIN:-}" && -n "${REPO:-}" ]] \
                || fail "client config must set DOMAIN and REPO for site provisioning"

            if [[ "${SSL:-}" == true && -z "${CERT_EMAIL:-}" ]]; then
                fail "client config: CERT_EMAIL is required when SSL=true"
            fi

            if [[ "$TIER" == "app" && ( -n "${DB_NAME:-}" || -n "${DB_USER:-}" || -n "${DB_PASS:-}" ) ]]; then
                [[ -n "${DB_NAME:-}" && -n "${DB_USER:-}" && -n "${DB_PASS:-}" ]] \
                    || fail "client config: set all of DB_NAME, DB_USER, DB_PASS for app tier"
            fi
            ;;
        *)
            fail "unknown client config mode: ${mode}"
            ;;
    esac
}

site_remote_extra_args() {
    local args=()

    if [[ "${SSL:-}" == true ]]; then
        args+=(--ssl --cert-email="${CERT_EMAIL}")
    fi

    if [[ -n "${DB_NAME:-}" ]]; then
        args+=(--db-name="${DB_NAME}" --db-user="${DB_USER}" --db-pass="${DB_PASS}")
    fi

    if [[ "${DEPLOY:-}" == true ]]; then
        args+=(--deploy)
    fi

    echo "${args[@]}"
}

GITHUB_DEPLOY_KEY_NAME="${GITHUB_DEPLOY_KEY_NAME:-github_deploy}"

github_deploy_key_path() {
    echo "${HOME}/.ssh/${GITHUB_DEPLOY_KEY_NAME}"
}

# git@github.com:org/repo.git → https://github.com/org/repo.git
github_repo_to_https() {
    local repo="$1"

    if [[ "$repo" =~ ^git@github\.com:([^/]+)/(.+)$ ]]; then
        local org="${BASH_REMATCH[1]}"
        local name="${BASH_REMATCH[2]}"
        name="${name%.git}"
        echo "https://github.com/${org}/${name}.git"
        return 0
    fi

    if [[ "$repo" =~ ^https://github\.com/[^/]+/[^/]+ ]]; then
        if [[ "$repo" != *.git ]]; then
            echo "${repo}.git"
        else
            echo "$repo"
        fi
        return 0
    fi

    return 1
}

github_repo_is_github() {
    local repo="$1"
    [[ "$repo" =~ ^git@github\.com: ]] || [[ "$repo" =~ ^https://github\.com/ ]]
}

# Public GitHub repos allow anonymous read over HTTPS.
github_repo_is_public() {
    local repo="$1"
    local https_url

    https_url="$(github_repo_to_https "$repo" 2>/dev/null)" || return 1

    git ls-remote --exit-code "$https_url" HEAD &>/dev/null
}

github_deploy_key_exists() {
    local key_path
    key_path="$(github_deploy_key_path)"
    [[ -f "${key_path}" && -f "${key_path}.pub" ]]
}

# Exit 0 when the deploy user can read the repo over SSH (deploy key on repo).
github_repo_ssh_accessible() {
    local repo="$1"
    local key_path
    key_path="$(github_deploy_key_path)"

    [[ -f "$key_path" ]] || return 1

    GIT_SSH_COMMAND="ssh -i ${key_path} -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=accept-new" \
        git ls-remote --exit-code "$repo" HEAD &>/dev/null
}

# Use HTTPS for public GitHub repos so SSH deploy keys are not required.
github_repo_clone_url() {
    local repo="$1"

    if github_repo_is_github "$repo" && github_repo_is_public "$repo"; then
        github_repo_to_https "$repo"
        return 0
    fi

    echo "$repo"
}

# Before git clone: fail early for private GitHub repos missing a deploy key setup.
require_github_repo_access() {
    local repo="$1"

    github_repo_is_github "$repo" || return 0

    if github_repo_is_public "$repo"; then
        ok "GitHub repo is public (deploy key optional)"
        return 0
    fi

    if ! github_deploy_key_exists; then
        warn "private GitHub repo requires a deploy key on this server"
        echo "       REPO=${repo}" >&2
        echo "       Run: ./bin/remote github-key CLIENT_NAME" >&2
        echo "       Then add the printed public key to the repo (Settings → Deploy keys, read-only)." >&2
        echo "       See MAINTENANCE.md → Private GitHub repos" >&2
        exit 1
    fi

    if ! github_repo_ssh_accessible "$repo"; then
        warn "deploy key exists but cannot read this private repo"
        echo "       REPO=${repo}" >&2
        echo "       Add ${HOME}/.ssh/${GITHUB_DEPLOY_KEY_NAME}.pub to the repo deploy keys (read-only)," >&2
        echo "       or run ./bin/remote github-key if you have not yet." >&2
        exit 1
    fi

    ok "deploy key can read private GitHub repo"
}
