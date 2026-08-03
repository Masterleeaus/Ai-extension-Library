# AI Extensions

Deep-scanned and losslessly extracted AI extension library from `Extensions(2).zip`, with verified extension overlays for upgraded packages.

## Repository scope

- **78 selected AI extensions**
- **4,590 source and asset files** after the current overlays
- **79.0 MiB raw extension content**
- Core suites: **AIChatPro**, **Chatbot**, and **AIAgent**
- Canonical chatbot: `Chatbot` v6.9.0. `TitanZeroChatbot` was excluded as an older near-duplicate; the canonical folder contains nine additional Titan Train/PWA files.
- Upgraded ecommerce package: `ChatbotEcommerce` **v4.9.0**, replacing the original v1.0.0 folder.

## Materialisation

The original extraction is stored as a lossless MiniUp Parquet transport dataset. Each row preserves the original path, file bytes compressed with zlib and base64, raw byte count, category, and SHA-256 checksum.

Upgraded extension packages are stored as separate lossless MiniUp overlay datasets. During materialisation, each overlay removes only its declared extension folder and replaces it with checksum-verified files from the overlay. This prevents future base-dataset restorations from reverting upgraded extensions.

The GitHub Actions workflow `.github/workflows/materialize-ai-extensions.yml` downloads the base dataset and all declared overlays, reconstructs the complete `extensions/` tree, verifies every file checksum, regenerates the inventories, and commits the result to the working branch.

## Documentation

- [`docs/CORE-SUITES-DEEP-SCAN.md`](docs/CORE-SUITES-DEEP-SCAN.md) — architecture and capability analysis of AIChatPro, Chatbot, and AIAgent.
- [`docs/EXTENSION-CATALOG.md`](docs/EXTENSION-CATALOG.md) — features, routes, data models, dependencies, external services, and public functions for the selected extensions.
- [`docs/CHATBOT-ECOMMERCE-V4.9.0-DEEP-SCAN.md`](docs/CHATBOT-ECOMMERCE-V4.9.0-DEEP-SCAN.md) — the upgraded ecommerce architecture, security boundaries, marketplace capabilities, verification evidence, and host-integration requirements.
- [`docs/ARCHITECTURE-RISKS-AND-RECOMMENDATIONS.md`](docs/ARCHITECTURE-RISKS-AND-RECOMMENDATIONS.md) — overlap, technical risks, extraction decisions, and recommended consolidation.
- [`docs/extension-inventory.csv`](docs/extension-inventory.csv) — compact machine-readable inventory.
- [`docs/extension-inventory.json`](docs/extension-inventory.json) — generated inventory of folders, versions, file counts, and sizes.

## Categories

- **AIChatPro & chat workspace:** 16 extensions, 260 files, 1.3 MB
- **Chatbot & customer conversation runtime:** 9 extensions, 2,090 files, approximately 7.7 MB
- **Autonomous agents & workflow automation:** 10 extensions, 679 files, 10.4 MB
- **Model providers & model orchestration:** 10 extensions, 113 files, 357.1 KB
- **Creative, image, audio & video AI:** 26 extensions, 976 files, 51.1 MB
- **Content, SEO, social & growth AI:** 6 extensions, 320 files, 7.9 MB
- **Business discovery & intelligence:** 1 extension, 152 files, 237.9 KB

## Installation model

These are MagicAI/Laravel marketplace extensions. Keep each extension folder name unchanged when copying into the host extension directory. Install the core extension before its add-ons and run the host migration/publish process in a controlled environment. Do not enable all packages simultaneously without resolving duplicated authority boundaries described in the architecture report.
