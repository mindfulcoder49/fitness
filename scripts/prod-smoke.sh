#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_URL="${PROD_BASE_URL:-https://vibecodeinternational.com}"

check_url() {
    local url="$1"
    local expected="${2:-200}"
    local code

    code="$(curl -L -sS -o /dev/null -w '%{http_code}' "$url")"

    if [[ "$code" != "$expected" ]]; then
        echo "Smoke check failed: $url returned $code, expected $expected" >&2
        exit 1
    fi

    echo "OK $code $url"
}

check_url "$BASE_URL/" 200
check_url "$BASE_URL/up" 200
check_url "$BASE_URL/victory-games" 200
check_url "$BASE_URL/login" 200

echo "==> Remote artisan about"
"$ROOT_DIR/scripts/prod-artisan.sh" about

echo "==> Remote Victory Games routes"
"$ROOT_DIR/scripts/prod-artisan.sh" route:list --path=victory-games
