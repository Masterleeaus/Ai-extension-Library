# Titan AI Governance Upgrade Pass 4

Adds a production OpenAI Responses API adapter for independent Model Council reviewers, strict JSON Schema responses, fail-closed distinct-model enforcement, retries, timeouts, token accounting, configurable cost limits, request IDs and response IDs.

Model slugs and pricing are configuration values. Deployments must set models actually available to their OpenAI project and current pricing values.
