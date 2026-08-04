# TitanAI Hybrid Composer Package Implementation Plan

**Goal:** Convert the TitanAI Hybrid overlay into the authoritative nested Composer package and make repository materialisation reproduce it safely.

**Architecture:** A deterministic Python converter transforms the verified Pass 3 overlay into `packages/titanai-hybrid-core`, rewrites shared namespaces to `TitanAI\Hybrid`, adds legacy aliases, and updates only the shared imports in AIAgent, AIChatPro, and Chatbot. GitHub Actions applies the converter after every overlay restore and verifies the package before committing materialised output.

**Tech Stack:** PHP 8.1–8.3, Laravel 10–13, Composer 2, Python 3.12, GitHub Actions.

## Global Constraints

- Keep AIAgent, AIChatPro, and Chatbot as separate marketplace extensions.
- WorkCore remains authoritative for operational records.
- `packages/titanai-hybrid-core` is the only authoritative foundation copy.
- Preserve a compatibility path for legacy shared `App\Domains\TitanAI` class references.
- Do not modify Chatbot-owned `App\Domains\TitanAI` channel, tier, model-routing, persona, or tool classes.
- Keep the existing MiniUp Pass 3 archive and checksum unchanged.
- Use small commits and merge the verified pull request into `main`.

## Tasks

1. Define deterministic conversion behavior and tests.
2. Materialise `titanai/hybrid-core` with Laravel package discovery.
3. Add compatibility aliases and extension dependency manifests.
4. Update the shared materialisation workflow without removing newer security patches.
5. Verify package, converter, PHP syntax, and existing ecommerce contracts.
6. Merge the verified short-lived branch into `main`.
