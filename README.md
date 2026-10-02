![Titan AI Extension Library — AI modules, governed by one platform](docs/images/portfolio-banner.svg)

# Titan AI Extension Library

**A provenance-aware library of AI extensions and shared platform capabilities for the Titan ecosystem.**

Titan AI Extension Library brings together a curated set of AI extensions, shared runtime foundations, integration patterns, and implementation records. It gives developers a traceable source for exploring, validating, and evolving AI capabilities across chat, agents, voice, model providers, creative tools, and business workflows.

The repository combines a broad extension catalogue with a focused shared foundation. Its extraction and materialisation workflow preserves source paths and file integrity, while verified overlays allow selected core suites to evolve without silently rewriting the remainder of the library.

## At a glance

| | |
|---|---|
| **Curated extensions** | 78 |
| **Source and asset files** | 4,213 |
| **Core suites** | AIChatPro, Chatbot, AIAgent |
| **Shared foundation** | `packages/titanai-hybrid-core/` |
| **Primary stack** | Laravel, PHP, JavaScript, Composer |

Counts describe the documented source inventory and should be regenerated when the underlying catalogue changes.

## What makes it distinctive

- **Traceable source preservation** — extracted files retain their paths, byte counts, categories, and SHA-256 checksums in the transport dataset.
- **Controlled core evolution** — verified overlays update AIChatPro, Chatbot, and AIAgent while preserving the other selected extensions.
- **Shared platform foundation** — common capabilities are organized as a Composer package instead of being duplicated across suites.
- **Broad integration surface** — the catalogue spans conversational AI, autonomous agents, voice, model providers, creative generation, content, and business workflows.
- **Reproducible materialisation** — repository automation restores source data, applies the verified overlay, validates package boundaries, and regenerates inventories.

## Architecture

```text
Lossless extension dataset
          │
          ├── Curated extensions ──> extensions/
          │
          └── Verified core overlay
                     │
                     ├── AIChatPro
                     ├── Chatbot
                     ├── AIAgent
                     └── Shared foundation ──> packages/titanai-hybrid-core/
```

The extraction dataset is the preserved source record. The verified overlay is the managed change layer for the selected core suites. The shared Composer package defines reusable platform code consumed by those integrations.

## Catalogue

The documented catalogue contains:

- AIChatPro and chat workspace — 16 extensions
- Chatbot and customer conversation runtime — 9 extensions
- Autonomous agents and workflow automation — 10 extensions
- Model providers and model orchestration — 10 extensions
- Creative, image, audio, and video AI — 26 extensions
- Content, SEO, social, and growth AI — 6 extensions

Browse the [extension catalogue](docs/EXTENSION-CATALOG.md) and machine-readable [CSV](docs/extension-inventory.csv) or [JSON](docs/extension-inventory.json) inventories for extension-level detail.

## Documentation

- [Start here](docs/00_READ_ME_FIRST.md)
- [Document index](docs/DOCUMENT-INDEX.md)
- [Core-suite architecture scan](docs/CORE-SUITES-DEEP-SCAN.md)
- [Verified core-suite overlay](docs/CORE-SUITES-PASS3-REPLACEMENT.md)
- [Shared Composer package](docs/TITANAI-COMPOSER-PACKAGE.md)
- [Architecture risks and recommendations](docs/ARCHITECTURE-RISKS-AND-RECOMMENDATIONS.md)
- [Historical root documents](docs/legacy-root/README.md)

## Development

This is a Laravel application repository with Composer and Node-based frontend tooling. Use the project dependency manifests and existing CI workflows as the source of truth for supported commands.

```bash
composer install
npm install
```

Before submitting changes, run the relevant project tests and checks for the files changed. For extension or source-dataset changes, also verify the generated inventories and materialisation workflow.

## Project status

This repository is an evolving source library and integration foundation. Its inventory and documented capabilities describe repository contents; they do not imply that every extension is production-ready, enabled, or supported as a standalone product.

## License and attribution

Review the applicable license and attribution information for the application, packages, and individual extensions before redistributing or deploying them. Third-party components may carry separate terms.