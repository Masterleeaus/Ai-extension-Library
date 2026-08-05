# Titan Reach Google Business Profile and Pinterest Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add official, tenant-safe, vertical-aware Google Business Profile and Pinterest adapters to Titan Reach.

**Architecture:** Extend the existing `PlatformEnum`, destination catalogue and `SocialMediaServiceProvider`. Each provider uses a config, encrypted-token helper, OAuth controller, governed application service and thin HTTP controller derived from the existing eBay implementation. Provider receipts remain in `DistributionItem.payload`; account health and discovered capabilities remain in `SocialMediaPlatform.credentials`.

**Tech Stack:** PHP 8.2+, Laravel HTTP client, Eloquent, Cache locks, Laravel Crypt, PHPUnit source contracts, Google Business Profile REST APIs, Pinterest API v5.

## Global Constraints

- Support exactly nine canonical vertical families from #337.
- Facilities maintenance remains under `field-home-services`.
- Do not create a new account table, distribution table, route provider, provider registry or Blade page.
- Plain OAuth tokens must never be persisted.
- Provider-changing operations require tenant ownership, a connected account, discovered scope permission, vertical suitability, approval, lock and idempotency.
- Unsupported provider permissions and operations fail closed.
- CRM, WorkCore, Commerce, Booking, Property, Automotive and Hire remain authoritative.

---

### Task 1: RED provider contract

**Files:**
- Create: `app/extensions/SocialMedia/tests/Unit/GoogleBusinessProfilePinterestContractTest.php`

**Interfaces:**
- Consumes: existing `config/distribution.php`, `config/vertical-distribution.php`, `PlatformEnum`, `SocialMediaServiceProvider`.
- Produces: contract assertions for all later tasks.

- [ ] **Step 1: Write the failing test**

Create a PHPUnit source contract that requires nine verticals and asserts provider configs, encrypted-token helpers, OAuth controllers, application services, routes, locks, request hashes, receipts, reconciliation and handoff safeguards.

- [ ] **Step 2: Verify RED**

Run:

```bash
phpunit app/extensions/SocialMedia/tests/Unit/GoogleBusinessProfilePinterestContractTest.php
```

Expected: FAIL because the Google/Pinterest config, helpers, controllers and services do not exist and the destination adapters are unavailable.

- [ ] **Step 3: Commit**

```bash
git add app/extensions/SocialMedia/tests/Unit/GoogleBusinessProfilePinterestContractTest.php
git commit -m "test: define Google Business and Pinterest provider contract"
```

### Task 2: Provider registry and configuration

**Files:**
- Create: `app/extensions/SocialMedia/config/google-business-profile.php`
- Create: `app/extensions/SocialMedia/config/pinterest.php`
- Modify: `app/extensions/SocialMedia/config/distribution.php`
- Modify: `app/extensions/SocialMedia/System/Enums/PlatformEnum.php`
- Modify: `app/extensions/SocialMedia/System/SocialMediaServiceProvider.php`

**Interfaces:**
- Produces: platform slugs `google-business-profile` and `pinterest`; config keys `social-media.google_business_profile` and `social-media.pinterest`; direct destination definitions.

- [ ] **Step 1: Add config fixtures**

Google config must define OAuth endpoints, API roots, `business.manage`, request timeout, retry policy and a direct destination supporting business updates, service/product offers, events, room/stay, membership, class/session and booking offers.

Pinterest config must define OAuth/API endpoints, continuous refresh, the five minimum scopes, request timeout, retry policy and a direct destination supporting social posts, product/service offers, property/vehicle, room/stay, membership, class/session, booking and hire/rental content.

- [ ] **Step 2: Extend the existing enum and catalogue**

Add both cases to `channels()` but not the legacy `all()` list. Add labels, credential settings and content lengths. Mark both destination adapters available and approval-required.

- [ ] **Step 3: Register provider configs**

Merge both config files through the existing service provider and set their destination definitions under `social-media.distribution.destinations`.

- [ ] **Step 4: Re-run the contract**

