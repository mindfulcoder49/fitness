from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path

from dotenv import load_dotenv


class ConfigError(ValueError):
    """Raised when required analytics configuration is missing or invalid."""


def repo_root() -> Path:
    return Path(__file__).resolve().parents[3]


def load_local_env() -> None:
    root = repo_root()
    load_dotenv(root / ".env", override=False)
    load_dotenv(root / "tools" / "analytics" / ".env", override=True)


def normalize_property_id(value: str | None) -> str:
    if value is None:
        raise ConfigError("GA4 property id is required.")

    normalized = value.strip()
    if not normalized:
        raise ConfigError("GA4 property id is required.")

    if normalized.startswith("properties/"):
        normalized = normalized.split("/", maxsplit=1)[1]

    if not normalized.isdigit():
        raise ConfigError(
            "GA4 property id must be numeric or in the form 'properties/<numeric-id>'."
        )

    return normalized


def resolve_credentials_path(value: str | Path | None) -> Path | None:
    raw_value = value or os.getenv("GA4_SERVICE_ACCOUNT_JSON") or os.getenv(
        "GOOGLE_APPLICATION_CREDENTIALS"
    )

    if not raw_value:
        return None

    candidate = Path(raw_value).expanduser()
    if not candidate.is_absolute():
        candidate = (repo_root() / candidate).resolve()

    if not candidate.exists():
        raise ConfigError(f"Credentials file not found: {candidate}")

    return candidate


@dataclass(frozen=True)
class RuntimeConfig:
    property_id: str
    credentials_path: Path | None

    @classmethod
    def from_inputs(
        cls,
        property_id: str | None = None,
        credentials_path: str | Path | None = None,
    ) -> "RuntimeConfig":
        load_local_env()

        resolved_property_id = normalize_property_id(property_id or os.getenv("GA4_PROPERTY_ID"))
        resolved_credentials_path = resolve_credentials_path(credentials_path)

        return cls(
            property_id=resolved_property_id,
            credentials_path=resolved_credentials_path,
        )
