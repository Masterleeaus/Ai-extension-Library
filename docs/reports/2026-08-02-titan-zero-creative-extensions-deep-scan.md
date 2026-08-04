# Titan Zero — Creative Extensions Deep Scan

## Executive summary

The `Creative.zip` archive contains **21 official extensions**:

- **13 image/design extensions**
- **6 video extensions**
- **2 music extensions**
- **728 files**
- **469 PHP files**
- Approximately **78,378 lines of PHP**
- All PHP files pass syntax validation
- No automated tests are included

The archive is commercially valuable, but the extensions should not be sold as 21 separate products. They overlap heavily. The strongest approach is to consolidate them into a small number of field-service-specific micro-apps and shared creative services.

## Strongest standalone micro-app candidates

| Priority | Proposed micro-app | Main donor extensions | Readiness |
|---:|---|---|---|
| 1 | **Titan Showcase** | AI Photoshoot, Product Shot, AI Image Pro, Advanced Image, Viral Clips, URL to Video, Video Editor | High |
| 2 | **Titan Inspect** | Creative Suite Annotations, Creative Suite, Advanced Image, AIChatPro Vision | Medium-high |
| 3 | **Titan Training Studio** | Video Editor, Video Dubbing, AI Presentation, AI Video Pro, AI Music Pro | High |
| 4 | **Titan Proposal Studio** | AI Presentation, Creative Suite, AI Template, Quote/Tender data | Medium-high |
| 5 | **Titan Local Ads** | URL to Video, AI Photoshoot, Product Shot, AI Image Pro, Video Editor | Medium-high |
| 6 | **Titan Dubbing** | Video Dubbing | High technically, narrower market |
| 7 | **Titan Clips** | AI Viral Clips, Video Editor | Medium |
| 8 | **Titan Brand Studio** | Creative Suite, AI Image Pro, Advanced Image | Medium, but generic |

## 1. Titan Showcase

### Product concept

A before-and-after marketing and social-proof app for field and home service businesses.

A worker completes a job, selects approved before/after photos, and Titan produces:

- Branded before-and-after images
- Image carousels
- Short vertical videos
- Reels and stories
- Website case studies
- Review graphics
- Customer-approved social posts
- Multi-language versions

### Donor extensions

- **AI Photoshoot**: product/background workflows, templates, image editing, async generation
- **AI Product Shot**: simple product-background generation
- **AI Image Pro**: templates, tools, media library, community/share mechanisms, prompt enhancement
- **Advanced Image**: background removal, cleanup, inpainting, relighting, reimagine, upscale
- **AI Viral Clips**: Klap and Vizard long-video-to-short workflows
- **URL to Video**: Creatify and TopView ad-video workflows
- **Video Editor**: timeline editing, AI media generation, voice/music and FFmpeg export
- **Creative Suite**: branded layouts and reusable templates

### Required modifications

- Replace retail-product templates with cleaning, plumbing, landscaping, painting, HVAC and electrical templates
- Pull media directly from WorkCore job records
- Add customer media consent and withdrawal
- Automatically blur faces, addresses, documents and number plates
- Prevent fabricated before/after transformations
- Preserve original evidence separately from edited marketing derivatives
- Add Google Business Profile, WordPress and social publishing approvals
- Generate factual captions from job scope and verified outcomes
- Add business brand kits, locations and franchise controls

### Recommended pricing

- Pro: **A$29/month**
- Business: **A$79/month**
- Managed media-generation usage billed separately or through Titan Zero AI/media credits

## 2. Titan Inspect

### Product concept

A visual inspection, markup, punch-list and field-report app.

This is not yet implemented as a finished inspection system, but the archive contains a strong technical foundation.

### Key existing capability

`CreativeSuiteAnnotationsVisionService` already supports:

- OpenAI vision
- Anthropic vision
- Gemini vision
- xAI vision
- Image downscaling before inference
- Normalised 0–1000 image coordinates
- Structured bounding boxes
- Pins and region overlays
- Rectangle, oval, lasso and brush masks
- Targeted region instructions
- Asynchronous edit jobs