Expected: provider registry assertions pass; helper/service assertions still fail.

- [ ] **Step 5: Commit**

```bash
git add app/extensions/SocialMedia/config app/extensions/SocialMedia/System/Enums/PlatformEnum.php app/extensions/SocialMedia/System/SocialMediaServiceProvider.php
git commit -m "feat: register Google Business and Pinterest channels"
```

### Task 3: Encrypted OAuth helpers and controllers

**Files:**
- Create: `app/extensions/SocialMedia/System/Helpers/GoogleBusinessProfile.php`
- Create: `app/extensions/SocialMedia/System/Helpers/Pinterest.php`
- Create: `app/extensions/SocialMedia/System/Http/Controllers/Oauth/GoogleBusinessProfileController.php`
- Create: `app/extensions/SocialMedia/System/Http/Controllers/Oauth/PinterestController.php`
- Modify: `app/extensions/SocialMedia/System/SocialMediaServiceProvider.php`

**Interfaces:**
- Google helper produces `authorizationUrl()`, `exchangeCode()`, `refreshAccessToken()`, `accounts()`, `locations()`, `createLocalPost()`, `getLocalPost()`, `createMedia()`, `reviews()`, `replyToReview()` and `performance()`.
- Pinterest helper produces `authorizationUrl()`, `exchangeCode()`, `refreshAccessToken()`, `userAccount()`, `boards()`, `createPin()`, `getPin()` and `pinAnalytics()`.

- [ ] **Step 1: Implement helper token vault behaviour**

Decrypt only `access_token_encrypted` and `refresh_token_encrypted`, refresh before expiry, persist encrypted replacements and never write plain tokens.

- [ ] **Step 2: Implement bounded provider requests**

Retry one time only for GET/idempotent PUT on 429/5xx. Preserve response headers for normalized rate-limit snapshots. Never retry unsafe POST publication automatically.

- [ ] **Step 3: Implement OAuth redirects/callbacks**

Use single-use, ten-minute, user/platform-bound state. Exchange the code, discover the account/locations or user/boards, derive capabilities from granted scopes and persist encrypted credentials in `SocialMediaPlatform`.

- [ ] **Step 4: Register OAuth routes**

Add connect/callback routes using the exact platform slugs so the existing platform-card Blade resolves them automatically.

- [ ] **Step 5: Re-run the contract and lint**

```bash
php -l app/extensions/SocialMedia/System/Helpers/GoogleBusinessProfile.php
php -l app/extensions/SocialMedia/System/Helpers/Pinterest.php
php -l app/extensions/SocialMedia/System/Http/Controllers/Oauth/GoogleBusinessProfileController.php
php -l app/extensions/SocialMedia/System/Http/Controllers/Oauth/PinterestController.php
```

- [ ] **Step 6: Commit**

```bash
git add app/extensions/SocialMedia/System/Helpers app/extensions/SocialMedia/System/Http/Controllers/Oauth app/extensions/SocialMedia/System/SocialMediaServiceProvider.php
git commit -m "feat: add encrypted Google and Pinterest OAuth"
```

### Task 4: Google Business Profile operations

**Files:**
- Create: `app/extensions/SocialMedia/System/Services/GoogleBusinessProfileService.php`
- Create: `app/extensions/SocialMedia/System/Http/Controllers/GoogleBusinessProfileController.php`
- Modify: `app/extensions/SocialMedia/System/SocialMediaServiceProvider.php`

**Interfaces:**
- Produces service methods `readiness`, `publish`, `uploadPhoto`, `reconcile`, `reviews`, `replyToReview`, `reviewHandoff`, and `performance`.

- [ ] **Step 1: Implement readiness and discovered capabilities**

Verify tenant/platform/connection, call accounts and locations, derive operation flags from granted scopes plus successful discovery, update a bounded health snapshot and audit the result.

- [ ] **Step 2: Implement vertical-aware local post publication**

Call `forVerticalDestination($user, 'google-business-profile', $item->content_type, ...)`, require approval, validate standard/offer/event payloads, publish immediately, store a request hash and provider receipt, and update item status only after provider success.

