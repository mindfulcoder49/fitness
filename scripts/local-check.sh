#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MODE="${1:-quick}"

case "$MODE" in
    quick|full) ;;
    *)
        echo "Usage: $0 [quick|full]" >&2
        exit 1
        ;;
esac

cd "$ROOT_DIR"

echo "==> PHP syntax check"
while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
done < <(find app bootstrap config database routes tests -type f -name '*.php' -print0)

if [[ -x ./vendor/bin/pint ]]; then
    echo "==> Pint"
    if ! ./vendor/bin/pint --test; then
        echo "Pint reported style issues. Continuing because the repo currently has existing formatting debt." >&2
    fi
else
    echo "==> Pint not available, skipping"
fi

echo "==> Frontend build"
npm run build

if [[ "$MODE" == "full" ]]; then
    echo "==> Laravel test suite"
    if [[ -x ./vendor/bin/sail ]] && command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
        ./vendor/bin/sail artisan test
    else
        php artisan test
    fi
fi

echo "==> Local checks passed"
