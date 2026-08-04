---
title: "MagicAI 11 Complete System Inventory"
scan_number: 1
date: "2026-08-02"
magicai_version: "11.00"
status: "Completed"
source: "Magicai-Server-Files(3).zip"
---

# Scan 01 — MagicAI 11 Complete System Inventory

## Executive conclusion

MagicAI 11 is a substantial Laravel SaaS and AI application host, but this server ZIP is **not a complete bundle of every feature advertised by the platform**.

The package contains the complete MagicAI host, AI engine and entity routing, user and team accounts, subscriptions, shared credits, payments, native AI Writer and AI Chat foundations, documents, support, public chatbot transport, marketplace installation, dynamic menus and plan entitlements.

It also registers **114 optional extension providers**, but none of those provider implementations are present under `app/Extensions/` in this ZIP. This includes the new paid CRM, AI Chat Pro, AI Agent, messaging channels and most advanced creative tools.

> **MagicAI should be treated as the host platform and integration surface.**  
> **WorkCore should remain the authoritative operational engine.**

---

# 1. Scope and method

The scan inspected:

- Application source
- Framework and package manifests
- Configuration and bootstrap
- Routes and API documentation
- Models and migrations
- Controllers, middleware and requests
- Services and domains
- Providers
- Jobs, events, listeners and commands
- Blade views and frontend sources
- Compiled frontend assets
- Marketplace provider registry
- Tests and CI
- Bundled runtime artefacts

Validation included first-party PHP syntax checking, source-presence checks for extension providers, static route counts, component inventories and package-hygiene review without exposing secret values.

---

# 2. Package baseline

| Measure | Result |
|---|---:|
| MagicAI version | `11.00` |
| Total files | 5,089 |
| Extracted size | 113,409,203 bytes |
| PHP files | 2,706 |
| Application PHP files | 1,031 |
| Database PHP files | 427 |
| Migrations | 399 |
| Core models | 100 |
| Controllers | 129 |
| Middleware | 26 |
| Form requests | 12 |
| API resources | 3 |
| Services | 77 |
| Domain PHP files | 364 |
| Providers | 11 core providers |
| Jobs | 11 |
| Events | 14 |
| Listeners | 13 |
| Blade views | 610 |
| Config files | 46 |
| Route declarations in route files | 595 |
| Additional native routes found outside route files | At least 19 |
| Static menu definitions | 221 |
| Top-level menu candidates | 74 |
| Optional provider registrations | 114 |
| Optional provider source implementations present | 0 |
| OpenAPI documented paths | 61 |
| Tests | 0 |
| CI workflows | 0 |
| First-party PHP files passing syntax validation | 1,551 |
| PHP syntax failures | 0 |

## Major package areas

| Folder | Approximate size |
|---|---:|
| `public/` | 98 MB |
| `resources/` | 7.8 MB |
| `app/` | 7.5 MB |
| `packages/` | 6.0 MB |
| `storage/` | 2.4 MB |
| `database/` | 1.8 MB |
| `config/` | 372 KB |
| `docs/` | 236 KB |
| `routes/` | 108 KB |

The application is frontend-asset heavy. A large share of the distribution is compiled JavaScript, CSS, theme media and demonstration assets rather than business logic.

---

# 3. Runtime baseline

## Backend

| Component | Version or role |
|---|---|
| PHP | `^8.2` |
| Laravel | `^10.0` |
| Laravel Cashier | `14.14.0` |
| Laravel Octane | `^2.8` |
| Laravel Passport | `^12.3` |
| Laravel Sanctum | `^3.2` |
| Laravel Socialite | `^5.6` |
| Livewire | `^3.5` |
| Spatie Permission | `^6.9` |
| Sentry Laravel | `^4.9` |
| L5 Swagger | `^8.5` |
| AWS SDK | `^3.275` |

## Frontend

| Component | Version or role |
|---|---|
| Vite | `^7.1.3` |
| React | `^19.2.5` |
| Tiptap | `^3.2.0` |
| XYFlow React | `^12.10.2` |
| Zustand | `^5.0.12` |
| Tailwind CSS | `^3.4.15` |
| Laravel Echo | `^1.16.1` |
| Pusher JS | `^8.4.0-rc2` |

## Immediate WorkCore compatibility issue

```text
WorkCore standalone shell: Laravel 12
MagicAI host:             Laravel 10
```

