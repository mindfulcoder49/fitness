from __future__ import annotations

from datetime import datetime, timezone
from uuid import uuid4

from .storage import QueueStore


VALID_STATUSES = ("open", "in_progress", "done", "canceled")
VALID_TASK_TYPES = ("founder_required", "founder_review", "recommended")
VALID_PRIORITIES = ("low", "medium", "high", "urgent")


def utc_now() -> str:
    return datetime.now(timezone.utc).isoformat().replace("+00:00", "Z")


def build_task(
    *,
    project_slug: str,
    title: str,
    action_text: str,
    task_type: str = "founder_required",
    priority: str = "medium",
    external_system: str | None = None,
    success_criteria: str | None = None,
    blocking_reason: str | None = None,
    source: str | None = None,
    metadata: dict | None = None,
) -> dict:
    if task_type not in VALID_TASK_TYPES:
        raise ValueError(f"Invalid task type: {task_type}")

    if priority not in VALID_PRIORITIES:
        raise ValueError(f"Invalid priority: {priority}")

    now = utc_now()
    task_id = f"task_{uuid4().hex[:12]}"

    return {
        "id": task_id,
        "project_slug": project_slug,
        "title": title.strip(),
        "status": "open",
        "task_type": task_type,
        "priority": priority,
        "external_system": external_system,
        "action_text": action_text.strip(),
        "success_criteria": success_criteria,
        "blocking_reason": blocking_reason,
        "source": source,
        "metadata": metadata or {},
        "created_at": now,
        "updated_at": now,
        "completed_at": None,
        "events": [
            {
                "type": "created",
                "at": now,
                "actor": "agent",
                "note": "Task created",
            }
        ],
    }


def sort_tasks(tasks: list[dict]) -> list[dict]:
    status_order = {"open": 0, "in_progress": 1, "done": 2, "canceled": 3}
    return sorted(
        tasks,
        key=lambda task: (
            status_order.get(task.get("status", "open"), 99),
            task.get("updated_at", ""),
        ),
        reverse=False,
    )


def list_tasks(
    store: QueueStore,
    *,
    project_slug: str | None = None,
    status: str | None = None,
    task_type: str | None = None,
) -> list[dict]:
    tasks = store.all()

    if project_slug:
        tasks = [task for task in tasks if task.get("project_slug") == project_slug]

    if status and status != "all":
        tasks = [task for task in tasks if task.get("status") == status]

    if task_type and task_type != "all":
        tasks = [task for task in tasks if task.get("task_type") == task_type]

    return sort_tasks(tasks)


def complete_task(
    store: QueueStore,
    task_id: str,
    *,
    actor: str = "founder",
    note: str | None = None,
) -> dict:
    task = store.get(task_id)
    if task is None:
        raise KeyError(f"Task not found: {task_id}")

    now = utc_now()
    task["status"] = "done"
    task["completed_at"] = now
    task["updated_at"] = now
    task.setdefault("events", []).append(
        {
            "type": "completed",
            "at": now,
            "actor": actor,
            "note": note or "Task marked complete",
        }
    )

    store.save(task)
    return task
