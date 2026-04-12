# Operating System

This document defines the operating loop for Vibecode International with stability, deployability, and trustworthy measurement as the primary constraints.

## Goal

Build a repeatable operating system where:

- app changes are small, reversible, and verifiable
- production deploys are routine instead of improvised
- queue-driven features such as native AIUX runs stay observable and supportable
- analytics can be instrumented and validated without storing Google credentials in production
- founder-required external actions are clearly separated from agent-drivable repo and server work

## Current Priorities

1. Native AIUX Stability
   Keep the queued Laravel AI plus Playwright flow reliable before expanding its action vocabulary.

2. Victory Games Product Flow
   Preserve the logged-in victor and app workflow while extending it for native runs.

3. Hostinger Deployment Hygiene
   Standardize preflight, deploy, cache hygiene, and production smoke checks.

4. Analytics Foundation
   Keep measurement setup explicit and local-tool-driven until production instrumentation is ready.

## Domain Playbooks

- [Backend Administration](./backend-administration.md)
- [Analytics](./analytics.md)

## Operating Rules

- Prefer production evidence over local assumptions for broad health checks.
- Prefer local verification before any production deploy attempt.
- Use queue-backed flows for long-running work; do not block HTTP requests with agentic browser runs.
- Treat Playwright runtime installation, queue worker state, and database migrations as first-class deploy concerns for native AIUX.
- Prefer narrow, recent validation windows when checking analytics after a change.
- Keep operational scripts in `scripts/` and local-only tooling in `tools/`.
- Do not commit credentials, service-account JSON files, or local tool databases.
- Keep the repo docs aligned with the actual Hostinger layout and deploy flow.

## Current Confirmed Facts

- This app shares the same Hostinger account as the Boston project, but the live domain here is `vibecodeinternational.com`.
- The current remote app path is `/home/u353344964/domains/vibecodeinternational.com/fitness`.
- The current public web root is `/home/u353344964/domains/vibecodeinternational.com/public_html`.
- The current remote deploy script is `/home/u353344964/vibecodeinternationaldeploy.sh`.
- As checked on April 11, 2026, live public routes `/`, `/up`, `/victory-games`, and `/login` return HTTP `200`.
- As checked on April 11, 2026, production `php artisan about` via the default shell `php` reports Laravel `12.32.5`, PHP `8.2.30`, environment `local`, debug enabled, queue driver `database`, routes cached, and views cached.
- As checked on April 11, 2026, PHP `8.3.30` is available on Hostinger at `/opt/alt/php83/usr/bin/php`, and this repo's deploy and Artisan flows should use that binary explicitly.
- The current remote deploy script fetches `origin/main`, hard-resets to it, installs Composer dependencies, builds frontend assets, and syncs public assets. It does not run migrations or the Playwright browser runtime install command.
- No frontend GA4 instrumentation is currently present in the app codebase.
- As checked on April 11, 2026, production `.env` does not contain `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, or `GEMINI_API_KEY`, so real native AIUX agent runs are blocked until one provider key is configured.
- Local-only analytics and founder queue tooling are available under `tools/analytics` and `tools/exoskeleton`.

## Delivery Loop

Standard delivery sequence for code changes:

- make the change locally
- run the relevant local verification
- run `./scripts/prod-preflight.sh`
- if preflight passes, run `./scripts/deploy-hostinger.sh`
- run production smoke checks
- if analytics changed, run local analytics verification against the live property after deploy

Use this loop conservatively:

- do not deploy unverified changes
- do not ignore a failed PHP-binary or environment preflight
- do not assume queued features work in production until the queue worker and Playwright runtime are verified

## Founder Action Queue

Use the local founder action queue in `tools/exoskeleton` when work reveals an external action the founder must perform.

Examples:

- granting Google Analytics or Search Console access
- changing DNS or Hostinger control-panel settings
- approving high-risk production changes
- entering credentials or secrets that should not live in git

Queue items must be self-contained and include exact values, exact copy, or exact step text where applicable.

## Approval Boundaries

Low-risk, generally agent-drivable:

- repo documentation and operational script updates
- local verification and test additions
- low-risk production smoke checks
- routine deploys after successful verification and a passing preflight
- analytics tooling setup that stays local-only

Medium-risk, usually founder review first:

- production instrumentation changes
- queue or scheduler process changes
- major Victory Games UI flow changes
- deploys that include schema changes or new runtime dependencies

High-risk, founder approval required:

- domain, billing, DNS, or control-panel changes
- creating or modifying external production accounts
- destructive data operations
- unclear production deploys where rollback is uncertain
