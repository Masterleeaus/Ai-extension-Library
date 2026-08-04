from __future__ import annotations

import tempfile
import unittest
from unittest.mock import patch
from pathlib import Path

from app.agents.erp_agent import ERPAgent
from app.localbrain.client import LocalBrainClient
from app.memory.conversation_memory import ConversationMemoryStore, MemoryScope
from mock_data.init_db import initialise_database


class ERPAgentTest(unittest.TestCase):
    def setUp(self) -> None:
        self.directory = tempfile.TemporaryDirectory()
        base = Path(self.directory.name)
        self.erp_db = base / "school.sqlite3"
        self.memory_db = base / "memory.sqlite3"
        initialise_database(self.erp_db)
        self.agent = ERPAgent(
            localbrain=LocalBrainClient(),
            memory=ConversationMemoryStore(self.memory_db),
            database_path=self.erp_db,
            minimum_confidence=0.65,
        )
        self.scope = MemoryScope("school-a", "parent-a", "STU101")

    def tearDown(self) -> None:
        self.directory.cleanup()


    def test_agent_operates_when_external_sockets_are_blocked(self) -> None:
        with patch("socket.create_connection", side_effect=AssertionError("network access attempted")):
            response = self.agent.execute("Show my attendance", self.scope)
        self.assertEqual("Attendance", response.intent)
        self.assertEqual("offline", response.mode)

    def test_executes_attendance_tool_from_localbrain_intent(self) -> None:
        response = self.agent.execute("Show my attendance for this month", self.scope)
        self.assertEqual("Attendance", response.intent)
        self.assertEqual("offline", response.mode)
        self.assertIn("attendance", response.response.lower())
        self.assertIn("get_attendance_summary", " ".join(response.plan))

    def test_all_supported_intents_route_to_local_tools(self) -> None:
        cases = [
            ("What marks did I get in mathematics?", "Marks", "get_marks_records"),
            ("Are any school fees still pending?", "Fees", "get_fee_status"),
            ("Show my pending homework", "Homework", "get_pending_homework"),
            ("Show my timetable for Monday", "Timetable", "get_timetable_schedule"),
            ("Summarise my academic performance", "Performance Analytics", "generate_performance_analytics"),
            ("Build a study plan for my exams", "Study Planner", "generate_exam_study_plan"),
            ("Create a progress report for my parent", "Progress Report", "generate_parent_progress_report"),
        ]
        for message, expected_intent, expected_tool in cases:
            with self.subTest(message=message):
                response = self.agent.execute(message, self.scope)
                self.assertEqual(expected_intent, response.intent)
                self.assertEqual(expected_tool, response.tool)
                self.assertEqual("offline", response.mode)

    def test_low_confidence_input_requests_clarification(self) -> None:
        response = self.agent.execute("blue maybe random later", self.scope)
        self.assertEqual("Clarification", response.intent)
        self.assertEqual("Needs Clarification", response.status)
        self.assertNotIn("Select ERP Tool", " ".join(response.plan))


if __name__ == "__main__":
    unittest.main()
