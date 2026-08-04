from __future__ import annotations

import sqlite3
from contextlib import closing
import threading
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from app.core.config import settings


@dataclass(frozen=True)
class MemoryScope:
    tenant_id: str
    user_id: str
    subject_id: str


class ConversationMemoryStore:
    def __init__(self, database_path: Path | None = None, history_limit: int | None = None) -> None:
        self.database_path = (database_path or settings.MEMORY_DATABASE).expanduser().resolve()
        self.database_path.parent.mkdir(parents=True, exist_ok=True)
        self.history_limit = history_limit or settings.MEMORY_HISTORY_LIMIT
        self._lock = threading.RLock()
        self._initialise()

    def _connect(self) -> sqlite3.Connection:
        connection = sqlite3.connect(self.database_path)
        connection.row_factory = sqlite3.Row
        return connection

    def _initialise(self) -> None:
        with self._lock, closing(self._connect()) as connection:
            connection.execute(
                """
                CREATE TABLE IF NOT EXISTS conversation_messages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    subject_id TEXT NOT NULL,
                    role TEXT NOT NULL CHECK(role IN ('user','assistant')),
                    content TEXT NOT NULL,
                    created_at TEXT NOT NULL
                )
                """
            )
            connection.execute(
                "CREATE INDEX IF NOT EXISTS idx_conversation_scope ON conversation_messages(tenant_id,user_id,subject_id,id)"
            )
            connection.commit()

    def add_message(self, scope: MemoryScope, role: str, content: str) -> int:
        if role not in {"user", "assistant"}:
            raise ValueError("role must be user or assistant")
        timestamp = datetime.now(timezone.utc).isoformat()
        with self._lock, closing(self._connect()) as connection:
            cursor = connection.execute(
                "INSERT INTO conversation_messages(tenant_id,user_id,subject_id,role,content,created_at) VALUES(?,?,?,?,?,?)",
                (scope.tenant_id, scope.user_id, scope.subject_id, role, content, timestamp),
            )
            connection.execute(
                """
                DELETE FROM conversation_messages
                WHERE tenant_id=? AND user_id=? AND subject_id=? AND id NOT IN (
                    SELECT id FROM conversation_messages
                    WHERE tenant_id=? AND user_id=? AND subject_id=?
                    ORDER BY id DESC LIMIT ?
                )
                """,
                (
                    scope.tenant_id, scope.user_id, scope.subject_id,
                    scope.tenant_id, scope.user_id, scope.subject_id,
                    self.history_limit,
                ),
            )
            connection.commit()
            return int(cursor.lastrowid)

    def get_messages(self, scope: MemoryScope, limit: int | None = None) -> list[dict[str, Any]]:
        requested = min(limit or self.history_limit, self.history_limit)
        with self._lock, closing(self._connect()) as connection:
            rows = connection.execute(
                """
                SELECT id, tenant_id, user_id, subject_id, role, content, created_at
                FROM conversation_messages
                WHERE tenant_id=? AND user_id=? AND subject_id=?
                ORDER BY id DESC LIMIT ?
                """,
                (scope.tenant_id, scope.user_id, scope.subject_id, requested),
            ).fetchall()
        return [dict(row) for row in reversed(rows)]

    def clear(self, scope: MemoryScope) -> int:
        with self._lock, closing(self._connect()) as connection:
            cursor = connection.execute(
                "DELETE FROM conversation_messages WHERE tenant_id=? AND user_id=? AND subject_id=?",
                (scope.tenant_id, scope.user_id, scope.subject_id),
            )
            connection.commit()
            return int(cursor.rowcount)
