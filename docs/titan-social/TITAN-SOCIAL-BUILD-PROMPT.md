# Titan Social — Consolidation and Upgrade Prompt

Upgrade the existing MagicAI `SocialMedia` extension into the customer-facing **Titan Social** product.

This is a controlled consolidation project with strict compatibility and extension-boundary rules.

## 1. Product identity and installation compatibility

Keep the primary extension's technical identity unchanged:

- install folder: `SocialMedia`
- namespace: `App\Extensions\SocialMedia`
- service provider: `SocialMediaServiceProvider`
- existing extension slug
- existing configuration keys
- existing route names
- existing migration history
- existing database table names, unless a backwards-compatible migration and alias strategy is provided

Change customer-facing branding to **Titan Social** in:

- extension manifest display name
- navigation labels
- page titles
- headings and breadcrumbs
- onboarding
- empty states
- connection screens
- settings
- plan and feature labels
- notifications
- visible validation and status messages
- help text and documentation

Do not rename PHP namespaces, folders, tables, route names or configuration keys merely for branding.

Existing `SocialMedia` installations must upgrade without losing accounts, OAuth connections, posts, campaigns, schedules, analytics, media, webhooks, automation rules or publishing history.

---

## 2. Mandatory extension boundaries

### 2.1 `SocialMedia` becomes Titan Social

`SocialMedia` is the authoritative Titan Social application.

It owns:

- connected social accounts and pages
- provider permissions and account health
- social posts and media attachments
- content calendar and publishing state
- campaign grouping
- scheduled publishing
- approval state
- comments, mentions and social inbox references
- platform metrics and analytics
- social listening results
- trend intelligence
- creator and influencer relationships
- UGC campaign records
- social automation rules
- publishing receipts and failure state

### 2.2 `AIAgent` remains separate

Do not merge or copy the general `AIAgent` runtime into Titan Social.

`AIAgent` remains responsible for:

- general agent orchestration
- generic memory
- cross-product tool selection
- multi-step reasoning
- non-social agent workflows
- generic agent conversations

Titan Social must work when `AIAgent` is not installed.

### 2.3 `AIAgentToolSocialMediaAgent` remains separate

Do not merge `AIAgentToolSocialMediaAgent` into `SocialMedia` or `AIAgent`.

It remains the optional governed bridge between AI Agent and Titan Social:

```text
AIAgent
   |
   v
AIAgentToolSocialMediaAgent
   |
   v
SocialMedia / Titan Social
```

The bridge may depend on both products.

`SocialMedia` must not require the bridge.

`AIAgent` must not require Titan Social.

When either dependency is unavailable, the bridge must disable itself cleanly without breaking application boot.

The bridge must be upgraded to expose all consolidated Titan Social capabilities through stable, governed tools.

---

## 3. Consolidation donors

The following extensions may be integrated into `SocialMedia` as internal Titan Social modules.

### Social product donors

- `AISocialMedia`
- `SocialMediaAgent`
- `SocialMediaAutomation`

Consolidate useful capabilities rather than keeping duplicate menus, account models, post models, schedulers, analytics records or automation engines.

#### `AISocialMedia`

Use as a donor for:

- AI content generation
- platform connections not already covered
- campaign scheduling
- publishing workflows
- automation features

Migrate useful capabilities into Titan Social's authoritative account, post, campaign and publishing models.

#### `SocialMediaAgent`

Integrate its domain-specific planning, generation, trend analysis, recommendations and approval workflows into an internal **Social Intelligence and Content Agent** module.

Do not retain a second independent social post authority.

Its reusable services should call Titan Social application services rather than writing competing records directly.

#### `SocialMediaAutomation`

Integrate its comment, mention and direct-message automation into an internal **Engagement Automation** module.

Use Titan Social's canonical connected accounts, permission checks, webhook verification, inbox references and audit log.

Do not preserve a second webhook or provider-account authority.

### Creative donors

- `InfluencerAvatar`
- `UGCCreator`
- `UGCFactory`
- `AiViralClips`
- `UrlToVideo`
- `AiCaptions`
- `VideoEditor`

Integrate useful capabilities into Titan Social's **Creative Studio**, **UGC Studio**, **Influencer Studio** and **Video Studio**.

Avoid duplicate provider settings, media libraries, generation histories and publishing handoff records.

Creative donor packages are source material. Do not automatically uninstall them until migration and parity are verified.

### Marketing product boundary

