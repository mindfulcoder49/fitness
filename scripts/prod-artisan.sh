#!/usr/bin/env bash

set -euo pipefail

HOSTINGER_SSH_HOST="${HOSTINGER_SSH_HOST:-195.179.236.61}"
HOSTINGER_APP_PATH="${HOSTINGER_APP_PATH:-/home/u353344964/domains/vibecodeinternational.com/fitness}"
HOSTINGER_PHP_BIN="${HOSTINGER_PHP_BIN:-/opt/alt/php83/usr/bin/php}"

if [[ $# -eq 0 ]]; then
    echo "Usage: $0 <artisan arguments...>" >&2
    exit 1
fi

printf -v REMOTE_CD '%q' "$HOSTINGER_APP_PATH"
printf -v REMOTE_ARTISAN '%q ' "$HOSTINGER_PHP_BIN" artisan "$@"

ssh "$HOSTINGER_SSH_HOST" "cd $REMOTE_CD && $REMOTE_ARTISAN"
