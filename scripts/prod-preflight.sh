#!/usr/bin/env bash

set -euo pipefail

HOSTINGER_SSH_HOST="${HOSTINGER_SSH_HOST:-195.179.236.61}"
HOSTINGER_APP_PATH="${HOSTINGER_APP_PATH:-/home/u353344964/domains/vibecodeinternational.com/fitness}"
HOSTINGER_DEPLOY_SCRIPT="${HOSTINGER_DEPLOY_SCRIPT:-/home/u353344964/vibecodeinternationaldeploy.sh}"
HOSTINGER_PHP_BIN="${HOSTINGER_PHP_BIN:-/opt/alt/php83/usr/bin/php}"

ssh "$HOSTINGER_SSH_HOST" bash -s -- "$HOSTINGER_APP_PATH" "$HOSTINGER_DEPLOY_SCRIPT" "$HOSTINGER_PHP_BIN" <<'REMOTE'
set -euo pipefail

APP_PATH="$1"
DEPLOY_SCRIPT="$2"
PHP_BIN="$3"

echo "==> Host"
hostname

echo "==> Checking remote paths"
[[ -d "$APP_PATH" ]]
[[ -f "$APP_PATH/artisan" ]]
[[ -f "$DEPLOY_SCRIPT" ]]
[[ -x "$PHP_BIN" ]]
echo "App path: $APP_PATH"
echo "Deploy script: $DEPLOY_SCRIPT"
echo "PHP bin: $PHP_BIN"

DEFAULT_PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
TARGET_PHP_VERSION="$("$PHP_BIN" -r 'echo PHP_VERSION;')"
echo "==> Default remote PHP: $DEFAULT_PHP_VERSION"
echo "==> Target remote PHP: $TARGET_PHP_VERSION"

if ! "$PHP_BIN" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'; then
    echo "Configured remote PHP binary is below 8.3. Deployment is blocked." >&2
    exit 1
fi

cd "$APP_PATH"

echo "==> Git origin"
git remote get-url origin

echo "==> Git branch"
git rev-parse --abbrev-ref HEAD

echo "==> Artisan about"
"$PHP_BIN" artisan about

echo "==> Visible user crontab"
if command -v crontab >/dev/null 2>&1 && crontab -l >/tmp/vci_crontab.$$ 2>/dev/null; then
    cat /tmp/vci_crontab.$$
    rm -f /tmp/vci_crontab.$$
else
    echo "No user crontab visible over SSH."
fi
REMOTE