- [ ] **Step 3: Implement photos, reconciliation, reviews and performance**

Require approved HTTPS photo media, reconcile stored local-post names, list reviews, submit only explicit approved replies, create human handoffs using profile targets, and read provider performance metrics.

- [ ] **Step 4: Add locked authenticated routes**

Use a per-user/item/provider lock for DistributionItem mutations and tenant-scoped account lookup for every route.

- [ ] **Step 5: Re-run contract and lint**

```bash
php -l app/extensions/SocialMedia/System/Services/GoogleBusinessProfileService.php
php -l app/extensions/SocialMedia/System/Http/Controllers/GoogleBusinessProfileController.php
```

- [ ] **Step 6: Commit**

```bash
git add app/extensions/SocialMedia/System/Services/GoogleBusinessProfileService.php app/extensions/SocialMedia/System/Http/Controllers/GoogleBusinessProfileController.php app/extensions/SocialMedia/System/SocialMediaServiceProvider.php
git commit -m "feat: add governed Google Business Profile operations"
```

### Task 5: Pinterest operations

**Files:**
- Create: `app/extensions/SocialMedia/System/Services/PinterestService.php`
- Create: `app/extensions/SocialMedia/System/Http/Controllers/PinterestController.php`
- Modify: `app/extensions/SocialMedia/System/SocialMediaServiceProvider.php`

**Interfaces:**
- Produces service methods `readiness`, `boards`, `publish`, `reconcile`, `analytics`, and `engagementHandoff`.

- [ ] **Step 1: Implement readiness/board discovery**

Verify tenant/platform/connection, list user and boards, derive scope flags, persist health and audit.

- [ ] **Step 2: Implement vertical-aware Pin publication**

Require `forVerticalDestination()`, approval, a board, title, approved original image URL, optional destination link and product link for product offers. Store provider Pin ID, request hash, profile provenance and rate-limit receipt.

- [ ] **Step 3: Implement reconciliation, analytics and handoff**

Fetch the stored Pin, read analytics with validated dates/metrics, and record bounded human handoff snapshots with no automatic reply.

- [ ] **Step 4: Add locked authenticated routes**

Use tenant-scoped account lookup and a per-user/item/provider lock for mutations.

- [ ] **Step 5: Re-run contract and lint**

```bash
php -l app/extensions/SocialMedia/System/Services/PinterestService.php
php -l app/extensions/SocialMedia/System/Http/Controllers/PinterestController.php
```

- [ ] **Step 6: Commit**

```bash
git add app/extensions/SocialMedia/System/Services/PinterestService.php app/extensions/SocialMedia/System/Http/Controllers/PinterestController.php app/extensions/SocialMedia/System/SocialMediaServiceProvider.php
git commit -m "feat: add governed Pinterest operations"
```

### Task 6: Documentation, verification and merge gate

**Files:**
- Create: `app/extensions/SocialMedia/docs/TITAN-REACH-GOOGLE-PINTEREST-FILE-MAP.md`
- Modify: `app/extensions/SocialMedia/tests/Unit/GoogleBusinessProfilePinterestContractTest.php` only to strengthen behaviours discovered during review.

- [ ] **Step 1: Document provenance and boundaries**

Map every new file to its closest existing source, describe OAuth/security, provider capability discovery, vertical behaviour, authority boundaries, unsupported operations and the no-UI boundary.

- [ ] **Step 2: Run focused verification**

Run PHP lint on every changed PHP file and the provider contract. Run available repository SocialMedia tests without claiming unavailable live provider validation.

- [ ] **Step 3: Review the full diff**

Check for plain token fields, missing tenant filters, unbounded arrays, automatic replies, unsupported provider claims, a tenth vertical, new Blade files and accidental changes outside SocialMedia/docs.

- [ ] **Step 4: Open PR and resolve review findings**

Open a PR closing #272, address all actionable review threads, synchronize with current `main`, re-run focused verification and merge only the exact reviewed head.
