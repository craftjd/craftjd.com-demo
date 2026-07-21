#!/usr/bin/env bash

# Shared by all bin scripts. BIN_ROOT is set by callers in subdirs.
BIN_ROOT="${BIN_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"

ok() { echo "  ok  $1"; }
fail() { echo "  FAIL  $1" >&2; exit 1; }
warn() { echo "  warn  $1"; }
step() { echo "  →  $1"; }

# Local Sail: bin scripts use ./vendor/bin/sail artisan when vendor/bin/sail exists.
# Production / plain PHP dev: uses host php artisan (Sail not in vendor with --no-dev).
# Override: JOES_BIN_PHP=1 ./bin/internal/fulfill.sh …  forces host php even when Sail is present.
use_sail_artisan() {
    if [[ "${JOES_BIN_PHP:-}" == "1" ]]; then
        return 1
    fi

    local root
    root="$(project_root)"
    [[ -x "${root}/vendor/bin/sail" ]]
}

artisan() {
    local root
    root="$(project_root)"

    if use_sail_artisan; then
        (cd "$root" && ./vendor/bin/sail artisan "$@")
    else
        (cd "$root" && php artisan "$@")
    fi
}

run_php() {
    local root
    root="$(project_root)"

    if use_sail_artisan; then
        (cd "$root" && ./vendor/bin/sail php "$@")
    else
        php "$@"
    fi
}

require_artisan_runner() {
    if use_sail_artisan; then
        require_command docker
    else
        require_command php
    fi
}

# Short hints when Joe's deploy/fulfill fails git validation.
explain_joes_git_failure() {
    local msg="$1"

    if [[ "$msg" == *"matches the SHA recorded at claim"* ]] \
        || [[ "$msg" == *"matches the git SHA from claim"* ]]; then
        echo "  hint: deploy SHA equals the SHA captured at claim time." >&2
        echo "        Production: commit your edits, then REQUEST_PUBLIC_ID=… ./bin/internal/deploy.sh" >&2
        echo "        Local:        task claim:deploy-local PUBLIC_ID=… (no new commit required)" >&2
    fi

    if [[ "$msg" == *"not ahead of the claim SHA"* ]] \
        || [[ "$msg" == *"Rollback deploys"* ]]; then
        echo "  hint: deploy SHA is older than (or not descended from) the claim SHA." >&2
        echo "        Re-open the claim link after a rollback, then deploy forward commits." >&2
        echo "        Local: task claim:deploy-local PUBLIC_ID=… with JOES_LOCAL_DEPLOY_BYPASS_GIT=true in .env" >&2
    fi

    if [[ "$msg" == *"No deploy recorded for this claim"* ]]; then
        echo "  hint: run REQUEST_PUBLIC_ID=… ./bin/internal/deploy.sh (production) or task claim:deploy-local (local) before fulfill." >&2
    fi

    if [[ "$msg" == *"No pending claim"* ]]; then
        echo "  hint: open the signed claim link, or: php artisan joes:claim {public-id} (bin/* uses Sail when vendor/bin/sail exists)" >&2
    fi
}

project_root() {
    cd "${BIN_ROOT}/.." && pwd
}

load_env_var() {
    local key="$1"
    local file="${2:-.env}"

    if [[ -f "$file" ]]; then
        # grep exit 1 when key is missing — must not abort callers under set -o pipefail.
        grep -E "^${key}=" "$file" 2>/dev/null | tail -n1 | cut -d= -f2- | tr -d '"' | tr -d "'" || true
    fi
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "missing command: $1"
}

# Git Bash stores a passphrase-unlocked agent in ~/.ssh/environment (see ~/.bashrc).
# PowerShell `task` → non-interactive bash skips .bashrc; load it here for SSH tasks.
load_ssh_agent() {
    local env_file="${HOME}/.ssh/environment"
    if [[ -f "$env_file" ]]; then
        # shellcheck disable=SC1090
        source "$env_file"
    fi
}

load_ssh_agent

