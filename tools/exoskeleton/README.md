# Project Exoskeleton

Standalone founder-ops support tooling that sits around the main project instead of inside it.

This workspace is meant for:

- founder action queues
- approval-required task tracking
- local operational web tools
- future cross-project support tooling that should not ship inside Laravel or production hosting

## Current Feature: Founder Action Queue

The first feature is a local queue with:

- SQLite persistence
- JSON-blob task documents
- a CLI for creating, listing, showing, and completing tasks
- a small web UI for viewing open work and marking tasks complete
- startup scripts that choose an open port dynamically

Queue discipline:
- when an agent identifies `founder_review` or `founder_required` work, it should create or update the queue item before handing the task back
- queue items should be specific enough that the founder can complete them without guessing
- founder-facing queue items should be self-contained; do not send the founder to markdown files just to find the real instructions
- if the task is a post, reply, setting, form entry, or approval request, include the exact copy or values directly in the task
- stale or superseded tasks should be updated or deleted, not left ambiguous

The database uses a deliberately thin schema:

- one SQLite table
- one JSON document blob per task
- no relational task model

## Install

```bash
cd tools/exoskeleton
python3 -m venv .venv
source .venv/bin/activate
python -m pip install --upgrade pip
python -m pip install -e ".[dev]"
```

## Usage

Create a task:

```bash
project-exo queue create \
  --title "Grant GA4 Viewer access to service account" \
  --action-text "Add the service account email to the GA4 property with Viewer access." \
  --task-type founder_required \
  --external-system "Google Analytics" \
  --success-criteria "vci-analytics smoke-test succeeds."
```

List tasks:

```bash
project-exo queue list --status open
```

Complete a task:

```bash
project-exo queue complete task_123456789abc --note "Access granted and smoke test passed."
```

Run the web UI:

```bash
project-exo queue serve
```

Or use the startup script, which creates a venv if needed and chooses an open port automatically:

```bash
./scripts/start_queue.sh
```

## Config

You can place settings in the repo root `.env` or `tools/exoskeleton/.env`.

Example values are in [example.env](example.env).

Supported environment variables:

- `PROJECT_EXO_PROJECT_SLUG`
- `PROJECT_EXO_DB_PATH`
- `PROJECT_EXO_HOST`

Defaults:

- `PROJECT_EXO_PROJECT_SLUG`: current repo directory name
- `PROJECT_EXO_DB_PATH`: `tools/exoskeleton/data/exoskeleton.sqlite3`
- `PROJECT_EXO_HOST`: `127.0.0.1`

## Web UI

The web UI is intentionally small:

- list open or completed tasks
- filter by project slug and task type
- inspect the full JSON blob for a task
- mark tasks complete

## Notes

- The queue is local-first and not intended as a multi-user production app.
- Task documents are stored as JSON blobs so the exoskeleton can evolve without schema churn.
- Future support tools can write tasks into this queue without depending on the Laravel codebase.
