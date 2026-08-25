#!/usr/bin/env bash

set -Eeuo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
php_bin="${PHP_BIN:-php}"
current_user="$(id -un)"
current_uid="$(id -u)"
warm_caches=true

umask 0002

if [[ "${1:-}" == "--no-cache" ]]; then
    warm_caches=false
elif [[ $# -gt 0 ]]; then
    printf 'Usage: %s [--no-cache]\n' "$(basename "$0")" >&2
    exit 64
fi

cd "$root_dir"

if [[ "$current_uid" -eq 0 ]]; then
    printf 'Do not run deploy.sh as root; run it as the deployment user that owns the project.\n' >&2
    printf 'Provision web-server access to storage separately with groups or ACLs when required.\n' >&2
    exit 77
fi

runtime_directories=(
    "bootstrap/cache"
    "bootstrap/cache/config"
    "bootstrap/cache/container"
    "bootstrap/cache/routes"
    "storage"
    "storage/app"
    "storage/cache"
    "storage/cache/auth"
    "storage/cache/file"
    "storage/cache/local"
    "storage/cache/locks"
    "storage/cache/php-files"
    "storage/logs"
    "storage/sessions"
    "storage/uploads"
)

for directory in "${runtime_directories[@]}"; do
    mkdir -p "$directory"
done

blocked_path="$(
    find bootstrap/cache storage \
        \( -type d -o -type f \) \
        ! -writable \
        -print \
        -quit
)"
if [[ -n "$blocked_path" ]]; then
    printf 'Runtime path is not writable by deployment user %s: %s\n' "$current_user" "$blocked_path" >&2
    printf 'Restore project ownership once, then rerun deploy.sh without sudo.\n' >&2
    exit 77
fi

if [[ "$warm_caches" == false ]]; then
    printf 'Runtime directories are writable. Cache warming skipped.\n'
    exit 0
fi

command -v "$php_bin" >/dev/null 2>&1 || {
    printf 'PHP executable not found: %s\n' "$php_bin" >&2
    exit 127
}

php_path="$(command -v "$php_bin")"

"$php_path" infbyte optimize

printf 'Deployment directories and Foundation 2.0 runtime artifacts are ready.\n'
