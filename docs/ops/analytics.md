# Analytics

This playbook covers local-only analytics tooling, production instrumentation expectations, and the workflow for validating analytics changes.

## Current State

As checked on April 11, 2026:

- no frontend analytics instrumentation is present in the repo
- no `gtag`, `dataLayer`, `GTM-`, or GA4-specific code was found in `resources/`, `app/`, `public/`, `config/`, or `routes/`
- local analytics tooling is available in [tools/analytics](../../tools/analytics/README.md)
- founder-action queue tooling is available in [tools/exoskeleton](../../tools/exoskeleton/README.md)

This means the repo is ready for local analytics operations and validation tooling, but production analytics setup still requires explicit instrumentation and Google-side configuration.

## Local Tooling

The analytics workspace is intentionally separate from Laravel and Hostinger.

Install:

```bash
cd tools/analytics
python3 -m venv .venv
source .venv/bin/activate
python -m pip install --upgrade pip
python -m pip install -e ".[dev]"
```

Configuration lives in the repo root `.env` or `tools/analytics/.env`.

Relevant environment variables:

- `GA4_PROPERTY_ID`
- `GA4_SERVICE_ACCOUNT_JSON`
- `SEARCH_CONSOLE_SITE_URL`
- `GOOGLE_APPLICATION_CREDENTIALS`

Default Search Console property value for this app:

```bash
SEARCH_CONSOLE_SITE_URL=sc-domain:vibecodeinternational.com
```

## Core Commands

Smoke test:

```bash
cd tools/analytics
source .venv/bin/activate
vci-analytics smoke-test
```

GA4 report:

```bash
vci-analytics run-report \
  --dimension pagePath \
  --metric activeUsers \
  --metric sessions \
  --start-date 7daysAgo \
  --end-date today \
  --limit 25
```

Search Console baseline:

```bash
vci-analytics search-console baseline-report \
  --site-url sc-domain:vibecodeinternational.com
```

Submit sitemap:

```bash
vci-analytics search-console submit-sitemap \
  --site-url sc-domain:vibecodeinternational.com \
  --sitemap-url https://vibecodeinternational.com/sitemap.xml
```

## Change Workflow

When analytics instrumentation changes:

1. implement the code change locally
2. deploy it
3. validate with a narrow 24 to 72 hour post-change window first
4. only then use broader 14 to 30 day windows for trend context

Do not treat a broad historical report as proof that a fresh analytics change worked or failed.

## Founder-Required Actions

Use `tools/exoskeleton` when analytics work requires external actions such as:

- creating or locating the GA4 property
- granting GA4 property access to a service account
- granting Search Console property access
- entering service-account credentials locally
- changing Google-side admin settings

These queue items should include exact URLs, exact property identifiers, exact email addresses, and exact success criteria.
