from __future__ import annotations

import tempfile
import unittest
from pathlib import Path

from app.memory.conversation_memory import ConversationMemoryStore, MemoryScope


class ConversationMemoryStoreTest(unittest.TestCase):
    def test_history_is_isolated_by_tenant_user_and_subject(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            store = ConversationMemoryStore(Path(directory) / "memory.sqlite3")
            first = MemoryScope("tenant-a", "user-a", "STU101")
            other_user = MemoryScope("tenant-a", "user-b", "STU101")
            other_tenant = MemoryScope("tenant-b", "user-a", "STU101")
            store.add_message(first, "user", "private message")
            self.assertEqual(1, len(store.get_messages(first)))
            self.assertEqual([], store.get_messages(other_user))
            self.assertEqual([], store.get_messages(other_tenant))


if __name__ == "__main__":
    unittest.main()
