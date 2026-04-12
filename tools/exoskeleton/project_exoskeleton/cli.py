from __future__ import annotations

import json
import logging
import sys
import threading
import webbrowser

import click
import uvicorn

from .config import RuntimeConfig, load_local_env
from .ports import find_open_port, is_port_available
from .queue_service import (
    VALID_PRIORITIES,
    VALID_TASK_TYPES,
    build_task,
    complete_task,
    list_tasks,
)
from .storage import QueueStore
from .web import create_app


logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)],
)


def runtime_config() -> RuntimeConfig:
    return RuntimeConfig.load()


def queue_store() -> QueueStore:
    config = runtime_config()
    config.ensure_data_dir()
    store = QueueStore(config.db_path)
    store.init_db()
    return store


def parse_metadata_json(raw_value: str) -> dict:
    if not raw_value.strip():
        return {}

    try:
        parsed = json.loads(raw_value)
    except json.JSONDecodeError as exc:
        raise click.ClickException(f"Invalid metadata JSON: {exc}") from exc

    if not isinstance(parsed, dict):
        raise click.ClickException("Metadata JSON must decode to an object.")

    return parsed


@click.group()
@click.option("--debug", is_flag=True, help="Enable verbose debug logging.")
def cli(debug: bool) -> None:
    """Standalone local exoskeleton tooling."""
    load_local_env()
    if debug:
        logging.getLogger().setLevel(logging.DEBUG)


@cli.group("queue")
def queue_group() -> None:
    """Founder action queue commands."""


@queue_group.command("init-db")
def init_db_command() -> None:
    """Create the local queue database if it does not exist."""
    store = queue_store()
    click.echo(f"Queue database ready at {store.db_path}")


@queue_group.command("create")
@click.option("--project-slug", help="Project slug. Defaults to PROJECT_EXO_PROJECT_SLUG or repo name.")
@click.option("--title", required=True, help="Short task title.")
@click.option("--action-text", required=True, help="Exact action the founder should take.")
@click.option(
    "--task-type",
    default="founder_required",
    show_default=True,
    type=click.Choice(VALID_TASK_TYPES, case_sensitive=False),
)
@click.option(
    "--priority",
    default="medium",
    show_default=True,
    type=click.Choice(VALID_PRIORITIES, case_sensitive=False),
)
@click.option("--external-system", help="External system involved, if any.")
@click.option("--success-criteria", help="How to know the task is done.")
@click.option("--blocking-reason", help="Why this is blocking progress.")
@click.option("--source", help="Source workflow, tool, or session.")
@click.option(
    "--metadata-json",
    default="{}",
    show_default=True,
    help="Extra JSON metadata object.",
)
@click.option(
    "--format",
    "output_format",
    type=click.Choice(["table", "json"], case_sensitive=False),
    default="table",
    show_default=True,
)
def create_command(
    project_slug: str | None,
    title: str,
    action_text: str,
    task_type: str,
    priority: str,
    external_system: str | None,
    success_criteria: str | None,
    blocking_reason: str | None,
    source: str | None,
    metadata_json: str,
    output_format: str,
) -> None:
    """Create a queue task."""
    config = runtime_config()
    store = queue_store()

    task = build_task(
        project_slug=project_slug or config.project_slug,
        title=title,
        action_text=action_text,
        task_type=task_type,
        priority=priority,
        external_system=external_system,
        success_criteria=success_criteria,
        blocking_reason=blocking_reason,
        source=source,
        metadata=parse_metadata_json(metadata_json),
    )
    store.save(task)

    if output_format == "json":
        click.echo(json.dumps(task, indent=2, ensure_ascii=False))
        return

    click.echo(f"Created {task['id']}")
    click.echo(f"  project: {task['project_slug']}")
    click.echo(f"  type: {task['task_type']}")
    click.echo(f"  priority: {task['priority']}")
    click.echo(f"  title: {task['title']}")


@queue_group.command("list")
@click.option("--project-slug", help="Project slug filter.")
@click.option(
    "--status",
    default="open",
    show_default=True,
    type=click.Choice(["open", "in_progress", "done", "canceled", "all"], case_sensitive=False),
)
@click.option(
    "--task-type",
    default="all",
    show_default=True,
    type=click.Choice([*VALID_TASK_TYPES, "all"], case_sensitive=False),
)
@click.option(
    "--format",
    "output_format",
    type=click.Choice(["table", "json"], case_sensitive=False),
    default="table",
    show_default=True,
)
def list_command(
    project_slug: str | None,
    status: str,
    task_type: str,
    output_format: str,
) -> None:
    """List queue tasks."""
    config = runtime_config()
    store = queue_store()
    tasks = list_tasks(
        store,
        project_slug=project_slug or config.project_slug,
        status=status,
        task_type=task_type,
    )

    if output_format == "json":
        click.echo(json.dumps(tasks, indent=2, ensure_ascii=False))
        return

    if not tasks:
        click.echo("No matching tasks.")
        return

    click.echo("ID              STATUS       TYPE              PRIORITY  TITLE")
    click.echo("--------------  -----------  ----------------  --------  ------------------------------")
    for task in tasks:
        click.echo(
            f"{task['id']:<14}  "
            f"{task['status']:<11}  "
            f"{task['task_type']:<16}  "
            f"{task['priority']:<8}  "
            f"{task['title']}"
        )


@queue_group.command("show")
@click.argument("task_id")
def show_command(task_id: str) -> None:
    """Show the full JSON document for one task."""
    store = queue_store()
    task = store.get(task_id)
    if task is None:
        raise click.ClickException(f"Task not found: {task_id}")

    click.echo(json.dumps(task, indent=2, ensure_ascii=False))


@queue_group.command("complete")
@click.argument("task_id")
@click.option("--actor", default="founder", show_default=True, help="Who completed the task.")
@click.option("--note", help="Optional completion note.")
def complete_command(task_id: str, actor: str, note: str | None) -> None:
    """Mark a task complete."""
    store = queue_store()
    try:
        task = complete_task(store, task_id, actor=actor, note=note)
    except KeyError as exc:
        raise click.ClickException(str(exc)) from exc

    click.echo(f"Completed {task['id']}: {task['title']}")


@queue_group.command("delete")
@click.argument("task_id")
def delete_command(task_id: str) -> None:
    """Delete a task permanently."""
    store = queue_store()
    if not store.delete(task_id):
        raise click.ClickException(f"Task not found: {task_id}")

    click.echo(f"Deleted {task_id}")


@queue_group.command("serve")
@click.option("--host", help="Host to bind. Defaults to PROJECT_EXO_HOST or 127.0.0.1.")
@click.option("--port", type=int, help="Specific port to bind. If omitted, an open port is chosen dynamically.")
@click.option("--open-browser", is_flag=True, help="Open the queue UI in your default browser.")
def serve_command(host: str | None, port: int | None, open_browser: bool) -> None:
    """Run the local queue web UI."""
    config = runtime_config()
    resolved_host = host or config.host

    if port is not None:
        if not is_port_available(resolved_host, port):
            raise click.ClickException(f"Port {port} is not available on {resolved_host}.")
        resolved_port = port
    else:
        resolved_port = find_open_port(resolved_host)

    app = create_app(config)
    url = f"http://{resolved_host}:{resolved_port}"
    click.echo(f"Queue UI running at {url}")

    if open_browser:
        threading.Timer(0.8, lambda: webbrowser.open(url)).start()

    uvicorn.run(app, host=resolved_host, port=resolved_port, log_level="info")
