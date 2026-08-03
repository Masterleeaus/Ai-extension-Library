# AI Extension Catalogue

Static deep scan of all 78 selected AI extensions. Each category page records detected features, public methods, routes, persistence, integration signals, providers, and review notes.

## How to read this catalogue

The catalogue is an evidence index, not a runtime certification.

- **Functions** are detected public executable methods. Dynamically registered behaviour may add more.
- **Routes** are statically detected declarations. Reachability, middleware, naming, registration order, and collisions require runtime tests.
- **Tables and migrations** are detected schema declarations. Successful apply/rollback and compatibility with the host are not yet proven.
- **Dependencies** or cross-extension relationships are static integration signals. They may represent a hard prerequisite, an optional integration, an add-on consuming the named extension, or a conditional marketplace check. Direction must be verified through manifests and boot/install tests.
- **Providers** are detected external-service references, not confirmation that credentials, callbacks, quotas, or error paths are production-ready.
- **No bundled tests detected** means no tests were found in that selected extension folder by the scan; tests may exist elsewhere upstream.
- File-facet counts may overlap. Blade views are PHP files, so PHP and view counts must not be added together.

The compact CSV and JSON inventories preserve source-manifest metadata. Known upstream defects include `AzureOpenai` and `AiViralClips` identifying themselves as `Example`, and `AiVideoPro` identifying itself as `AI Avatar Pro`. Human-readable headings use capability-derived names. See [Documentation Audit](DOCUMENTATION-AUDIT.md).

## Categories

- [AIChatPro & Chat Workspace](catalog/core-chat.md) — 16 extensions
- [Chatbot & Conversation Runtime](catalog/chatbot-suite.md) — 9 extensions
- [Autonomous Agents & Workflow Automation](catalog/agent-suite.md) — 10 extensions
- [Model Providers & Orchestration](catalog/model-providers.md) — 10 extensions
- [Creative, Image, Audio & Video AI](catalog/creative-ai.md) — 26 extensions
- [Content, SEO, Social & Growth AI](catalog/growth-ai.md) — 6 extensions
- [Business Intelligence](catalog/business-intelligence.md) — 1 extension

## Aggregate verification

- Category extension counts total **78**.
- Category file counts total **4,204**.
- The canonical `Chatbot` folder is retained instead of the older `TitanZeroChatbot` near-duplicate.
- Detailed confidence limits, manifest defects, and outstanding runtime checks are documented in [DOCUMENTATION-AUDIT.md](DOCUMENTATION-AUDIT.md).
