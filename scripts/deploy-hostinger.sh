#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOSTINGER_SSH_HOST="${HOSTINGER_SSH_HOST:-195.179.236.61}"
HOSTINGER_DEPLOY_SCRIPT="${HOSTINGER_DEPLOY_SCRIPT:-/home/u353344964/vibecodeinternationaldeploy.sh}"

SKIP_PREFLIGHT=0
SKIP_SMOKE=0
RUN_MIGRATE=0
INSTALL_BROWSER_RUNTIME=0
RESTART_QUEUE=0

for arg in "$@"; do
    case "$arg" in
        --skip-preflight) SKIP_PREFLIGHT=1 ;;
        --skip-smoke) SKIP_SMOKE=1 ;;
        --migrate) RUN_MIGRATE=1 ;;
        --install-browser-runtime) INSTALL_BROWSER_RUNTIME=1 ;;
        --queue-restart) RESTART_QUEUE=1 ;;
        *)
            echo "Unknown option: $arg" >&2
            echo "Usage: $0 [--skip-preflight] [--skip-smoke] [--migrate] [--install-browser-runtime] [--queue-restart]" >&2
            exit 1
            ;;
    esac
done

if [[ "$SKIP_PREFLIGHT" -eq 0 ]]; then
    "$ROOT_DIR/scripts/prod-preflight.sh"
fi

echo "==> Running remote deploy script"
ssh "$HOSTINGER_SSH_HOST" "$HOSTINGER_DEPLOY_SCRIPT"

echo "==> Clearing Laravel caches"
"$ROOT_DIR/scripts/prod-artisan.sh" optimize:clear

if [[ "$RUN_MIGRATE" -eq 1 ]]; then
    echo "==> Running migrations"
    "$ROOT_DIR/scripts/prod-artisan.sh" migrate --force
fi

if [[ "$INSTALL_BROWSER_RUNTIME" -eq 1 ]]; then
    echo "==> Installing Playwright runtime"
    "$ROOT_DIR/scripts/prod-artisan.sh" victory-games:install-browser-runtime
fi

if [[ "$RESTART_QUEUE" -eq 1 ]]; then
    echo "==> Restarting queue workers"
    "$ROOT_DIR/scripts/prod-artisan.sh" queue:restart
fi

if [[ "$SKIP_SMOKE" -eq 0 ]]; then
    "$ROOT_DIR/scripts/prod-smoke.sh"
fi

echo "==> Deploy flow completed"
