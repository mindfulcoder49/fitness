from datetime import date

import pytest

from vci_analytics.config import ConfigError
from vci_analytics.search_console import (
    build_baseline_report,
    default_sitemap_url,
    default_window,
    normalize_site_url,
    normalize_sitemap_url,
    search_analytics_rows,
    submit_sitemap,
)


def test_normalize_site_url_accepts_domain_property() -> None:
    assert normalize_site_url("sc-domain:vibecodeinternational.com") == "sc-domain:vibecodeinternational.com"


def test_normalize_site_url_accepts_bare_domain_as_domain_property() -> None:
    assert normalize_site_url("vibecodeinternational.com") == "sc-domain:vibecodeinternational.com"


def test_normalize_site_url_accepts_url_prefix_property() -> None:
    assert normalize_site_url("https://vibecodeinternational.com") == "https://vibecodeinternational.com/"


def test_default_sitemap_url_uses_https_for_domain_property() -> None:
    assert (
        default_sitemap_url("sc-domain:vibecodeinternational.com")
        == "https://vibecodeinternational.com/sitemap.xml"
    )


def test_normalize_site_url_rejects_path_in_domain_property() -> None:
    with pytest.raises(ConfigError):
        normalize_site_url("sc-domain:vibecodeinternational.com/path")


def test_normalize_sitemap_url_rejects_query_strings() -> None:
    with pytest.raises(ConfigError):
        normalize_sitemap_url(
            "https://vibecodeinternational.com/sitemap.xml?foo=bar",
            site_url="sc-domain:vibecodeinternational.com",
        )


def test_normalize_sitemap_url_rejects_hosts_outside_domain_property() -> None:
    with pytest.raises(ConfigError):
        normalize_sitemap_url(
            "https://example.com/sitemap.xml",
            site_url="sc-domain:vibecodeinternational.com",
        )


def test_default_window_uses_two_day_lag() -> None:
    assert default_window(today=date(2026, 3, 24)) == ("2026-02-23", "2026-03-22")


def test_search_analytics_rows_formats_metrics() -> None:
    response = {
        "rows": [
            {
                "keys": ["public data watch"],
                "clicks": 3,
                "impressions": 17,
                "ctr": 0.1764705882,
                "position": 8.625,
            }
        ]
    }

    assert search_analytics_rows(response, ("query",)) == [
        ["public data watch", "3", "17", "17.65%", "8.62"]
    ]


def test_build_baseline_report_handles_empty_property(monkeypatch: pytest.MonkeyPatch) -> None:
    def fake_query(*args, **kwargs):
        return {"responseAggregationType": "byProperty"}

    monkeypatch.setattr("vci_analytics.search_console.query_search_analytics", fake_query)

    report = build_baseline_report(
        "sc-domain:vibecodeinternational.com",
        "2026-02-23",
        "2026-03-22",
        row_limit=5,
    )

    assert "Search Console API access is working" in report
    assert "_No rows returned._" in report


def test_submit_sitemap_uses_put_with_encoded_urls(monkeypatch: pytest.MonkeyPatch) -> None:
    class DummyResponse:
        ok = True
        text = ""
        status_code = 200

        @staticmethod
        def json() -> dict[str, str]:
            return {}

    class DummySession:
        def __init__(self) -> None:
            self.requests: list[tuple[str, str, dict | None]] = []

        def request(self, method: str, url: str, json=None):
            self.requests.append((method, url, json))
            return DummyResponse()

    session = DummySession()
    monkeypatch.setattr(
        "vci_analytics.search_console.build_session",
        lambda *args, **kwargs: session,
    )

    result = submit_sitemap("sc-domain:vibecodeinternational.com")

    assert result == {
        "siteUrl": "sc-domain:vibecodeinternational.com",
        "sitemapUrl": "https://vibecodeinternational.com/sitemap.xml",
        "status": "submitted",
    }
    assert session.requests == [
        (
            "PUT",
            "https://www.googleapis.com/webmasters/v3/sites/sc-domain%3Avibecodeinternational.com/"
            "sitemaps/https%3A%2F%2Fvibecodeinternational.com%2Fsitemap.xml",
            None,
        )
    ]
