# Tier 3 Agent Restoration — Runtime Repair Pass 4

The supplied capability review was compared against the runtime rather than copied as duplicate classes.

## Result

- 62 canonical Tier 3 action classes were already present.
- All 50 agents named in the capability review are present.
- The additional 12 field-service agents are also present.
- Eleven renamed field-service identities remain compatibility aliases and do not inflate counts.

## Repair

A canonical Tier 3 capability catalog now enriches every agent definition with:

- domain and capability category;
- triggers and follow-up chains;
- governed execution mode;
- timeout and retry policy;
- idempotency and audit requirements;
- WorkCore-only operational authority;
- offline/realtime metadata;
- human-approval requirements for high-risk actions.

Native executable agents continue using their typed WorkCore clients. Declarative agents continue through the governed WorkCore execution path; they are not allowed to bypass governance.