WorkCore cannot simply be copied into this host. Its domain code must be validated and adapted for Laravel 10, or isolated behind a compatibility layer.

---

# 4. Application architecture

## Application PHP distribution

| Area | Files |
|---|---:|
| Domains | 364 |
| HTTP | 171 |
| Models | 100 |
| Local API packages | 89 |
| Services | 77 |
| View components | 68 |
| Enums | 28 |
| Helpers | 18 |
| Console | 17 |
| Events | 14 |
| Listeners | 13 |
| Providers | 11 |
| Livewire | 11 |
| Jobs | 11 |
| Observers | 9 |
| Mail | 7 |
| Actions | 5 |
| Concerns | 4 |
| Extensions | 0 |

## Core domains

### Engine

The Engine domain resolves AI providers and contains concrete drivers for OpenAI, Anthropic, Gemini, DeepSeek, XAI, OpenRouter, Together, Stable Diffusion, Fal AI, PiAPI, ElevenLabs, Google, Azure, Perplexity, Speechify and multiple specialised media providers.

### Entity

The Entity domain is MagicAI's model and capability catalogue. It handles model-specific drivers, inputs, pricing and credit calculations. It contains 248 driver files across 16 provider families.

### Marketplace

The Marketplace domain handles extension registration, installation, uninstallation, licensing, repositories and conditional provider loading. This is the primary integration boundary for WorkCore.

---

# 5. AI provider and capability inventory

Confirmed provider families include:

```text
OpenAI
Anthropic
Gemini
DeepSeek
XAI
OpenRouter
Together
Stable Diffusion
Fal AI
PiAPI
ElevenLabs
Google
Azure
Speechify
Serper
Perplexity
Clipdrop
Novita
Freepik
PlagiarismCheck
Synthesia
HeyGen
Pebblely
Gamma AI
Creatify
Topview
Vizard
Klap
Minimax
Unsplash
Pexels
Pixabay
Captions
```

The entity catalogue represents:

- Text generation
- Reasoning
- Search-enabled models
- Deep research models
- Embeddings
- Realtime chat and voice
- Speech-to-text
- Text-to-speech
- Image generation
- Image editing
- Text-to-video
- Image-to-video
- Reference-to-video
- Video upscaling
- Dubbing and captions
- AI music
- Avatar video
- Product photography
- Stock-media search
- Plagiarism checking
- Presentation generation

An entity entry proves that MagicAI understands a model. It does **not** prove that the corresponding premium extension or UI is installed.

## WorkCore use

WorkCore should reuse MagicAI's Engine and Entity domains for provider selection, BYO keys, model routing, cost calculation and shared-credit charging rather than creating a second AI catalogue.

---

# 6. Confirmed native capabilities

## Identity and accounts

- Registration and login
- Password reset
- Email verification
- OTP verification
- Google 2FA
- Google, GitHub, Twitter and Facebook login
- Apple login through API
- Profile API
- Account deletion requests
- Passport API authentication
- Session authentication

## Teams and SaaS

- Subscription and prepaid plans
- Free and private plans
- Team plans
- Team managers and members
- Invitations
- Shared team credits
- Plan features and limits
- Model and tool access by plan
- Usage records
- Orders
- Subscription status checks

## Shared credits

- Shared balance
- Shared-credit transaction history
- Entity/model costs
- Cost preview
- User and team pools
- Admin adjustments
- Low-balance notifications
- Migration tools and technical documentation

## Payment and platform finance

Native services exist for:

```text
Stripe
PayPal
Paddle
Razorpay
Paystack
Iyzico
Coingate
Coinbase
YooKassa
2Checkout
RevenueCat
Bank transfer
Free checkout
```

The platform also has coupons, taxes, token packs, gateway products, payment proof, affiliate referrals and withdrawals.

These are MagicAI SaaS-billing capabilities, not replacements for WorkCore job costing or operational accounting.

## AI Writer and content

- Generator templates
- Custom generators
- Streamed text output
- Workbooks
- User documents
- Favourite generators
- Prompt records
- Article Wizard
- Rewriter
- Brand Voice
- Company and product context
- AI Editor
- PDF parsing and export foundations

## AI Chat

- Conversations and messages
- Chat categories and templates
- New chat, rename, delete and clear
- Recent-chat and history search
- Document chat
- Chat image output
- Streaming
- PDF export
- Model selection
- Public chatbot output handling
- CRM Assistant host hooks when CRM is installed

