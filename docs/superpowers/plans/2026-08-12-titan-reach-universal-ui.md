# Titan Reach Universal UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver issue #274 with a native, vertical-aware Titan Reach workspace and navigation while preserving existing SocialMedia compatibility and authority boundaries.

**Architecture:** Reuse existing native pages for Overview, Organic Social, Connections and Settings. Add one controller plus one workspace Blade duplicated from the existing dashboard and one navigation partial duplicated from the existing native tools-card component. Consume the existing #337 vertical resolver and distribution capability service; create only provider-neutral drafts and never bypass provider/governance application services.

**Tech Stack:** Laravel 10, PHP 8.3, Blade, Alpine/native MagicAI components, Eloquent, GitHub Actions.

## Global Constraints

- Internal extension identity remains `SocialMedia`.
- Preserve all existing route names and provider/account authorities.
- Exactly nine top-level vertical families; `facilities-maintenance` remains under `field-home-services`.
- Generic-business fallback is mandatory.
- Edit existing views first; new Blade files require documented native parent provenance.
- No new operational database, provider registry, inbox store, CRM, catalogue source-of-truth or spend authority.
- Unsupported/not-applicable combinations fail closed and remain visibly unavailable.

---

### Task 1: RED UI contract

**Files:**
- Create: `app/extensions/SocialMedia/tests/universal_reach_ui_contract.php`
- Create: `.github/workflows/titan-reach-universal-ui-verification.yml`

**Interfaces:**
- Consumes: issue #274, `config/vertical-distribution.php`, existing route/view files.
- Produces: focused executable source contract for all #274 acceptance criteria.

- [ ] **Step 1:** Add assertions for exactly nine vertical families, facilities maintenance subtype ownership, twelve navigation labels, `workspace/{section}` route, `workspace/create/draft` route, vertical resolution, suitability checks, generic fallback, four destination modes, tenant-bound draft creation, social-post refusal and Blade provenance.
- [ ] **Step 2:** Run `php app/extensions/SocialMedia/tests/universal_reach_ui_contract.php` in Actions and confirm it fails because #274 production files/routes do not yet exist.
- [ ] **Step 3:** Lint the contract with `php -l`.
- [ ] **Step 4:** Commit RED state.

### Task 2: Workspace controller and routes

**Files:**
- Create: `app/extensions/SocialMedia/System/Http/Controllers/ReachWorkspaceController.php`
- Modify: `app/extensions/SocialMedia/System/SocialMediaServiceProvider.php`

**Interfaces:**
- Consumes: `DistributionCapabilityService::resolveVerticalProfile()`, `DistributionCapabilityService::suitabilityFor()`, `DistributionItem`, `SocialMediaPlatform`.
- Produces: `show(Request $request, string $section)` and `storeDraft(Request $request)`.

- [ ] **Step 1:** Duplicate the structural conventions of `SocialMediaController` for the new controller.
- [ ] **Step 2:** Allowlist workspace sections: `create`, `distribute`, `listings`, `paid-media`, `creative-studio`, `catalogues`, `inbox`, `analytics`.
- [ ] **Step 3:** Resolve canonical profile/subtype for the authenticated user, build supported content types, destination suitability/modes, tenant-owned items/accounts and section metrics.
- [ ] **Step 4:** Implement draft validation that rejects `social_post`, rejects content types outside the resolved profile and writes only tenant-owned `DistributionItem` drafts with profile provenance metadata.
- [ ] **Step 5:** Add authenticated routes under the existing `dashboard.user.social-media.` namespace.
- [ ] **Step 6:** Run the focused contract; expected remaining failures should be Blade/navigation only.

### Task 3: Native workspace and navigation

**Files:**
- Create: `app/extensions/SocialMedia/resources/views/workspace.blade.php`
- Create: `app/extensions/SocialMedia/resources/views/components/reach-navigation.blade.php`
- Modify: `app/extensions/SocialMedia/resources/views/index.blade.php`
- Modify: `app/extensions/SocialMedia/resources/views/post/index.blade.php`
- Modify: `app/extensions/SocialMedia/resources/views/platforms.blade.php`
- Modify: `app/extensions/SocialMedia/resources/views/setting/index.blade.php`

**Interfaces:**
- Consumes: controller variables `section`, `profile`, `verticals`, `contentTypes`, `destinations`, `items`, `accounts`, `metrics`.
- Produces: responsive customer-facing navigation and workspace sections.

- [ ] **Step 1:** Duplicate `index.blade.php` into `workspace.blade.php`, preserving panel layout/titlebar/native card conventions.
- [ ] **Step 2:** Duplicate/adapt `components/home/tools.blade.php` into `components/reach-navigation.blade.php` using native buttons/cards and accessible horizontal scrolling.
- [ ] **Step 3:** Render vertical context, subtype, profile provenance and generic fallback status.
- [ ] **Step 4:** Implement Create cards, Distribute matrix, Listings filters, Paid Media guardrails, Creative Studio links, Catalogue profile view, Inbox account capability links and Analytics metrics in the single workspace template.
- [ ] **Step 5:** Include Reach navigation in existing Overview, Organic Social, Connections and Settings views without removing their current content.
- [ ] **Step 6:** Ensure direct/partner/assisted/export-only labels are visible and `not-applicable` actions are disabled.
- [ ] **Step 7:** Run focused contract and PHP lint.

### Task 4: Provenance and compatibility hardening

**Files:**
- Create: `app/extensions/SocialMedia/docs/TITAN-REACH-UNIVERSAL-UI-FILE-MAP.md`
- Modify: `app/extensions/SocialMedia/tests/universal_reach_ui_contract.php`

**Interfaces:**
- Consumes: final route/view/controller implementation.
- Produces: permanent provenance/authority map and regression guards.

- [ ] **Step 1:** Document every changed/new UI file, native parent, reason, route ownership and authority boundary.
- [ ] **Step 2:** Lock the exact two-new-Blade limit into the contract.
- [ ] **Step 3:** Add assertions that existing `index`, `post.index`, `platforms` and settings routes/views remain present.
- [ ] **Step 4:** Add assertions that no tenth vertical and no direct provider mutation endpoint is introduced by `ReachWorkspaceController`.
- [ ] **Step 5:** Run focused contract and lint every changed PHP file.

### Task 5: Review and integration

**Files:**
- Pull request only.

**Interfaces:**
- Consumes: green branch.
- Produces: reviewed merge closing #274.

- [ ] **Step 1:** Compare branch against current `main`; require zero-behind or rebase/refresh before merge.
- [ ] **Step 2:** Open PR with `Closes #274`, design summary, provenance and verification evidence.
- [ ] **Step 3:** Inspect all review threads and high-risk diffs: tenant scoping, draft writes, route compatibility, Blade provenance and vertical count.
- [ ] **Step 4:** Re-run exact-head `Titan Reach Universal UI Verification`.
- [ ] **Step 5:** Merge only with expected head SHA after focused CI is green; document unrelated repository-wide workflow failures separately if any.
