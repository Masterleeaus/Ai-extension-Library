from __future__ import annotations

from pathlib import Path
from typing import Any, Callable

from app.core.config import settings
from app.localbrain.client import LocalBrainClient, LocalBrainError, LocalBrainResult
from app.memory.conversation_memory import ConversationMemoryStore, MemoryScope
from app.models.schemas import ERPResponse
from app.services import erp_service
from app.tools.erp_tools import (
    generate_exam_study_plan,
    generate_parent_progress_report,
    generate_performance_analytics,
    get_attendance_summary,
    get_fee_status,
    get_marks_records,
    get_pending_homework,
    get_timetable_schedule,
)
from app.utils import helpers

Tool = Callable[..., Any]


class ERPAgent:
    INTENT_MAP: dict[str, tuple[str, str, Tool]] = {
        "attendance_query": ("Attendance", "get_attendance_summary", get_attendance_summary),
        "marks_query": ("Marks", "get_marks_records", get_marks_records),
        "fees_query": ("Fees", "get_fee_status", get_fee_status),
        "homework_query": ("Homework", "get_pending_homework", get_pending_homework),
        "timetable_query": ("Timetable", "get_timetable_schedule", get_timetable_schedule),
        "performance_analysis": ("Performance Analytics", "generate_performance_analytics", generate_performance_analytics),
        "study_plan": ("Study Planner", "generate_exam_study_plan", generate_exam_study_plan),
        "progress_report": ("Progress Report", "generate_parent_progress_report", generate_parent_progress_report),
    }

    def __init__(
        self,
        *,
        localbrain: LocalBrainClient | None = None,
        memory: ConversationMemoryStore | None = None,
        database_path: Path | None = None,
        minimum_confidence: float | None = None,
    ) -> None:
        self.localbrain = localbrain or LocalBrainClient()
        self.memory = memory or ConversationMemoryStore()
        self.database_path = (database_path or settings.DATABASE_URL).resolve()
        self.minimum_confidence = minimum_confidence or settings.LOCALBRAIN_MIN_CONFIDENCE
        erp_service.set_database_path(self.database_path)
        helpers.set_database_path(self.database_path)

    def execute(self, user_message: str, scope: MemoryScope) -> ERPResponse:
        message = user_message.strip()
        if not message:
            return ERPResponse(
                intent="Error",
                response="Please enter a request.",
                status="Error",
                plan=["Reject empty request"],
            )
        prior_messages = self.memory.get_messages(scope, limit=10)
        self.memory.add_message(scope, "user", message)
        memory_candidates = [
            {"id": str(item["id"]), "content": item["content"], "timestamp": item["created_at"]}
            for item in prior_messages
        ]
        try:
            brain = self.localbrain.process(
                message,
                tenant_id=scope.tenant_id,
                user_id=scope.user_id,
                subject_id=scope.subject_id,
                memories=memory_candidates,
            )
        except LocalBrainError as exc:
            response = ERPResponse(
                intent="Error",
                response="LocalBrain v2 could not process the request locally.",
                status="Error",
                plan=["Invoke LocalBrain v2", f"Local processing failed: {exc}"],
            )
            self.memory.add_message(scope, "assistant", response.response)
            return response

        plan = [
            f"User Query: '{message}'",
            f"LocalBrain Intent: {brain.intent}",
            f"LocalBrain Confidence: {brain.confidence:.4f}",
            "Cloud Calls: 0",
            f"Ranked Memories: {len(brain.ranked_memories)}",
        ]
        mapping = self.INTENT_MAP.get(brain.intent)
        if brain.confidence < self.minimum_confidence or mapping is None:
            response = ERPResponse(
                intent="Clarification",
                response="I could not match that request confidently to a supported school ERP action. Please ask about attendance, marks, fees, homework, timetable, performance, a study plan, or a progress report.",
                status="Needs Clarification",
                plan=plan + ["Request clarification; no ERP tool executed"],
                confidence=brain.confidence,
            )
            self.memory.add_message(scope, "assistant", response.response)
            return response

        display_intent, tool_name, tool = mapping
        arguments = self._arguments_for(brain, scope.subject_id)
        plan.append(f"Select ERP Tool: {tool_name}({arguments})")
        try:
            data = tool(**arguments)
        except Exception as exc:  # defensive boundary around donor tools
            response = ERPResponse(
                intent="Error",
                response="The local ERP tool failed while processing the request.",
                status="Error",
                plan=plan + [f"Tool failure: {exc}"],
                confidence=brain.confidence,
                tool=tool_name,
            )
            self.memory.add_message(scope, "assistant", response.response)
            return response

        error = self._extract_error(data)
        if error:
            response = ERPResponse(
                intent="Error",
                response=error,
                status="Error",
                plan=plan + ["Tool returned an error"],
                confidence=brain.confidence,
                tool=tool_name,
                data=data,
            )
        else:
            text, status = self._format_response(brain, data, display_intent, scope.subject_id)
            response = ERPResponse(
                intent=display_intent,
                response=text,
                status=status,
                plan=plan + ["Format deterministic response from verified local data"],
                confidence=brain.confidence,
                tool=tool_name,
                data=data,
            )
        self.memory.add_message(scope, "assistant", response.response)
        return response

    def _arguments_for(self, brain: LocalBrainResult, student_id: str) -> dict[str, Any]:
        entities = brain.entities
        args: dict[str, Any] = {"student_id": entities.get("student_id", student_id)}
        if brain.intent == "attendance_query" and entities.get("month"):
            args["month"] = entities["month"]
        elif brain.intent == "marks_query" and entities.get("subject"):
            args["subject"] = entities["subject"]
        elif brain.intent == "timetable_query":
            args["day"] = entities.get("day", "Today")
        elif brain.intent == "study_plan":
            args["days_until_exam"] = 15
        return args

    @staticmethod
    def _extract_error(data: Any) -> str | None:
        if isinstance(data, dict) and data.get("error"):
            return str(data["error"])
        if isinstance(data, list) and data and isinstance(data[0], dict) and data[0].get("error"):
            return str(data[0]["error"])
        return None

    @staticmethod
    def _format_response(brain: LocalBrainResult, data: Any, intent: str, student_id: str) -> tuple[str, str]:
        metrics = brain.persona.get("metrics", {}) if isinstance(brain.persona, dict) else {}
        polarity = float(metrics.get("sentiment_polarity", 0.0))
        prefix = "I understand this may be concerning. " if polarity < -0.25 else ""
        if intent == "Attendance" and isinstance(data, dict):
            pct = data.get("attendance_percentage", 0)
            return f"{prefix}{student_id}'s attendance is {pct}%: {data.get('days_present', 0)} present and {data.get('days_absent', 0)} absent across {data.get('total_records', 0)} records.", str(data.get("status", "Good"))
        if intent == "Marks" and isinstance(data, list):
            records = [item for item in data if isinstance(item, dict) and "subject" in item]
            if not records:
                return f"{prefix}No marks records were found for {student_id}.", "Pending"
            summary = ", ".join(f"{r['subject']}: {r.get('percentage', r.get('marks_obtained', 'n/a'))}%" for r in records[:5])
            return f"{prefix}Marks for {student_id}: {summary}.", "Good"
        if intent == "Fees" and isinstance(data, list):
            summary = next((item.get("summary") for item in data if isinstance(item, dict) and item.get("summary")), {})
            pending = summary.get("total_pending", 0)
            months = ", ".join(summary.get("pending_months", [])) or "none"
            return f"{prefix}{student_id} has ${pending:.2f} pending. Unpaid months: {months}.", "Pending" if pending else "Good"
        if intent == "Homework" and isinstance(data, list):
            assignments = [item for item in data if isinstance(item, dict) and item.get("assignment_title")]
            if not assignments:
                return f"{prefix}No pending homework was found for {student_id}.", "Good"
            titles = "; ".join(f"{a['subject']}: {a['assignment_title']} ({a['status']})" for a in assignments[:5])
            return f"{prefix}Pending homework for {student_id}: {titles}.", "Pending"
        if intent == "Timetable" and isinstance(data, list):
            classes = [item for item in data if isinstance(item, dict) and item.get("subject")]
            if not classes:
                return f"{prefix}No classes were found for the requested day.", "Pending"
            day = classes[0].get("day_of_week", "requested day")
            details = "; ".join(f"{c['class_time']} {c['subject']} in {c['room']}" for c in classes)
            return f"{prefix}{day}'s timetable: {details}.", "Good"
        if intent == "Performance Analytics" and isinstance(data, dict):
            return f"{prefix}{student_id}'s overall average is {data.get('average_score_percentage', 0)}% with GPA {data.get('gpa', 0)}. Academic health: {data.get('academic_health_index', 'Pending')}.", str(data.get("academic_health_index", "Good"))
        if intent == "Study Planner" and isinstance(data, dict):
            priorities = ", ".join(data.get("priority_subjects", [])) or "all subjects"
            return f"{prefix}A {data.get('days_until_exam', 15)}-day local study plan is ready for {student_id}. Priority subjects: {priorities}.", "Good"
        if intent == "Progress Report" and isinstance(data, dict):
            return f"{prefix}The progress report for {student_id} is ready, combining attendance, marks, homework and fee information.", "Good"
        return f"{prefix}The local ERP request completed successfully.", "Good"