# Write storage/app/deploy-meta.json with safe JSON encoding.
# Expects env: GIT_SHA, DEPLOYED_AT, GIT_COMMIT_MESSAGE; optional REQUEST_ID, DEPLOY_SUMMARY.
write_deploy_meta_json() {
    local path="${1:-storage/app/deploy-meta.json}"

    DEPLOY_META_PATH="$path" run_php -r '
        $path = getenv("DEPLOY_META_PATH") ?: "storage/app/deploy-meta.json";
        $existing = [];

        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $entry = [
            "git_sha" => (string) (getenv("GIT_SHA") ?: ""),
            "deployed_at" => (string) (getenv("DEPLOYED_AT") ?: ""),
            "git_commit_message" => trim((string) (getenv("GIT_COMMIT_MESSAGE") ?: "")),
        ];

        $requestId = getenv("REQUEST_ID");
        if (is_string($requestId) && $requestId !== "") {
            $entry["request_id"] = (int) $requestId;
        }

        $summary = trim((string) (getenv("DEPLOY_SUMMARY") ?: ""));
        if ($summary !== "") {
            $entry["summary"] = $summary;
        }

        $deployments = is_array($existing["deployments"] ?? null) ? $existing["deployments"] : [];

        if ($deployments === [] && is_string($existing["git_sha"] ?? null) && $existing["git_sha"] !== "") {
            $previous = [
                "git_sha" => $existing["git_sha"],
                "deployed_at" => (string) ($existing["deployed_at"] ?? ""),
                "git_commit_message" => trim((string) ($existing["git_commit_message"] ?? "")),
            ];

            if (isset($existing["request_id"])) {
                $previous["request_id"] = (int) $existing["request_id"];
            }

            if (is_string($existing["summary"] ?? null) && trim($existing["summary"]) !== "") {
                $previous["summary"] = trim($existing["summary"]);
            }

            $deployments[] = $previous;
        }

        $knownShas = array_column($deployments, "git_sha");
        if ($entry["git_sha"] !== "" && ! in_array($entry["git_sha"], $knownShas, true)) {
            array_unshift($deployments, $entry);
        }

        $deployments = array_slice($deployments, 0, 20);

        $meta = $entry + ["deployments" => $deployments];

        file_put_contents(
            $path,
            json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    '
}

set_env_var() {
    local key="$1"
    local value="$2"
    local file="${3:-.env}"
    local line tmp

    if [[ "$value" =~ [^a-zA-Z0-9._/@-] ]]; then
        line="${key}=\"${value//\"/\\\"}\""
    else
        line="${key}=${value}"
    fi

    # Replace via grep -v + append — sed breaks on &, \, |, etc. in values.
    tmp="$(mktemp)"
    if [[ -f "$file" ]]; then
        grep -vE "^${key}=" "$file" >"$tmp" || true
    else
        : >"$tmp"
    fi
    echo "$line" >>"$tmp"
    mv "$tmp" "$file"
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
    local lib_dir
    lib_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    local file="${lib_dir}/internal/provision/clients/${name}.conf"

    [[ -f "$file" ]] || fail "missing client config: ${file} (copy bin/internal/provision/clients/_example.conf)"

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
        env)
            [[ -n "${DOMAIN:-}" ]] \
                || fail "client config must set DOMAIN for env management"
            ;;
        *)
            fail "unknown client config mode: ${mode}"
            ;;
    esac
}

# Derive a deploy key name from the site domain.
# Called after the .conf is sourced (so $DOMAIN is set when available).
github_deploy_key_name() {
    local domain="${1:-${DOMAIN:-}}"
    if [[ -n "$domain" ]]; then
        echo "github_deploy_${domain//./_}"
    else
        echo "github_deploy"
    fi
}

github_deploy_key_path() {
    local domain="${1:-}"
    echo "${HOME}/.ssh/$(github_deploy_key_name "$domain")"
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
    local domain="${1:-}"
    local key_path
    key_path="$(github_deploy_key_path "$domain")"
    [[ -f "${key_path}" && -f "${key_path}.pub" ]]
}

