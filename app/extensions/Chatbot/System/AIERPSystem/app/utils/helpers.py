"""
app/utils/helpers.py
Utility functions: student ID validation, date helpers, text normalization.
"""
import sqlite3
from contextlib import closing
from datetime import datetime, timedelta
from pathlib import Path
from typing import Optional

DB_PATH = Path(__file__).resolve().parent.parent.parent / "mock_data" / "school_erp.db"


def set_database_path(path: Path) -> None:
    global DB_PATH
    DB_PATH = Path(path).expanduser().resolve()


# ---------------------------------------------------------------------------
# Student validation
# ---------------------------------------------------------------------------

def validate_student_id(student_id: str) -> dict:
    """
    Validate that a student_id exists in the database.

    Returns:
        dict with keys:
          - valid (bool)
          - student (dict | None): {id, name, grade} if found
          - error (str | None): human-readable error if not found
    """
    if not student_id or not student_id.strip():
        return {"valid": False, "student": None, "error": "Student ID cannot be empty."}

    try:
        with closing(sqlite3.connect(DB_PATH)) as conn:
            conn.row_factory = sqlite3.Row
            cursor = conn.cursor()
            cursor.execute("SELECT id, name, grade FROM students WHERE id = ?", (student_id.strip(),))
            row = cursor.fetchone()

        if row:
            return {
                "valid": True,
                "student": {"id": row["id"], "name": row["name"], "grade": row["grade"]},
                "error": None,
            }
        return {
            "valid": False,
            "student": None,
            "error": f"Student ID '{student_id}' not found. Please use a valid ID (e.g., STU101).",
        }
    except Exception as e:
        return {"valid": False, "student": None, "error": f"Database error during validation: {e}"}


# ---------------------------------------------------------------------------
# Date helpers
# ---------------------------------------------------------------------------

def resolve_day(day_str: str) -> Optional[str]:
    """
    Resolve a day string like 'Tomorrow', 'Today', 'Monday', etc.
    Returns a canonical weekday name or None if invalid.
    """
    DAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
    normalized = day_str.strip().title()

    if normalized == "Today":
        return DAYS[datetime.now().weekday()]
    if normalized == "Tomorrow":
        return DAYS[(datetime.now().weekday() + 1) % 7]
    if normalized in DAYS:
        return normalized
    return None


def days_until(date_str: str) -> int:
    """Return the number of days from today until the given date (YYYY-MM-DD). Negative = overdue."""
    today = datetime.now().date()
    target = datetime.strptime(date_str, "%Y-%m-%d").date()
    return (target - today).days


def current_month_name() -> str:
    """Return the current month name, e.g. 'June'."""
    return datetime.now().strftime("%B")


def get_semester_remaining_days() -> int:
    """Rough estimate of remaining semester days (ends Dec 31 or Jun 30)."""
    today = datetime.now().date()
    year = today.year
    if today.month <= 6:
        end = datetime(year, 6, 30).date()
    else:
        end = datetime(year, 12, 31).date()
    return max((end - today).days, 0)


# ---------------------------------------------------------------------------
# Status label helpers
# ---------------------------------------------------------------------------

def attendance_status_label(percentage: float) -> str:
    if percentage >= 90:
        return "Excellent"
    if percentage >= 75:
        return "Good"
    if percentage >= 60:
        return "Average"
    return "Needs Improvement"


def marks_status_label(percentage: float) -> str:
    if percentage >= 85:
        return "Excellent"
    if percentage >= 70:
        return "Good"
    if percentage >= 50:
        return "Average"
    return "Needs Improvement"
