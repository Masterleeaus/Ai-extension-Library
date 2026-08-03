# Pass 11 — Project Source Architecture Scan

## Promoted architectural rules

- MagicAI/Meetup host owns identity, tenancy, memberships and extension lifecycle.
- Chatbot/Titan Go owns external and field-worker conversational surfaces and local-first presentation state.
- Titan Zero owns intent, orchestration, delegation, governance and memory.
- WorkCore is the only authority allowed to mutate operational business records.
- Titan Money owns operational finance when separated from WorkCore finance; MagicAI billing remains platform billing.
- Optional extensions and Titan BOS modules are capability donors until explicitly promoted through canonical contracts.

## Interaction Engine decision

The Interaction Engine contains a broad and useful contract inventory, but it is not merged as a parallel runtime. Pass 11 catalogues its cognitive, planning, memory, interaction, infrastructure, business-intelligence and learning interfaces. Individual contracts may be promoted only through typed adapters, governance, tests and measured runtime benefit.

## Added diagnostics

`GET /api/v2/chatbot/titan-ai/runtime/project-architecture`

The endpoint reports canonical authority boundaries, project-source roles, Interaction Engine adoption policy and hard architectural rules.
