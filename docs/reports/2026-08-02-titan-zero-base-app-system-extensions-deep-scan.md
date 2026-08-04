# Titan Zero — Base App System Extensions Deep Scan

## Executive summary

The archive contains **34 Laravel/MagicAI-style extensions**:

- **21 feature extensions**
- **7 AI/model extensions**
- **6 integrations**
- **504 extracted files**
- **359 PHP files**
- Approximately **42,156 lines of PHP**
- All 359 PHP files pass `php -l`
- **No automated tests**
- **No Composer or npm dependency manifests**
- **No licence files**

The archive contains valuable donor code, but it is not safe to install wholesale into Titan Zero. The best assets are reusable orchestration, media, voice, onboarding, pricing and integration patterns. Most code is tightly coupled to the MagicAI base application and many external-provider credentials are stored as global settings rather than tenant/device-scoped secrets.

## Highest-value extraction targets

1. **Model Council** — multi-provider parallel inference, consensus, confidence and synthesis.
2. **OpenAI Realtime Chat** — authenticated ephemeral realtime voice sessions.
3. **ElevenLabs Voice Chat** — voice agents with text, file and URL knowledge ingestion.
4. **Cloudflare R2 + Content Manager** — media/storage foundation for Evidence, Asset, Tender and SafeWork.
5. **AI Captions** — asynchronous video processing, duration metering and captioned-video delivery.
6. **AI Writer Templates** — reusable structured-document template pattern.
7. **Announcement + Onboarding Pro** — alerts, guided tours, surveys, acknowledgements and training delivery.
8. **Discount Manager + Checkout Registration** — premium app sales, bundles, promotions and subscription onboarding.
9. **Xero** — OAuth and contact-sync skeleton, but not a complete invoice/payment integration.
10. **Migration** — extensible importer architecture for customer onboarding.

## App-by-app value map

| Titan app | Valuable donor extensions | Main additions |
|---|---|---|
| Titan Pay | Xero, ElevenLabs Voice Chat, OpenAI Realtime, Azure TTS, Discount Manager, Checkout Registration | Voice follow-up, accounting connection skeleton, payment-plan and promotion rules, app subscription checkout |
| Titan Quote | AI Writer Templates, Discount Manager, Model Council, Multi-Model, Content Manager | Quote templates, conditional offers, multi-model quote review, media attachments |
| Titan Sentinel | Model Council, Perplexity, Announcement, Onboarding Pro, AI Writer Templates, AI Persona, AI Captions | Regulatory change review, confidence/consensus, critical alerts, training and acknowledgement |
| Titan Evidence | Cloudflare R2, Content Manager, AI Captions | Durable media storage, media selection, video captioning/transcription pipeline |
| Titan Field Expert | ElevenLabs Voice Chat, OpenAI Realtime, Azure TTS, Azure OpenAI, OpenRouter, Perplexity, Model Council | Realtime voice, grounded knowledge agents, provider routing and high-risk answer cross-checking |
| Titan SafeWork | AI Writer Templates, Onboarding Pro, Announcement, AI Persona, AI Captions, Model Council | Safety templates, micro-training, acknowledgements and multi-model review |
| Titan Smart Dispatch | OpenAI Realtime, ElevenLabs Voice Chat, Azure TTS, HubSpot | Voice intake and conversational dispatch; no route-optimisation engine is present |
| Titan Award | Xero, Migration, Model Council | Accounting linkage and data import; no award/payroll rules engine is present |
| Titan Tender | Model Council, Perplexity, Plagiarism, AI Writer Templates, Content Manager, Cloudflare R2 | Research, multi-model analysis, document library, originality checks and response templates |
| Titan Asset | Content Manager, Cloudflare R2, Field Expert model stack | Manuals, photos, equipment files and troubleshooting; no asset lifecycle engine is present |

## Full extension matrix

### Feature extensions

