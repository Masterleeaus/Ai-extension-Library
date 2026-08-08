# Titan Reach Engagement and Inbox Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add truthful, vertical-aware, governed engagement/inbox support for Facebook, Instagram, YouTube, LinkedIn and X while explicitly failing closed for unsupported commercial TikTok comment management.

**Architecture:** Add one shared `EngagementGovernanceService` and one thin `EngagementController`. Reuse existing provider helpers for transport, `SocialMediaPlatform` for accounts, `DistributionCapabilityService` for #337 vertical context, the existing distribution audit table for immutable engagement receipts, and SocialMediaAutomation only as an inbound/proposal source. No new inbox table, account model, route provider or Blade UI.

**Tech Stack:** Laravel/PHP, Laravel HTTP client/cache/database facades, existing SocialMedia and SocialMediaAutomation extensions, GitHub Actions focused PHP contract.

## Global Constraints

- Support exactly the nine canonical #337 vertical families plus generic-business fallback.
- Facilities maintenance remains inside `field-home-services`.
- Suggested replies remain proposals until an explicit governed send approval.
- AI/automation may not bypass Titan Reach application rules.
- Provider capabilities are account-specific and fail closed when scopes/product access are absent.
- TikTok commercial comment management is unavailable; do not call research-only or invented endpoints.
- Use the existing immutable distribution audit table; do not create a parallel engagement ledger.
- Add no Blade page.
- Never log access tokens, full webhook bodies, comment/reply text, or raw provider response bodies.

---

### Task 1: RED engagement contract and provider policy catalogue

**Files:**
- Test: `app/extensions/SocialMedia/tests/engagement_inbox_parity_contract.php`
- Modify: `app/extensions/SocialMedia/config/social-media.php`
- Modify: `app/extensions/SocialMedia/config/vertical-distribution.php`

**Interfaces:**
- Consumes: existing provider connection config and `DistributionCapabilityService::resolveVerticalProfile()`.
- Produces: `social-media.engagement.providers.<platform>` definitions and `engagement_policy` blocks on generic plus all nine vertical profiles.

- [x] **Step 1: Write the failing contract**
- [x] **Step 2: Run focused CI and verify RED on missing #273 behavior**
- [ ] **Step 3: Add provider engagement capability definitions**
  - Facebook: read/public/private capability scopes.
  - Instagram: comments/private-message scopes.
  - YouTube: `youtube.force-ssl` comment operations.
  - LinkedIn: Community Management organisation scopes, gated by actual grants.
  - X: mentions/reply/delete with `tweet.read`, `tweet.write`, `users.read`.
  - TikTok: all comment-changing capabilities false with explicit unavailability reason.
- [ ] **Step 4: Remove unsupported TikTok `comment.list`/`comment.create` OAuth scopes**
- [ ] **Step 5: Add generic plus nine vertical engagement policies with `auto_send_allowed=false`, terminology, risk/suppression/handoff/quiet-period policy**
- [ ] **Step 6: Commit and run the focused contract to identify the next GREEN failures**

### Task 2: Existing provider helper engagement transports

**Files:**
- Modify: `app/extensions/SocialMedia/System/Helpers/Contracts/BaseMetaHelper.php`
- Modify: `app/extensions/SocialMedia/System/Helpers/Facebook.php`
- Modify: `app/extensions/SocialMedia/System/Helpers/Instagram.php`
- Modify: `app/extensions/SocialMedia/System/Helpers/Youtube.php`
- Modify: `app/extensions/SocialMedia/System/Helpers/Linkedin.php`
- Modify: `app/extensions/SocialMedia/System/Helpers/X.php`

**Interfaces:**
- Consumes: provider access token already supplied to each helper.
- Produces: provider read/reply/private/edit/delete methods used only through engagement governance.

- [ ] **Step 1: Add Meta token debug/scope discovery helper without persisting app secrets**
- [ ] **Step 2: Add Facebook comments, public reply and supported private reply methods**
- [ ] **Step 3: Add Instagram comments, public reply and private reply methods**
- [ ] **Step 4: Add YouTube comment threads/comments/reply/update/delete methods using official v3 resource shapes**
- [ ] **Step 5: Add LinkedIn organisation ACL discovery and comment read/create/edit/delete methods using Rest.li headers and organisation URNs**
- [ ] **Step 6: Add X mentions, reply and delete methods**
- [ ] **Step 7: Do not add TikTok commercial comment methods**
- [ ] **Step 8: Lint helpers and rerun focused contract**

### Task 3: Shared Titan Reach engagement governance

