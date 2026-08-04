"""
app/tools/erp_tools.py
All ERP tool functions called by the AI agent.
Delegates raw DB access to app.services.erp_service.
Student ID validation is enforced on every tool.
"""
from datetime import datetime
from typing import Optional

from app.services.erp_service import (
    fetch_attendance_summary,
    fetch_attendance_records,
    fetch_marks,
    fetch_subject_averages,
    fetch_fees,
    fetch_homework,
    fetch_timetable,
    fetch_student_grade,
    fetch_student,
)
from app.utils.helpers import (
    validate_student_id,
    resolve_day,
    days_until,
    attendance_status_label,
    marks_status_label,
    get_semester_remaining_days,
)


# ────────────────────────────────────────────────────────────────────────────
# Internal guard
# ────────────────────────────────────────────────────────────────────────────

def _guard(student_id: str):
    """
    Validate student_id. Returns None if valid,
    or a dict error payload that should be returned directly.
    """
    result = validate_student_id(student_id)
    if not result["valid"]:
        return {"error": result["error"], "student_id": student_id}
    return None


# ────────────────────────────────────────────────────────────────────────────
# Tool 1 – Attendance
# ────────────────────────────────────────────────────────────────────────────

def get_attendance_summary(student_id: str, month: Optional[str] = None) -> dict:
    """
    Calculate attendance summary for a student.

    Parameters
    ----------
    student_id : str
        The unique student identifier (e.g., 'STU101').
    month : str, optional
        Month name filter (e.g., 'January'). If omitted, returns full year.

    Returns
    -------
    dict
        - student_id, total_records, days_present, days_absent,
          attendance_percentage, status, month (if filtered)

    Examples
    --------
    >>> get_attendance_summary("STU101")
    {'student_id': 'STU101', 'attendance_percentage': 80.0, 'status': 'Good', ...}
    >>> get_attendance_summary("STU101", "January")
    {'student_id': 'STU101', 'month': 'January', 'attendance_percentage': 85.0, ...}
    """
    err = _guard(student_id)
    if err:
        return err

    try:
        row = fetch_attendance_summary(student_id, month)
        total = row.get("total") or 0
        present = row.get("present") or 0
        absent = row.get("absent") or 0

        if total == 0:
            return {
                "student_id": student_id,
                "total_records": 0,
                "days_present": 0,
                "days_absent": 0,
                "attendance_percentage": 0.0,
                "status": "No records found",
                **({"month": month} if month else {}),
            }

        pct = round(present / total * 100, 2)
        return {
            "student_id": student_id,
            "total_records": total,
            "days_present": present,
            "days_absent": absent,
            "attendance_percentage": pct,
            "status": attendance_status_label(pct),
            **({"month": month} if month else {}),
        }
    except Exception as e:
        return {"error": str(e)}


# ────────────────────────────────────────────────────────────────────────────
# Tool 2 – Marks
# ────────────────────────────────────────────────────────────────────────────

def get_marks_records(student_id: str, subject: Optional[str] = None) -> list:
    """
    Retrieve exam marks for a student, optionally filtered by subject.

    Parameters
    ----------
    student_id : str
        The unique student identifier (e.g., 'STU101').
    subject : str, optional
        Filter by subject name (e.g., 'Mathematics'). Returns all if omitted.

    Returns
    -------
    list[dict]
        Records with student_id, subject, semester, marks_obtained,
        max_marks, percentage, average, highest_score, lowest_score.

    Examples
    --------
    >>> get_marks_records("STU101", "Mathematics")
    [{'subject': 'Mathematics', 'percentage': 89.01, ...}]
    """
    err = _guard(student_id)
    if err:
        return [err]

    try:
        rows = fetch_marks(student_id, subject)
        if not rows:
            return [{"student_id": student_id, "subject": subject or "All", "message": "No marks records found"}]

        records = []
        for r in rows:
            pct = round(r["marks_obtained"] / r["max_marks"] * 100, 2)
            records.append({
                "student_id": student_id,
                "subject": r["subject"],
                "semester": r["semester"],
                "marks_obtained": r["marks_obtained"],
                "max_marks": r["max_marks"],
                "percentage": pct,
            })

        percentages = [r["percentage"] for r in records]
        avg = round(sum(percentages) / len(percentages), 2)
        highest = max(records, key=lambda x: x["percentage"])
        lowest = min(records, key=lambda x: x["percentage"])

        for r in records:
            r["average"] = avg
            r["highest_subject"] = highest["subject"]
            r["highest_percentage"] = highest["percentage"]
            r["lowest_subject"] = lowest["subject"]
            r["lowest_percentage"] = lowest["percentage"]

        return records
    except Exception as e:
        return [{"error": str(e)}]


