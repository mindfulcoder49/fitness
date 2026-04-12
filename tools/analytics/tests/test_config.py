from pathlib import Path

import pytest

from vci_analytics.config import ConfigError, normalize_property_id, resolve_credentials_path


def test_normalize_property_id_accepts_numeric_value() -> None:
    assert normalize_property_id("123456789") == "123456789"


def test_normalize_property_id_accepts_prefixed_value() -> None:
    assert normalize_property_id("properties/123456789") == "123456789"


def test_normalize_property_id_rejects_non_numeric_value() -> None:
    with pytest.raises(ConfigError):
        normalize_property_id("abc123")


def test_resolve_credentials_path_accepts_existing_file(tmp_path: Path) -> None:
    credentials_file = tmp_path / "service-account.json"
    credentials_file.write_text("{}", encoding="utf-8")

    assert resolve_credentials_path(credentials_file) == credentials_file


def test_resolve_credentials_path_rejects_missing_file(tmp_path: Path) -> None:
    with pytest.raises(ConfigError):
        resolve_credentials_path(tmp_path / "missing.json")