**Files:**
- Create: `app/extensions/SocialMedia/System/Services/EngagementGovernanceService.php`
- Create: `app/extensions/SocialMedia/System/Http/Controllers/EngagementController.php`
- Modify: `app/extensions/SocialMedia/System/SocialMediaServiceProvider.php`

**Interfaces:**
- `EngagementGovernanceService::capabilities(User $user, SocialMediaPlatform $account): array`
- `EngagementGovernanceService::inbox(User $user, SocialMediaPlatform $account, array $filters = []): array`
- `EngagementGovernanceService::proposeReply(User $user, SocialMediaPlatform $account, array $engagement, ?string $suggestedText = null): array`
- `EngagementGovernanceService::sendReply(User $user, SocialMediaPlatform $account, array $input): array`
- `EngagementGovernanceService::privateReply(User $user, SocialMediaPlatform $account, array $input): array`
- `EngagementGovernanceService::editReply(User $user, SocialMediaPlatform $account, array $input): array`
- `EngagementGovernanceService::deleteReply(User $user, SocialMediaPlatform $account, array $input): array`
- `EngagementGovernanceService::handoff(User $user, SocialMediaPlatform $account, array $engagement): array`
- `EngagementGovernanceService::ingestWebhookEvent(SocialMediaPlatform $account, array $engagement): array`

- [ ] **Step 1: Implement tenant/account assertions and per-account capability discovery**
- [ ] **Step 2: Normalize provider inbox data into the common engagement shape**
- [ ] **Step 3: Resolve #337 profile and classify vertical/high-risk/suppression context**
- [ ] **Step 4: Implement immutable proposal receipts with message hashes and bounded excerpts**
- [ ] **Step 5: Implement explicit approval, proposal-hash match, quiet-period, suppression, high-risk acknowledgement and duplicate checks before sends**
- [ ] **Step 6: Dispatch only supported provider operations and record immutable result receipts**
- [ ] **Step 7: Implement authoritative handoff target selection from the resolved vertical profile**
- [ ] **Step 8: Add thin authenticated tenant-scoped controller methods with validation and cache locks**
- [ ] **Step 9: Register routes in the existing `SocialMediaServiceProvider`**
- [ ] **Step 10: Lint and rerun focused contract**

### Task 4: Govern the existing automation/webhook path

**Files:**
- Modify: `app/extensions/SocialMediaAutomation/System/Services/AutomationExecutionService.php`
- Modify: `app/extensions/SocialMediaAutomation/System/Services/WebhookProcessor.php`
- Modify: `app/extensions/SocialMedia/System/Http/Controllers/Oauth/FacebookController.php`
- Modify: `app/extensions/SocialMedia/System/Http/Controllers/Oauth/InstagramController.php` only if required for normalized governance routing.

**Interfaces:**
- Consumes: `EngagementGovernanceService::ingestWebhookEvent()` and `proposeReply()`.
- Produces: `AutomationExecutionService::stageGovernedProposal()` and no provider-changing action from an unapproved automation trigger.

- [ ] **Step 1: Route inbound normalized webhook events through Titan Reach governance before creating automation work**
- [ ] **Step 2: Replace direct public/DM execution with proposal staging**
- [ ] **Step 3: Remove the invented TikTok reply endpoint**
- [ ] **Step 4: Remove raw comment/reply/provider-body logging; log only IDs/status/hashes**
- [ ] **Step 5: Keep duplicate protection compatible with existing automation logs while Titan Reach audit receipts remain authoritative for external sends**
- [ ] **Step 6: Lint and rerun focused contract**

### Task 5: Documentation, review and merge gate

**Files:**
- Create: `app/extensions/SocialMedia/docs/TITAN-REACH-ENGAGEMENT-FILE-MAP.md`
- Modify: `.github/workflows/titan-reach-engagement-verification.yml`
- Update PR #485 description.

**Interfaces:**
- Produces: permanent provenance/boundary documentation and focused verification evidence.

- [ ] **Step 1: Document every new file’s provenance and necessity**
- [ ] **Step 2: Extend focused workflow to lint every changed engagement PHP file after the source contract passes**
- [ ] **Step 3: Run focused workflow on the exact PR head and require success**
- [ ] **Step 4: Inspect PR diff for scope, secrets, provider false claims and unrelated changes**
- [ ] **Step 5: Run automated review and resolve every actionable thread**
- [ ] **Step 6: Compare with latest `main`; inspect any divergence for SocialMedia overlap**
- [ ] **Step 7: Merge using expected head SHA only after the reviewed head remains clean**
- [ ] **Step 8: Verify issue #273 closes as completed and implementation files exist on `main`**
