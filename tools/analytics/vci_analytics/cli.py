from __future__ import annotations

import json
import logging
import sys
from datetime import date
from pathlib import Path

import click
from google.auth.exceptions import DefaultCredentialsError
from google.api_core.exceptions import (
    GoogleAPICallError,
    InvalidArgument,
    NotFound,
    PermissionDenied,
)

from .admin import (
    TimezoneUpdatePreview,
    access_binding_rows,
    enhanced_measurement_rows,
    get_enhanced_measurement_settings,
    inspect_property,
    list_access_bindings,
    list_data_streams,
    message_to_dict,
    property_rows,
    set_property_timezone,
    stream_rows,
    timezone_preview_rows,
)
from .config import (
    ConfigError,
    RuntimeConfig,
    load_local_env,
    repo_root,
    resolve_credentials_path,
)
from .ga4 import (
    ReportSpec,
    render_table,
    render_test_filter_summary_rows,
    response_to_dict,
    response_to_rows,
    run_report,
    summarize_test_filter_rows,
)
from .search_console import (
    SearchConsoleAPIError,
    SearchConsoleQuerySpec,
    build_baseline_report,
    default_sitemap_url,
    default_window,
    list_sites,
    normalize_sitemap_url,
    query_search_analytics,
    render_search_analytics_table,
    resolve_site_url,
    site_rows,
    submit_sitemap,
)


logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)],
)


def resolve_runtime_config(
    property_id: str | None,
    credentials_path: Path | None,
) -> RuntimeConfig:
    try:
        return RuntimeConfig.from_inputs(
            property_id=property_id,
            credentials_path=credentials_path,
        )
    except ConfigError as exc:
        raise click.ClickException(str(exc)) from exc


def render_response(output_format: str, response) -> str:
    if output_format == "json":
        return json.dumps(response_to_dict(response), indent=2)

    headers, rows = response_to_rows(response)
    return render_table(headers, rows)


def resolve_credentials_or_exit(credentials_path: Path | None) -> Path | None:
    try:
        return resolve_credentials_path(credentials_path)
    except ConfigError as exc:
        raise click.ClickException(str(exc)) from exc


def resolve_site_url_or_exit(site_url: str | None) -> str:
    try:
        return resolve_site_url(site_url)
    except ConfigError as exc:
        raise click.ClickException(str(exc)) from exc


def run_report_or_exit(
    config: RuntimeConfig,
    spec: ReportSpec,
    output_format: str,
) -> None:
    response = fetch_report_or_exit(config, spec)
    click.echo(render_response(output_format, response))


def fetch_report_or_exit(
    config: RuntimeConfig,
    spec: ReportSpec,
):
    try:
        return run_report(
            property_id=config.property_id,
            spec=spec,
            credentials_path=config.credentials_path,
        )
    except PermissionDenied as exc:
        raise click.ClickException(
            "GA4 permission denied. Check that the service account email has at least "
            f"Viewer access to property {config.property_id}."
        ) from exc
    except InvalidArgument as exc:
        raise click.ClickException(
            f"GA4 rejected the request. Check your dimensions, metrics, dates, or property id. {exc}"
        ) from exc
    except GoogleAPICallError as exc:
        raise click.ClickException(f"GA4 API call failed: {exc}") from exc


def render_admin_resource(output_format: str, resource, rows: list[list[str]]) -> str:
    if output_format == "json":
        return json.dumps(message_to_dict(resource), indent=2)

    return render_table(["field", "value"], rows)


def render_timezone_result(output_format: str, result) -> str:
    if isinstance(result, TimezoneUpdatePreview):
        if output_format == "json":
            return json.dumps(result.__dict__, indent=2)
        return render_table(["field", "value"], timezone_preview_rows(result))

    return render_admin_resource(output_format, result, property_rows(result))