## Realtime chat

The API contains a separate realtime surface with WebSocket credentials, realtime conversations, messages, history and conversation saving. The full premium realtime experience may still depend on an optional extension.

## Images, voice and video

Core or host-level foundations include:

- AI image API
- Image generation status
- Recent images
- Legacy image generator
- Uploads
- Advanced Image controller foundations
- Video Studio output hub
- UGC Studio output hub
- AI Influencer output records
- Voice records
- ElevenLabs voice creation job
- Realtime audio recorder/player frontend components

Most advanced media products remain optional extensions.

## Documents and files

- Document CRUD API
- Recent documents
- Generated images
- PDF extraction
- Word and spreadsheet libraries
- PDF generation
- Local and S3-compatible storage
- Storage administration

## Public chatbot transport

The core Chatbot service provider registers asset, token, chat, embed and session endpoints with CORS and API throttling. The complete chatbot-management product remains optional.

## Support and notifications

- Support tickets and messages
- User and admin support surfaces
- In-app notifications
- Low-balance notifications
- Confirmation, password-reset, invitation and payment emails

## Administration and CMS

- Admin dashboard and widgets
- User management
- Platform roles and permissions
- Plans and gateways
- Shared-credit administration
- Themes and marketplace
- Site health and Sentry
- Update and license systems
- SMTP, storage, GDPR and branding settings
- Frontend page management
- Blog, pages, FAQs and testimonials
- Advertisements and AdSense
- Email templates
- Announcements
- Translation, localisation and currencies

## DeFi subsystem

A separate native DeFi area provides market data, news, contract analysis, portfolio, settings and Synapse Bridge. It is unrelated to current WorkCore requirements and should remain isolated.

---

# 7. Routes and API

| Route file | Declarations |
|---|---:|
| `api.php` | 100 |
| `auth.php` | 22 |
| `panel.php` | 432 |
| `web.php` | 35 |
| `webhooks.php` | 6 |
| **Total in route files** | **595** |

Additional native routes include seven Chatbot provider routes, ten DeFi routes and two Marketplace install/uninstall routes, producing at least 614 statically discoverable native routes.

The API groups cover authentication, profile, app settings, usage, shared credits, search, AI Chat, realtime chat, AI Writer, AI images, payments, affiliates, support, documents, extensions, Brand Voice, voiceover and entity information.

## API documentation warning

The bundled OpenAPI document contains only 61 paths and no component schemas. The current API route file declares 100 routes. Newer areas such as shared credits and realtime additions are not fully documented.

The generated API document is useful but not authoritative.

---

# 8. Data model and tenancy

The 100 core models cover users, teams, plans, subscriptions, orders, gateways, shared credits, usage, generators, chats, chatbots, documents, companies, products, support, pages, blogs, frontend content, menus, settings, extensions, notifications, voices and affiliates.

## Core Company limitation

MagicAI's Company record contains brand-oriented fields such as name, industry, description, website, tagline, logo, brand colour, AI instructions and `user_id`.

It is not a full operational tenant with memberships, branches, worker identity, company roles, territories, active-company switching or governed record isolation.

## Core roles

```text
user
admin
super_admin
```

The core permissions are primarily platform-administration permissions. They do not cover operational roles such as dispatcher, supervisor, cleaner, auditor, technician or inventory manager.

## CRM absence confirmed by migrations

The 399 core migrations contain no CRM schema for contacts, deals, pipelines, CRM projects, CRM tasks, proposals, estimates, CRM invoices or CRM payments.

The paid CRM implementation and schema are not in this package.

---

# 9. Views and frontend

MagicAI contains 610 Blade views, including 372 panel views and 116 reusable components. The panel contains 206 admin views and 126 user views.

Major user-view areas include finance, generators, AI Chat, teams, settings, UGC Studio, Video Studio, orders, integrations, dashboard, affiliates, Article Wizard, companies, payments, voice and API keys.

## Theme-source mismatch

Only the default theme source exists in `resources/views/default` and `public/themes/default`.

The compiled Vite manifest references many other theme builds, including Bolt, Classic, Copyrocket, Creative, Dark, Modern, Neura, Oupi, Sleek, Magic Works and social-media themes. Those source themes are absent from this ZIP.

## Compiled optional-extension residue

The build manifest includes compiled assets for absent source paths such as:

- Chatbot external styles
- Chatbot Voice styles
- Creative Suite Annotations
- Social Media Automation builder
- UGC Creator
- UGC Factory

Compiled assets do not provide missing PHP providers, routes, migrations or models. A future frontend rebuild may remove them unless the actual extension sources are installed.

---

# 10. Marketplace extension inventory

MagicAI registers optional providers through:

```php
MarketplaceServiceProvider::$extensionProviders
```

Each provider is loaded only when its class exists.

| Measure | Result |
|---|---:|
| Provider registrations | 114 |
| Provider implementations present | 0 |
| PHP files under `app/Extensions/` | 0 |

## Registered families

### AI agents and tools

`ai-agent`, WhatsApp and Slack channels, CRM tool, Chatbot tool, Social Media tool, Marketing Bot tool, Gmail and Outlook.

### Advanced AI Chat

`ai-chat-pro`, Skills, Deep Research, File Chat, Image Chat, Folders, Memory, Smart Image, Entity Highlight, Highlight to Ask, Gmail, Outlook, Notion, Google Drive, Google Calendar, Multi Model, Model Council, Focus Mode, temporary chat and chat sharing.

### Chatbots and channels

Chatbot, booking, ecommerce, customer tags, reviews, human agent, voice, voice calls, phone agent, Telegram, WhatsApp, Messenger, Instagram, Web Chat and ElevenLabs Voice Chat.

### Creative media

Photo Studio, Product Shot, AI Avatar, AI Persona, AI Music, AI Music Pro, AI Video Pro, video-to-video, Advanced Image, AI Image Pro, Midjourney, Ideogram, Flux Pro, Nano Banana, SeeDream, Creative Suite, annotations, AI templates, AI Photoshoot, Fashion Studio, UGC Factory, UGC Creator, Video Editor, dubbing and captions.

### Marketing and publishing

SEO Tool, AI Social Media, Social Media Agent, Social Media Automation, BlogPilot, Marketing Bot, Content Manager, Newsletter, Mailchimp, WordPress and HubSpot.

### Infrastructure and commercial integrations

Cloudflare R2, Azure OpenAI, Azure TTS, OpenRouter, Xero, Cryptomus, checkout registration, discounts, affiliates, migration and maintenance.

### CRM

`crm`

## WorkCore target

```php
'workcore' => WorkCoreServiceProvider::class,
'ai-agent-tool-workcore' => AIAgentToolWorkCoreServiceProvider::class,
```

MagicAI should see one installable WorkCore extension while WorkCore internally retains its modular domains.

---

# 11. Menus and plan entitlements

`MenuService` contains 221 static definitions and supports database ordering, nesting, activation, custom entries, active-route conditions, plan-aware caching, optional per-user caching, theme-aware output, badges and conditional extension visibility.

There are 74 top-level menu candidates, but many become visible only when an extension route or provider exists.

The menu system is sufficient for WorkCore to add:

```text
Operations
Workforce
Resources
Commercial
```

along with user-specific navigation for managers, office staff and field workers.

---

# 12. Jobs, events and commands

## Jobs

- Cancel awaiting-payment subscriptions
- Process gateway customers
- Process Razorpay customers
- Process Stripe customers
- Send confirmation email
- Send email
- Send invitation
- Send password-reset email
- Send payment confirmation
- Send team invitation
- Create ElevenLabs voice

## Events

- `ContactCapturedEvent`
- Payment and lifetime events for Stripe, PayPal, Paystack and Iyzico
- Bank transfer
- Free payment
- 2Checkout webhook
- YooKassa webhook
- User activity

`ContactCapturedEvent` is the most relevant existing bridge point for CRM and WorkCore.

## Extension development commands

MagicAI includes Artisan generators for extension controllers, models and migrations. This confirms that substantial extension-owned application code is an intended architecture.

---

# 13. Quality and package-hygiene findings

## PHP syntax

1,551 first-party files passed syntax checking with no failures.

## Tests and CI

A `phpunit.xml` exists, but there is no `tests/` directory, no test files and no CI workflow. Behavioural correctness is therefore unproven by this distribution.

WorkCore integration must add its own compatibility, tenancy, permission, installation and upgrade regression tests.

## Incomplete implementation markers

Six explicit TODO markers exist in first-party PHP, including deferred or unimplemented behaviour in Paddle subscription checkout/trial handling and renewal logic for several gateways.