# Exit 0 when the deploy user can read the repo over SSH (deploy key on repo).
github_repo_ssh_accessible() {
    local repo="$1"
    local domain="${2:-}"
    local key_path
    key_path="$(github_deploy_key_path "$domain")"

    [[ -f "$key_path" ]] || return 1

    GIT_SSH_COMMAND="ssh -i ${key_path} -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=accept-new" \
        git ls-remote --exit-code "$repo" HEAD &>/dev/null
}

# Use HTTPS for public GitHub repos so SSH deploy keys are not required.
github_repo_clone_url() {
    local repo="$1"
    local domain="${2:-}"

    if github_repo_is_github "$repo" && github_repo_is_public "$repo"; then
        github_repo_to_https "$repo"
        return 0
    fi

    # Private GitHub repo: inject domain alias so ~/.ssh/config selects the per-domain deploy key.
    if [[ -n "$domain" ]] && [[ "$repo" =~ ^git@github\.com:(.+)$ ]]; then
        echo "git@github.com-${domain}:${BASH_REMATCH[1]}"
        return 0
    fi

    echo "$repo"
}

# Before git clone: fail early for private GitHub repos missing a deploy key setup.
require_github_repo_access() {
    local repo="$1"
    local domain="${2:-}"

    github_repo_is_github "$repo" || return 0

    if github_repo_is_public "$repo"; then
        ok "GitHub repo is public (deploy key optional)"
        return 0
    fi

    if ! github_deploy_key_exists "$domain"; then
        warn "private GitHub repo requires a deploy key on this server"
        echo "       REPO=${repo}" >&2
        echo "       Run: ./bin/remote github-key CLIENT_NAME" >&2
        echo "       Then add the printed public key to the repo (Settings → Deploy keys, read-only)." >&2
        echo "       See MAINTENANCE.md → Private GitHub repos" >&2
        exit 1
    fi

    if ! github_repo_ssh_accessible "$repo" "$domain"; then
        local key_name
        key_name="$(github_deploy_key_name "$domain")"
        warn "deploy key exists but cannot read this private repo"
        echo "       REPO=${repo}" >&2
        echo "       Add ${HOME}/.ssh/${key_name}.pub to the repo deploy keys (read-only)," >&2
        echo "       or run ./bin/remote github-key if you have not yet." >&2
        exit 1
    fi

    ok "deploy key can read private GitHub repo"
}

# --- Client config → .env sync ---

client_env_is_secret() {
    case "$1" in
        APP_KEY | MONITOR_KEY | MAIL_PASSWORD | MAIL_USERNAME | DB_PASSWORD | JOES_WEBHOOK_SECRET) return 0 ;;
        *) return 1 ;;
    esac
}

client_env_format_display() {
    local key="$1"
    local value="$2"

    if client_env_is_secret "$key"; then
        if [[ -n "$value" ]]; then
            echo "***"
        else
            echo "(empty)"
        fi
    elif [[ -z "$value" ]]; then
        echo "(empty)"
    else
        echo "$value"
    fi
}

client_env_add_if_set() {
    local -n _dest="$1"
    local key="$2"
    local value="${3:-}"

    if [[ -n "$value" ]]; then
        _dest["$key"]="$value"
    fi
}

