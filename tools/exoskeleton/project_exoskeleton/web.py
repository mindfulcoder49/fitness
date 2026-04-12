from __future__ import annotations

import json
from pathlib import Path

from fastapi import FastAPI, HTTPException
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.templating import Jinja2Templates
from starlette.requests import Request

from .config import RuntimeConfig
from .queue_service import complete_task, list_tasks
from .storage import QueueStore


def _normalize_display_text(value: str | None) -> str | None:
    if value is None:
        return None

    return value.replace("\\n", "\n")


def create_app(config: RuntimeConfig | None = None) -> FastAPI:
    runtime = config or RuntimeConfig.load()
    runtime.ensure_data_dir()

    store = QueueStore(runtime.db_path)
    store.init_db()

    templates_dir = Path(__file__).resolve().parent / "templates"
    templates = Jinja2Templates(directory=str(templates_dir))

    app = FastAPI(title="Project Exoskeleton")

    @app.get("/", response_class=HTMLResponse)
    def index(
        request: Request,
        status: str = "open",
        task_type: str = "all",
        project_slug: str | None = None,
    ) -> HTMLResponse:
        selected_project_slug = project_slug or runtime.project_slug
        tasks = list_tasks(
            store,
            project_slug=selected_project_slug if selected_project_slug != "all" else None,
            status=status,
            task_type=task_type,
        )

        return templates.TemplateResponse(
            request,
            "index.html",
            {
                "tasks": tasks,
                "status_filter": status,
                "task_type_filter": task_type,
                "project_slug": selected_project_slug,
                "default_project_slug": runtime.project_slug,
            },
        )

    @app.get("/tasks/{task_id}", response_class=HTMLResponse)
    def task_detail(request: Request, task_id: str) -> HTMLResponse:
        task = store.get(task_id)
        if task is None:
            raise HTTPException(status_code=404, detail="Task not found")

        display_task = dict(task)
        for field_name in ("action_text", "success_criteria", "blocking_reason"):
            display_task[field_name] = _normalize_display_text(display_task.get(field_name))

        return templates.TemplateResponse(
            request,
            "task_detail.html",
            {
                "task": display_task,
                "task_json": json.dumps(task, indent=2, ensure_ascii=False),
            },
        )

    @app.post("/tasks/{task_id}/complete")
    def mark_complete(
        task_id: str,
        status_filter: str = "open",
        task_type_filter: str = "all",
        project_slug: str | None = None,
    ) -> RedirectResponse:
        try:
            complete_task(store, task_id, actor="founder")
        except KeyError as exc:
            raise HTTPException(status_code=404, detail=str(exc)) from exc

        redirect_url = (
            f"/?status={status_filter}&task_type={task_type_filter}"
            f"&project_slug={project_slug or runtime.project_slug}"
        )
        return RedirectResponse(url=redirect_url, status_code=303)

    return app
