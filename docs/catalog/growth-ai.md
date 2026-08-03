# Content, SEO, Social & Growth AI

Functions and routes are statically detected. Cross-extension relationships are integration signals unless verified by manifests and boot tests. See [Documentation Audit](../DOCUMENTATION-AUDIT.md).

## AI Plagiarism (`AIPlagiarism`) — v2.2

Plagiarism checking and AI-content detection with report persistence and admin settings.

- **Shape:** 9 files; 1 controller; 1 service; 1 migration; 8 detected routes.
- **Functions:** `checkPlagiarism`, `detectAIContent`, `plagiarismCheck`, `detectAIContentCheck`, `plagiarismSave`, `detectAIContentSave`, `plagiarismSetting`, `plagiarismSettingSave`.
- **Providers:** PlagiarismCheck and Serper.
- **Review:** no bundled tests detected; two TODO/FIXME markers. Provider confidence must not be presented as proof of authorship or misconduct. Add transparent scores, error ranges, retention policy, user consent, retries, and provider-failure handling.

## AI Social Media (`AISocialMedia`) — v4.12

Social-platform connection, content generation, campaign scheduling, publishing, and automation.

- **Shape:** 71 files; 7 controllers; 6 services; 6 models; 13 migrations; 29 detected routes.
- **Functions:** `getProfile`, `shareNone`, `share`, `platforms`, `find`, `findAutomationPlatform`, upload, post generation, scheduling, campaign operations, and platform-connection lifecycle.
- **Tables:** `automation_campaigns`, `automation_platforms`, `automations`, `linkedin_tokens`, `scheduled_posts`, `twitter_settings`.
- **Providers:** Meta, LinkedIn, X/Twitter, and OpenAI.
- **Review:** no bundled tests detected; three TODO/FIXME markers. This overlaps the larger SocialMedia platform, SocialMediaAgent, and SocialMediaAutomation. Clarify product authority before migration; tokens should move to the credential vault and durable publishing to the workflow runtime.

## BlogPilot (`BlogPilot`) — v1.4

AI blog planning and generation with agents, topic generation, bulk posts, calendar, image generation, and status tracking.

- **Shape:** 74 files; 1 controller; 2 services; 2 models; 2 migrations; 21 detected routes.
- **Functions:** `generateImageForPost`, `checkStatus`, `setModel`, `generatePost`, `generateBulkPosts`, `generateTopics`, `__invoke`.
- **Tables:** `ext_blogpilot`, `ext_blogpilot_posts`.
- **Providers:** OpenAI and Fal.
- **Review:** no bundled tests detected; one TODO marker and one commented process-execution match. Bulk generation needs queueing, idempotency, cancellation, provider budgets, source/citation policy, editorial approval, and recoverable scheduling.

## Content Manager (`ContentManager`) — v1.6

Central listing and update surface for generated content.

- **Shape:** 10 files; 1 controller; 1 model; 2 detected routes.
- **Functions:** `index`, `update`.
- **Routes:** `GET /`, `POST /update` within the extension's route group as statically observed; final full paths depend on service-provider grouping.
- **Review:** no bundled tests detected. Generic route fragments must be verified for host collisions, authorization, tenant scoping, and content ownership.

## SEO Tool (`SEOTool`) — v3.6

Keyword discovery, AI article analysis, content improvement, SEO generation, and related-question discovery.

- **Shape:** 13 files; 1 controller; 2 services; 1 model; 7 detected routes.
- **Functions:** `getKeywords`, `analiyzeWithAI`, `improveWithAI`, `generateSEO`, `getSearchQuestions`, `suggestKeywords`, `analyseArticle`.
- **Provider:** Google Serper.
- **Review:** no bundled tests detected. Two scanner matches are JavaScript regex `.exec()` calls, not shell execution. The misspelled method `analiyzeWithAI` should be retained for compatibility only behind a correctly named service method. Add source provenance, request limits, provider errors, and output-quality tests.

## AI Social Media Pro (`SocialMedia`) — v5.13

Full social publishing and analytics platform with platform OAuth, webhooks, campaigns, posts, metrics, shared logs, bulk upload, and media processing.

- **Shape:** 143 files; 20 controllers; 15 services; 6 models; 19 migrations; 43 detected routes.
- **Functions:** `sync`, `fetch`, `splitAndUpload`, `cleanup`, `storeBulk`, platform redirects/callbacks, webhook handlers, publishing, analytics, and campaign operations.
- **Tables:** `ext_social_media_analyses`, `ext_social_media_campaigns`, `ext_social_media_platforms`, `ext_social_media_post_daily_metrics`, `ext_social_media_posts`, `ext_social_media_shared_logs`.
- **Detected add-ons/consumers:** SocialMediaAgent and SocialMediaAutomation. The earlier catalogue wording could be read backwards; those packages appear to consume or extend the SocialMedia platform rather than SocialMedia necessarily requiring both.
- **Providers:** Meta, LinkedIn, X, Google/YouTube, and TikTok.
- **Review:** no bundled tests detected. Add OAuth state and PKCE checks where applicable, encrypted tokens, provider-specific webhook signatures, replay prevention, idempotent publishing, media validation, rate-limit handling, account permissions, approval gates, and durable retry workflows.

## Consolidation direction

- Establish one authoritative social account and token model.
- Keep connectors responsible for authentication, inbound/outbound transport, webhook verification, retries, and health.
- Keep SocialMedia responsible for posts, campaigns, calendars, metrics, and publishing state.
- Let SocialMediaAgent plan and draft; let AIAgent or a shared workflow runtime execute durable schedules and retries.
- Require approvals and receipts for external publishing or account-changing operations.

> No bundled automated tests were detected in these six selected extension folders; tests may exist elsewhere upstream.
