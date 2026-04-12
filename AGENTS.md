# AGENTS.md

Repository guidance for coding agents working in this project.

## Session Start

At the beginning of every session, before doing substantial work:

1. Read [docs/ops/OPERATING_SYSTEM.md](docs/ops/OPERATING_SYSTEM.md).
2. Read the relevant playbook in `docs/ops/` for the domain you are touching:
   - [docs/ops/backend-administration.md](docs/ops/backend-administration.md)
   - [docs/ops/analytics.md](docs/ops/analytics.md)
3. Use [CLAUDE.md](CLAUDE.md) for repo architecture, commands, and implementation details.
4. If the task touches local ops tooling, also read the relevant workspace doc:
   - [tools/analytics/README.md](tools/analytics/README.md) for GA4 and Search Console work
   - [tools/exoskeleton/README.md](tools/exoskeleton/README.md) for founder action queue work

## Operating Expectations

- Treat [docs/ops/OPERATING_SYSTEM.md](docs/ops/OPERATING_SYSTEM.md) as the top-level operating policy.
- Prefer small, reversible, well-instrumented changes over large speculative changes.
- Treat broad status requests as production-first requests unless the user explicitly asks for local or dev state.
- Before deployment work, use:
  - `./scripts/local-check.sh quick` or `./scripts/local-check.sh full`
  - `./scripts/prod-preflight.sh`
  - `./scripts/deploy-hostinger.sh`
  - `./scripts/prod-smoke.sh`
- Use `./scripts/prod-artisan.sh ...` for remote Laravel checks on Hostinger.
- When analytics instrumentation or reporting changes, validate with `tools/analytics` using a narrow post-change window first.
- When work requires a founder action outside the repo or server, create or update a queue item in `tools/exoskeleton` before handing the task back.
- Keep markdown operating docs up to date when the delivery flow, production layout, or tooling changes.

## Current Confirmed Facts

- This app is deployed on the same Hostinger account as the Boston project, but under `vibecodeinternational.com`.
- The Laravel app path on Hostinger is `/home/u353344964/domains/vibecodeinternational.com/fitness`.
- The public web root on Hostinger is `/home/u353344964/domains/vibecodeinternational.com/public_html`.
- The current remote deploy script is `/home/u353344964/vibecodeinternationaldeploy.sh`.
- The default SSH target in local scripts is `195.179.236.61` from `~/.ssh/config` on this machine.
- As checked on April 11, 2026, the default `php` on the Hostinger shell is still `8.2.30`, but PHP `8.3.30` is available at `/opt/alt/php83/usr/bin/php`.
- Production deploy and Artisan flows for this repo must use `/opt/alt/php83/usr/bin/php`, not the default `php` on `PATH`.
- As checked on April 11, 2026, production `php artisan about` reports environment `local`, debug enabled, queue driver `database`, and route caching enabled.
- As checked on April 11, 2026, no frontend analytics instrumentation is present in the repo yet.
- As checked on April 11, 2026, production `.env` is missing `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, and `GEMINI_API_KEY`, so real native AIUX agent runs are still blocked until at least one provider key is added.
- Local analytics and founder-ops tooling now exist in `tools/analytics` and `tools/exoskeleton`.

## Default System Check

When the user asks a broad question such as:

- `check the system`
- `how is production`
- `can you do a morning check`

treat that as a production-first check by default.

Default review order:

1. Backend and deployment readiness:
   - live HTTP smoke
   - remote `php artisan about`
   - remote route visibility for touched areas
   - queue and scheduler assumptions if the task touches jobs
2. Feature-specific runtime checks:
   - Victory Games routes
   - native AIUX prerequisites such as queue workers and Playwright runtime when relevant
3. Analytics sanity when relevant:
   - use `tools/analytics`
   - prefer a post-change window first

Default response shape:

- short status summary
- ranked issues or blockers
- recommended actions
- founder-review actions
- founder-required actions

Always state whether the result is production, local, or mixed-environment.
