# Titan 14-App Template Schema

All Titan apps now use `resources/titan-apps/TemplateSchemas/*.json` as the common UI and device-runtime contract.

Each schema defines identity, primary and drawer navigation, home widgets, persistent-chat context policy, WorkCore domains/commands/read models, offline records, permissions, privacy defaults, notifications, settings sections and preview states.

`TemplateSchema` loads these contracts and `TemplateNavigation` exposes the active schema to the chatbot shell. WorkCore remains server-authoritative and chat context is minimum-scope by default.
