from __future__ import annotations

import sqlite3
from contextlib import closing
from datetime import date, timedelta
from pathlib import Path


def initialise_database(path: Path) -> Path:
    path = Path(path).expanduser().resolve()
    path.parent.mkdir(parents=True, exist_ok=True)
    with closing(sqlite3.connect(path)) as connection:
        connection.executescript(
            """
            DROP TABLE IF EXISTS students;
            DROP TABLE IF EXISTS attendance;
            DROP TABLE IF EXISTS marks;
            DROP TABLE IF EXISTS fees;
            DROP TABLE IF EXISTS homework;
            DROP TABLE IF EXISTS timetable;
            CREATE TABLE students(id TEXT PRIMARY KEY, name TEXT NOT NULL, grade TEXT NOT NULL);
            CREATE TABLE attendance(id INTEGER PRIMARY KEY AUTOINCREMENT, student_id TEXT NOT NULL, date TEXT NOT NULL, status TEXT NOT NULL, subject TEXT NOT NULL);
            CREATE TABLE marks(id INTEGER PRIMARY KEY AUTOINCREMENT, student_id TEXT NOT NULL, subject TEXT NOT NULL, semester TEXT NOT NULL, marks_obtained REAL NOT NULL, max_marks REAL NOT NULL);
            CREATE TABLE fees(id INTEGER PRIMARY KEY AUTOINCREMENT, student_id TEXT NOT NULL, month TEXT NOT NULL, amount REAL NOT NULL, status TEXT NOT NULL, payment_date TEXT);
            CREATE TABLE homework(id INTEGER PRIMARY KEY AUTOINCREMENT, student_id TEXT NOT NULL, subject TEXT NOT NULL, assignment_title TEXT NOT NULL, due_date TEXT NOT NULL, status TEXT NOT NULL);
            CREATE TABLE timetable(id INTEGER PRIMARY KEY AUTOINCREMENT, grade TEXT NOT NULL, day_of_week TEXT NOT NULL, class_time TEXT NOT NULL, subject TEXT NOT NULL, room TEXT NOT NULL);
            """
        )
        connection.execute("INSERT INTO students(id,name,grade) VALUES(?,?,?)", ("STU101", "Alex Student", "10"))
        start = date.today() - timedelta(days=24)
        for index in range(20):
            status = "Absent" if index in {4, 11, 17} else "Present"
            connection.execute("INSERT INTO attendance(student_id,date,status,subject) VALUES(?,?,?,?)", ("STU101", (start + timedelta(days=index)).isoformat(), status, "General"))
        for subject, scores in {"Mathematics": [88, 92], "Physics": [82, 86], "English": [76, 80], "Chemistry": [68, 72]}.items():
            for semester, score in enumerate(scores, start=1):
                connection.execute("INSERT INTO marks(student_id,subject,semester,marks_obtained,max_marks) VALUES(?,?,?,?,?)", ("STU101", subject, f"Semester {semester}", score, 100))
        for month, status in [("January", "Paid"), ("February", "Paid"), ("March", "Pending")]:
            connection.execute("INSERT INTO fees(student_id,month,amount,status,payment_date) VALUES(?,?,?,?,?)", ("STU101", month, 500.0, status, date.today().isoformat() if status == "Paid" else None))
        connection.execute("INSERT INTO homework(student_id,subject,assignment_title,due_date,status) VALUES(?,?,?,?,?)", ("STU101", "Mathematics", "Algebra worksheet", (date.today() + timedelta(days=2)).isoformat(), "Pending"))
        connection.execute("INSERT INTO homework(student_id,subject,assignment_title,due_date,status) VALUES(?,?,?,?,?)", ("STU101", "Physics", "Motion report", (date.today() + timedelta(days=5)).isoformat(), "Pending"))
        schedule = [("Monday", "08:00-09:00", "Mathematics", "R1"), ("Monday", "09:00-10:00", "Physics", "Lab"), ("Tuesday", "08:00-09:00", "English", "R2"), ("Wednesday", "10:00-11:00", "Chemistry", "Lab")]
        for day, class_time, subject, room in schedule:
            connection.execute("INSERT INTO timetable(grade,day_of_week,class_time,subject,room) VALUES(?,?,?,?,?)", ("10", day, class_time, subject, room))
        connection.commit()
    return path


if __name__ == "__main__":
    default = Path(__file__).resolve().parent / "school_erp.db"
    print(initialise_database(default))