Do not merge `MarketingBot` or Titan Marketing into Titan Social.

Titan Marketing owns audience journeys across email, SMS, WhatsApp, Messenger and voice.

Titan Social owns public social publishing, engagement, listening and social analytics.

Allow optional cross-product contracts for campaign briefs, approved content, lead handoff and attribution.

---

## 4. Target Titan Social architecture

Refactor `SocialMedia` into focused internal modules while preserving the external install identity.

Suggested structure:

```text
SocialMedia/
├── System/
│   ├── Accounts/
│   ├── Providers/
│   ├── Publishing/
│   ├── Calendar/
│   ├── Campaigns/
│   ├── Inbox/
│   ├── EngagementAutomation/
│   ├── Listening/
│   ├── Trends/
│   ├── Intelligence/
│   ├── Analytics/
│   ├── Attribution/
│   ├── Approvals/
│   ├── CreativeStudio/
│   ├── VideoStudio/
│   ├── UGC/
│   ├── Influencers/
│   ├── Integrations/
│   └── Contracts/
├── resources/
├── routes/
├── tests/
└── extension.json
```

Use focused services and explicit contracts. Do not create one oversized controller or service.

---

## 5. Provider-neutral social contracts

Publishing, analytics, engagement and webhooks must use provider-neutral contracts.

Example publishing contract:

```php
interface SocialPublisher
{
    public function platform(): string;

    public function capabilities(): array;

    public function validate(
        SocialAccount $account,
        SocialPostDraft $draft
    ): void;

    public function preview(
        SocialAccount $account,
        SocialPostDraft $draft
    ): SocialPostPreview;

    public function publish(
        SocialAccount $account,
        ApprovedSocialPost $post
    ): SocialPublishReceipt;

    public function status(
        SocialAccount $account,
        string $providerPostId
    ): SocialPublishStatus;

    public function delete(
        SocialAccount $account,
        string $providerPostId
    ): SocialDeleteReceipt;
}
```

Create related contracts for:

- OAuth and account connection
- webhook verification
- comments and mentions
- direct messages
- analytics and metrics
- listening and search
- media requirements
- rate-limit and quota reporting

Support available providers such as:

- Facebook Pages
- Instagram Business
- LinkedIn profiles and organisations
- X/Twitter
- TikTok
- YouTube
- future provider adapters

Missing or unconfigured providers must appear as unavailable integrations rather than causing boot failures.

Credentials and refresh tokens must use the shared encrypted credential vault.

---

## 6. Expanded `AIAgentToolSocialMediaAgent` bridge

Modify the bridge to understand the consolidated Titan Social product.

### Required read tools

- list connected social accounts
- inspect account permissions and health
- list posts
- inspect post
- list scheduled posts
- inspect calendar
- list social campaigns
- inspect campaign
- inspect pending approvals
- inspect publishing failures
- inspect inbox queues
- inspect comments and mentions
- inspect automation rules
- inspect trends
- inspect listening topics
- inspect platform analytics
- inspect post analytics
- inspect creator and influencer records
- inspect UGC campaigns
- inspect content assets

### Required drafting and planning tools

- create post draft
- create campaign draft
- create content-calendar draft
- generate platform-specific variants
- generate caption variants
- generate hashtag recommendations
- generate creative brief
- generate image brief
- generate short-video brief
- generate UGC brief
- generate influencer outreach draft
- recommend publishing times
- recommend platform mix
- recommend trend opportunities
- recommend repurposing plan
- recommend engagement automation rule

### Required governed action tools

- request post approval
- request campaign approval
- approve only when the current user has explicit authority
- schedule approved post
- publish approved post
- reschedule post
- pause scheduled campaign
- resume campaign
- cancel scheduled post
- retry eligible publishing failure
- reply to comment with approval rules
- send or queue a direct-message response with approval rules
- enable approved automation rule
- disable automation rule
- hand conversation to a human queue

### Governance rules

AI Agent must never silently:

- connect or disconnect accounts
- change OAuth permissions
- publish unapproved external content
- materially alter approved content and publish it without renewed approval
- delete published content
- send bulk direct messages
- enable aggressive comment or DM automation
- purchase advertising
- increase budgets
- impersonate a person or creator
- suppress required AI or sponsorship disclosures
- delete audit evidence

Every governed tool response must include:

- action status
- approval status
- actor
- workspace
- account and platform
- post or campaign identifier
- content version
- policy checks
- provider receipt or refusal reason

