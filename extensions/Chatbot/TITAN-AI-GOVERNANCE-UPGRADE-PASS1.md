# Titan AI Governance Upgrade — Pass 1

Implemented:

- Risk levels: green, amber, red and critical.
- Independent same-provider/model-slug council reviewers.
- Evidence-focused structured council output.
- Confidence threshold and disagreement handling.
- Mandatory human approval for critical actions.
- WorkCore fact-verification adapter.
- Governed tool definitions and executor.
- Skill definitions and allow-listed orchestration.
- Operating personas with tools, skills, models, memory and approval rules.
- Ten explicit memory scopes and write policies.
- Reflection memory marked suggested/non-authoritative.
- WorkCore-authoritative booking, availability, rescheduling and cancellation.
- Removal of fabricated booking/job/customer IDs from Agent Booking.

Next passes should add persistence tables, administration UI, reviewer audit records, human approval queues, provider adapters for the host router, and concrete packaged skills/personas.