Its current visual analysis is designed to locate text blocks. The schema can be changed to return inspection findings instead.

### Proposed finding schema

```json
{
  "findings": [
    {
      "type": "possible_water_stain",
      "label": "Possible moisture staining",
      "x": 120,
      "y": 210,
      "width": 260,
      "height": 180,
      "confidence": 0.78,
      "severity": "review",
      "reason": "Irregular dark discolouration near ceiling edge",
      "next_capture": "Take a wider photo and a close-up from the left",
      "requires_human_confirmation": true
    }
  ]
}
```

### Titan uses

- Cleaning quality checks
- Property-condition reports
- Pre-existing damage capture
- Defect and punch-list reports
- Quote scope inspections
- Safety observations
- Asset-condition checks
- Supervisor approval
- Customer sign-off

### Required modifications

- Never alter evidence originals
- Store annotations as non-destructive overlays
- Add inspection profiles by vertical and job type
- Add before/after comparison
- Add required-angle and missing-photo checks
- Add confidence and human-confirmation status
- Add job, worker, property and customer context
- Add signed customer acknowledgements
- Add evidence hashes and immutable audit history
- Add local/BYO vision routing
- Route findings into Evidence, Quote, SafeWork, Asset or Field Expert

### Recommended pricing

- Pro: **A$39/month**
- Business: **A$99/month**

## 3. Titan Training Studio

### Product concept

Turn approved SOPs, manuals and regulatory guidance into branded, multilingual worker training.

### Donor extensions

- **Video Editor**: multi-track timeline, projects, media, AI video, voice, music and server-side FFmpeg export
- **Video Dubbing**: ElevenLabs and HeyGen translation/dubbing, queue jobs, status polling and local output download
- **AI Presentation**: Gamma generation, PDF/PPTX export, gallery and status handling
- **AI Video Pro**: Sora and other video-model configuration
- **AI Video to Video**: style conversion and upscaling
- **AI Music Pro**: Lyria/ElevenLabs music generation

### Outputs

- SOP training videos
- Toolbox talks
- Induction modules
- Equipment explainers
- Customer instruction videos
- Regulatory-change briefings
- Multi-language training
- Captioned and dubbed versions
- Presentation and PDF handouts
- Knowledge checks and acknowledgements

### Required modifications

- Generate only from approved source material
- Link every module to the SOP/source version
- Automatically invalidate or flag training when the source changes
- Add role and competency targeting
- Add quizzes, completion, acknowledgement and refresher schedules
- Add accessibility captions and transcripts
- Add local/private media storage and retention controls
- Add manager approval before publication
- Integrate Sentinel, SafeWork, Academy and Onboarding Pro

### Recommended pricing

- Pro: **A$49/month**
- Business: **A$129/month**

## 4. Titan Proposal Studio

### Product concept

Generate customer proposals, capability statements, tender presentations and completion reports from verified Titan records.

### Donor extensions

- **AI Presentation**: Gamma generation, PDF/PPTX output and stored generation history
- **Creative Suite**: visual document editor and templates
- **Creative Suite AI Template**: AI-created layouts
- **Creative Suite Annotations**: targeted edits and text replacement
- **AI Image Pro**: branded supporting imagery
- **Titan Quote/Tender/Evidence**: source business content

### Outputs

- Good-Better-Best visual proposals
- Tender presentation decks
- Capability statements
- Property-condition reports
- Project completion reports
- Maintenance plans
- Customer education packs
- Environmental performance reports

### Required modifications

- Populate documents from verified WorkCore data
- Lock pricing and legal clauses to the owning application
- Add reusable business templates and brand governance
- Add versioning, approval and e-signature
- Add source citations and evidence attachments
- Remove fabricated claims and unsupported statistics
- Support customer portal delivery

### Recommended pricing

- Pro: **A$39/month**
- Business: **A$99/month**

## 5. Titan Local Ads

### Product concept

Convert a business website, service page, offer or completed job into a local-service advertisement.

### Donor extensions