# ────────────────────────────────────────────────────────────────────────────
# Tool 3 – Fee Status
# ────────────────────────────────────────────────────────────────────────────

def get_fee_status(student_id: str, status_filter: Optional[str] = None) -> list:
    """
    Retrieve fee payment history and pending balance for a student.

    Parameters
    ----------
    student_id : str
        The unique student identifier.
    status_filter : str, optional
        'Paid' or 'Pending' to filter. If None, returns all records.

    Returns
    -------
    list[dict]
        Fee records with summary dict (total_amount, total_paid,
        total_pending, pending_months).

    Examples
    --------
    >>> get_fee_status("STU101")
    [{'month': 'January', 'status': 'Pending', ...}, ...]
    >>> get_fee_status("STU101", "Pending")
    [{'month': 'January', 'status': 'Pending', ...}]
    """
    err = _guard(student_id)
    if err:
        return [err]

    try:
        rows = fetch_fees(student_id, status_filter)
        if not rows:
            msg = f"No {status_filter.lower()} fee records found." if status_filter else "No fee records found."
            return [{"student_id": student_id, "message": msg}]

        # Compute summary from ALL fees (not just filtered)
        all_rows = fetch_fees(student_id)
        total_amount = sum(r["amount"] for r in all_rows)
        total_paid = sum(r["amount"] for r in all_rows if r["status"] == "Paid")
        pending_months = [r["month"] for r in all_rows if r["status"] == "Pending"]

        summary = {
            "total_amount": round(total_amount, 2),
            "total_paid": round(total_paid, 2),
            "total_pending": round(total_amount - total_paid, 2),
            "pending_months": pending_months,
        }

        records = []
        for r in rows:
            records.append({
                "student_id": student_id,
                "month": r["month"],
                "amount": r["amount"],
                "status": r["status"],
                "payment_date": r.get("payment_date"),
                "summary": summary,
            })

        return records
    except Exception as e:
        return [{"error": str(e)}]


# ────────────────────────────────────────────────────────────────────────────
# Tool 4 – Homework
# ────────────────────────────────────────────────────────────────────────────

def get_pending_homework(student_id: str, due_filter: Optional[str] = None) -> list:
    """
    Fetch pending homework for a student with optional due-date filter.

    Parameters
    ----------
    student_id : str
        The unique student identifier.
    due_filter : str, optional
        'today', 'tomorrow', or 'week' to filter upcoming assignments.

    Returns
    -------
    list[dict]
        Pending homework with days_until_due and urgency label.

    Examples
    --------
    >>> get_pending_homework("STU101")
    [{'subject': 'Physics', 'due_date': '2025-07-05', 'days_until_due': 4, ...}]
    """
    err = _guard(student_id)
    if err:
        return [err]

    try:
        rows = fetch_homework(student_id, "Pending")
        if not rows:
            return [{"student_id": student_id, "message": "No pending homework found. You are all caught up!"}]

        today = datetime.now().date()
        records = []
        for r in rows:
            delta = days_until(r["due_date"])
            # Apply due_filter
            if due_filter:
                f = due_filter.lower()
                if f == "today" and delta != 0:
                    continue
                if f == "tomorrow" and delta != 1:
                    continue
                if f == "week" and not (0 <= delta <= 7):
                    continue

            if delta < 0:
                urgency = "Overdue"
            elif delta == 0:
                urgency = "Due Today"
            elif delta <= 3:
                urgency = "Due Soon"
            elif delta <= 7:
                urgency = "This Week"
            else:
                urgency = "Upcoming"

            records.append({
                "student_id": student_id,
                "subject": r["subject"],
                "assignment_title": r["assignment_title"],
                "due_date": r["due_date"],
                "status": urgency,
                "days_until_due": delta,
            })

        if not records:
            filter_label = f" due {due_filter}" if due_filter else ""
            return [{"student_id": student_id, "message": f"No pending homework{filter_label} found."}]

        # Sort: overdue first, then soonest
        records.sort(key=lambda x: x["days_until_due"])
        return records
    except Exception as e:
        return [{"error": str(e)}]


