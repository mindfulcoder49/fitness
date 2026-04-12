from pathlib import Path

from project_exoskeleton.queue_service import build_task, complete_task, list_tasks
from project_exoskeleton.storage import QueueStore


def make_store(tmp_path: Path) -> QueueStore:
    store = QueueStore(tmp_path / "queue.sqlite3")
    store.init_db()
    return store


def test_task_round_trip_and_completion(tmp_path: Path) -> None:
    store = make_store(tmp_path)

    task = build_task(
        project_slug="demo-project",
        title="Grant GA4 access",
        action_text="Add the service account to the GA4 property.",
        success_criteria="Smoke test succeeds.",
        metadata={"area": "analytics"},
    )
    store.save(task)

    tasks = list_tasks(store, project_slug="demo-project", status="open")
    assert len(tasks) == 1
    assert tasks[0]["title"] == "Grant GA4 access"

    completed = complete_task(store, task["id"], actor="founder", note="Done")
    assert completed["status"] == "done"
    assert completed["completed_at"] is not None

    stored = store.get(task["id"])
    assert stored is not None
    assert stored["events"][-1]["type"] == "completed"


def test_delete_removes_task(tmp_path: Path) -> None:
    store = make_store(tmp_path)

    task = build_task(
        project_slug="demo-project",
        title="Delete me",
        action_text="Remove this task.",
    )
    store.save(task)

    assert store.delete(task["id"]) is True
    assert store.get(task["id"]) is None