- **URL to Video**: Creatify and TopView workflows, scripts, avatars, voices, music and video rendering
- **AI Photoshoot/Product Shot**: service/product imagery
- **AI Image Pro**: social ad templates
- **Video Editor**: final customisation and export
- **AI Viral Clips**: alternative short-video formats

### Required modifications

- Ingest Titan service catalogue, service areas, reviews and brand kit
- Generate local-service rather than ecommerce ads
- Add substantiation checks for claims
- Add media consent and privacy redaction
- Add location, offer and platform variants
- Add ad-library history and campaign attribution
- Add call tracking and lead source tracking
- Require approval before publishing

### Recommended pricing

- Pro: **A$39/month**
- Business: **A$99/month**

## 6. Titan Dubbing

### Product concept

Translate and dub training, customer and marketing videos into multiple languages.

The existing extension is one of the cleaner isolated products in the archive. It has:

- Uploaded source files
- ElevenLabs and HeyGen providers
- Language lists
- Background jobs
- Status refresh
- Download of completed outputs
- User-owned records
- Bulk deletion

### Best positioning

It is commercially viable as a standalone micro-app, but it is even stronger as part of Titan Training Studio.

### Recommended pricing

- Pro: **A$29/month**
- Business: **A$79/month**
- Provider usage passed through separately

## 7. Titan Clips

### Product concept

Turn long training videos, job walkthroughs, testimonials and webinars into short social clips.

### Existing capability

- Klap and Vizard integrations
- Uploaded files or source video URLs
- Target clip count and duration
- Captions, intro titles and emojis
- Preview lists
- Export and status workflows

### Required modifications

- Add consent and privacy screening
- Prefer job/customer-approved source videos
- Add brand templates and factual caption controls
- Add scheduling and publishing
- Store provider task ownership explicitly
- Add a local fallback through the Video Editor where possible

### Recommendation

Sell as a **Showcase Business** feature first. Split it into a standalone app only if usage proves strong.

## 8. Titan Brand Studio

### Product concept

A Canva-like brand and design workspace for small service businesses.

### Donor extensions

- Creative Suite
- Creative Suite AI Template
- AI Image Pro
- Advanced Image
- Realtime Image
- Flux, Nano Banana, Seedream and Midjourney adapters

### Existing capabilities

- Documents and previews
- Templates
- Text, image and layout editing
- AI image editing
- Layers, resize, alignment and typography
- Image uploads
- AI-created templates
- Image generation and public/private sharing

### Recommendation

Technically viable, but too generic to lead Titan’s premium strategy. Use it as the shared editor behind Showcase, Proposal Studio and Training Studio.

## Extensions best absorbed into existing Titan apps

| Extension | Best Titan destination |
|---|---|
| Advanced Image | Shared Titan Vision/media utility |
| Creative Suite Annotations | Titan Inspect, Evidence and visual helpers |
| AI Presentation | Titan Tender, Quote and Proposal Studio |
| AI Product Shot | Titan Showcase and Asset supplier catalogues |
| AI Realtime Image | Showcase/Brand Studio prompt ideation |
| AI Video Pro | Training Studio and Showcase |
| AI Video to Video | Training Studio and Showcase |
| AI Viral Clips | Showcase Business |
| AI Music / AI Music Pro | Training Studio and Showcase soundtrack generation |

## Provider adapters — not standalone products

These should remain model/provider choices inside the shared creative engine:

- Flux Pro
- Nano Banana
- Seedream v4
- Midjourney/PiAPI
- ClipDrop
- Freepik
- Novita
- Pebblely
- Together
- FAL
- Sora
- Kling
- Veo
- Luma
- Creatify
- TopView
- Klap
- Vizard
- Gamma
- ElevenLabs
- HeyGen
- Lyria

Customers should choose:

- Local/device model where supported
- BYO provider key
- Titan Zero AI/media allowance

## Extension-by-extension assessment

