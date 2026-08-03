# Titan AI Governance Upgrade — Design

## Scope

This first cumulative pass converts five existing feature families into one governed execution pipeline:

`Persona → Skill → Risk classification → Model Council → Governed tool → WorkCore`.

## Architecture

- **WorkCore remains authoritative.** AI code may request reads and mutations only through `WorkCoreGateway`; booking code must not fabricate authoritative customer, booking or job IDs.
- **Council review is risk-based.** Green actions run without council review. Amber uses one independent reviewer. Red uses two. Critical actions use two reviewers and always require human approval.
- **Same-provider diversity is supported.** Reviewers are configured by model slug, so two OpenAI models can independently review the same proposed action. Each receives the original case, facts and constraints—not another model's hidden reasoning.
- **Tools are governed operations.** Each tool defines domain, operation, risk, permissions, audit, idempotency, timeout and rollback support.
- **Skills orchestrate allow-listed tools.** A skill cannot invoke tools outside its declared tool set.
- **Personas are operating profiles.** They constrain tools, skills, models, memory scopes and approval rules rather than merely changing tone.
- **Memory is scoped and policy-controlled.** Conversation, session, user, business, domain, agent, episodic, semantic, procedural and reflection scopes are explicit. Reflection memory is non-authoritative until approved.

## Booking flow

1. Validate required booking data.
2. Query WorkCore availability.
3. Reject unavailable requests without creating local authoritative records.
4. Classify booking as amber risk.
5. Run an independent council verifier.
6. Execute `jobs.book_job` through WorkCore with tenant, user, device and idempotency context.
7. Return the WorkCore result as the canonical booking result.

## Failure behaviour

- Missing WorkCore binding blocks mutation.
- Insufficient council reviewers blocks council-required actions.
- Any reviewer rejection blocks the action.
- Low-confidence or disagreement routes to human review.
- Critical actions never auto-execute.
