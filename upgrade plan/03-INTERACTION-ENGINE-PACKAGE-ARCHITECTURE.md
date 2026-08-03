# Interaction Engine Package Architecture

## Decision

The Interaction Engine is infrastructure shared by Chatbot, AIAgent and future Titan applications. Its authoritative PHP runtime is therefore a Composer library, not another independently enabled feature extension.

The offline PWA runtime is a separate NPM package. The Chatbot marketplace layer uses a thin bridge that depends on both packages and may include compiled distribution assets, but it must not fork the source implementation.

## Package map

```text
packages/titan-interaction-engine/          Composer/Laravel authority
packages/titan-interaction-engine-offline/  IndexedDB, encrypted outbox, local intelligence and sync
packages/titan-interaction-contracts/        OpenAPI and JSON Schema protocol source of truth
extensions/InteractionEngineChatbotBridge/   Thin Chatbot/PWA adapter
```

## Authority boundary

The device may validate drafts, make deterministic local suggestions and queue encrypted commands. It may not grant authorization or perform authoritative operational writes. On sync, the server must authenticate the actor and device, resolve the tenant, validate protocol versions, enforce policy and permissions, apply idempotency, execute through WorkCore and return a receipt.

## Distribution

During monorepo development, the host uses Composer and NPM path repositories. Production releases should publish the Composer packages through Private Packagist, Satis or GitHub Packages and publish the NPM packages through a private registry. Semantic versions are independent but protocol compatibility is explicit.

## Migration from the uploaded archive

The uploaded archive mixed Composer, Laravel module, NPM and marketplace extension identities. The import removes `module.json`, root `extension.json`, PWA source and duplicated `src/Extensions` code from the Composer library. The original provenance, reports, templates, wizards, tests and server implementation are preserved in their appropriate package.

## Verification gates

- Composer package is `type: library` with Laravel package discovery.
- No marketplace descriptor or PWA TypeScript remains inside the Composer package.
- Offline NPM package builds and passes its eight tests independently.
- Server package passes its PHP verification suite independently.
- Shared JSON schemas parse and carry explicit versioned identifiers.
- Chatbot bridge contains no domain implementation or migrations.