def run_admin_call_or_exit(callback):
    try:
        return callback()
    except PermissionDenied as exc:
        raise click.ClickException(
            "GA4 Admin API permission denied. Confirm the service account has access to the "
            "property and that the Google Analytics Admin API is enabled for its Google Cloud "
            f"project. Google returned: {exc}"
        ) from exc
    except InvalidArgument as exc:
        raise click.ClickException(f"GA4 Admin API rejected the request. {exc}") from exc
    except NotFound as exc:
        raise click.ClickException(f"GA4 Admin API resource not found. {exc}") from exc
    except GoogleAPICallError as exc:
        raise click.ClickException(f"GA4 Admin API call failed: {exc}") from exc


def run_search_console_call_or_exit(callback):
    try:
        return callback()
    except DefaultCredentialsError as exc:
        raise click.ClickException(
            "Search Console credentials were not found. Pass --credentials or set "
            "GA4_SERVICE_ACCOUNT_JSON / GOOGLE_APPLICATION_CREDENTIALS."
        ) from exc
    except SearchConsoleAPIError as exc:
        if exc.status_code in {401, 403}:
            raise click.ClickException(
                "Search Console API access failed. Confirm the service account has access to "
                "the Search Console property and that the Google Search Console API is enabled "
                f"for its Google Cloud project. Google returned: {exc}"
            ) from exc
        raise click.ClickException(f"Search Console API call failed: {exc}") from exc


def property_option(function):
    return click.option(
        "--property-id",
        help="GA4 property id. Falls back to GA4_PROPERTY_ID.",
    )(function)


def credentials_option(function):
    return click.option(
        "--credentials",
        "credentials_path",
        type=click.Path(dir_okay=False, path_type=Path),
        help=(
            "Service account JSON path. Falls back to GA4_SERVICE_ACCOUNT_JSON or "
            "GOOGLE_APPLICATION_CREDENTIALS."
        ),
    )(function)


def site_url_option(function):
    return click.option(
        "--site-url",
        help=(
            "Search Console site URL. Falls back to SEARCH_CONSOLE_SITE_URL. "
            "Use sc-domain:example.com for a domain property."
        ),
    )(function)


def format_option(function):
    return click.option(
        "--format",
        "output_format",
        type=click.Choice(["table", "json"], case_sensitive=False),
        default="table",
        show_default=True,
    )(function)


@click.group()
@click.option("--debug", is_flag=True, help="Enable verbose debug logging.")
def cli(debug: bool) -> None:
    """Local analytics and Search Console tooling."""
    load_local_env()
    if debug:
        logging.getLogger().setLevel(logging.DEBUG)


@cli.command("smoke-test")
@property_option
@credentials_option
@click.option("--days", default=7, show_default=True, type=int, help="Days of lookback.")
@click.option("--limit", default=10, show_default=True, type=int, help="Max rows to return.")
@format_option
def smoke_test(
    property_id: str | None,
    credentials_path: Path | None,
    days: int,
    limit: int,
    output_format: str,
) -> None:
    """Run a simple GA4 report to verify access."""
    config = resolve_runtime_config(property_id, credentials_path)
    spec = ReportSpec(
        dimensions=("city",),
        metrics=("activeUsers",),
        start_date=f"{days}daysAgo",
        end_date="today",
        limit=limit,
    )
    run_report_or_exit(config, spec, output_format)


@cli.command("run-report")
@property_option
@credentials_option
@click.option(
    "--dimension",
    "dimensions",
    multiple=True,
    help="Dimension to include. Repeat the flag for multiple dimensions.",
)
@click.option(
    "--metric",
    "metrics",
    multiple=True,
    required=True,
    help="Metric to include. Repeat the flag for multiple metrics.",
)
@click.option("--start-date", default="30daysAgo", show_default=True)
@click.option("--end-date", default="today", show_default=True)
@click.option("--limit", default=25, show_default=True, type=int)
@format_option
def run_report_command(
    property_id: str | None,
    credentials_path: Path | None,
    dimensions: tuple[str, ...],
    metrics: tuple[str, ...],
    start_date: str,
    end_date: str,
    limit: int,
    output_format: str,
) -> None:
    """Run an ad hoc GA4 Data API report."""
    config = resolve_runtime_config(property_id, credentials_path)
    spec = ReportSpec(
        dimensions=dimensions,
        metrics=metrics,
        start_date=start_date,
        end_date=end_date,
        limit=limit,
    )
    run_report_or_exit(config, spec, output_format)


