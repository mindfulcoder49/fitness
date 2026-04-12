from __future__ import annotations

import json
import sqlite3
from pathlib import Path


SCHEMA = """
CREATE TABLE IF NOT EXISTS queue_documents (
    id TEXT PRIMARY KEY,
    blob TEXT NOT NULL
);
"""


class QueueStore:
    def __init__(self, db_path: Path):
        self.db_path = db_path

    def _connect(self) -> sqlite3.Connection:
        connection = sqlite3.connect(self.db_path)
        connection.row_factory = sqlite3.Row
        return connection

    def init_db(self) -> None:
        with self._connect() as connection:
            connection.execute(SCHEMA)
            connection.commit()

    def save(self, document: dict) -> None:
        payload = json.dumps(document, ensure_ascii=False, separators=(",", ":"))

        with self._connect() as connection:
            connection.execute(
                """
                INSERT INTO queue_documents (id, blob)
                VALUES (?, ?)
                ON CONFLICT(id) DO UPDATE SET blob = excluded.blob
                """,
                (document["id"], payload),
            )
            connection.commit()

    def get(self, document_id: str) -> dict | None:
        with self._connect() as connection:
            row = connection.execute(
                "SELECT blob FROM queue_documents WHERE id = ?",
                (document_id,),
            ).fetchone()

        if row is None:
            return None

        return json.loads(row["blob"])

    def all(self) -> list[dict]:
        with self._connect() as connection:
            rows = connection.execute(
                "SELECT blob FROM queue_documents ORDER BY id DESC"
            ).fetchall()

        return [json.loads(row["blob"]) for row in rows]

    def delete(self, document_id: str) -> bool:
        with self._connect() as connection:
            cursor = connection.execute(
                "DELETE FROM queue_documents WHERE id = ?",
                (document_id,),
            )
            connection.commit()

        return cursor.rowcount > 0
