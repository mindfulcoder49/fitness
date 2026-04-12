#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ ! -d ".venv" ]]; then
  python3 -m venv .venv
fi

source .venv/bin/activate

if ! python -c "import project_exoskeleton" >/dev/null 2>&1; then
  python -m pip install --upgrade pip
  python -m pip install -e ".[dev]"
fi

python -m project_exoskeleton queue serve "$@"