# ────────────────────────────────────────────────────────────────────────────
# Tool 5 – Timetable
# ────────────────────────────────────────────────────────────────────────────

def get_timetable_schedule(student_id: str, day: str) -> list:
    """
    Return the class schedule for a student on a specific day.

    Parameters
    ----------
    student_id : str
        The unique student identifier.
    day : str
        Day of the week (e.g., 'Monday', 'Tomorrow', 'Today').

    Returns
    -------
    list[dict]
        Schedule entries with grade, day_of_week, class_time, subject, room.

    Examples
    --------
    >>> get_timetable_schedule("STU101", "Monday")
    [{'grade': '10', 'class_time': '08:00-09:00', 'subject': 'Mathematics', ...}]
    """
    err = _guard(student_id)
    if err:
        return [err]

    try:
        resolved_day = resolve_day(day)
        if not resolved_day:
            return [{"error": f"Invalid day '{day}'. Use a valid weekday name (e.g., Monday) or 'Today'/'Tomorrow'."}]

        grade = fetch_student_grade(student_id)
        if not grade:
            return [{"error": f"Student '{student_id}' not found."}]

        rows = fetch_timetable(grade, resolved_day)
        if not rows:
            return [{"grade": grade, "day_of_week": resolved_day, "message": "No timetable found for this day."}]

        return [
            {
                "grade": grade,
                "day_of_week": resolved_day,
                "class_time": r["class_time"],
                "subject": r["subject"],
                "room": r["room"],
            }
            for r in rows
        ]
    except Exception as e:
        return [{"error": str(e)}]


# ────────────────────────────────────────────────────────────────────────────
# Tool 6 – Academic Performance Analytics (Bonus #2)
# ────────────────────────────────────────────────────────────────────────────

