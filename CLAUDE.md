# Project: Vibecode International

## Stack

- Backend: Laravel 12 on PHP 8.3 locally
- Frontend: Vue 3, Inertia, Tailwind, Vite
- Database: MySQL in production
- AI runtime: Laravel AI SDK plus `playwright-php/playwright`
- Long-running work: Laravel queues

## Local Environment

Sail is available, but it is not required for every workflow on this machine. Prefer direct local commands unless you specifically need Sail parity.

Useful commands:

```bash
composer install
npm install
php artisan migrate
php artisan serve
php artisan queue:work
npm run dev
```

Repo-level helper scripts:

```bash
./scripts/local-check.sh quick
./scripts/local-check.sh full
./scripts/prod-preflight.sh
./scripts/deploy-hostinger.sh
./scripts/prod-smoke.sh
./scripts/prod-artisan.sh about
```

## Native AIUX

The repo now contains a queued native AIUX implementation for Victory Games using Laravel AI plus Playwright PHP.

Important operational facts:

- native runs are queued and executed by `RunVictoryGamesNativeAiuxJob`
- the browser runtime install command is `php artisan victory-games:install-browser-runtime`
- production deploys that touch native AIUX need migrations, queue workers, and the Playwright runtime
- the current action vocabulary is intentionally narrow: `navigate` and `execute_js`

If you are validating native AIUX locally, the minimum flow is:

```bash
php artisan migrate
php artisan victory-games:install-browser-runtime
php artisan queue:work
```

## Production

This repo is deployed to the same Hostinger account as the Boston project, but under `vibecodeinternational.com`.

Current remote facts confirmed on April 11, 2026:

- SSH target on this machine: `195.179.236.61`
- app path: `/home/u353344964/domains/vibecodeinternational.com/fitness`
- public path: `/home/u353344964/domains/vibecodeinternational.com/public_html`
- deploy script: `/home/u353344964/vibecodeinternationaldeploy.sh`
- live `php artisan about` reports Laravel `12.32.5`
- default shell `php` is `8.2.30`
- PHP `8.3.30` is available at `/opt/alt/php83/usr/bin/php`
- live environment is `local`
- live debug is enabled
- live queue driver is `database`

Important runtime notes:

- production deploy and Artisan commands for this repo must use `/opt/alt/php83/usr/bin/php`
- production `.env` currently has no `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, or `GEMINI_API_KEY`
- native AIUX browser jobs can be deployed and runtime-tested, but real planner/postmortem agent runs need at least one provider key

The helper deploy flow is documented in [docs/ops/backend-administration.md](docs/ops/backend-administration.md).

## Analytics

Local analytics tooling now lives in [tools/analytics](tools/analytics/README.md). It is intended for local GA4 and Search Console work and should not be deployed to Hostinger.

As of April 11, 2026, the app does not yet contain frontend analytics instrumentation. Use the analytics toolchain for setup, validation, and reporting once instrumentation is added.

## Frontend Theming

The app supports multiple user-selectable themes. Do not hardcode Tailwind color classes such as `bg-gray-800` or `text-indigo-300`.

Prefer the existing theme utility classes defined in `resources/css/app.css` and mapped in `tailwind.config.js`, including:

- `bg-theme-page`
- `bg-theme-card`
- `text-theme-text-primary`
- `text-theme-text-secondary`
- `border-theme-border`
- `bg-theme-accent`
- `bg-theme-accent-hover`
- `bg-theme-btn-primary`
- `bg-theme-btn-secondary`

Use existing theme utilities whenever you touch UI code.
