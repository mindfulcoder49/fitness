from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path

from dotenv import load_dotenv


def repo_root() -> Path:
    return Path(__file__).resolve().parents[3]


def tool_root() -> Path:
    return repo_root() / "tools" / "exoskeleton"


def load_local_env() -> None:
    load_dotenv(repo_root() / ".env", override=False)
    load_dotenv(tool_root() / ".env", override=True)


def default_project_slug() -> str:
    return os.getenv("PROJECT_EXO_PROJECT_SLUG", repo_root().name)


def default_db_path() -> Path:
    raw_value = os.getenv("PROJECT_EXO_DB_PATH", "tools/exoskeleton/data/exoskeleton.sqlite3")
    candidate = Path(raw_value).expanduser()
    if not candidate.is_absolute():
        candidate = (repo_root() / candidate).resolve()
    return candidate


def default_host() -> str:
    return os.getenv("PROJECT_EXO_HOST", "127.0.0.1")


@dataclass(frozen=True)
class RuntimeConfig:
    project_slug: str
    db_path: Path
    host: str

    @classmethod
    def load(cls) -> "RuntimeConfig":
        load_local_env()
        return cls(
            project_slug=default_project_slug(),
            db_path=default_db_path(),
            host=default_host(),
        )

    def ensure_data_dir(self) -> None:
        self.db_path.parent.mkdir(parents=True, exist_ok=True)
