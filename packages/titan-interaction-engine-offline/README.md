# Titan Interaction Engine Offline

Browser-safe offline companion for the Titan Interaction Engine.

It owns IndexedDB persistence, encrypted command and cognitive-event outboxes, local wizard drafts, deterministic local language understanding, memory reranking, persona-drift signals, vector clocks and sync transport. It does **not** grant server authority: every synced command is re-authenticated, tenant-authorized and policy-validated by the Composer package.

```bash
npm install @titanzero/interaction-engine-offline
```

The package is CommonJS-compatible so Vite can bundle it into the Chatbot PWA.