| Extension | What it actually provides | Standalone verdict |
|---|---|---|
| AI Music | Basic AIMLAPI music generation and saved songs | Merge into Training/Showcase |
| AI Music Pro | Lyria/ElevenLabs music providers, lyrics and history | Module, not core standalone |
| Advanced Image | Broad image editing toolbox across several providers | Shared utility |
| AI Image Pro | Image generation, templates, community, sharing, likes, media library and realtime mode | Merge into Brand/Showcase |
| AI Photoshoot | Products, backgrounds, templates, custom scenes, editing, video and async jobs | Strong Showcase foundation |
| AI Presentation | Gamma presentations with PPTX/PDF storage and gallery | Strong Proposal foundation |
| AI Product Shot | Pebblely background removal and scene generation | Merge into Showcase |
| AI Realtime Image | Together prompt-to-image generation and gallery | Small module |
| Creative Suite | Design editor, documents, previews, templates and AI edits | Shared editor or Brand Studio |
| Creative Suite AI Template | AI-generated Creative Suite templates | Shared editor module |
| Creative Suite Annotations | Vision OCR boxes, annotation tools, masks and targeted AI edits | Strong Inspect foundation |
| Flux Pro | FAL model tabs/webhook | Provider adapter |
| Midjourney | PiAPI settings, status and webhook | Provider adapter |
| Nano Banana | FAL model tabs/webhook | Provider adapter |
| Seedream v4 | FAL model tab/webhook | Provider adapter |
| AI Video Pro | Multi-model video generation, status and local download | Training/Showcase engine |
| Video to Video | AnimateDiff/CogVideo/FAL transformations and upscaling | Video Studio module |
| AI Viral Clips | Klap/Vizard long-video-to-shorts | Showcase feature or Clips |
| URL to Video | Creatify/TopView product and avatar ad workflows | Strong Local Ads foundation |
| Video Dubbing | ElevenLabs/HeyGen multilingual dubbing pipeline | Standalone or Training module |
| Video Editor | Projects, media, timeline, AI generation, voice/music and FFmpeg export | Strongest shared video foundation |

## Production and privacy issues that remain

Because these are official extensions, coupling to the base platform is expected. The following still needs correction for Titan Zero:

1. **No tests** across the archive.
2. **Global API settings** must become tenant/user/device-scoped BYO credentials.
3. **Cloud-first media processing** needs clear consent, provider routing and local preprocessing.
4. **Destructive GET routes** exist in music, product-shot, dubbing and some export flows.
5. **Creative Suite ownership checks are incomplete**: several document operations use raw IDs/model binding without constraining records to the authenticated user.
6. **Admin route protection is inconsistent** in some extensions.
7. **Original evidence and edited media must be separated**. Marketing and AI edits must never overwrite evidence originals.
8. **Remote URL downloads need SSRF and size protections**.
9. **Provider output downloads need MIME, size and domain validation**.
10. **Long-running generation requires queue monitoring, cancellation, retry and idempotency**.
11. **Media consent, copyright, customer property privacy and retention controls are absent**.
12. **Usage costs vary heavily**, especially video. Provider fees should be passed through or metered.

## Recommended consolidation architecture

Create shared Titan services:

- `TitanCreativeGateway`
- `TitanVisionAnalysis`
- `TitanAnnotationEngine`
- `TitanMediaVault`
- `TitanBrandKit`
- `TitanTemplateEngine`
- `TitanVideoTimeline`
- `TitanMediaExport`
- `TitanDubbing`
- `TitanProviderRouter`
- `TitanMediaConsent`
- `TitanContentApproval`

Then expose those capabilities through focused apps rather than duplicating provider integrations.

## Recommended build order

1. **Titan Showcase**
2. **Titan Inspect**
3. **Titan Training Studio**
4. **Titan Proposal Studio**
5. **Titan Local Ads**
6. Dubbing and Clips as optional standalone products only after usage validation

## Final conclusion

The archive contains enough code to create several lucrative products, but its greatest value comes from consolidation.

The best commercial additions are:

- **Titan Showcase** — turns completed work into marketing and reviews
- **Titan Inspect** — turns job photos into structured findings and reports
- **Titan Training Studio** — turns SOPs and guides into multilingual training
- **Titan Proposal Studio** — turns verified Titan data into proposals and tender decks
- **Titan Local Ads** — turns services, websites and job results into advertisements

The provider-specific image and video extensions should remain hidden infrastructure behind those products.