---

## 7. Titan Social application gateway for AI Agent

The bridge must call a dedicated application service rather than controllers or models directly.

Example:

```php
interface TitanSocialAgentGateway
{
    public function capabilities(
        AgentExecutionContext $context
    ): SocialCapabilityMap;

    public function createPostDraft(
        AgentExecutionContext $context,
        CreateSocialPostDraftData $data
    ): SocialPostDraftResult;

    public function requestApproval(
        AgentExecutionContext $context,
        RequestSocialApprovalData $data
    ): SocialApprovalResult;

    public function publishApprovedPost(
        AgentExecutionContext $context,
        PublishApprovedSocialPostData $data
    ): SocialPublishReceipt;
}
```

The gateway must enforce:

- tenant and workspace scope
- user permissions
- account permissions
- content approval
- platform policy
- media requirements
- rate limits
- budget rules where applicable
- idempotency
- audit logging

The bridge must never bypass the application services used by the Titan Social UI.

---

## 8. Social publishing and calendar

Support:

- single-platform posts
- multi-platform post groups
- platform-specific variants
- drafts
- approvals
- scheduled publishing
- recurring content plans without duplicate delivery
- queues and calendar views
- timezone-aware scheduling
- test and preview mode
- media validation
- alt text and accessibility metadata
- first-comment strategies where supported
- post versioning
- pause, resume and cancellation
- retry with bounded backoff
- immutable publishing receipts
- deletion and edit policies based on provider support

Large media processing and publishing operations must run through queues or the shared durable workflow runtime.

---

## 9. Social inbox and engagement automation

Create one governed social inbox surface for:

- comments
- mentions
- replies
- direct messages where provider permissions allow
- review and escalation queues
- assigned conversations
- sentiment and urgency indicators
- human handoff
- response history

Automation must support:

- keyword triggers
- comment triggers
- mention triggers
- direct-message triggers
- approved response templates
- AI-assisted draft responses
- delay and quiet-period rules
- duplicate-event prevention
- rate limits
- escalation rules
- suppression and block lists
- human approval modes
- transparent automation logs

Verify provider webhooks using provider-specific signatures and replay protection.

---

## 10. Listening, trends and intelligence

Integrate social intelligence capabilities for:

- tracked keywords
- brand mentions
- competitor monitoring
- trend discovery
- content-gap analysis
- sentiment signals
- recurring topic reports
- audience questions
- creator discovery
- opportunity recommendations

Clearly distinguish:

- directly measured provider data
- third-party sourced data
- AI-generated inference
- confidence level
- unavailable or incomplete coverage

Do not present inferred sentiment or trend predictions as objective fact.

---

## 11. Creative, video, UGC and influencer modules

### Creative Studio

Support:

- platform-aware image and video briefs
- reusable brand kits
- templates
- aspect-ratio variants
- caption and text overlays
- accessibility review
- safe media library
- approval and version history

### Video Studio

Integrate:

- URL-to-video workflows
- long-video-to-short-clips
- captions
- timeline editing
- thumbnails
- platform-safe exports
- publishing handoff

Use queues, cancellation, provider budgets and recoverable status polling.

### UGC Studio

Support:

- UGC campaign briefs
- creator instructions
- generated UGC concepts
- submission tracking
- rights and usage-status fields
- disclosure requirements
- approval state
- channel-ready variants

### Influencer Studio

Support:

- creator profiles
- campaign fit
- outreach drafts
- deliverables
- rights and usage terms
- disclosure requirements
- content approvals
- performance attribution

Never represent an AI-generated avatar as a real human creator without clear disclosure.

---

## 12. Unified Titan Social UI

Create these customer-facing sections:

1. **Overview**
   - account health
   - scheduled posts
   - engagement
   - reach
   - exceptions
   - approvals
   - trend opportunities

2. **Calendar**
   - day, week and month views
   - drag-and-drop rescheduling
   - platform filters
   - campaign filters
   - approval state

3. **Create**
   - post composer
   - platform variants
   - media validation
   - AI assistance
   - preview
   - approval

4. **Campaigns**
   - campaign grouping
   - objectives
   - content plans
   - schedules
   - results

5. **Inbox**
   - comments
   - mentions
   - replies
   - direct messages where supported
   - assignment
   - escalation

6. **Automations**
   - comment and DM rules
   - triggers
   - templates
   - approval mode
   - logs