## Runtime artefacts bundled in source ZIP

The archive contains:

- A populated `.env`
- A generated application key
- A framework session file
- An extension license file
- A Livewire temporary uploaded image
- Generated caches and API documentation

Values were not exposed during the scan.

Before using this as a clean integration baseline:

1. Remove sessions and temporary files.
2. Remove license artefacts from source archives.
3. Replace `.env` with `.env.example`.
4. Generate a unique application key per installation.
5. Clear caches.
6. Verify that secrets never enter the WorkCore repository.

---

# 14. WorkCore integration authority

## Reuse MagicAI for

- Authentication
- User accounts
- SaaS plans and subscription billing
- Team seats
- AI engines and model entities
- Shared-credit accounting
- BYO API keys
- Main UI shell
- Dynamic menus
- Plan entitlements
- Marketplace installation
- Themes and components
- AI Chat transport
- AI Agent host when installed
- Public chatbot transport
- Notifications and email
- File storage
- Queue infrastructure
- API authentication
- External integrations

## Keep WorkCore authoritative for

- Operational companies and memberships
- Operational roles and permissions
- Branches and territories
- Customers as service recipients
- Properties and service sites
- Jobs and work orders
- Recurring visits
- Scheduling and dispatch
- Workforce, rosters and attendance
- Forms and checklists
- Inspections and compliance
- Incidents and corrective actions
- Assets, fleet and inventory
- Procurement
- Job costing
- Operational evidence
- Offline synchronisation
- Governed actions and approvals
- Idempotency
- Audit and Rewind

## Do not confuse

| MagicAI concept | Not equivalent to |
|---|---|
| Core Company | Operational business tenant |
| Team | WorkCore company membership |
| CRM Projects | Work orders and recurring visits |
| CRM Tasks | Field execution tasks |
| SaaS invoices | Operational job billing authority |
| Chat history | Operational audit trail |
| User activity | Titan Rewind |
| Generic file upload | Immutable field evidence |
| AI tool entitlement | Permission to perform operational actions |

---

# 15. Integration risk register

| Risk | Assessment | Required response |
|---|---|---|
| Laravel 10/12 mismatch | High | Compatibility pass before integration |
| Optional registry entries mistaken for installed features | High | Require source-package evidence |
| No tests | High | Build WorkCore host regression suite |
| No CI | High | Add automated integration checks |
| Runtime residue and secrets in ZIP | High | Sanitize and rotate |
| Operational tenancy absent | High | Preserve WorkCore tenancy |
| Compiled assets without source | Medium | Rebuild from installed sources |
| API documentation incomplete | Medium | Generate authoritative route index |
| Payment TODOs | Medium | Audit only selected gateways |
| Large core route and menu surface | Medium | Keep WorkCore routes and menus extension-owned |
| Vendor update conflicts | High | Target zero-core-patch integration |

---

# 16. Decisions established by Scan 01

1. MagicAI 11 is the host and WorkCore is the operational extension.
2. Registry entries are not counted as installed features.
3. The CRM cannot be fully mapped until its separate extension ZIP is supplied.
4. WorkCore retains company, membership and operational governance.
5. WorkCore should reuse MagicAI AI engines, entities, subscriptions, credits, menus and marketplace.
6. WorkCore must be made compatible with Laravel 10 for this host version.
7. Integration code should remain extension-owned.
8. The integration project must provide tests and CI.
9. The supplied ZIP must be sanitized before becoming a clean baseline.
10. Later scans will refine the authority and integration decision matrices.

---

# 17. Next scan

```text
02-magicai-bootstrap-and-runtime-architecture.md
```

The next scan should trace:

- Laravel bootstrap
- Provider loading order
- Container bindings
- Route boot order
- Middleware stack
- Exception handling
- Configuration loading
- Console boot
- Marketplace provider registration
- WorkCore provider insertion points
- Services WorkCore may safely decorate
- Upgrade-safe versus core-patch options

---

# Evidence limitations

This inventory does not assume that:

- A marketplace provider registration means the extension is installed.
- A compiled JavaScript file proves backend functionality.
- A menu item proves its route exists.
- A model enum proves provider credentials or successful execution.
- A controller proves a complete workflow.
- An API route proves correct authorisation or tenant isolation.
- A migration proves production data safety.

Those questions are reserved for the focused scans that follow.