@cli.command("check-test-filter")
@property_option
@credentials_option
@click.option("--start-date", default="3daysAgo", show_default=True)
@click.option("--end-date", default="today", show_default=True)
@click.option("--limit", default=100, show_default=True, type=int)
@click.option(
    "--dimension",
    "extra_dimensions",
    multiple=True,
    help=(
        "Optional extra dimension for row breakdown, such as eventName or pagePath. "
        "The command always includes testDataFilterName."
    ),
)
@click.option(
    "--include-rows",
    is_flag=True,
    help="Include the raw GA4 rows after the filter-health summary.",
)
@format_option
def check_test_filter_command(
    property_id: str | None,
    credentials_path: Path | None,
    start_date: str,
    end_date: str,
    limit: int,
    extra_dimensions: tuple[str, ...],
    include_rows: bool,
    output_format: str,
) -> None:
    """Check whether GA4 testing-filter rows are matching recent internal traffic."""
    config = resolve_runtime_config(property_id, credentials_path)
    dimensions = ("testDataFilterName",) + tuple(
        dimension for dimension in extra_dimensions if dimension != "testDataFilterName"
    )
    spec = ReportSpec(
        dimensions=dimensions,
        metrics=("eventCount",),
        start_date=start_date,
        end_date=end_date,
        limit=limit,
    )
    response = fetch_report_or_exit(config, spec)
    headers, rows = response_to_rows(response)
    summary = summarize_test_filter_rows(headers, rows)

    if output_format == "json":
        payload = {
            "property_id": config.property_id,
            "start_date": start_date,
            "end_date": end_date,
            "summary": {
                "status": "matched" if summary.has_matches else "no matched filter rows found",
                "total_event_count": summary.total_event_count,
                "matched_event_count": summary.matched_event_count,
                "unmatched_event_count": summary.unmatched_event_count,
                "unmatched_rate": summary.unmatched_rate,
                "matched_filters": [
                    {"name": bucket.name, "event_count": bucket.event_count}
                    for bucket in summary.matched_filters
                ],
            },
        }
        if include_rows:
            payload["headers"] = headers
            payload["rows"] = rows
        click.echo(json.dumps(payload, indent=2))
        return

    output_blocks = [
        render_table(["field", "value"], render_test_filter_summary_rows(summary)),
        (
            "Interpretation: non-zero matched filter rows mean the testing filter is "
            "seeing at least some internal-tagged traffic."
            if summary.has_matches
            else "Interpretation: every recent row is still '(not set)', so the testing "
            "filter does not appear to be matching this report slice yet."
        ),
    ]

    if include_rows:
        output_blocks.extend(
            [
                "",
                "Raw rows",
                render_table(headers, rows),
            ]
        )

    click.echo("\n".join(output_blocks))


@cli.group("admin")
def admin_group() -> None:
    """Inspect and update GA4 Admin API resources."""


@admin_group.command("inspect-property")
@property_option
@credentials_option
@format_option
def inspect_property_command(
    property_id: str | None,
    credentials_path: Path | None,
    output_format: str,
) -> None:
    """Show GA4 property details such as timezone and currency."""
    config = resolve_runtime_config(property_id, credentials_path)
    property_resource = run_admin_call_or_exit(
        lambda: inspect_property(config.property_id, config.credentials_path)
    )
    click.echo(render_admin_resource(output_format, property_resource, property_rows(property_resource)))