client_env_collect_desired() {
    local -n _desired="$1"
    local app_url db_name db_user db_pass mail_from main_site_url

    if [[ -n "${DOMAIN:-}" ]]; then
        app_url="http://${DOMAIN}"
        if [[ "${SSL:-}" == true ]]; then
            app_url="https://${DOMAIN}"
        fi
        _desired[APP_URL]="$app_url"
    fi

    client_env_add_if_set _desired APP_NAME "${APP_NAME:-}"
    client_env_add_if_set _desired SITE_DESCRIPTION "${SITE_DESCRIPTION:-}"
    client_env_add_if_set _desired CONTACT_EMAIL "${CONTACT_EMAIL:-}"
    client_env_add_if_set _desired GA_MEASUREMENT_ID "${GA_MEASUREMENT_ID:-}"

    if [[ -n "${MAIN_SITE_WEBHOOK_URL:-}" ]]; then
        client_env_add_if_set _desired MAIN_SITE_WEBHOOK_URL "${MAIN_SITE_WEBHOOK_URL}"
    elif [[ -n "${MAIN_SITE_DOMAIN:-}" ]]; then
        main_site_url="http://${MAIN_SITE_DOMAIN}"
        if [[ "${MAIN_SITE_SSL:-true}" == true ]]; then
            main_site_url="https://${MAIN_SITE_DOMAIN}"
        fi
        _desired[MAIN_SITE_WEBHOOK_URL]="${main_site_url}/api/joes/requests"
    fi

    client_env_add_if_set _desired JOES_WEBHOOK_SECRET "${JOES_WEBHOOK_SECRET:-}"
    client_env_add_if_set _desired JOES_LOCAL_DEPLOY_BYPASS_GIT "${JOES_LOCAL_DEPLOY_BYPASS_GIT:-}"

    client_env_add_if_set _desired MAIL_MAILER "${MAIL_MAILER:-}"
    client_env_add_if_set _desired MAIL_HOST "${MAIL_HOST:-}"
    client_env_add_if_set _desired MAIL_PORT "${MAIL_PORT:-}"
    client_env_add_if_set _desired MAIL_USERNAME "${MAIL_USERNAME:-}"
    client_env_add_if_set _desired MAIL_PASSWORD "${MAIL_PASSWORD:-}"
    client_env_add_if_set _desired MAIL_SCHEME "${MAIL_SCHEME:-}"

    mail_from="${MAIL_FROM_ADDRESS:-}"
    if [[ -z "$mail_from" && -n "${DOMAIN:-}" ]]; then
        mail_from="noreply@${DOMAIN}"
    fi
    client_env_add_if_set _desired MAIL_FROM_ADDRESS "$mail_from"

    db_name="${DB_DATABASE:-${DB_NAME:-}}"
    db_user="${DB_USERNAME:-${DB_USER:-}}"
    db_pass="${DB_PASSWORD:-${DB_PASS:-}}"
    client_env_add_if_set _desired DB_DATABASE "$db_name"
    client_env_add_if_set _desired DB_USERNAME "$db_user"
    client_env_add_if_set _desired DB_PASSWORD "$db_pass"

    client_env_add_if_set _desired MONITOR_KEY "${MONITOR_KEY:-}"
    client_env_add_if_set _desired DEPLOY_BRANCH "${DEPLOY_BRANCH:-}"
    client_env_add_if_set _desired CHECK_URL "${CHECK_URL:-}"
}

# Apply client config values into a site .env (used by provision/site and env push).
client_env_apply_to_file() {
    local env_file="$1"
    local -n _desired="$2"
    local key

    for key in $(client_env_sorted_keys "$2"); do
        set_env_var "$key" "${_desired[$key]}" "$env_file"
    done
}

client_env_sorted_keys() {
    local -n _desired="$1"
    printf '%s\n' "${!_desired[@]}" | sort
}

client_env_count_changes() {
    local env_file="$1"
    local -n _desired="$2"
    local key old new count=0

    for key in $(client_env_sorted_keys "$2"); do
        old="$(load_env_var "$key" "$env_file")"
        new="${_desired[$key]}"
        if [[ "$old" != "$new" ]]; then
            count=$((count + 1))
        fi
    done

    echo "$count"
}

client_env_print_diff() {
    local env_file="$1"
    local -n _desired="$2"
    local key old new

    for key in $(client_env_sorted_keys "$2"); do
        old="$(load_env_var "$key" "$env_file")"
        new="${_desired[$key]}"
        if [[ "$old" != "$new" ]]; then
            echo "  ${key}"
            echo "    - $(client_env_format_display "$key" "$old")"
            echo "    + $(client_env_format_display "$key" "$new")"
        fi
    done
}

client_env_confirm_push() {
    local confirm_yes="${1:-false}"

    if [[ "$confirm_yes" == true ]]; then
        return 0
    fi

    if [[ ! -t 0 ]]; then
        fail "push requires confirmation — re-run with --yes, or use ssh -t (./bin/remote env CLIENT push)"
    fi

    local reply
    read -r -p "Apply changes? [Y/n] " reply
    [[ "${reply:-Y}" =~ ^[Yy]$ ]]
}

