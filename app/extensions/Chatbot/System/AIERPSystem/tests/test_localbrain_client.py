from __future__ import annotations

import unittest

from app.localbrain.client import LocalBrainClient


class LocalBrainClientTest(unittest.TestCase):
    def test_calls_php_localbrain_without_network(self) -> None:
        client = LocalBrainClient()
        result = client.process(
            "Show my attendance for this month",
            tenant_id="school-a",
            user_id="user-a",
            subject_id="STU101",
        )
        self.assertEqual("attendance_query", result.intent)
        self.assertGreaterEqual(result.confidence, 0.65)
        self.assertFalse(result.cloud_used)

    def test_ranks_local_conversation_memories(self) -> None:
        client = LocalBrainClient()
        result = client.process(
            "Show my mathematics marks",
            tenant_id="school-a",
            user_id="user-a",
            subject_id="STU101",
            memories=[
                {"id": "m1", "content": "We discussed mathematics results yesterday", "timestamp": "2026-08-03T10:00:00+10:00"},
                {"id": "m2", "content": "The school fee is pending", "timestamp": "2026-01-03T10:00:00+10:00"},
            ],
        )
        self.assertEqual("m1", result.ranked_memories[0]["id"])


if __name__ == "__main__":
    unittest.main()
