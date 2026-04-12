#!/usr/bin/env bash

set -euo pipefail

APP_DIR="${HOSTINGER_APP_PATH:-/home/u353344964/domains/vibecodeinternational.com/fitness}"
PHP_BIN="${HOSTINGER_PHP_BIN:-/opt/alt/php83/usr/bin/php}"
NODE_BIN="${PLAYWRIGHT_NODE_PATH:-/home/u353344964/.nvm/versions/node/v20.2.0/bin/node}"
NODE_DIR="$(dirname "$NODE_BIN")"
USER_HOME="${HOSTINGER_HOME:-$(dirname "$(dirname "$(dirname "$APP_DIR")")")}"

cd "$APP_DIR"

export HOME="${HOME:-$USER_HOME}"
export PATH="$NODE_DIR:/usr/local/bin:/usr/bin:/bin"
export PLAYWRIGHT_NODE_PATH="$NODE_BIN"
export PLAYWRIGHT_LIBRARY_PATH="${PLAYWRIGHT_LIBRARY_PATH:-$APP_DIR/storage/app/playwright-libs/lib64}"

exec "$PHP_BIN" artisan queue:work database --queue=default --stop-when-empty --tries=1 --timeout=1200 "$@"
