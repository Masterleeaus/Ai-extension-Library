# Integrated Staff Inbox Implementation Plan

**Goal:** Merge the chatbot-agent staff inbox into the Chatbot extension as a first-class feature without replacing the builder or creating legacy integration folders.

**Architecture:** Staff inbox controllers, realtime services, events, views, and styles live under the existing `App\\Extensions\\Chatbot` namespace. Existing chatbot models and services remain authoritative. Route names use `dashboard.chatbot.inbox.*`; realtime delivery uses the existing Ably settings and gracefully no-ops when no key is configured.

**Constraints:** Preserve builder code and visual design; reuse existing Chatbot models and services; no duplicate extension provider; no legacy/source/integration folders; maintain existing Laravel APIs.

## Tasks

1. Relocate staff inbox controllers and realtime classes into focused Chatbot subnamespaces.
2. Relocate Blade templates and SCSS into `resources/views/staff-inbox` and `resources/assets/scss`.
3. Register staff inbox and realtime settings routes in `ChatbotServiceProvider`.
4. Redirect existing chatbot realtime dispatch imports to the integrated service.
5. Remove self-registration checks and old `chatbot-agent` view namespaces.
6. Run PHP syntax, reference, archive, and folder-policy checks.
