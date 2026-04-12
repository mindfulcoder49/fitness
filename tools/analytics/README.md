# Analytics Tools

Standalone local tooling for Google Analytics 4 and Google Search Console access and reporting.

This package is intentionally separate from the Laravel app:

- it does not need to run on Hostinger
- it can use local-only credentials safely
- it is a better home for founder/agent ops scripts
- it leaves room for future non-production tooling such as Search Console pulls and frontend test support

## What It Does Today

- validates local GA4 configuration
- runs a smoke-test report against a GA4 property
- runs ad hoc GA4 Data API reports from the command line
- checks whether the GA4 testing filter is actually matching internal traffic rows
- inspects GA4 Admin API property settings, streams, enhanced measurement, and access bindings
- previews or applies GA4 property timezone changes with an explicit `--apply` flag
- lists Search Console properties visible to the configured service account
- runs ad hoc Search Console performance queries
- submits sitemaps to Search Console
- writes a simple Search Console baseline report in Markdown

## Setup

1. Create a Google Cloud project or choose an existing one.
2. Enable the Google Analytics Data API, Google Analytics Admin API, and Google Search Console API for that project.
3. Create a service account and download its JSON key.
4. In Google Analytics, add the service account email to the GA4 property.
5. Install this package in a local virtual environment.
6. Run the smoke test.

## Google-Side Access

The official GA Data API quickstart says to grant the service account access to the Google Analytics property and use the credentials referenced by `GOOGLE_APPLICATION_CREDENTIALS`.

For the GA4 property permission:

- an existing GA Administrator has to add the user at the property level
- Google documents that adding or modifying users requires the Administrator role
- Google documents that the Viewer role can see settings and data and can see shared assets via the APIs

For this tool, `Viewer` should be sufficient for read-only reporting. If you want more room for UI-side analysis work, `Analyst` is also reasonable.

Official references:

- Google Analytics Data API quickstart: https://developers.google.com/analytics/devguides/reporting/data/v1/quickstart
- Google Analytics Admin API quickstart: https://developers.google.com/analytics/devguides/config/admin/v1/quickstart
- GA4 user management: https://support.google.com/analytics/answer/9305788
- GA4 roles and restrictions: https://support.google.com/analytics/answer/9305587

## Local Config

You can put these in the repo root `.env` or in `tools/analytics/.env`.

Example values are in [example.env](example.env).

Supported environment variables:

- `GA4_PROPERTY_ID`
- `GA4_SERVICE_ACCOUNT_JSON`
- `SEARCH_CONSOLE_SITE_URL`
- `GOOGLE_APPLICATION_CREDENTIALS`

`GA4_SERVICE_ACCOUNT_JSON` is the preferred local setting for this package. If it is not set, the tool falls back to `GOOGLE_APPLICATION_CREDENTIALS`.

For Search Console, set the verified property identifier in `SEARCH_CONSOLE_SITE_URL`.

Examples:

- `SEARCH_CONSOLE_SITE_URL=sc-domain:vibecodeinternational.com`
- `SEARCH_CONSOLE_SITE_URL=https://vibecodeinternational.com/`

## Install

```bash
cd tools/analytics
python3 -m venv .venv
source .venv/bin/activate
python -m pip install --upgrade pip
python -m pip install -e ".[dev]"
```

## Usage

Smoke test:

```bash
cd tools/analytics
source .venv/bin/activate
vci-analytics smoke-test
```

Ad hoc report:

```bash
cd tools/analytics
source .venv/bin/activate
vci-analytics run-report \
  --dimension sessionDefaultChannelGroup \
  --dimension pagePath \
  --metric activeUsers \
  --metric sessions \
  --start-date 30daysAgo \
  --end-date today \
  --limit 20
```

JSON output:

```bash
vci-analytics run-report \
  --metric activeUsers \
  --start-date 7daysAgo \
  --end-date today \
  --format json
```

