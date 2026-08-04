# Titan Zero Offline School ERP Façade

This FastAPI service is a local tool façade powered exclusively by the PHP LocalBrain v2 runtime in the sibling `interaction-engine` directory. It contains no cloud model client, no provider key selection and no online fallback.

## Supported queries

- Attendance.
- Marks.
- Fees.
- Homework.
- Timetable.
- Performance analytics.
- Study plans.
- Parent progress reports.

## Execution flow

```text
request
 -> scoped SQLite history
 -> PHP LocalBrain subprocess
 -> intent/entities/persona/confidence/memory ranking
 -> confidence gate
 -> allowlisted local ERP tool
 -> deterministic response
 -> scoped SQLite history
```

Python does not contain a fallback intent classifier. If LocalBrain cannot run, the request fails closed.

## Setup

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python3 mock_data/init_db.py
uvicorn app.main:app --host 127.0.0.1 --port 8000
```

Install test-only dependencies with:

```bash
pip install -r requirements-dev.txt
```

## API

### `POST /chat`

```json
{
  "message": "Show my attendance for this month",
  "student_id": "STU101",
  "tenant_id": "school-a",
  "user_id": "parent-a"
}
```

The response includes the LocalBrain confidence, selected local tool, execution plan, verified local data and `mode: offline`.

### `GET /chat/history`

Required scope parameters are tenant, user and student. The store never performs an unscoped history query.

### `DELETE /chat/history`

Clears only the supplied scope.

### `GET /health`

Returns:

```json
{
  "status": "healthy",
  "mode": "offline",
  "brain": "local-brain-v2",
  "cloud_enabled": false,
  "network_ai_calls": 0
}
```

## Tests

```bash
python3 -m unittest discover -s tests -v
```

The tests cover all eight intents, low-confidence clarification, LocalBrain subprocess operation, LocalBrain memory ranking, API startup and tenant/user memory isolation.
