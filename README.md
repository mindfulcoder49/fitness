# Vibecode International

Laravel 12 application for Vibecode International, including the Victory Games surface and a queued native AIUX runner built with the Laravel AI SDK and Playwright PHP.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

For native AIUX work, also install the Playwright runtime:

```bash
php artisan victory-games:install-browser-runtime
```

Run the app locally:

```bash
composer dev
```

Or start processes separately:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

## Local Verification

Quick verification:

```bash
./scripts/local-check.sh quick
```

Full verification:

```bash
./scripts/local-check.sh full
```

`full` mode attempts the Laravel test suite and expects a working local database configuration.

## Production Deploy

This app is deployed to the same Hostinger account as the Boston project under `vibecodeinternational.com`.

Use the local wrappers:

```bash
./scripts/prod-preflight.sh
./scripts/deploy-hostinger.sh
./scripts/prod-smoke.sh
```

Useful remote Artisan access:

```bash
./scripts/prod-artisan.sh about
./scripts/prod-artisan.sh route:list --path=victory-games
```

## Important Production Notes

As of April 11, 2026:

- the default Hostinger shell `php` is still `8.2.30`
- PHP `8.3.30` is available at `/opt/alt/php83/usr/bin/php`
- deploy and Artisan flows for this repo must use the PHP 8.3 binary explicitly
- production `.env` currently has no `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, or `GEMINI_API_KEY`

So the code can be deployed with the PHP 8.3 CLI, but real native AIUX agent runs still need a provider key to be configured first.

## Analytics Tooling

Local-only analytics tooling lives in [tools/analytics](tools/analytics/README.md) and supports:

- GA4 smoke tests and ad hoc reports
- Search Console queries and sitemap submission
- local credential handling without putting Google credentials on Hostinger

Founder-action queue tooling lives in [tools/exoskeleton](tools/exoskeleton/README.md).

## Ops Docs

- [AGENTS.md](AGENTS.md)
- [CLAUDE.md](CLAUDE.md)
- [docs/ops/OPERATING_SYSTEM.md](docs/ops/OPERATING_SYSTEM.md)
- [docs/ops/backend-administration.md](docs/ops/backend-administration.md)
- [docs/ops/analytics.md](docs/ops/analytics.md)
