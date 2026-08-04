"""
app/services/erp_service.py
Service layer: raw SQL queries decoupled from AI tool wrappers.
All tools delegate to these service functions for clean architecture.
"""
import sqlite3
from contextlib import contextmanager
from pathlib import Path
from typing import Optional

DB_PATH = Path(__file__).resolve().parent.parent.parent / "mock_data" / "school_erp.db"


def set_database_path(path: Path) -> None:
    global DB_PATH
    DB_PATH = Path(path).expanduser().resolve()


@contextmanager
def _conn():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    try:
        yield conn
    finally:
        conn.close()


# ── Attendance ───────────────────────────────────────────────────────────────

def fetch_attendance_summary(student_id: str, month: Optional[str] = None) -> dict:
    """Raw DB query: aggregate attendance for a student, optionally filtered by month name."""
    with _conn() as conn:
        cursor = conn.cursor()
        if month:
            cursor.execute(
                """
                SELECT COUNT(*) AS total,
                       SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present,
                       SUM(CASE WHEN status='Absent'  THEN 1 ELSE 0 END) AS absent
                FROM attendance
                WHERE student_id = ?
                  AND strftime('%m', date) = (
                      SELECT printf('%02d', m.rownum) FROM (
                          SELECT 'January' n,1 rownum UNION SELECT 'February',2 UNION
                          SELECT 'March',3 UNION SELECT 'April',4 UNION SELECT 'May',5 UNION
                          SELECT 'June',6 UNION SELECT 'July',7 UNION SELECT 'August',8 UNION
                          SELECT 'September',9 UNION SELECT 'October',10 UNION
                          SELECT 'November',11 UNION SELECT 'December',12
                      ) m WHERE m.n = ?
                  )
                """,
                (student_id, month),
            )
        else:
            cursor.execute(
                """
                SELECT COUNT(*) AS total,
                       SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present,
                       SUM(CASE WHEN status='Absent'  THEN 1 ELSE 0 END) AS absent
                FROM attendance WHERE student_id = ?
                """,
                (student_id,),
            )
        row = cursor.fetchone()
        return dict(row) if row else {}


def fetch_attendance_records(student_id: str) -> list[dict]:
    """Return all individual attendance rows for a student."""
    with _conn() as conn:
        cursor = conn.cursor()
        cursor.execute(
            "SELECT date, status, subject FROM attendance WHERE student_id = ? ORDER BY date",
            (student_id,),
        )
        return [dict(r) for r in cursor.fetchall()]


# ── Marks ────────────────────────────────────────────────────────────────────

def fetch_marks(student_id: str, subject: Optional[str] = None) -> list[dict]:
    """Return all marks rows optionally filtered by subject."""
    with _conn() as conn:
        cursor = conn.cursor()
        if subject:
            cursor.execute(
                "SELECT subject, semester, marks_obtained, max_marks FROM marks WHERE student_id=? AND subject=? ORDER BY semester",
                (student_id, subject),
            )
        else:
            cursor.execute(
                "SELECT subject, semester, marks_obtained, max_marks FROM marks WHERE student_id=? ORDER BY subject, semester",
                (student_id,),
            )
        return [dict(r) for r in cursor.fetchall()]


def fetch_subject_averages(student_id: str) -> list[dict]:
    """Return per-subject average percentage for a student."""
    with _conn() as conn:
        cursor = conn.cursor()
        cursor.execute(
            """
            SELECT subject,
                   AVG(marks_obtained * 1.0 / max_marks * 100) AS avg_pct,
                   COUNT(*) AS exam_count
            FROM marks WHERE student_id=?
            GROUP BY subject
            ORDER BY avg_pct DESC
            """,
            (student_id,),
        )
        return [dict(r) for r in cursor.fetchall()]


# ── Fees ─────────────────────────────────────────────────────────────────────

def fetch_fees(student_id: str, status_filter: Optional[str] = None) -> list[dict]:
    """Return fee records, optionally filtered to 'Paid' or 'Pending' only."""
    with _conn() as conn:
        cursor = conn.cursor()
        if status_filter:
            cursor.execute(
                "SELECT month, amount, status, payment_date FROM fees WHERE student_id=? AND status=? ORDER BY id",
                (student_id, status_filter),
            )
        else:
            cursor.execute(
                "SELECT month, amount, status, payment_date FROM fees WHERE student_id=? ORDER BY id",
                (student_id,),
            )
        return [dict(r) for r in cursor.fetchall()]


# ── Homework ─────────────────────────────────────────────────────────────────

def fetch_homework(student_id: str, status_filter: Optional[str] = "Pending") -> list[dict]:
    """Return homework records. status_filter: 'Pending', 'Completed', or None (all)."""
    with _conn() as conn:
        cursor = conn.cursor()
        if status_filter:
            cursor.execute(
                "SELECT subject, assignment_title, due_date, status FROM homework WHERE student_id=? AND status=? ORDER BY due_date",
                (student_id, status_filter),
            )
        else:
            cursor.execute(
                "SELECT subject, assignment_title, due_date, status FROM homework WHERE student_id=? ORDER BY due_date",
                (student_id,),
            )
        return [dict(r) for r in cursor.fetchall()]


# ── Timetable ─────────────────────────────────────────────────────────────────

def fetch_student_grade(student_id: str) -> Optional[str]:
    """Return the grade string for a given student_id, or None if not found."""
    with _conn() as conn:
        cursor = conn.cursor()
        cursor.execute("SELECT grade FROM students WHERE id=?", (student_id,))
        row = cursor.fetchone()
        return row["grade"] if row else None


def fetch_timetable(grade: str, day: str) -> list[dict]:
    """Return timetable rows for a grade and day."""
    with _conn() as conn:
        cursor = conn.cursor()
        cursor.execute(
            "SELECT day_of_week, class_time, subject, room FROM timetable WHERE grade=? AND day_of_week=? ORDER BY class_time",
            (grade, day),
        )
        return [dict(r) for r in cursor.fetchall()]


# ── Student ─────────────────────────────────────────────────────────────────

def fetch_student(student_id: str) -> Optional[dict]:
    """Return student info dict or None."""
    with _conn() as conn:
        cursor = conn.cursor()
        cursor.execute("SELECT id, name, grade FROM students WHERE id=?", (student_id,))
        row = cursor.fetchone()
        return dict(row) if row else None