@admin_group.command("list-streams")
@property_option
@credentials_option
@format_option
def list_streams_command(
    property_id: str | None,
    credentials_path: Path | None,
    output_format: str,
) -> None:
    """List the GA4 data streams for the configured property."""
    config = resolve_runtime_config(property_id, credentials_path)
    streams = run_admin_call_or_exit(
        lambda: list_data_streams(config.property_id, config.credentials_path)
    )
    if output_format == "json":
        click.echo(json.dumps([message_to_dict(stream) for stream in streams], indent=2))
        return

    click.echo(
        render_table(
            [
                "name",
                "display_name",
                "type",
                "measurement_id",
                "default_uri",
                "create_time",
                "update_time",
            ],
            stream_rows(streams),
        )
    )


@admin_group.command("get-enhanced-measurement")
@property_option
@credentials_option
@click.option(
    "--stream",
    help=(
        "Data stream id or full resource name. If omitted, the command auto-selects the "
        "only web stream on the property."
    ),
)
@format_option
def get_enhanced_measurement_command(
    property_id: str | None,
    credentials_path: Path | None,
    stream: str | None,
    output_format: str,
) -> None:
    """Show enhanced measurement settings for a web data stream."""
    config = resolve_runtime_config(property_id, credentials_path)
    settings = run_admin_call_or_exit(
        lambda: get_enhanced_measurement_settings(
            config.property_id,
            config.credentials_path,
            stream=stream,
        )
    )
    click.echo(
        render_admin_resource(output_format, settings, enhanced_measurement_rows(settings))
    )


@admin_group.command("list-access-bindings")
@property_option
@credentials_option
@format_option
def list_access_bindings_command(
    property_id: str | None,
    credentials_path: Path | None,
    output_format: str,
) -> None:
    """List property access bindings and roles."""
    config = resolve_runtime_config(property_id, credentials_path)
    bindings = run_admin_call_or_exit(
        lambda: list_access_bindings(config.property_id, config.credentials_path)
    )
    if output_format == "json":
        click.echo(json.dumps([message_to_dict(binding) for binding in bindings], indent=2))
        return

    click.echo(
        render_table(
            ["user", "roles", "name"],
            access_binding_rows(bindings),
        )
    )


@admin_group.command("set-timezone")
@property_option
@credentials_option
@click.argument("time_zone")
@click.option(
    "--apply",
    is_flag=True,
    help="Write the timezone change to GA4. Without this flag, the command is a dry run.",
)
@format_option
def set_timezone_command(
    property_id: str | None,
    credentials_path: Path | None,
    time_zone: str,
    apply: bool,
    output_format: str,
) -> None:
    """Preview or update the GA4 property timezone."""
    config = resolve_runtime_config(property_id, credentials_path)
    result = run_admin_call_or_exit(
        lambda: set_property_timezone(
            config.property_id,
            time_zone,
            config.credentials_path,
            apply=apply,
        )
    )
    click.echo(render_timezone_result(output_format, result))


@cli.group("search-console")
def search_console_group() -> None:
    """Inspect and report on Google Search Console data."""


@search_console_group.command("list-sites")
@credentials_option
@format_option
def list_sites_command(
    credentials_path: Path | None,
    output_format: str,
) -> None:
    """List Search Console properties visible to the configured credentials."""
    resolved_credentials_path = resolve_credentials_or_exit(credentials_path)
    sites = run_search_console_call_or_exit(lambda: list_sites(resolved_credentials_path))
    if output_format == "json":
        click.echo(json.dumps(sites, indent=2))
        return

    click.echo(render_table(["site_url", "permission_level"], site_rows(sites)))