def generate_performance_analytics(student_id: str) -> dict:
    """
    Generate a comprehensive academic performance summary (Bonus Feature #2).

    Aggregates marks and attendance into a unified Academic Health Index,
    including GPA, strongest/weakest subjects, attendance summary, and AI
    data-driven study recommendations.

    Parameters
    ----------
    student_id : str
        The unique student identifier.

    Returns
    -------
    dict
        - student_id, gpa, average_score_percentage, strongest_subjects,
          weakest_subjects, attendance_percentage, attendance_warning,
          academic_health_index, subjects_breakdown, recommendations

    Examples
    --------
    >>> generate_performance_analytics("STU101")
    {'gpa': 8.9, 'academic_health_index': 'Good', 'recommendations': [...]}
    """
    err = _guard(student_id)
    if err:
        return err

    try:
        subject_avgs = fetch_subject_averages(student_id)
        if not subject_avgs:
            return {"student_id": student_id, "message": "No marks records found"}

        overall_avg = round(sum(r["avg_pct"] for r in subject_avgs) / len(subject_avgs), 2)
        gpa = round(overall_avg / 10, 2)

        strongest = [r["subject"] for r in subject_avgs if r["avg_pct"] > 85]
        weakest = [r["subject"] for r in subject_avgs if r["avg_pct"] < 60]

        subjects_breakdown = [
            {
                "subject": r["subject"],
                "average_percentage": round(r["avg_pct"], 2),
                "exam_count": r["exam_count"],
                "grade": marks_status_label(r["avg_pct"]),
            }
            for r in subject_avgs
        ]

        att_row = fetch_attendance_summary(student_id)
        total_att = att_row.get("total") or 0
        present_att = att_row.get("present") or 0
        att_pct = round(present_att / total_att * 100, 2) if total_att else 0.0
        att_warning = "Critical – below 75%" if att_pct < 75 else "Good"

        if overall_avg >= 85 and att_pct >= 90:
            health = "Excellent"
        elif overall_avg >= 70 and att_pct >= 75:
            health = "Good"
        elif overall_avg >= 60 and att_pct >= 60:
            health = "Warning"
        else:
            health = "At Risk"

        recommendations = []
        if att_pct < 75:
            recommendations.append(
                f"⚠️ Attendance is at {att_pct}%. You need at least 75% to be eligible for exams."
            )
        if weakest:
            recommendations.append(f"📚 Focus extra revision on: {', '.join(weakest)}.")
        if strongest:
            recommendations.append(f"🌟 Keep up the great work in: {', '.join(strongest)}.")
        if overall_avg < 70:
            recommendations.append("📝 Schedule weekly tutoring sessions and revise class notes regularly.")
        if overall_avg >= 85 and att_pct >= 90:
            recommendations.append("🏆 Outstanding performance! Consider mentoring peers or tackling advanced topics.")
        if not recommendations:
            recommendations.append("✅ Steady progress – maintain your current revision schedule.")

        return {
            "student_id": student_id,
            "gpa": gpa,
            "average_score_percentage": overall_avg,
            "strongest_subjects": strongest,
            "weakest_subjects": weakest,
            "attendance_percentage": att_pct,
            "attendance_warning": att_warning,
            "academic_health_index": health,
            "subjects_breakdown": subjects_breakdown,
            "recommendations": recommendations,
        }
    except Exception as e:
        return {"error": str(e)}


# ────────────────────────────────────────────────────────────────────────────
# Tool 7 – Exam Study Planner (Bonus #6)
# ────────────────────────────────────────────────────────────────────────────

