#!/usr/bin/env python3
from __future__ import annotations

import subprocess
import sys
import venv
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
VENV_DIR = ROOT / ".venv"


def python_bin() -> Path:
    if sys.platform.startswith("win"):
        return VENV_DIR / "Scripts" / "python.exe"
    return VENV_DIR / "bin" / "python"


def ensure_venv() -> None:
    if not VENV_DIR.exists():
        venv.create(VENV_DIR, with_pip=True)


def ensure_install() -> None:
    py = str(python_bin())
    try:
        subprocess.run(
            [py, "-c", "import project_exoskeleton"],
            cwd=ROOT,
            check=True,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )
    except subprocess.CalledProcessError:
        subprocess.run([py, "-m", "pip", "install", "--upgrade", "pip"], cwd=ROOT, check=True)
        subprocess.run([py, "-m", "pip", "install", "-e", ".[dev]"], cwd=ROOT, check=True)


def main() -> int:
    ensure_venv()
    ensure_install()
    cmd = [str(python_bin()), "-m", "project_exoskeleton", "queue", "serve", *sys.argv[1:]]
    return subprocess.call(cmd, cwd=ROOT)


if __name__ == "__main__":
    raise SystemExit(main())