@search_console_group.command("query")
@credentials_option
@site_url_option
@click.option("--start-date", required=True, help="Start date in YYYY-MM-DD format.")
@click.option("--end-date", required=True, help="End date in YYYY-MM-DD format.")
@click.option(
    "--dimension",
    "dimensions",
    multiple=True,
    help="Dimension to include. Repeat for multiple dimensions.",
)
@click.option("--row-limit", default=25, show_default=True, type=int)
@click.option(
    "--aggregation-type",
    type=click.Choice(["auto", "byPage", "byProperty"], case_sensitive=False),
)
@click.option(
    "--search-type",
    help="Optional Search Console search type, such as web or discover.",
)
@format_option
def search_console_query_command(
    credentials_path: Path | None,
    site_url: str | None,
    start_date: str,
    end_date: str,
    dimensions: tuple[str, ...],
    row_limit: int,
    aggregation_type: str | None,
    search_type: str | None,
    output_format: str,
) -> None:
    """Run an ad hoc Search Console performance query."""
    resolved_credentials_path = resolve_credentials_or_exit(credentials_path)
    resolved_site_url = resolve_site_url_or_exit(site_url)
    response = run_search_console_call_or_exit(
        lambda: query_search_analytics(
            SearchConsoleQuerySpec(
                site_url=resolved_site_url,
                start_date=start_date,
                end_date=end_date,
                dimensions=dimensions,
                row_limit=row_limit,
                aggregation_type=aggregation_type,
                search_type=search_type,
            ),
            resolved_credentials_path,
        )
    )

    if output_format == "json":
        click.echo(json.dumps(response, indent=2))
        return

    click.echo(render_search_analytics_table(response, dimensions))


@search_console_group.command("baseline-report")
@credentials_option
@site_url_option
@click.option(
    "--start-date",
    help="Start date in YYYY-MM-DD format. Defaults to a 28-day window ending 2 days ago.",
)
@click.option(
    "--end-date",
    help="End date in YYYY-MM-DD format. Defaults to a 28-day window ending 2 days ago.",
)
@click.option("--row-limit", default=10, show_default=True, type=int)
@click.option(
    "--output",
    "output_path",
    type=click.Path(dir_okay=False, path_type=Path),
    help="Optional output path for the Markdown report.",
)
def baseline_report_command(
    credentials_path: Path | None,
    site_url: str | None,
    start_date: str | None,
    end_date: str | None,
    row_limit: int,
    output_path: Path | None,
) -> None:
    """Write a simple Search Console baseline report in Markdown."""
    resolved_credentials_path = resolve_credentials_or_exit(credentials_path)
    resolved_site_url = resolve_site_url_or_exit(site_url)
    if not start_date or not end_date:
        default_start_date, default_end_date = default_window()
        start_date = start_date or default_start_date
        end_date = end_date or default_end_date

    report = run_search_console_call_or_exit(
        lambda: build_baseline_report(
            resolved_site_url,
            start_date,
            end_date,
            resolved_credentials_path,
            row_limit=row_limit,
        )
    )

    final_output_path = output_path or (
        repo_root()
        / "tools"
        / "analytics"
        / "reports"
        / f"{date.today().isoformat()}-search-console-baseline.md"
    )
    final_output_path.parent.mkdir(parents=True, exist_ok=True)
    final_output_path.write_text(report + "\n", encoding="utf-8")
    click.echo(str(final_output_path))


@search_console_group.command("submit-sitemap")
@credentials_option
@site_url_option
@click.option(
    "--sitemap-url",
    help=(
        "Full sitemap URL to submit. Defaults to the standard sitemap location for the "
        "configured Search Console property."
    ),
)
@format_option
def submit_sitemap_command(
    credentials_path: Path | None,
    site_url: str | None,
    sitemap_url: str | None,
    output_format: str,
) -> None:
    """Submit a sitemap to Search Console for the configured property."""
    resolved_credentials_path = resolve_credentials_or_exit(credentials_path)
    resolved_site_url = resolve_site_url_or_exit(site_url)
    normalized_sitemap_url = normalize_sitemap_url(
        sitemap_url,
        site_url=resolved_site_url,
    )

    result = run_search_console_call_or_exit(
        lambda: submit_sitemap(
            resolved_site_url,
            normalized_sitemap_url,
            resolved_credentials_path,
        )
    )

    if output_format == "json":
        click.echo(json.dumps(result, indent=2))
        return

    click.echo(
        render_table(
            ["field", "value"],
            [
                ["site_url", result["siteUrl"]],
                ["sitemap_url", result["sitemapUrl"]],
                ["status", result["status"]],
                ["default_sitemap_url", default_sitemap_url(resolved_site_url)],
            ],
        )
    )


if __name__ == "__main__":
    cli()
