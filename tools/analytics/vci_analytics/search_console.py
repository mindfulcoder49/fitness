from __future__ import annotations

import os
from dataclasses import dataclass
from datetime import date, timedelta
from pathlib import Path
from typing import Any
from urllib.parse import quote, urlparse

import google.auth
from google.auth.transport.requests import AuthorizedSession
from google.oauth2 import service_account

from .config import ConfigError
from .ga4 import render_table


SEARCH_CONSOLE_READONLY_SCOPE = "https://www.googleapis.com/auth/webmasters.readonly"
SEARCH_CONSOLE_EDIT_SCOPE = "https://www.googleapis.com/auth/webmasters"
SEARCH_CONSOLE_API_BASE = "https://www.googleapis.com/webmasters/v3"


class SearchConsoleAPIError(RuntimeError):
    def __init__(
        self,
        status_code: int,
        message: str,
        *,
        payload: dict[str, Any] | None = None,
    ) -> None:
        super().__init__(message)
        self.status_code = status_code
        self.payload = payload or {}


@dataclass(frozen=True)
class SearchConsoleQuerySpec:
    site_url: str
    start_date: str
    end_date: str
    dimensions: tuple[str, ...] = ()
    row_limit: int = 25
    aggregation_type: str | None = None
    search_type: str | None = None


def resolve_site_url(site_url: str | None = None) -> str:
    return normalize_site_url(site_url or os.getenv("SEARCH_CONSOLE_SITE_URL"))


def normalize_site_url(value: str | None) -> str:
    if value is None:
        raise ConfigError(
            "Search Console site URL is required. Pass --site-url or set SEARCH_CONSOLE_SITE_URL."
        )

    normalized = value.strip()
    if not normalized:
        raise ConfigError(
            "Search Console site URL is required. Pass --site-url or set SEARCH_CONSOLE_SITE_URL."
        )

    if normalized.startswith("sc-domain:"):
        domain = normalized.removeprefix("sc-domain:").strip()
        if not domain:
            raise ConfigError("Search Console domain property must be in the form sc-domain:example.com.")
        if "/" in domain:
            raise ConfigError("Search Console domain property cannot include a path.")
        return f"sc-domain:{domain}"

    if normalized.startswith(("http://", "https://")):
        parsed = urlparse(normalized)
        if not parsed.scheme or not parsed.netloc:
            raise ConfigError(
                "Search Console URL-prefix property must be a full http:// or https:// URL."
            )
        if parsed.query or parsed.fragment:
            raise ConfigError("Search Console site URL cannot include a query string or fragment.")

        path = parsed.path or "/"
        if not path.startswith("/"):
            path = f"/{path}"
        return f"{parsed.scheme}://{parsed.netloc}{path}"

    if "/" in normalized or " " in normalized:
        raise ConfigError(
            "Search Console site URL must be a full URL-prefix property or sc-domain property."
        )

    return f"sc-domain:{normalized}"


def default_window(
    *,
    today: date | None = None,
    lookback_days: int = 28,
    lag_days: int = 2,
) -> tuple[str, str]:
    current_day = today or date.today()
    end = current_day - timedelta(days=lag_days)
    start = end - timedelta(days=lookback_days - 1)
    return start.isoformat(), end.isoformat()


def build_session(
    credentials_path: Path | None = None,
    *,
    write_access: bool = False,
) -> AuthorizedSession:
    scopes = [SEARCH_CONSOLE_EDIT_SCOPE if write_access else SEARCH_CONSOLE_READONLY_SCOPE]

    if credentials_path is None:
        credentials, _ = google.auth.default(scopes=scopes)
        return AuthorizedSession(credentials)

    credentials = service_account.Credentials.from_service_account_file(
        str(credentials_path),
        scopes=scopes,
    )
    return AuthorizedSession(credentials)


def default_sitemap_url(site_url: str) -> str:
    if site_url.startswith("sc-domain:"):
        domain = site_url.removeprefix("sc-domain:")
        return f"https://{domain}/sitemap.xml"

    parsed = urlparse(site_url)
    if not parsed.scheme or not parsed.netloc:
        raise ConfigError("Search Console site URL must be normalized before deriving a sitemap URL.")

    if parsed.path and parsed.path != "/":
        base_path = parsed.path.rstrip("/")
        return f"{parsed.scheme}://{parsed.netloc}{base_path}/sitemap.xml"

    return f"{parsed.scheme}://{parsed.netloc}/sitemap.xml"


