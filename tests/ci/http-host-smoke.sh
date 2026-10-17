#!/usr/bin/env bash
# Short shared-runner correctness/stability probe. NOT a certified throughput
# comparison or a replacement for Foundation's paired controlled-host benchmark.
set -Eeuo pipefail

if [[ $# -ne 3 ]]; then
    printf 'Usage: %s <base-url> <requests> <concurrency>\n' "$0" >&2
    exit 64
fi

base_url="${1%/}"
requests="$2"
concurrency="$3"

if [[ ! "$requests" =~ ^[1-9][0-9]*$ || ! "$concurrency" =~ ^[1-9][0-9]*$ ]]; then
    printf 'Request count and concurrency must be positive integers.\n' >&2
    exit 64
fi

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

start="$(date +%s%3N)"

seq 1 "$requests" | xargs -P "$concurrency" -I '{}' bash -c '
    set -Eeuo pipefail
    result="$(curl --silent --show-error --fail --max-time 6 \
        --write-out "\n%{time_total}" "$1/api/health")"
    body="${result%$'"'"'\n'"'"'*}"
    seconds="${result##*$'"'"'\n'"'"'}"
    if [[ "$body" != '"'"'{"status":"ok"}'"'"' ]]; then
        printf "Unexpected health response on request %s: %s\n" "$2" "$body" >&2
        exit 1
    fi
    printf "%s\n" "$seconds" > "$3/response-$2.txt"
' _ "$base_url" '{}' "$work"

count="$(find "$work" -maxdepth 1 -name 'response-*.txt' -type f | wc -l)"
if [[ "$count" -ne "$requests" ]]; then
    printf 'Missing request results: expected %s, found %s.\n' "$requests" "$count" >&2
    exit 1
fi

for attempt in 1 2 3; do
    code="$(curl --silent --output /dev/null --max-time 6 \
        --write-out '%{http_code}' "$base_url/unknown-infbyte-4-route")"
    if [[ "$code" != '404' ]]; then
        printf 'Expected an unknown-route 404, got %s.\n' "$code" >&2
        exit 1
    fi
done

if [[ "$(curl --silent --show-error --fail --max-time 6 "$base_url/api/health")" != '{"status":"ok"}' ]]; then
    printf 'Host did not recover from unknown route probes.\n' >&2
    exit 1
fi

end="$(date +%s%3N)"
sort -n "$work"/response-*.txt > "$work/times.txt"
percentiles="$(awk -v count="$requests" '
    NR == int(count * 0.50 + 0.99999) { p50 = $1 }
    NR == int(count * 0.95 + 0.99999) { p95 = $1 }
    NR == int(count * 0.99 + 0.99999) { p99 = $1 }
    END { printf "p50=%ss p95=%ss p99=%ss", p50, p95, p99 }
' "$work/times.txt")"

printf 'PASS: %s responses, concurrency=%s, wall_ms=%s; %s\n' \
    "$requests" "$concurrency" "$((end - start))" "$percentiles"
printf 'Shared-runner timings are diagnostic only, not comparable benchmark evidence.\n'
