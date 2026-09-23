#!/usr/bin/env bash

set -Eeuo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
php_bin="${PHP_BIN:-php}"
current_user="$(id -un)"
current_uid="$(id -u)"
build_generation=true

umask 0002

if [[ "${1:-}" == "--no-optimize" ]]; then
    build_generation=false
elif [[ $# -gt 0 ]]; then
    printf 'Usage: %s [--no-optimize]\n' "$(basename "$0")" >&2
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
    "storage"
    "storage/app"
    "storage/cache"
    "storage/logs"
    "storage/releases"
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

if [[ "$build_generation" == false ]]; then
    printf 'Runtime directories are writable. Foundation release generation build skipped.\n'
    exit 0
fi

command -v "$php_bin" >/dev/null 2>&1 || {
    printf 'PHP executable not found: %s\n' "$php_bin" >&2
    exit 127
}

php_path="$(command -v "$php_bin")"

"$php_path" infbyte config:validate --production
release_json="$("$php_path" infbyte optimize --json=1)"
printf '%s\n' "$release_json"
"$php_path" infbyte app:ready

release_root="$(printf '%s' "$release_json" | "$php_path" -r '
    $release = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    echo is_string($release["release_root"] ?? null) ? $release["release_root"] : "";
')"
manifest_sha256="$(printf '%s' "$release_json" | "$php_path" -r '
    $release = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    echo is_string($release["manifest_sha256"] ?? null) ? $release["manifest_sha256"] : "";
')"

if [[ -z "$release_root" || ! "$manifest_sha256" =~ ^[a-f0-9]{64}$ ]]; then
    printf 'Foundation optimize did not return trusted release metadata.\n' >&2
    exit 70
fi

printf '\nFoundation 3 release generation is ready.\n'
printf 'Configure the web/runtime supervisor with these immutable deployment inputs:\n'
printf 'export INFOCYPH_FOUNDATION_RELEASE_ROOT=%q\n' "$release_root"
printf 'export INFOCYPH_FOUNDATION_RELEASE_MANIFEST_SHA256=%q\n' "$manifest_sha256"
printf 'Keep these trust values in deployment/service metadata outside the writable release directory.\n'
