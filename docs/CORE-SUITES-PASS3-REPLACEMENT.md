# Core AI Suites — Pass 3 Replacement Scan

## Scope

This scan compares the materialised core suites in `extensions/` with the verified source bundle `TitanAI-Hybrid-Complete-Upgraded-v4-Pass3.zip` and documents the replacement applied to:

- `extensions/AIAgent/`
- `extensions/AIChatPro/`
- `extensions/Chatbot/`

The remaining 75 extension folders are preserved unchanged.

## Repository baseline

Before replacement, the repository inventory recorded:

| Extension | Version | Files | Bytes |
|---|---:|---:|---:|
| AIAgent | 1.1 | 169 | 1,352,714 |
| AIChatPro | 3.7 | 47 | 328,078 |
| Chatbot | 6.9.0-unified-ai-shell | 1,548 | 5,873,858 |
| **Core-suite total** |  | **1,764** | **7,554,650** |

The complete repository contained 78 extensions, 4,204 files and 81,323,004 raw extension bytes.

## Verified Pass 3 replacement

| Extension | Version | Files | Bytes | PHP | Migrations | Blade views | Bundled tests |
|---|---:|---:|---:|---:|---:|---:|---:|
| AIAgent | 1.1 | 171 | 1,367,253 | 149 | 20 | 53 | 0 |
| AIChatPro | 3.7 | 48 | 336,887 | 43 | 7 | 19 | 0 |
| Chatbot | 7.0.0-five-application-registry | 1,554 | 5,900,245 | 1,108 | 93 | 158 | 51 |
| **Core-suite total** |  | **1,773** | **7,604,385** | **1,300** | **120** | **230** | **51** |

After replacement, the `extensions/` tree contains 4,213 files and 81,372,739 raw bytes across the same 78 extensions.

## Confirmed architectural changes

The Pass 3 suites retain their original authority boundaries while sharing a common TitanAI foundation:

- **AIChatPro** remains the interactive internal AI workspace and connector surface.
- **Chatbot** remains the customer/device/channel runtime, offline PWA, staff inbox, governance and five-tier orchestration surface.
- **AIAgent** remains the durable autonomous workflow, action, channel and memory engine.
- **WorkCore** remains the authoritative owner of operational business records.

The replacement adds or hardens:

1. Unified component discovery across skills, actions and connectors.
2. Failure-isolated cross-extension event delivery.
3. Correlation and idempotency for action lifecycle events.
4. Transactional AI Agent memory mirroring into unified memory.
5. Bounded shared-memory context consumption by Chatbot.
6. Dynamic late registration for AI Agent actions and AIChatPro connectors.
7. Duplicate-provider listener prevention.
8. Hybrid diagnostics without raw memory values, exception messages or idempotency keys.
9. A scheduled expired-memory purge command.
10. Chatbot 7.0's five canonical application registry and legacy slug migration.

## Shared foundation packaging

The three extensions reference `App\Domains\TitanAI` classes. The verified companion overlay is therefore stored at:

```text
foundation/TitanAI-Hybrid/
```

It contains the exact Pass 3 foundation code, configuration, migration, tests, verification scripts and operating documentation. It is a host overlay, not a fourth marketplace extension.

## Security and reliability observations

- No `eval`, `shell_exec`, `proc_open`, `passthru` or `system` calls were detected in the three replacement folders.
- AIAgent and Chatbot retain substantial webhook surfaces. Host integration must still verify signatures, replay protection, tenant resolution, rate limiting and action permissions.
- AIAgent and AIChatPro still have no native bundled test trees; their TitanAI integration is covered by the shared standalone verification suite.
- Chatbot remains the dominant package by size and responsibility. Internal modularisation should continue without changing its marketplace identity.
- The archive-level suite cannot prove Composer resolution, Laravel provider boot order, real database persistence, queues, schedules or host authorization. Those remain host-integration checks.

## Reproducible materialisation

The repository continues to restore the original 78-extension lossless MiniUp dataset, then applies a second verified MiniUp overlay containing the three Pass 3 core suites and the shared foundation.

Overlay controls:

- Dataset: `TitanAI Core Suites Pass 3 Overlay`
- Rows: 12 ordered base64 archive chunks
- Archive bytes: 3,121,113
- SHA-256: `6fc826403f57d9e2c6278a2412052f4b7d9973592ba0414ae6fbd4e3de31a0e2`
- Replaced folders: `AIAgent`, `AIChatPro`, `Chatbot`
- Other extension folders: preserved

The materialisation script rejects path traversal, symbolic links, non-contiguous chunks, inconsistent metadata, checksum mismatches, archive-size mismatches, missing manifests and unexpected extracted-file counts.

## Validation gate

The GitHub workflow performs:

1. Lossless restoration of the original 78-extension dataset.
2. Verified reconstruction and atomic application of the Pass 3 overlay.
3. JSON parsing of all three core manifests.
4. PHP linting across the three core suites and shared foundation.
5. Reconstruction of a temporary host layout.
6. Execution of `verify-titanai-pass3.php` and all earlier regression gates.
7. Regeneration of extension inventories and materialisation totals.
