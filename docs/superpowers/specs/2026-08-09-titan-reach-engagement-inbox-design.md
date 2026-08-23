# Titan Reach Engagement and Inbox Design

## Objective

Implement issue #273 inside the existing SocialMedia/Titan Reach and SocialMediaAutomation extensions without creating a second account store, provider registry, inbox database, vertical model, or AI execution path.

## Architecture

Titan Reach owns the engagement governance decision. Existing provider helpers remain the transport layer. `SocialMediaPlatform` remains the connected-account record. `DistributionCapabilityService` remains the canonical nine-vertical resolver. `ext_social_media_distribution_audits` remains the immutable receipt ledger. SocialMediaAutomation may detect/queue engagement, but it must stage proposals through Titan Reach and may not directly send public replies or private replies.

## Provider capability truth

### Facebook

Direct when the connected Page token has the required Page scopes. Inbox/comment read requires Page read permissions; public reply requires `pages_manage_engagement`; supported private reply requires `pages_messaging`. Webhook subscription remains the primary inbound path.

### Instagram

Direct when the connected professional account has the required Instagram scopes. Comments/public replies require `instagram_manage_comments`; private replies require `instagram_manage_messages`.

### YouTube

Direct for comment threads, replies, edit and delete when the connected channel has `https://www.googleapis.com/auth/youtube.force-ssl`. `snippet.canReply` and provider responses still determine whether a particular thread can be changed.

### LinkedIn

Direct only when Community Management access and the relevant organisation feed scopes are actually granted. Read requires `r_organization_social_feed`; write requires `w_organization_social_feed`. Organisation identity must come from organisation ACL discovery. Basic `w_member_social` connection alone does not imply organisation comment capability.

### X

Direct for mentions/replies only when `tweet.read`, `tweet.write` and `users.read` are granted as required by the operation. Inbox is based on authenticated-user mentions rather than pretending X exposes a generic social inbox.

### TikTok

Commercial comment management is unavailable through the normal TikTok for Developers products used by this extension. Remove unsupported `comment.list` and `comment.create` scopes. Capabilities fail closed with `commercial_comment_management_unavailable`. No synthetic comment endpoint is called.

## Engagement policy

The existing `vertical-distribution.php` profiles gain an `engagement_policy` block. Generic business plus all nine verticals set `auto_send_allowed` to false. Policies define vertical terminology, suppression phrases, high-risk keywords, human-handoff categories, optional quiet periods and quiet-period bypass categories.

Tenant overrides continue to use the existing recursive profile override path in `DistributionCapabilityService`; no separate tenant-policy table is introduced.

## Proposal and approval flow

1. Normalize inbound engagement.
2. Resolve the canonical vertical profile or generic fallback.
3. Classify the engagement using the profile policy.
4. Detect suppression/high-risk/quiet-period conditions.
5. Record an immutable `engagement_reply_proposed` audit receipt containing hashes and bounded metadata, never tokens.
6. Return a proposal; do not send.
7. A separate governed send request must provide explicit approval and match the proposal ID/hash.
8. High-risk items require human-handoff acknowledgement before send.
9. Duplicate sends are rejected using an operation key plus immutable audit history and cache locking.
10. Record provider result as an immutable receipt.

## Handoffs

The service selects handoff targets from the resolved #337 profile. It may return `crm`, `workcore`, `bookings`, `property`, `commerce`, `hire`, memberships or other existing canonical targets. The service does not invent a new CRM or operational record store. Handoff receipts state `human_handoff_required` and the authoritative targets.

## Inbox normalization

Every provider result is normalized to:

- `provider`
- `engagement_id`
- `resource_id`
- `parent_id`
- `actor_id`
- `actor_alias`
- `message`
- `received_at`
- `provider_context`
- `classification`
- `risk`
- `handoff_targets`

Provider-specific raw data may be retained only in bounded provider context and must not include access tokens.

## Edit and delete

Only providers with supported edit/delete operations may advertise them. The governance service checks the per-account capability map again before transport calls. Unsupported operations fail closed.

## Automation boundary

`AutomationExecutionService` must no longer perform provider-changing engagement actions directly from a webhook trigger. It stages a governed proposal. Existing provider send helpers may remain for explicit governed calls or compatibility, but the automation execution path cannot call them automatically.

The bogus TikTok comment-reply transport is removed.

## Logging and privacy

Facebook/Instagram webhook controllers and automation services must not log full webhook bodies, comment text, reply text, access tokens or provider response bodies. Logs contain operation IDs, platform/account IDs, status and hashes only.

## UI boundary

Issue #273 adds no Blade page. Authenticated JSON routes expose capability, inbox, proposal, reply, private reply, edit, delete and handoff operations. Customer-facing inbox UI remains separate work.

## Verification

A focused GitHub Actions workflow runs `app/extensions/SocialMedia/tests/engagement_inbox_parity_contract.php`, PHP lints every changed engagement PHP file, and PR review verifies no unresolved actionable findings before merge.