def normalize_sitemap_url(
    sitemap_url: str | None,
    *,
    site_url: str | None = None,
) -> str:
    candidate = (sitemap_url or "").strip()
    if not candidate:
        if site_url is None:
            raise ConfigError("Sitemap URL is required. Pass --sitemap-url or provide a site URL.")
        candidate = default_sitemap_url(site_url)

    parsed = urlparse(candidate)
    if parsed.scheme not in {"http", "https"} or not parsed.netloc:
        raise ConfigError("Sitemap URL must be a full http:// or https:// URL.")
    if parsed.query or parsed.fragment:
        raise ConfigError("Sitemap URL cannot include a query string or fragment.")

    normalized = f"{parsed.scheme}://{parsed.netloc}{parsed.path or '/'}"

    if site_url and site_url.startswith("sc-domain:"):
        domain = site_url.removeprefix("sc-domain:")
        hostname = parsed.hostname or ""
        if hostname != domain and not hostname.endswith(f".{domain}"):
            raise ConfigError(
                f"Sitemap URL host must match the Search Console domain property `{domain}`."
            )

    if site_url and site_url.startswith(("http://", "https://")):
        normalized_site_url = normalize_site_url(site_url).rstrip("/")
        if not normalized.startswith(normalized_site_url):
            raise ConfigError(
                "Sitemap URL must be inside the configured Search Console URL-prefix property."
            )

    return normalized


def _error_message(payload: Any, fallback: str) -> str:
    if isinstance(payload, dict):
        error = payload.get("error")
        if isinstance(error, dict) and error.get("message"):
            return str(error["message"])
    return fallback


def _request_json(
    session: AuthorizedSession,
    method: str,
    url: str,
    *,
    payload: dict[str, Any] | None = None,
) -> dict[str, Any]:
    response = session.request(method=method, url=url, json=payload)
    if not response.ok:
        parsed_payload: dict[str, Any] | None
        try:
            decoded = response.json()
            parsed_payload = decoded if isinstance(decoded, dict) else None
        except ValueError:
            parsed_payload = None

        raise SearchConsoleAPIError(
            response.status_code,
            _error_message(parsed_payload, response.text or "Search Console API request failed."),
            payload=parsed_payload,
        )

    if not response.text.strip():
        return {}

    decoded = response.json()
    if not isinstance(decoded, dict):
        raise SearchConsoleAPIError(
            response.status_code,
            "Search Console API returned an unexpected payload.",
        )

    return decoded


def list_sites(credentials_path: Path | None = None) -> list[dict[str, Any]]:
    session = build_session(credentials_path)
    payload = _request_json(session, "GET", f"{SEARCH_CONSOLE_API_BASE}/sites")
    entries = payload.get("siteEntry", [])
    return entries if isinstance(entries, list) else []


def submit_sitemap(
    site_url: str,
    sitemap_url: str | None = None,
    credentials_path: Path | None = None,
) -> dict[str, str]:
    normalized_sitemap_url = normalize_sitemap_url(sitemap_url, site_url=site_url)
    session = build_session(credentials_path, write_access=True)
    encoded_site_url = quote(site_url, safe="")
    encoded_sitemap_url = quote(normalized_sitemap_url, safe="")
    _request_json(
        session,
        "PUT",
        f"{SEARCH_CONSOLE_API_BASE}/sites/{encoded_site_url}/sitemaps/{encoded_sitemap_url}",
    )
    return {
        "siteUrl": site_url,
        "sitemapUrl": normalized_sitemap_url,
        "status": "submitted",
    }


def query_search_analytics(
    spec: SearchConsoleQuerySpec,
    credentials_path: Path | None = None,
) -> dict[str, Any]:
    session = build_session(credentials_path)
    body: dict[str, Any] = {
        "startDate": spec.start_date,
        "endDate": spec.end_date,
        "rowLimit": spec.row_limit,
    }
    if spec.dimensions:
        body["dimensions"] = list(spec.dimensions)
    if spec.aggregation_type:
        body["aggregationType"] = spec.aggregation_type
    if spec.search_type:
        body["type"] = spec.search_type

    encoded_site_url = quote(spec.site_url, safe="")
    return _request_json(
        session,
        "POST",
        f"{SEARCH_CONSOLE_API_BASE}/sites/{encoded_site_url}/searchAnalytics/query",
        payload=body,
    )


def site_rows(site_entries: list[dict[str, Any]]) -> list[list[str]]:
    return [
        [str(entry.get("siteUrl", "")), str(entry.get("permissionLevel", ""))]
        for entry in site_entries
    ]


