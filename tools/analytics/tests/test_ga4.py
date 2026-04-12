import pytest

from vci_analytics.ga4 import (
    render_test_filter_summary_rows,
    summarize_test_filter_rows,
)


def test_summarize_test_filter_rows_detects_matched_filter_rows() -> None:
    headers = ["testDataFilterName", "eventName", "eventCount"]
    rows = [
        ["(not set)", "page_view", "12"],
        ["Internal traffic", "page_view", "5"],
        ["Internal traffic", "click", "3"],
        ["Office QA", "page_view", "2"],
    ]

    summary = summarize_test_filter_rows(headers, rows)

    assert summary.total_event_count == 22
    assert summary.matched_event_count == 10
    assert summary.unmatched_event_count == 12
    assert summary.has_matches is True
    assert [(bucket.name, bucket.event_count) for bucket in summary.matched_filters] == [
        ("Internal traffic", 8),
        ("Office QA", 2),
    ]


def test_summarize_test_filter_rows_handles_only_unmatched_rows() -> None:
    headers = ["testDataFilterName", "eventCount"]
    rows = [["(not set)", "7"], ["", "3"]]

    summary = summarize_test_filter_rows(headers, rows)

    assert summary.total_event_count == 10
    assert summary.matched_event_count == 0
    assert summary.unmatched_event_count == 10
    assert summary.has_matches is False
    assert summary.matched_filters == ()
    assert summary.unmatched_rate == 1.0


def test_summarize_test_filter_rows_requires_filter_dimension() -> None:
    with pytest.raises(ValueError, match="Missing required filter dimension column"):
        summarize_test_filter_rows(["eventName", "eventCount"], [["page_view", "7"]])


def test_summarize_test_filter_rows_requires_event_count_metric() -> None:
    with pytest.raises(ValueError, match="Missing required metric column"):
        summarize_test_filter_rows(["testDataFilterName"], [["(not set)"]])


def test_render_test_filter_summary_rows_renders_human_summary() -> None:
    summary = summarize_test_filter_rows(
        ["testDataFilterName", "eventCount"],
        [["Internal traffic", "4"], ["(not set)", "6"]],
    )

    assert render_test_filter_summary_rows(summary) == [
        ["status", "matched"],
        ["total_event_count", "10"],
        ["matched_event_count", "4"],
        ["unmatched_event_count", "6"],
        ["unmatched_rate", "60.0%"],
        ["matched_filters", "Internal traffic (4)"],
    ]
