# Titan Zero Offline Architecture

## Architectural rule

LocalBrain v2 is the only intelligence engine in this package. The runtime must remain functional with the network physically disconnected. External AI SDKs, provider keys and silent online fallbacks are prohibited.

## Components

### Interaction Engine

The Laravel-compatible package owns:

- LocalBrain v2.
- Wizard and interaction definitions.
- Local reasoning and persona analysis.
- Memory ranking and behavioral learning.
- Governance, authority and audit.
- Offline queues and synchronization.
- WorkCore command preparation and adapters.

### LocalBrain CLI bridge

`interaction-engine/bin/localbrain.php` accepts one JSON document on stdin and returns one JSON document on stdout. It loads the same PHP LocalBrain classes used by the Laravel package.

Input:

```json
{
  "message": "Show my attendance",
  "context": {
    "tenant_id": "school-a",
    "user_id": "parent-a",
    "subject_id": "STU101"
  },
  "memories": []
}
```

The output includes perception, persona, decision, ranked memories, confidence, model version and an audit field proving `cloud_used=false`.

### Offline ERP façade

The Python service does not classify intent independently. Its responsibilities are limited to:

1. Resolve tenant, user and student scope.
2. Retrieve scoped local conversation messages.
3. Invoke LocalBrain through the CLI bridge.
4. Enforce the confidence threshold.
5. Map supported LocalBrain intents to local tools.
6. Execute deterministic SQLite queries.
7. Format a response from verified tool data and LocalBrain persona signals.
8. Store the response in scoped local memory.

## Data flow

```text
FastAPI request
  -> MemoryScope(tenant, user, student)
  -> prior local messages
  -> LocalBrain CLI
  -> intent + entities + confidence + persona + ranked memories
  -> confidence >= configured threshold?
       no: clarification, no tool
       yes: allowlisted read-only ERP tool
  -> SQLite result
  -> deterministic response template
  -> scoped SQLite memory
```

## Supported school intents

| LocalBrain intent | Tool | Consequence |
|---|---|---|
| `attendance_query` | `get_attendance_summary` | Read only |
| `marks_query` | `get_marks_records` | Read only |
| `fees_query` | `get_fee_status` | Read only |
| `homework_query` | `get_pending_homework` | Read only |
| `timetable_query` | `get_timetable_schedule` | Read only |
| `performance_analysis` | `generate_performance_analytics` | Derived local analysis |
| `study_plan` | `generate_exam_study_plan` | Derived local plan |
| `progress_report` | `generate_parent_progress_report` | Derived local report |

Unsupported or low-confidence intents return clarification and execute nothing.

## Memory model

Conversation messages are stored in SQLite and partitioned by:

- tenant ID;
- user ID;
- subject/student ID.

LocalBrain performs ranking over candidate memories. Conversation history is not automatically promoted to operational truth.

## Security boundaries

- No provider SDKs.
- No AI API keys.
- No remote LocalBrain URL.
- The LocalBrain script path must remain inside the extracted package.
- The subprocess environment receives only the executable search path.
- Tool names are allowlisted.
- Low-confidence inputs cannot execute tools.
- Logs record query length rather than raw request text.
- Memory queries always require the full scope tuple.

## Remaining production work

- Host-authenticated tenant resolution instead of caller-supplied API scope.
- Encryption at rest for the Python memory database.
- Concrete WorkCore adapters for all required business writes.
- Payload-bound, one-time approval consumption.
- Full device benchmark suite.
- Concrete vertical adapters.