| Extension | What the code actually contains | Titan value | Verdict |
|---|---|---|---|
| Migration | Driver-based importer, SQL parsing, users, plans, gateways, subscribers, documents and chats | Customer migration framework | **Extract architecture; rewrite security boundaries** |
| UGC Creator | Queued video generation, reference images, presets, OpenAI/ElevenLabs voiceover and FAL API | Titan Showcase and training content | **Useful later** |
| Focus Mode | Header/dropdown UI focus controls | Cleaner field UI | **Small UX donor** |
| AI Captions | Captions API, background jobs, video duration, monthly minute limits and downloads | Evidence video, training and accessibility | **High-value donor** |
| Announcement | Announcement CRUD, types and cache invalidation | Sentinel and SafeWork alerts | **High-value small donor** |
| Mega Menu | Menu groups, items, ordering, columns and icon upload | App navigation shell | **Platform-only** |
| Menu | Navigation ordering, badges, status and custom items | App navigation shell | **Platform-only** |
| AI Avatar | Synthesia avatar-video generation and history | Training and customer explainers | **Secondary** |
| Content Manager | Media-manager injection and file-type/size limits | Evidence, Asset, Tender and SafeWork media | **High value, but depends on missing base Livewire component** |
| AI Persona | HeyGen avatars, voice types, locales and video settings | Safety and compliance training | **Secondary/high potential** |
| Checkout Registration | Registration tied to Stripe/PayPal plan checkout | Selling Titan add-ons and bundles | **Useful, Stripe stronger than PayPal implementation** |
| Onboarding Pro | Guided introductions, banners, surveys, results and Shepherd.js tours | Customer onboarding, worker induction and acknowledgements | **High-value donor** |
| Live Customizer | Runtime visual customisation and font settings | White-label business portals | **Useful platform feature** |
| Discount Manager | Conditional discounts, coupons, plan/gateway/user conditions and promo banners | Quote promotions, annual plans and bundle sales | **High-value commercial donor** |
| Plagiarism | PlagiarismCheck.org, AI-content checking and Serper integration | Tender response integrity | **Optional; privacy and API dependency concerns** |
| Influencer Avatar | Short social-video generation and status polling | Marketing only | **Low priority** |
| AI Writer Templates | Seeded structured prompt/document templates | Quote, Tender, Sentinel and SafeWork document generation | **High-value donor** |
| ElevenLabs Voice Chat | Voice-agent creation, voices, knowledge-base files/text/URLs and agent updates | Field Expert, Dispatch and Pay voice agent | **High value; crawler must be replaced** |
| UGC Factory | Actor generation, video queues, presets, voiceover and credit handling | Showcase and training-video factory | **Useful later** |
| Maintenance | Maintenance-mode admin UI | Operational control | **Small platform donor** |
| Introductions | Basic introduction/onboarding display | Duplicate of Onboarding Pro | **Do not keep separately** |

### AI/model extensions

| Extension | What the code actually contains | Titan value | Verdict |
|---|---|---|---|
| Multi-Model | Shared response UUID/model fields and “accept one response” controller | Compare model answers | **Schema/UI donor; generation logic is elsewhere** |
| Azure TTS | Azure SSML speech synthesis via cURL | Voice fallback and enterprise TTS | **Useful donor** |
| Perplexity | Settings and model-selection UI only | Sentinel/Tender research | **Thin wrapper; provider logic assumed in base app** |
| Azure OpenAI | Azure-hosted streaming chat and settings | Enterprise/private AI deployment | **High potential but tightly coupled** |
| OpenAI Realtime Chat | Ephemeral realtime session tokens, transcription and voice | Field Expert, Dispatch and Pay | **High-value donor** |
| Model Council | Parallel OpenAI/Anthropic/Gemini/DeepSeek/xAI/OpenRouter calls, consensus metrics, synthesis and persistence | Sentinel, Tender, SafeWork and complex quotes | **Best extension in archive** |
| OpenRouter | Settings, model selection and a thin chat-completions service | BYO model routing | **Useful concept; rewrite streaming and tenancy** |

### Integration extensions

| Extension | What the code actually contains | Titan value | Verdict |
|---|---|---|---|
| Cloudflare R2 | Global filesystem credentials and R2 settings | Evidence and Asset media storage | **High-value donor; redesign tenant encryption** |
| WordPress | JWT login, media upload, posts, categories and tags | Showcase/content publishing | **Useful later; validate target domains** |
| Mailchimp | Admin settings only | Marketing lists | **Thin integration** |
| Xero | OAuth configuration, keep-alive schedule and contact creation | Titan Pay/Award/Quote accounting bridge | **Useful skeleton only; no invoice sync or reconciliation** |
| HubSpot | Access-token settings and contact list/create | Lead and customer sync | **Thin integration** |
| Cryptomus | Payment-gateway service with webhook/subscription concepts | Optional crypto payments | **Do not prioritise; incomplete subscription method** |