def generate_exam_study_plan(student_id: str, days_until_exam: int = 15) -> dict:
    """
    Generate a personalized exam study schedule (Bonus Feature #6).

    Based on the student's weakest subjects and available days,
    creates a prioritized daily study plan.

    Parameters
    ----------
    student_id : str
        The unique student identifier.
    days_until_exam : int
        Number of days until exams start (default 15).

    Returns
    -------
    dict
        - student_id, days_until_exam, study_plan (list of {day, subjects, hours}),
          priority_subjects, revision_tips

    Examples
    --------
    >>> generate_exam_study_plan("STU101", 15)
    {'study_plan': [{'day': 1, 'subjects': ['Physics', 'Chemistry'], 'hours': 4}], ...}
    """
    err = _guard(student_id)
    if err:
        return err

    try:
        subject_avgs = fetch_subject_averages(student_id)
        if not subject_avgs:
            return {"student_id": student_id, "message": "No marks data available to create a study plan."}

        # Priority: weakest subjects get most revision days
        weak = [r for r in subject_avgs if r["avg_pct"] < 70]
        moderate = [r for r in subject_avgs if 70 <= r["avg_pct"] < 85]
        strong = [r for r in subject_avgs if r["avg_pct"] >= 85]

        # Allocate study days: 50% weak, 35% moderate, 15% strong (revision)
        weak_days = max(1, int(days_until_exam * 0.5))
        mod_days = max(1, int(days_until_exam * 0.35))
        strong_days = max(1, days_until_exam - weak_days - mod_days)

        study_plan = []
        day_counter = 1

        def add_block(subjects: list, num_days: int, label: str):
            nonlocal day_counter
            if not subjects:
                return
            per_subject = max(1, num_days // len(subjects))
            for s in subjects:
                for _ in range(per_subject):
                    if day_counter > days_until_exam:
                        return
                    study_plan.append({
                        "day": day_counter,
                        "subjects": [s["subject"]],
                        "focus": label,
                        "recommended_hours": 4 if label == "Intensive" else (3 if label == "Practice" else 2),
                        "activities": _study_activities(label),
                    })
                    day_counter += 1

        add_block(weak, weak_days, "Intensive")
        add_block(moderate, mod_days, "Practice")
        add_block(strong, strong_days, "Revision")

        # Fill remaining days with mixed revision
        while day_counter <= days_until_exam:
            all_subjects = [r["subject"] for r in subject_avgs]
            study_plan.append({
                "day": day_counter,
                "subjects": all_subjects[:3],
                "focus": "Mixed Revision",
                "recommended_hours": 3,
                "activities": ["Review all subjects", "Past paper practice", "Summarize notes"],
            })
            day_counter += 1

        priority_subjects = [r["subject"] for r in weak] or [r["subject"] for r in moderate]

        tips = [
            "Study in focused 45-minute blocks with 10-minute breaks (Pomodoro technique).",
            "Attempt at least one past paper per subject before exams.",
            "Revise your weakest topics first when your mind is freshest.",
            "Teach concepts to a friend — it reinforces memory.",
            "Get 7–8 hours of sleep each night; it's essential for memory consolidation.",
        ]
        if weak:
            tips.insert(0, f"🔴 Priority subjects needing immediate attention: {', '.join([r['subject'] for r in weak])}.")

        return {
            "student_id": student_id,
            "days_until_exam": days_until_exam,
            "total_study_days": len(study_plan),
            "priority_subjects": priority_subjects,
            "study_plan": study_plan,
            "revision_tips": tips,
        }
    except Exception as e:
        return {"error": str(e)}


def _study_activities(focus: str) -> list[str]:
    if focus == "Intensive":
        return ["Review chapter notes", "Solve practice problems", "Identify knowledge gaps", "Summarize key concepts"]
    if focus == "Practice":
        return ["Attempt practice questions", "Review textbook examples", "Clarify doubts"]
    return ["Quick chapter review", "Highlight key formulas", "Self-test with past papers"]


# ────────────────────────────────────────────────────────────────────────────
# Tool 8 – Parent Progress Report (Bonus #7)
# ────────────────────────────────────────────────────────────────────────────

def generate_parent_progress_report(student_id: str) -> dict:
    """
    Generate a comprehensive parent-facing progress report (Bonus Feature #7).

    Combines attendance, marks, homework status, fee status, and AI-generated
    suggestions into a single 360-degree student health report.

    Parameters
    ----------
    student_id : str
        The unique student identifier.

    Returns
    -------
    dict
        Full report including:
        - student_info, attendance_summary, subject_wise_marks,
          homework_status, fee_status, overall_health, ai_suggestions

    Examples
    --------
    >>> generate_parent_progress_report("STU101")
    {'student_info': {'name': 'Anupam Hegde', ...}, 'overall_health': 'Good', ...}
    """
    err = _guard(student_id)
    if err:
        return err

    try:
        # Student info
        student = fetch_student(student_id)
        if not student:
            return {"error": f"Student '{student_id}' not found."}

        # Attendance
        att_row = fetch_attendance_summary(student_id)
        total_att = att_row.get("total") or 0
        present_att = att_row.get("present") or 0
        att_pct = round(present_att / total_att * 100, 2) if total_att else 0.0

        # Marks
        subject_avgs = fetch_subject_averages(student_id)
        overall_marks_avg = (
            round(sum(r["avg_pct"] for r in subject_avgs) / len(subject_avgs), 2) if subject_avgs else 0.0
        )
        strongest = [r["subject"] for r in subject_avgs if r["avg_pct"] > 85]
        weakest = [r["subject"] for r in subject_avgs if r["avg_pct"] < 60]

        # Homework
        all_hw = fetch_homework(student_id, status_filter=None)
        total_hw = len(all_hw)
        pending_hw = [r for r in all_hw if r["status"] == "Pending"]
        completed_hw = total_hw - len(pending_hw)
        completion_rate = round(completed_hw / total_hw * 100, 2) if total_hw else 0.0

        # Fees
        all_fees = fetch_fees(student_id)
        total_fee = sum(r["amount"] for r in all_fees)
        paid_fee = sum(r["amount"] for r in all_fees if r["status"] == "Paid")
        pending_fee = total_fee - paid_fee
        pending_fee_months = [r["month"] for r in all_fees if r["status"] == "Pending"]

        # Overall health
        score = 0
        if att_pct >= 90:
            score += 3
        elif att_pct >= 75:
            score += 2
        else:
            score += 0

        if overall_marks_avg >= 85:
            score += 3
        elif overall_marks_avg >= 70:
            score += 2
        elif overall_marks_avg >= 50:
            score += 1

        if completion_rate >= 80:
            score += 2
        elif completion_rate >= 50:
            score += 1

        if score >= 7:
            overall_health = "Excellent"
        elif score >= 5:
            overall_health = "Good"
        elif score >= 3:
            overall_health = "Needs Attention"
        else:
            overall_health = "Urgent Review Required"

        # AI suggestions
        suggestions = []
        if att_pct < 75:
            suggestions.append(
                f"📌 Attendance is critically low at {att_pct}%. Consistent attendance is crucial for academic success."
            )
        if weakest:
            suggestions.append(
                f"📚 Your child needs additional support in: {', '.join(weakest)}. Consider tutoring or extra practice."
            )
        if completion_rate < 60:
            suggestions.append(
                "📝 Homework completion is below 60%. Please encourage regular completion of assignments."
            )
        if pending_fee > 0:
            suggestions.append(
                f"💰 Fee of ₹{pending_fee:.2f} is outstanding for: {', '.join(pending_fee_months)}. Kindly clear dues at the earliest."
            )
        if strongest:
            suggestions.append(
                f"🌟 Excellent performance in {', '.join(strongest)}. Encourage your child to explore advanced topics."
            )
        if not suggestions:
            suggestions.append("✅ Your child is performing well across all areas. Keep up the great work!")

        return {
            "report_title": f"Progress Report – {student['name']}",
            "generated_at": datetime.now().strftime("%Y-%m-%d %H:%M"),
            "student_info": {
                "id": student_id,
                "name": student["name"],
                "grade": student["grade"],
            },
            "attendance_summary": {
                "total_days": total_att,
                "days_present": present_att,
                "days_absent": total_att - present_att,
                "attendance_percentage": att_pct,
                "status": attendance_status_label(att_pct),
            },
            "subject_wise_marks": [
                {
                    "subject": r["subject"],
                    "average_percentage": round(r["avg_pct"], 2),
                    "grade": marks_status_label(r["avg_pct"]),
                }
                for r in subject_avgs
            ],
            "strongest_subjects": strongest,
            "weakest_subjects": weakest,
            "overall_marks_average": overall_marks_avg,
            "homework_status": {
                "total_assignments": total_hw,
                "completed": completed_hw,
                "pending": len(pending_hw),
                "completion_rate_percentage": completion_rate,
            },
            "fee_status": {
                "total_fee": round(total_fee, 2),
                "total_paid": round(paid_fee, 2),
                "total_pending": round(pending_fee, 2),
                "pending_months": pending_fee_months,
            },
            "overall_health": overall_health,
            "ai_suggestions": suggestions,
        }
    except Exception as e:
        return {"error": str(e)}