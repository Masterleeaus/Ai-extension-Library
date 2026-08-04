# Interaction Engine Chatbot Bridge

This is a thin marketplace adapter, not a second Interaction Engine.

- PHP authority comes from `titanzero/interaction-engine` through Composer.
- Offline PWA behaviour comes from `@titanzero/interaction-engine-offline` through the Chatbot asset build.
- Shared payload shapes come from `@titanzero/interaction-contracts`.

The bridge intentionally contains no duplicated domain services, models, migrations or command handlers.
