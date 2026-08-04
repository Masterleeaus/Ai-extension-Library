# TitanAI Hybrid Composer Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

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

---

### Task 1: Define package conversion behavior

**Files:**
- Create: `scripts/tests/test_package_titanai_hybrid.py`
- Create: `scripts/package_titanai_hybrid.py`

**Interfaces:**
- Consumes: repository root containing `foundation/TitanAI-Hybrid` and the three core extension folders.
- Produces: `convert(root: pathlib.Path) -> ConversionSummary` and `check(root: pathlib.Path) -> list[str]`.

- [ ] **Step 1: Write failing Python tests**

Test that conversion creates package metadata and source files, rewrites package and extension namespaces, writes three dependency manifests, removes the old foundation, and passes `--check`.

- [ ] **Step 2: Run tests and verify RED**

Run: `python -m unittest scripts.tests.test_package_titanai_hybrid -v`
Expected: FAIL because `scripts.package_titanai_hybrid` does not exist.

- [ ] **Step 3: Implement minimal deterministic converter**

Implement strict source validation, file copying, targeted namespace replacement, generated metadata, legacy alias generation, foundation removal, and check mode.

- [ ] **Step 4: Run tests and verify GREEN**

Run: `python -m unittest scripts.tests.test_package_titanai_hybrid -v`
Expected: all conversion tests pass.

- [ ] **Step 5: Commit**

```bash
git add scripts docs/superpowers
git commit -m "build: define TitanAI package conversion"
```

### Task 2: Materialise the Composer package

**Files:**
- Create: `packages/titanai-hybrid-core/composer.json`
- Create: `packages/titanai-hybrid-core/README.md`
- Create: `packages/titanai-hybrid-core/src/**`
- Create: `packages/titanai-hybrid-core/config/titanai.php`
- Create: `packages/titanai-hybrid-core/database/migrations/**`
- Create: `packages/titanai-hybrid-core/tests/**`
- Create: `packages/titanai-hybrid-core/bin/**`
- Delete: `foundation/TitanAI-Hybrid/**`

**Interfaces:**
- Consumes: conversion behavior from Task 1.
- Produces: package `titanai/hybrid-core` with provider `TitanAI\Hybrid\TitanAIServiceProvider`.

- [ ] **Step 1: Run converter**

Run: `python scripts/package_titanai_hybrid.py --root .`
Expected: package created, legacy foundation removed, extension imports updated.

- [ ] **Step 2: Validate Composer metadata**

Run: `php -r '$j=json_decode(file_get_contents("packages/titanai-hybrid-core/composer.json"), true, 512, JSON_THROW_ON_ERROR); exit(($j["name"] ?? null)==="titanai/hybrid-core" ? 0 : 1);'`
Expected: exit 0.

- [ ] **Step 3: Lint package and modified extension PHP**

Run: `find packages/titanai-hybrid-core/src extensions/AIAgent extensions/AIChatPro extensions/Chatbot -type f -name '*.php' -print0 | xargs -0 -n1 php -l`
Expected: no syntax errors.

- [ ] **Step 4: Run package checks**

Run: `python scripts/package_titanai_hybrid.py --root . --check`
Expected: `TitanAI package check PASSED`.

- [ ] **Step 5: Commit**

```bash
git add packages extensions foundation scripts
git commit -m "refactor: package TitanAI hybrid core"
```

### Task 3: Update repository materialisation and operating documentation

**Files:**
- Modify: `.github/workflows/materialize-ai-extensions.yml`
- Modify: `README.md`
- Modify: `MATERIALIZED.md`
- Create: `docs/TITANAI-COMPOSER-PACKAGE.md`

**Interfaces:**
- Consumes: converter and package from Tasks 1–2.
- Produces: materialisation workflow that cannot restore the legacy foundation as an active duplicate.

- [ ] **Step 1: Add workflow assertions test**

Extend the Python test to require the workflow to invoke `scripts/package_titanai_hybrid.py`, lint `packages/titanai-hybrid-core`, run `--check`, and stage `packages` instead of `foundation`.

- [ ] **Step 2: Run test and verify RED**

Run: `python -m unittest scripts.tests.test_package_titanai_hybrid -v`
Expected: workflow assertions fail before workflow is updated.

- [ ] **Step 3: Update workflow and documentation**

Call the converter after the Pass 3 overlay, validate Composer metadata, lint package sources, run package checks, adapt the Pass 3 verifier path, and stage `packages` while deleting `foundation`.

- [ ] **Step 4: Run complete verification**

Run:

```bash
python -m unittest scripts.tests.test_package_titanai_hybrid -v
python scripts/package_titanai_hybrid.py --root . --check
find packages/titanai-hybrid-core/src extensions/AIAgent extensions/AIChatPro extensions/Chatbot -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

Expected: all checks pass.

- [ ] **Step 5: Commit**

```bash
git add .github README.md MATERIALIZED.md docs scripts packages extensions foundation
git commit -m "ci: materialize TitanAI Composer package"
```

### Task 4: Publish and merge

**Files:**
- No source changes expected.

**Interfaces:**
- Consumes: verified feature branch.
- Produces: merged pull request into `main`.

- [ ] **Step 1: Review final diff and confirm no unrelated extensions changed**

Run: `git diff --stat main...HEAD` and inspect paths.
Expected: only package, three core extensions, conversion tooling, workflow, and documentation.

- [ ] **Step 2: Re-run verification from a clean conversion fixture**

Run: `python -m unittest scripts.tests.test_package_titanai_hybrid -v`
Expected: all tests pass.

- [ ] **Step 3: Push branch and open pull request**

Create a pull request from `agent/package-titanai-hybrid-core` to `main` describing architecture, compatibility, materialisation, and verification.

- [ ] **Step 4: Merge after checks**

Merge the pull request into `main` using squash or merge commit according to repository policy.
