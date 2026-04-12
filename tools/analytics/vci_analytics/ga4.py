from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path

from google.analytics.data_v1beta import BetaAnalyticsDataClient
from google.analytics.data_v1beta.types import (
    DateRange,
    Dimension,
    Metric,
    RunReportRequest,
    RunReportResponse,
)
from google.oauth2 import service_account


READ_ONLY_SCOPES = ["https://www.googleapis.com/auth/analytics.readonly"]
UNMATCHED_TEST_FILTER_NAME = "(not set)"


@dataclass(frozen=True)
class ReportSpec:
    dimensions: tuple[str, ...]
    metrics: tuple[str, ...]
    start_date: str
    end_date: str
    limit: int = 10


@dataclass(frozen=True)
class TestFilterBucket:
    name: str
    event_count: int


@dataclass(frozen=True)
class TestFilterHealthSummary:
    total_event_count: int
    matched_event_count: int
    unmatched_event_count: int
    matched_filters: tuple[TestFilterBucket, ...]
    unmatched_filter_name: str = UNMATCHED_TEST_FILTER_NAME

    @property
    def has_matches(self) -> bool:
        return self.matched_event_count > 0

    @property
    def matched_row_count(self) -> int:
        return len(self.matched_filters)

    @property
    def unmatched_rate(self) -> float:
        if self.total_event_count == 0:
            return 0.0
        return self.unmatched_event_count / self.total_event_count


def build_client(credentials_path: Path | None = None) -> BetaAnalyticsDataClient:
    if credentials_path is None:
        return BetaAnalyticsDataClient()

    credentials = service_account.Credentials.from_service_account_file(
        str(credentials_path),
        scopes=READ_ONLY_SCOPES,
    )
    return BetaAnalyticsDataClient(credentials=credentials)


def run_report(
    property_id: str,
    spec: ReportSpec,
    credentials_path: Path | None = None,
) -> RunReportResponse:
    client = build_client(credentials_path)
    request = RunReportRequest(
        property=f"properties/{property_id}",
        dimensions=[Dimension(name=name) for name in spec.dimensions],
        metrics=[Metric(name=name) for name in spec.metrics],
        date_ranges=[DateRange(start_date=spec.start_date, end_date=spec.end_date)],
        limit=spec.limit,
    )
    return client.run_report(request)


def response_to_rows(response: RunReportResponse) -> tuple[list[str], list[list[str]]]:
    headers = [header.name for header in response.dimension_headers] + [
        header.name for header in response.metric_headers
    ]

    rows: list[list[str]] = []
    for row in response.rows:
        rows.append(
            [value.value for value in row.dimension_values]
            + [value.value for value in row.metric_values]
        )

    return headers, rows


def response_to_dict(response: RunReportResponse) -> dict[str, object]:
    headers, rows = response_to_rows(response)
    return {
        "dimension_headers": [header.name for header in response.dimension_headers],
        "metric_headers": [header.name for header in response.metric_headers],
        "headers": headers,
        "rows": rows,
        "row_count": response.row_count,
        "metadata": {
            "currency_code": response.metadata.currency_code,
            "time_zone": response.metadata.time_zone,
        },
    }


def render_table(headers: list[str], rows: list[list[str]]) -> str:
    if not headers:
        return "(no columns)"

    widths = [len(header) for header in headers]
    for row in rows:
        for idx, cell in enumerate(row):
            widths[idx] = max(widths[idx], len(cell))

    def format_row(values: list[str]) -> str:
        return " | ".join(value.ljust(widths[idx]) for idx, value in enumerate(values))

    separator = "-+-".join("-" * width for width in widths)
    body = [format_row(row) for row in rows] if rows else ["(no rows)"]

    return "\n".join([format_row(headers), separator, *body])


def summarize_test_filter_rows(
    headers: list[str],
    rows: list[list[str]],
    *,
    filter_dimension: str = "testDataFilterName",
    count_metric: str = "eventCount",
    unmatched_filter_name: str = UNMATCHED_TEST_FILTER_NAME,
) -> TestFilterHealthSummary:
    try:
        filter_idx = headers.index(filter_dimension)
    except ValueError as exc:
        raise ValueError(
            f"Missing required filter dimension column: {filter_dimension}"
        ) from exc

    try:
        count_idx = headers.index(count_metric)
    except ValueError as exc:
        raise ValueError(f"Missing required metric column: {count_metric}") from exc

    matched_by_filter: dict[str, int] = {}
    unmatched_event_count = 0
    total_event_count = 0

    for row in rows:
        filter_name = row[filter_idx]
        event_count = int(float(row[count_idx]))
        total_event_count += event_count

        if not filter_name or filter_name == unmatched_filter_name:
            unmatched_event_count += event_count
            continue

        matched_by_filter[filter_name] = matched_by_filter.get(filter_name, 0) + event_count

    matched_filters = tuple(
        TestFilterBucket(name=name, event_count=event_count)
        for name, event_count in sorted(
            matched_by_filter.items(),
            key=lambda item: (-item[1], item[0]),
        )
    )
    matched_event_count = sum(bucket.event_count for bucket in matched_filters)

    return TestFilterHealthSummary(
        total_event_count=total_event_count,
        matched_event_count=matched_event_count,
        unmatched_event_count=unmatched_event_count,
        matched_filters=matched_filters,
        unmatched_filter_name=unmatched_filter_name,
    )


def render_test_filter_summary_rows(summary: TestFilterHealthSummary) -> list[list[str]]:
    matched_filters = ", ".join(
        f"{bucket.name} ({bucket.event_count})" for bucket in summary.matched_filters
    )
    if not matched_filters:
        matched_filters = "(none)"

    return [
        ["status", "matched" if summary.has_matches else "no matched filter rows found"],
        ["total_event_count", str(summary.total_event_count)],
        ["matched_event_count", str(summary.matched_event_count)],
        ["unmatched_event_count", str(summary.unmatched_event_count)],
        ["unmatched_rate", f"{summary.unmatched_rate:.1%}"],
        ["matched_filters", matched_filters],
    ]
