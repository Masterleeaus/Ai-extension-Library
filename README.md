# AI Extensions

Deep-scanned and losslessly extracted AI extension library from `Extensions(2).zip`.

## Repository scope

- **78 selected AI extensions**
- **4,204 source and asset files**
- **77.6 MB raw extension content**
- Core suites: **AIChatPro**, **Chatbot**, and **AIAgent**
- Canonical chatbot: `Chatbot` v6.9.0. `TitanZeroChatbot` was excluded as an older near-duplicate; the canonical folder contains nine additional Titan Train/PWA files.

## Materialisation

The complete extraction is stored as a lossless MiniUp Parquet transport dataset. Each row preserves the original path, file bytes compressed with zlib and base64, raw byte count, category, and SHA-256 checksum.

The GitHub Actions workflow `.github/workflows/materialize-ai-extensions.yml` downloads that dataset and reconstructs the full `extensions/` tree, verifies every checksum, then commits the result to the working branch.

## Documentation

- [`docs/CORE-SUITES-DEEP-SCAN.md`](docs/CORE-SUITES-DEEP-SCAN.md) — architecture and capability analysis of AIChatPro, Chatbot, and AIAgent.
- [`docs/EXTENSION-CATALOG.md`](docs/EXTENSION-CATALOG.md) — features, routes, data models, dependencies, external services, and public functions for all 78 selected extensions.
- [`docs/ARCHITECTURE-RISKS-AND-RECOMMENDATIONS.md`](docs/ARCHITECTURE-RISKS-AND-RECOMMENDATIONS.md) — overlap, technical risks, extraction decisions, and recommended consolidation.
- [`docs/extension-inventory.csv`](docs/extension-inventory.csv) — compact machine-readable inventory.
- [`docs/extension-inventory.json`](docs/extension-inventory.json) — detailed machine-readable scan with class methods, routes, tables, hashes, dependencies, domains, TODOs, and flagged execution patterns.

## Categories

- **AIChatPro & chat workspace:** 16 extensions, 260 files, 1.3 MB
- **Chatbot & customer conversation runtime:** 9 extensions, 1,704 files, 6.2 MB
- **Autonomous agents & workflow automation:** 10 extensions, 679 files, 10.4 MB
- **Model providers & model orchestration:** 10 extensions, 113 files, 357.1 KB
- **Creative, image, audio & video AI:** 26 extensions, 976 files, 51.1 MB
- **Content, SEO, social & growth AI:** 6 extensions, 320 files, 7.9 MB
- **Business discovery & intelligence:** 1 extension, 152 files, 237.9 KB

## Installation model

These are MagicAI/Laravel marketplace extensions. Keep each extension folder name unchanged when copying into the host extension directory. Install the core extension before its add-ons and run the host migration/publish process in a controlled environment. Do not enable all packages simultaneously without resolving duplicated authority boundaries described in the architecture report.
