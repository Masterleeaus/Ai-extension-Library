# Documentation Audit

## Scope

This audit reviews every file currently under `docs/` on the `docs/ai-extension-upgrade-plan` branch:

1. `CORE-SUITES-DEEP-SCAN.md`
2. `EXTENSION-CATALOG.md`
3. `ARCHITECTURE-RISKS-AND-RECOMMENDATIONS.md`
4. `catalog/core-chat.md`
5. `catalog/chatbot-suite.md`
6. `catalog/agent-suite.md`
7. `catalog/model-providers.md`
8. `catalog/creative-ai.md`
9. `catalog/growth-ai.md`
10. `catalog/business-intelligence.md`
11. `extension-inventory.csv`
12. `extension-inventory.json`

The audit cross-checks catalogue claims against the selected-extension inventory, source manifests, repository structure, known route declarations, and the existing deep-scan evidence. It distinguishes source facts from static-scan inference.

## Audit result

The documentation is broadly useful and the aggregate inventory is internally consistent:

- 78 selected extensions.
- 4,204 materialised source and asset files.
- Category extension counts total 78.
- Category file counts total 4,204.
- `Chatbot` is correctly treated as the canonical folder rather than the older `TitanZeroChatbot` near-duplicate.

However, several statements required correction or qualification.

## Confirmed corrections

### 1. Compact inventory scope

The former repository description overstated the contents of `extension-inventory.json`. The CSV and JSON files contain compact manifest-derived inventory fields:

- folder
- category
- name
- version
- description
- file count
- byte count

They do **not** contain the detailed methods, routes, tables, hashes, dependency analysis, TODO markers, or execution-pattern findings. Those details live in the catalogue and architecture reports.

### 2. Upstream manifest defects

The compact inventories faithfully preserve source manifest data, including incorrect upstream display metadata:

| Folder | Manifest value | Capability-derived documentation name | Finding |
|---|---|---|---|
| `AzureOpenai` | `Example` | Azure OpenAI | Placeholder manifest name and description |
| `AiViralClips` | `Example` | AI Viral Clips | Placeholder manifest name and description |
| `AiVideoPro` | `AI Avatar Pro` | AI Video Pro | Manifest identity does not match the implemented video-generation capability and folder name |

The inventory files remain source-faithful. The human-readable catalogue uses capability-derived names and explicitly records these defects. The extension manifests themselves should be corrected before marketplace publication.

### 3. Dependency direction

Several catalogue bullets previously used the word **Dependencies** for relationships detected through imports, extension checks, UI integrations, tool bridges, or optional capability references. Static scanning does not always establish installation direction.

Examples include:

- AIChatPro references to its add-on suite.
- CreativeSuite references to AI Template and Annotations add-ons.
- SocialMedia references to SocialMediaAgent and SocialMediaAutomation, which are more plausibly consumers of the SocialMedia platform.
- AIImagePro, VideoEditor, FashionStudio, ChatbotAgent, and other packages with optional cross-extension integrations.

Catalogue relationship bullets must therefore be read as **detected integration/dependency signals**, not automatically as verified hard prerequisites. Hard dependency direction requires manifest constraints, service-provider boot tests, and install-order tests.

### 4. Static-scan confidence

Detected public methods, routes, migrations, tables, providers, TODO markers, and process-execution matches are observations from static inspection. They are not proof that:

- every service provider boots;
- every route is reachable;
- every migration applies or rolls back cleanly;
- every dependency is installed in the asserted direction;
- every provider callback is authenticated;
- dynamically registered tools, actions, or routes are complete;
- host overrides do not change runtime behaviour.

All catalogue pages now inherit this interpretation rule from `EXTENSION-CATALOG.md`.

### 5. Overlapping file counts

Counts such as “PHP files” and “view files” may overlap because Blade templates are PHP files. They are measured facets, not mutually exclusive categories, and should not be added together to infer a total.

### 6. Route collisions

The documentation correctly identifies two confirmed collision families:

- `FluxPro`, `NanoBanana`, and `SeeDreamV4` each declare `ANY generator/webhook/fal-ai`.
- `ModelCouncil` and `MultiModel` each declare `POST /accept-response`.

These are not harmless duplicates. Route registration order can determine which controller receives a callback. The upgrade plan must add route collision tests and either package-prefix routes or provide shared callback dispatchers.

### 7. Test coverage language

The scan detected a meaningful test tree only in the canonical `Chatbot` package. “No bundled tests detected” means no tests were found by the static scan in the selected extension folder; it does not prove that tests do not exist in a separate upstream repository or private CI environment.

## File-by-file verdict

| File | Verdict | Action |
|---|---|---|
| `CORE-SUITES-DEEP-SCAN.md` | Sound architecture; needed confidence and count-overlap qualification | Corrected |
| `EXTENSION-CATALOG.md` | Correct category index; methodology was too terse | Corrected |
| `ARCHITECTURE-RISKS-AND-RECOMMENDATIONS.md` | Sound; missing inventory limits, manifest defects, route collisions, and bounded-storage warning | Corrected |
| `catalog/core-chat.md` | Capabilities broadly consistent; relationship direction is not always a verified hard dependency | Interpreted under corrected catalogue rules |
| `catalog/chatbot-suite.md` | Broadly consistent; add-on relationships and detected routes require runtime verification | Interpreted under corrected catalogue rules |
| `catalog/agent-suite.md` | Broadly consistent; webhook safety and tool-bridge claims require runtime tests | Interpreted under corrected catalogue rules |
| `catalog/model-providers.md` | Broadly consistent; contains confirmed callback and route collisions | Collision warning retained and strengthened in architecture report |
| `catalog/creative-ai.md` | Broadly consistent; manifest-derived naming defects corrected in headings, and integration direction remains provisional | Explained here and in catalogue rules |
| `catalog/growth-ai.md` | Broadly consistent; SocialMedia relationship direction must not be treated as a hard prerequisite | Interpreted under corrected catalogue rules |
| `catalog/business-intelligence.md` | Concise and internally consistent | No content correction required |
| `extension-inventory.csv` | Accurate source-manifest inventory, including upstream metadata defects | Retained as source-faithful data |
| `extension-inventory.json` | Accurate JSON form of the compact inventory; formerly over-described by the root README | Retained; surrounding documentation corrected |

## Runtime verification still required

The following claims remain provisional until executable checks are added:

1. Service-provider boot order for all 78 extensions.
2. Route-name and method/path collision detection.
3. Migration apply and rollback compatibility.
4. Namespace and autoload collision detection.
5. Required-versus-optional dependency direction.
6. Webhook signature, replay, idempotency, and tenant-resolution conformance.
7. Tool/action schema compatibility across AIChatPro, Chatbot, and AIAgent.
8. Credential storage and secret-redaction behaviour.
9. Provider callback ownership.
10. Cross-extension integration paths and feature-flag behaviour.

## Documentation maintenance rules

Future scans should:

- preserve raw manifest data in machine-readable inventories;
- keep corrected human-facing names in catalogue pages;
- attach confidence labels to inferred dependencies and providers;
- identify the source file for each route, table, and process-execution finding;
- distinguish detected files from runtime-tested capabilities;
- generate a route-collision report;
- generate a manifest-quality report;
- record scan commit SHA and tool version;
- fail CI when aggregate counts or catalogue links drift.
