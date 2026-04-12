# Backend Administration

This playbook is the source of truth for local verification, Hostinger deploys, production smoke checks, and native AIUX operational prerequisites.

## Current Hostinger Layout

- SSH host on this machine: `195.179.236.61`
- SSH user: `u353344964`
- SSH port: `65002`
- Laravel app path: `/home/u353344964/domains/vibecodeinternational.com/fitness`
- Public web root: `/home/u353344964/domains/vibecodeinternational.com/public_html`
- Remote deploy script: `/home/u353344964/vibecodeinternationaldeploy.sh`

## Current Production Facts

As checked on April 11, 2026:

- default shell `php` on production is `8.2.30`
- PHP `8.3.30` is available at `/opt/alt/php83/usr/bin/php`
- this repo should use `/opt/alt/php83/usr/bin/php` for deploy and Artisan commands
- production environment is `local`
- debug is enabled
- queue driver is `database`
- routes are cached
- production `.env` currently has no `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, or `GEMINI_API_KEY`
- user crontab was not visible over SSH, so scheduler execution may be panel-managed or otherwise out of band

The main remaining native AIUX blocker is missing provider credentials.

## Local Verification

Quick verification:

```bash
./scripts/local-check.sh quick
```

Full verification:

```bash
./scripts/local-check.sh full
```

`full` mode attempts the PHP test suite and expects a working local database configuration.

## Production Preflight

Before any production deploy attempt:

```bash
./scripts/prod-preflight.sh
```

This script checks:

- SSH connectivity
- remote app and deploy-script paths
- remote PHP version
- remote git origin and branch
- remote `php artisan about`
- visible crontab state if available

If the configured PHP 8.3 binary is missing or below `8.3`, preflight should fail and deployment should stop there.

## Deploy Flow

Use the local deploy wrapper instead of calling the remote script by hand:

```bash
./scripts/deploy-hostinger.sh
```

Optional flags:

- `--skip-preflight`
- `--skip-smoke`
- `--migrate`
- `--install-browser-runtime`
- `--queue-restart`

The wrapper:

- runs preflight unless told not to
- calls the existing remote deploy script
- clears Laravel caches on the remote app
- optionally runs migrations
- optionally installs the Playwright runtime used for native AIUX
- optionally restarts Laravel queue workers
- runs production smoke checks unless told not to

## Production Smoke

Public smoke test:

```bash
./scripts/prod-smoke.sh
```

This checks:

- `https://vibecodeinternational.com/`
- `https://vibecodeinternational.com/up`
- `https://vibecodeinternational.com/victory-games`
- `https://vibecodeinternational.com/login`

It also prints:

- remote `php artisan about`
- remote `php artisan route:list --path=victory-games`

For remote Laravel checks beyond the standard smoke:

```bash
./scripts/prod-artisan.sh about
./scripts/prod-artisan.sh route:list --path=victory-games
./scripts/prod-artisan.sh queue:failed
```

## Native AIUX Operational Requirements

The queued native AIUX runner depends on more than a normal Blade or Inertia deploy.

Production requirements:

- database migrations applied
- queue workers running
- Playwright PHP server dependencies installed
- Chromium installed for the configured runtime user

Useful commands:

```bash
./scripts/prod-artisan.sh migrate --force
./scripts/prod-artisan.sh victory-games:install-browser-runtime
./scripts/prod-artisan.sh queue:restart
```

If production routes are missing after deploy, clear caches first:

```bash
./scripts/prod-artisan.sh optimize:clear
```

## Known Risks

- Production deploys must use `/opt/alt/php83/usr/bin/php`, not the default shell `php`.
- The remote deploy script does not run migrations on its own.
- The remote deploy script does not install the Playwright runtime on its own.
- Real native AIUX agent runs are blocked until at least one AI provider key is added to production `.env`.
- Production is currently reporting environment `local` with debug enabled.
- Visible user crontab state is empty over SSH, so scheduler verification still needs explicit confirmation.
