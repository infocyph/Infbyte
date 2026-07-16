#!/usr/bin/env bash

set -Eeuo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
php_bin="${PHP_BIN:-php}"
app_user="${APP_USER:-$(id -un)}"
app_group="${APP_GROUP:-$(id -gn "$app_user")}"
warm_caches=true

if [[ "${1:-}" == "--no-cache" ]]; then
    warm_caches=false
elif [[ $# -gt 0 ]]; then
    printf 'Usage: %s [--no-cache]\n' "$(basename "$0")" >&2
    exit 64
fi

cd "$root_dir"

runtime_directories=(
    "bootstrap/cache"
    "bootstrap/cache/config"
    "bootstrap/cache/routes"
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
    install -d -m 2775 "$directory"
done

# The setgid bit keeps newly-created files in the runtime group.
for directory in "${runtime_directories[@]}"; do
    find "$directory" -type d -exec chmod 2775 {} +
    find "$directory" -type f -exec chmod 0664 {} +
done

if [[ "$(id -u)" -eq 0 ]]; then
    chown -R "$app_user:$app_group" bootstrap/cache storage
else
    printf 'Permissions prepared for %s:%s. Run as root with APP_USER/APP_GROUP if PHP uses another user.\n' "$app_user" "$app_group"
fi

if [[ "$warm_caches" == false ]]; then
    printf 'Runtime permissions prepared. Cache warming skipped.\n'
    exit 0
fi

command -v "$php_bin" >/dev/null 2>&1 || {
    printf 'PHP executable not found: %s\n' "$php_bin" >&2
    exit 127
}

"$php_bin" infbyte config:clear
"$php_bin" infbyte route:clear
"$php_bin" infbyte config:cache
"$php_bin" infbyte route:cache

if [[ "$(id -u)" -eq 0 ]]; then
    # CacheLayer may create private cache directories while warming.
    chown -R "$app_user:$app_group" bootstrap/cache storage
fi

printf 'Deployment runtime permissions and application caches are ready.\n'