def _format_int_like(value: Any) -> str:
    if isinstance(value, float) and value.is_integer():
        return str(int(value))
    return str(value)


def search_analytics_headers(dimensions: tuple[str, ...]) -> list[str]:
    return [*dimensions, "clicks", "impressions", "ctr", "position"]


def search_analytics_rows(
    response: dict[str, Any],
    dimensions: tuple[str, ...],
) -> list[list[str]]:
    rows: list[list[str]] = []
    for row in response.get("rows", []) or []:
        keys = row.get("keys", [])
        key_values = [str(value) for value in keys]

        while len(key_values) < len(dimensions):
            key_values.append("")

        ctr = row.get("ctr")
        position = row.get("position")
        rows.append(
            [
                *key_values[: len(dimensions)],
                _format_int_like(row.get("clicks", 0)),
                _format_int_like(row.get("impressions", 0)),
                f"{float(ctr) * 100:.2f}%" if ctr is not None else "",
                f"{float(position):.2f}" if position is not None else "",
            ]
        )
    return rows


def render_search_analytics_table(
    response: dict[str, Any],
    dimensions: tuple[str, ...],
) -> str:
    return render_table(
        search_analytics_headers(dimensions),
        search_analytics_rows(response, dimensions),
    )


def _summary_row(response: dict[str, Any]) -> dict[str, Any] | None:
    rows = response.get("rows", [])
    if not rows:
        return None

    first_row = rows[0]
    return first_row if isinstance(first_row, dict) else None


def _markdown_table(headers: list[str], rows: list[list[str]]) -> str:
    if not rows:
        return "_No rows returned._"

    separator = ["---"] * len(headers)
    lines = [
        "| " + " | ".join(headers) + " |",
        "| " + " | ".join(separator) + " |",
    ]
    for row in rows:
        lines.append("| " + " | ".join(row) + " |")
    return "\n".join(lines)


def build_baseline_report(
    site_url: str,
    start_date: str,
    end_date: str,
    credentials_path: Path | None = None,
    *,
    row_limit: int = 10,
) -> str:
    aggregate = query_search_analytics(
        SearchConsoleQuerySpec(
            site_url=site_url,
            start_date=start_date,
            end_date=end_date,
            row_limit=1,
            aggregation_type="byProperty",
        ),
        credentials_path,
    )
    top_queries = query_search_analytics(
        SearchConsoleQuerySpec(
            site_url=site_url,
            start_date=start_date,
            end_date=end_date,
            dimensions=("query",),
            row_limit=row_limit,
            aggregation_type="byProperty",
        ),
        credentials_path,
    )
    top_pages = query_search_analytics(
        SearchConsoleQuerySpec(
            site_url=site_url,
            start_date=start_date,
            end_date=end_date,
            dimensions=("page",),
            row_limit=row_limit,
            aggregation_type="byPage",
        ),
        credentials_path,
    )

    summary = _summary_row(aggregate)
    lines = [
        "# Search Console Baseline",
        "",
        f"Date generated: {date.today().isoformat()}  ",
        f"Property: `{site_url}`  ",
        f"Window: {start_date} through {end_date}",
        "",
    ]

    if summary is None:
        lines.extend(
            [
                "## Status",
                "",
                "Search Console API access is working, but this window returned no performance rows yet.",
                "",
                "Possible reasons:",
                "",
                "- the property is newly verified",
                "- Google has not populated enough data yet",
                "- impressions and clicks are still effectively zero in this window",
                "",
                "## Top Queries",
                "",
                "_No rows returned._",
                "",
                "## Top Pages",
                "",
                "_No rows returned._",
            ]
        )
        return "\n".join(lines)

    lines.extend(
        [
            "## Metrics Summary",
            "",
            f"- Clicks: {_format_int_like(summary.get('clicks', 0))}",
            f"- Impressions: {_format_int_like(summary.get('impressions', 0))}",
            f"- CTR: {float(summary.get('ctr', 0)) * 100:.2f}%",
            f"- Average position: {float(summary.get('position', 0)):.2f}",
            "",
            "## Top Queries",
            "",
            _markdown_table(
                search_analytics_headers(("query",)),
                search_analytics_rows(top_queries, ("query",)),
            ),
            "",
            "## Top Pages",
            "",
            _markdown_table(
                search_analytics_headers(("page",)),
                search_analytics_rows(top_pages, ("page",)),
            ),
        ]
    )

    return "\n".join(lines)
