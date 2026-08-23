# Titan Reach Engagement / Inbox File Map

Issue: #273

## Design rule

This implementation extends the existing SocialMedia/Titan Reach and SocialMediaAutomation extension seams. It does not create a second provider registry, account store, vertical catalogue, inbox database, audit ledger, CRM, operational store, route provider or Blade UI.

The canonical sources remain:

- connected accounts: `SocialMediaPlatform`;
- vertical context: `DistributionCapabilityService` + `config/vertical-distribution.php` (#337);
- provider transports: existing provider helper classes;
- immutable external-operation receipts: `ext_social_media_distribution_audits`;
- automation definitions/pending work: SocialMediaAutomation;
- customer-facing UI: separate follow-on work.

## New files

### `config/engagement.php`

**Necessity:** engagement permissions and response policy are a separate responsibility from distribution suitability. This file is a small overlay keyed by the canonical #337 vertical slug; it does not duplicate the nine vertical profiles.

Contains:

- provider operation requirements;
- explicit TikTok commercial-comment unavailability;
- generic-business response policy;
- exactly nine canonical vertical response-policy overlays;
- suppression/high-risk/quiet-period/handoff terminology;
- `auto_send_allowed=false` for every policy.

Tenant profile overrides remain authoritative because `EngagementGovernanceService` first resolves the existing #337 profile and then overlays any profile `engagement_policy` override.

### `System/Services/EngagementGovernanceService.php`

**Necessity:** #273 introduces one new domain responsibility: deciding whether a connected account may read/change engagement and enforcing approval, proposal, suppression, risk, duplicate, quiet-period and handoff policy before a provider transport is called.

Reuses:

- `SocialMediaPlatform` for tenant/account ownership;
- existing provider helpers for network calls;
- `DistributionCapabilityService::resolveVerticalProfile()` for canonical vertical context;
- `ext_social_media_distribution_audits` for immutable proposal/send/handoff receipts;
- cache locks for concurrency/idempotency protection.

Does not persist a parallel inbox. Provider APIs/webhooks remain the live source of engagement items.

### `System/Http/Controllers/EngagementController.php`

**Necessity:** thin authenticated JSON boundary for the new governance service.

Routes are tenant-scoped by account ID + `Auth::id()` and expose capability, inbox, proposal, approved reply/private reply, edit/delete and handoff operations. No Blade view is added.

### `SocialMediaAutomation/System/Services/GovernedAutomationExecutionService.php`

**Necessity:** existing automation directly executed public/private provider mutations from webhook events. A separate bound subclass is the narrowest compatibility-preserving way to retain existing trigger/pending-worker contracts while replacing provider-changing execution with Titan Reach proposal staging.

The SocialMediaAutomation service provider binds `AutomationExecutionService::class` to this governed implementation, so existing webhook processor and scheduled pending worker resolve the safe implementation without changing their public constructor contracts.

The bound implementation also overrides legacy `sendPublicReply()` and `sendDm()` entry points to fail closed. Existing legacy provider methods therefore cannot be reached through the application container.

### `tests/engagement_inbox_parity_contract.php`

**Necessity:** permanent source contract for provider truthfulness, canonical vertical coverage, governance boundaries, routes, fail-closed automation binding and webhook privacy.

### `.github/workflows/titan-reach-engagement-verification.yml`

**Necessity:** focused CI gate that runs the #273 contract and PHP-lints every engagement-related implementation file.

### Design/implementation documents

- `docs/superpowers/specs/2026-08-09-titan-reach-engagement-inbox-design.md`
- `docs/superpowers/plans/2026-08-09-titan-reach-engagement-inbox.md`

These record provider/API truth, architecture choices, implementation steps and merge criteria.

## Existing files modified

### Provider configuration

`config/social-media.php`

- requests Facebook engagement permissions;
- defines LinkedIn Community Management engagement scopes separately from baseline connection scopes;
- records X OAuth scopes explicitly;
- removes unsupported TikTok `comment.list` / `comment.create` commercial scopes;
- keeps YouTube `youtube.force-ssl` for governed comment operations.

### Existing provider helpers

`BaseMetaHelper.php`
- adds live Meta token/scope discovery.

`Facebook.php`
- adds comment read, public reply and supported private reply transports.

`Instagram.php`
- adds comment read, public reply and private reply transports.

`Youtube.php`
- adds current YouTube Data API comment thread/list/reply/update/delete transports.

`Linkedin.php`
- adds organisation ACL discovery and current Community Management comment read/create/edit/delete transports.

`X.php`
- adds authenticated-user mentions, reply and delete transports.

No TikTok comment transport is added.

### OAuth/account controllers

`FacebookController.php`
- requests the Page engagement permission needed by public replies;
- removes raw webhook payload, secret and provider-body logging.

`LinkedinController.php`
- requests Community Management scopes only when explicitly enabled;
- persists only scopes actually returned by OAuth;
- does not infer organisation engagement authority from `w_member_social`.

`XController.php`
- persists only OAuth scopes actually returned by X.

### Existing service providers

`SocialMediaServiceProvider.php`
- loads the engagement overlay;
- registers authenticated engagement JSON routes inside the existing provider.

`SocialMediaAutomationServiceProvider.php`
- binds existing `AutomationExecutionService` dependency to `GovernedAutomationExecutionService`.

### Existing webhook processor

`WebhookProcessor.php`
- retains the existing provider-event normalization contract;
- removes raw normalized comment logging;
- hands execution to the bound governed service.

## Provider capability boundary

| Provider | Inbox/read | Public reply | Private reply | Edit | Delete | Boundary |
|---|---|---|---|---|---|---|
| Facebook | Conditional direct | Conditional direct | Conditional direct | No | No | Live Meta scopes |
| Instagram | Conditional direct | Conditional direct | Conditional direct | No | No | Live Meta scopes |
| YouTube | Conditional direct | Conditional direct | No | Conditional direct | Conditional direct | Granted `youtube.force-ssl` + provider ownership |
| LinkedIn | Conditional direct | Conditional direct | No | Conditional direct | Conditional direct | Granted organisation feed scopes + organisation ACL discovery |
| X | Conditional direct | Conditional direct | No | No | Conditional direct | Granted OAuth scopes; provider may still reject unavailable plan/product operations |
| TikTok | No | No | No | No | No | `commercial_comment_management_unavailable` |

Connection alone never implies engagement authority.

## Data/privacy boundary

Immutable audit receipts contain bounded metadata, hashes, classifications, proposal IDs, operation keys, handoff targets and provider result IDs. They do not contain access tokens, client secrets, raw provider response bodies, or full comment/reply text.

The response proposal returned to the authenticated user contains the suggested text so it can be reviewed and approved. Sending requires a matching immutable proposal receipt and explicit approval.

## UI boundary

No new Blade page is added by #273. The existing/future Titan Reach customer UI consumes these authenticated routes. This preserves the native UI rule and avoids introducing a second inbox interface during provider-domain work.