7. **Listening**
   - topics
   - mentions
   - competitors
   - sentiment signals
   - opportunities

8. **Trends**
   - emerging topics
   - platform trends
   - recommended content
   - evidence and confidence

9. **Creative Studio**
   - images
   - videos
   - captions
   - UGC
   - influencers
   - approved assets

10. **Analytics**
    - platform comparison
    - post performance
    - campaign performance
    - engagement
    - conversion attribution
    - creator and UGC results

11. **Accounts**
    - connections
    - permissions
    - token health
    - webhook health
    - limits

12. **Settings**
    - branding
    - approvals
    - automation policies
    - disclosure
    - retention
    - integrations

Use **Titan Social** consistently in visible UI copy while retaining `SocialMedia` internally.

---

## 13. Data authority rules

Avoid duplicate authority.

- Titan Social owns social accounts, posts, publishing state, social metrics and engagement automation.
- WorkCore/Titan CRM owns canonical customers, leads and companies.
- Titan Marketing owns omnichannel marketing journeys outside public social publishing.
- AI Agent owns generic agent state.
- `AIAgentToolSocialMediaAgent` translates between AI Agent tools and Titan Social application services.
- Creative modules may store asset generation and editing state, but Titan Social owns publishing linkage and approval state.

---

## 14. Migration strategy

### Phase 1 — branding and compatibility

- rename visible UI to Titan Social
- preserve `SocialMedia` install identity
- add module boundaries and contracts
- add compatibility tests
- add feature flags
- expand bridge capability discovery

### Phase 2 — social product consolidation

- map `AISocialMedia` accounts, posts and schedules
- integrate `SocialMediaAgent` services into Intelligence and Create
- integrate `SocialMediaAutomation` into Inbox and Automations
- centralise account authority, webhooks and publishing receipts

### Phase 3 — creative consolidation

- integrate influencer, UGC, clips, URL-to-video, captions and editing modules
- centralise media assets and provider settings
- preserve legacy data through mappings and compatibility adapters

### Phase 4 — donor retirement

- verify feature parity
- provide migration commands
- provide rollback instructions
- disable duplicate menus and scheduled jobs
- retire donor extensions only after data and behaviour parity are proven

Do not delete donor data or uninstall donor extensions automatically.

---

## 15. Security, privacy and platform compliance

Implement:

- tenant isolation
- permission checks
- encrypted token references
- OAuth state checks
- PKCE where supported
- provider webhook signature validation
- webhook replay prevention
- idempotent publishing
- bounded retries
- provider rate-limit handling
- media type and size validation
- safe URL fetching
- audit logs
- immutable publishing receipts
- retention controls
- account permission checks
- approval gates
- disclosure controls
- block and suppression rules

Respect each platform's current API terms, content policies and automation restrictions. Unsupported actions must be unavailable rather than simulated.

---

## 16. Testing requirements

Add automated tests for:

- existing `SocialMedia` upgrade compatibility
- visible Titan Social branding
- unchanged internal install identity
- boot with optional donors absent
- boot with `AIAgent` absent
- boot with bridge absent
- bridge disabling when dependencies are missing
- bridge capability discovery
- tenant isolation
- account permission checks
- OAuth state handling
- webhook signature verification
- webhook replay prevention
- idempotent publishing
- duplicate webhook events
- media validation
- approval enforcement
- material content change requiring renewed approval
- provider failure and retry
- rate-limit handling
- calendar pause, resume and cancellation
- automation approval modes
- direct-message and comment policy enforcement
- donor data migration
- legacy route compatibility

Add contract tests proving the AI Agent bridge cannot perform any action that Titan Social's own application layer would reject.

---

## 17. Definition of done

The consolidation is complete when:

- the extension still installs and upgrades as `SocialMedia`
- the visible product name is **Titan Social**
- `AIAgent` remains separate
- `AIAgentToolSocialMediaAgent` remains a separate expanded bridge
- Titan Social works without AI Agent installed
- `AISocialMedia`, `SocialMediaAgent` and `SocialMediaAutomation` capabilities are available through one coherent Titan Social experience
- selected creative capabilities are integrated through Creative, Video, UGC and Influencer modules
- duplicate account, post, webhook, analytics and automation authorities are removed or safely adapted
- existing data is preserved
- publishing and externally visible actions are governed
- every external action creates an auditable receipt
- Titan Marketing remains a separate product connected through optional contracts
- automated tests pass
- migration and rollback documentation is complete