Check whether the GA4 testing filter is matching recent internal traffic:

```bash
vci-analytics check-test-filter \
  --start-date 3daysAgo \
  --end-date today \
  --include-rows
```

Add an event breakdown when you need more detail:

```bash
vci-analytics check-test-filter \
  --dimension eventName \
  --start-date 3daysAgo \
  --end-date today
```

Inspect property settings:

```bash
vci-analytics admin inspect-property
```

List property data streams:

```bash
vci-analytics admin list-streams
```

Inspect enhanced measurement for the default web stream:

```bash
vci-analytics admin get-enhanced-measurement
```

List property access bindings:

```bash
vci-analytics admin list-access-bindings
```

Preview and then apply a timezone change:

```bash
vci-analytics admin set-timezone America/New_York
vci-analytics admin set-timezone America/New_York --apply
```

List Search Console properties:

```bash
vci-analytics search-console list-sites
```

Run a Search Console query:

```bash
vci-analytics search-console query \
  --site-url sc-domain:vibecodeinternational.com \
  --start-date 2026-02-23 \
  --end-date 2026-03-22 \
  --dimension page \
  --row-limit 10
```

Write a baseline Search Console report:

```bash
vci-analytics search-console baseline-report \
  --site-url sc-domain:vibecodeinternational.com
```

Submit the production sitemap to Search Console:

```bash
vci-analytics search-console submit-sitemap \
  --site-url sc-domain:vibecodeinternational.com \
  --sitemap-url https://vibecodeinternational.com/sitemap.xml
```

## Post-Change Validation

When analytics instrumentation changed recently, do not start with a `30daysAgo` GA4 report and treat it as proof of current behavior.

Use this workflow instead:

1. Identify the production deploy or GA4 admin change time first.
2. Run a narrow post-change report for the last 24 to 72 hours in the GA4 property timezone.
3. Check both event delivery and whether the relevant pages actually had traffic in that same window.
4. Only then run a broader 14 to 30 day report for trend context, and label it as mixed historical data if it spans pre-change traffic.

Recommended first-pass commands after a recent analytics change:

```bash
cd tools/analytics
source .venv/bin/activate
vci-analytics run-report \
  --dimension date \
  --dimension eventName \
  --metric eventCount \
  --start-date 3daysAgo \
  --end-date today \
  --limit 200 \
  --format json
```

```bash
vci-analytics run-report \
  --dimension pagePath \
  --metric activeUsers \
  --metric sessions \
  --metric screenPageViews \
  --start-date 3daysAgo \
  --end-date today \
  --limit 25 \
  --format json
```

Interpretation guardrails:
- a broad window can still be dominated by historical legacy events such as old `click_event` traffic
- a newly added event is not evidence of failure if the relevant page had little or no post-change traffic yet
- GA4 uses the property timezone, so interpret recent windows in `America/New_York` for this property
- state the exact window used in any ops summary or system-check response
- if `check-test-filter` shows only `'(not set)'`, do not treat recent totals as clean public demand yet

## Notes

- `GA4_PROPERTY_ID` can be either a bare numeric property id like `123456789` or `properties/123456789`.
- `admin set-timezone` is dry-run by default. It only writes when `--apply` is passed.
- `admin get-enhanced-measurement` auto-selects the only web stream on the property. If the property has multiple web streams, pass `--stream`.
- If Admin API calls fail with `SERVICE_DISABLED`, enable `analyticsadmin.googleapis.com` in the Google Cloud project that owns the service-account key, then retry after propagation.
- If Search Console API calls fail with `SERVICE_DISABLED`, enable `searchconsole.googleapis.com` in the Google Cloud project that owns the service-account key, then retry after propagation.
- `search-console submit-sitemap` uses the read/write Search Console scope. The service account must have enough property access to submit sitemaps.
- If you later want automation for weekly summaries, channel reports, or landing-page exports, extend this package instead of adding that logic to Laravel.
