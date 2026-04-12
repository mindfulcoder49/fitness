import pytest

from vci_analytics.admin import normalize_stream_name, validate_timezone
from vci_analytics.config import ConfigError


def test_normalize_stream_name_accepts_full_resource_name() -> None:
    assert (
        normalize_stream_name("properties/123456789/dataStreams/987654321", "123456789")
        == "properties/123456789/dataStreams/987654321"
    )


def test_normalize_stream_name_accepts_numeric_stream_id() -> None:
    assert (
        normalize_stream_name("987654321", "123456789")
        == "properties/123456789/dataStreams/987654321"
    )


def test_normalize_stream_name_rejects_other_property() -> None:
    with pytest.raises(ConfigError):
        normalize_stream_name("properties/111/dataStreams/222", "123456789")


def test_validate_timezone_accepts_iana_timezone() -> None:
    assert validate_timezone("America/New_York") == "America/New_York"


def test_validate_timezone_rejects_unknown_timezone() -> None:
    with pytest.raises(ConfigError):
        validate_timezone("Mars/Olympus_Mons")