client_env_validate_config() {
    [[ -n "${APP_NAME:-}" ]] || fail "client config: APP_NAME is required"
    [[ -n "${CONTACT_EMAIL:-}" ]] || fail "client config: CONTACT_EMAIL is required"

    local mail_mailer="${MAIL_MAILER:-log}"

    if [[ "$mail_mailer" == "smtp" ]]; then
        [[ -n "${MAIL_HOST:-}" ]] || fail "client config: MAIL_HOST is required when MAIL_MAILER=smtp"
    fi
}

client_env_validate_mail_domain() {
    local env_file="$1"
    local from domain from_domain

    from="$(load_env_var MAIL_FROM_ADDRESS "$env_file")"
    domain="${DOMAIN:-}"

    [[ -n "$from" && -n "$domain" ]] || return 0

    from_domain="${from##*@}"
    if [[ "$from_domain" != "$domain" && "$from_domain" != *".${domain}" ]]; then
        warn "MAIL_FROM_ADDRESS (${from}) is not on ${domain}"
    fi
}

# --- Deployer layout (/var/www/DOMAIN/{current,releases,shared,.dep}) ---

WEB_ROOT="${WEB_ROOT:-/var/www}"

site_deploy_root() {
    [[ -n "${DOMAIN:-}" ]] || fail "DOMAIN is not set"
    echo "${WEB_ROOT}/${DOMAIN}"
}

# Path to the live .env (shared/.env once Deployer layout is active).
site_env_file() {
    local root="${1:-$(site_deploy_root)}"

    if [[ -f "${root}/shared/.env" ]]; then
        echo "${root}/shared/.env"
    elif [[ -f "${root}/.env" ]]; then
        echo "${root}/.env"
    else
        echo "${root}/shared/.env"
    fi
}

site_live_path() {
    local root="${1:-$(site_deploy_root)}"

    if [[ -L "${root}/current" ]]; then
        echo "${root}/current"
    elif [[ -f "${root}/artisan" ]]; then
        echo "${root}"
    elif [[ -f "${root}/current/artisan" ]]; then
        echo "${root}/current"
    else
        echo "${root}/current"
    fi
}

deployer_layout_active() {
    local root="${1:-$(site_deploy_root)}"
    [[ -L "${root}/current" ]]
}