## Important defects and portability blockers

1. **No tests:** none of the 34 extensions contains automated tests.
2. **No dependency manifests:** external packages such as Xero, HubSpot, Stripe, OpenAI and getID3 are assumed to exist in the base application.
3. **No licence files:** confirm commercial redistribution and modification rights before shipping extracted code.
4. **Global secrets:** many provider keys are stored through global `setting()` values or `.env`, conflicting with Titan’s tenant/device-first BYO-key model.
5. **Missing admin authorisation:** several `/dashboard/admin/...` route groups use only `auth`, including Announcement, Onboarding Pro, Migration, Content Manager, ElevenLabs Voice Chat, Xero and OpenRouter.
6. **State changes over GET:** delete actions exist as GET routes in Announcement, Menu, Mega Menu, Onboarding, AI Avatar and AI Persona.
7. **Unsafe URL crawling:** ElevenLabs `LinkParser` uses `file_get_contents()` on user-provided URLs without private-IP blocking, robust timeouts or redirect controls.
8. **Xero token security:** the Xero config defaults token encryption to `false`; client secrets are displayed and saved through generic settings.
9. **WordPress SSRF risk:** arbitrary user-supplied domains are contacted directly and require strict allowlisting/private-network blocking.
10. **Cloud-only assumptions:** avatar, captions, UGC, realtime and voice extensions send data to third parties and need explicit privacy controls and local/BYO alternatives.
11. **Incomplete implementations:** PayPal checkout methods, Cryptomus subscription checkout and many uninstall handlers contain TODOs.
12. **Coupling:** most extensions depend heavily on MagicAI models, helpers, marketplace registration, credit systems, Blade components and settings.

## Capabilities not present in the archive

The archive does **not** contain working implementations for:

- Visual defect detection or job-photo inspection
- OCR/measurement-based visual estimating
- Local or on-device vision models
- Maps, traffic, weather or route optimisation
- SMS or WhatsApp delivery
- E-signatures
- Government regulatory crawling suitable for Sentinel
- Tender/RFP PDF parsing
- Award/payroll rule calculation
- Asset lifecycle management
- Invoice synchronisation, reconciliation or dunning
- Trade licence registry verification
- Vector storage controlled by Titan
- Device vault or tenant-scoped encrypted BYO-key management

## Recommended extraction order

### Phase 1 — shared foundations

1. Model Council
2. OpenAI Realtime Chat
3. ElevenLabs Voice Chat, excluding its crawler
4. Cloudflare R2 abstraction
5. Content Manager
6. AI Writer Templates
7. Announcement
8. Onboarding Pro

### Phase 2 — commercial and operational connectors

9. Xero OAuth/contact skeleton
10. Discount Manager
11. Checkout Registration
12. Migration driver framework
13. Azure OpenAI, OpenRouter, Perplexity and Azure TTS adapters

### Phase 3 — secondary growth and training

14. AI Captions
15. AI Persona or AI Avatar — choose one, not both initially
16. UGC Creator
17. UGC Factory
18. WordPress
19. HubSpot and Mailchimp

### Exclude from initial Titan work

- Cryptomus
- Influencer Avatar
- Introductions as a separate system
- Duplicate menu/customiser systems unless the Titan shell specifically needs them

## Architectural recommendation

Do not install these as independent MagicAI extensions. Extract them into Titan-owned shared services:

- `TitanAiGateway`
- `TitanModelCouncil`
- `TitanRealtimeVoice`
- `TitanKnowledgeAgent`
- `TitanMediaVault`
- `TitanDocumentTemplateEngine`
- `TitanNotificationCenter`
- `TitanOnboardingAndTraining`
- `TitanAccountingConnector`
- `TitanCommerceRules`

The specialised Titan app should own the business decision. Shared services should only provide AI, media, voice, templates, storage and connector capabilities.