site_nginx_server_names() {
    local domain="$1"
    local dot_count="${domain//[^.]}"

    if ((${#dot_count} >= 2)); then
        echo "$domain"
    else
        echo "$domain www.${domain}"
    fi
}

site_certbot_domains() {
    local domain="$1"
    local dot_count="${domain//[^.]}"

    if ((${#dot_count} >= 2)); then
        echo "$domain"
    else
        echo "$domain www.${domain}"
    fi
}

# Symlink shared .env, storage, and SQLite into a release directory.
deployer_adopt_release() {
    local root="$1"
    local release_dir="$2"
    local tier="${3:-flat-file}"
    local shared="${root}/shared"

    mkdir -p "${root}/releases" "${root}/.dep" "${shared}/database"

    if [[ -f "${release_dir}/.env" && ! -L "${release_dir}/.env" ]]; then
        if [[ ! -f "${shared}/.env" ]]; then
            cp -a "${release_dir}/.env" "${shared}/.env"
        fi
        rm -f "${release_dir}/.env"
    fi
    ln -sfn ../../shared/.env "${release_dir}/.env"

    if [[ -d "${release_dir}/storage" && ! -L "${release_dir}/storage" ]]; then
        if [[ ! -d "${shared}/storage" ]]; then
            cp -a "${release_dir}/storage" "${shared}/"
        fi
        rm -rf "${release_dir}/storage"
    fi
    ln -sfn ../../shared/storage "${release_dir}/storage"

    if [[ "$tier" == "flat-file" ]]; then
        if [[ -f "${release_dir}/database/database.sqlite" && ! -L "${release_dir}/database/database.sqlite" ]]; then
            if [[ ! -f "${shared}/database/database.sqlite" ]]; then
                mv "${release_dir}/database/database.sqlite" "${shared}/database/database.sqlite"
            fi
        fi
        [[ -f "${shared}/database/database.sqlite" ]] || touch "${shared}/database/database.sqlite"
        mkdir -p "${release_dir}/database"
        rm -f "${release_dir}/database/database.sqlite"
        ln -sfn ../../shared/database/database.sqlite "${release_dir}/database/database.sqlite"
    fi

    ln -sfn "releases/$(basename "${release_dir}")" "${root}/current"
}

# Move a flat clone at $root into releases/1 + shared/ + current symlink.
deployer_migrate_flat_layout() {
    local root="${1:-$(site_deploy_root)}"
    local tier="${2:-${TIER:-flat-file}}"
    local release_dir="${root}/releases/1"
    local item base

    if deployer_layout_active "$root"; then
        ok "Deployer layout already active (${root}/current)"
        return 0
    fi

    rm -f "${root}/.dep/deploy.lock" 2>/dev/null || true

    mkdir -p "${root}/releases" "${root}/shared" "${root}/.dep"

    if [[ -d "$release_dir" && ! -f "${release_dir}/artisan" ]]; then
        rm -rf "$release_dir"
    fi

    if [[ -f "${root}/artisan" ]]; then
        ok "moving flat site into releases/1"
        mkdir -p "$release_dir"
        shopt -s dotglob nullglob
        for item in "${root}"/*; do
            base="$(basename "$item")"
            case "$base" in
                releases | shared | .dep | current) ;;
                *) mv "$item" "${release_dir}/" ;;
            esac
        done
        shopt -u dotglob nullglob
    elif [[ ! -f "${release_dir}/artisan" ]]; then
        fail "no Laravel app at ${root} or ${release_dir} — run provision/site first"
    fi

    deployer_adopt_release "$root" "$release_dir" "$tier"
    ok "Deployer layout ready (current → releases/1)"
}

nginx_write_site_config() {
    local domain="$1"
    local document_root="$2"
    local nginx_site="/etc/nginx/sites-available/${domain}"
    local server_names
    server_names="$(site_nginx_server_names "$domain")"

    ok "writing nginx config (${document_root})"
    sudo_cmd tee "$nginx_site" >/dev/null <<EOF
server {
    listen 80;
    server_name ${server_names};
    root ${document_root};

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

    sudo_cmd ln -sf "$nginx_site" "/etc/nginx/sites-enabled/${domain}"
    sudo_cmd nginx -t
    sudo_cmd systemctl reload nginx
}

# Point nginx at …/current/public (safe when Certbot already added SSL blocks).
nginx_update_document_root() {
    local domain="$1"
    local site_dir="$2"
    local nginx_site="/etc/nginx/sites-available/${domain}"
    local flat_root="${site_dir}/public"
    local deploy_root="${site_dir}/current/public"

    [[ -f "$nginx_site" ]] || fail "missing nginx config ${nginx_site}"

    if grep -q "root ${deploy_root};" "$nginx_site"; then
        ok "nginx already uses ${deploy_root}"
        return 0
    fi

    ok "updating nginx document root → ${deploy_root}"
    sudo_cmd sed -i "s|root ${flat_root};|root ${deploy_root};|g" "$nginx_site"
    sudo_cmd nginx -t
    sudo_cmd systemctl reload nginx
}

deployer_fix_permissions() {
    local root="${1:-$(site_deploy_root)}"
    local user
    user="$(whoami)"

    sudo_cmd chown -R "${user}:www-data" "${root}/shared" 2>/dev/null || true
    sudo_cmd chmod -R g+w "${root}/shared/storage" "${root}/shared/database" 2>/dev/null || true
    if [[ -d "${root}/current/storage" ]]; then
        sudo_cmd chown -R "${user}:www-data" "${root}/current/storage" "${root}/current/bootstrap/cache" 2>/dev/null || true
        sudo_cmd chmod -R g+w "${root}/current/storage" "${root}/current/bootstrap/cache" 2>/dev/null || true
    fi
}
