# Creative, Image, Audio & Video AI

Functions and routes are statically detected. Cross-extension relationships are integration signals unless verified by manifests and boot tests. Provider references indicate code paths, not production certification. See [Documentation Audit](../DOCUMENTATION-AUDIT.md).

## Advanced Image (`AdvancedImage`) — v2.14

Multi-provider image-transformation engine covering prompt extraction, sketch-to-image, background removal/replacement, cleanup, text removal, inpainting, reimagining, style transfer, relighting, and asynchronous status.

- **Shape:** 47 files; 8 controllers; 10 services; 2 migrations; 13 detected routes.
- **Functions:** `generate`, `sketch_to_image`, `remove_background`, `cleanup`, `remove_text`, `inpainting`, `replace_background`, `reimagine`, `style_transfer`, `image_relight`, `responseArray`, `checkStatus`.
- **Providers:** Freepik, Novita, Clipdrop, Fal, and Google-hosted asset references.
- **Detected integrations:** Product Photography, AI Video Pro, Nano Banana, SeeDream V4, and other image-provider extensions. These are not all proven hard prerequisites.
- **Review:** normalise provider jobs, validate remote downloads, isolate tenant assets, cap image dimensions, preserve provenance, and test provider-specific failure/retry paths.

## AI Avatar (`AiAvatar`) — v2.5

Synthesia-backed avatar/video creation with avatar/background discovery, video list/retrieval/delete, generation, and status checks.

- **Shape:** 16 files; 2 controllers; 2 services; 1 model; 1 migration; 4 detected routes.
- **Functions:** `createVideo`, `listVideos`, `retrieveVideo`, `deleteVideo`, `listAvatars`, `listBackgrounds`, `checkVideoStatus`, and CRUD.
- **Table:** `user_synthesia`.
- **Provider:** Synthesia.
- **Review:** add job idempotency, polling limits, remote-file validation, consent/likeness policy, deletion guarantees, and usage accounting.

## AI Captions (`AiCaptions`) — v1.2

Adds stylised animated captions to uploaded/generated videos through the Captions API, with template selection, dimensions, submission, polling, and content URL resolution.

- **Shape:** 27 files; 2 controllers; 2 services; 1 model; 2 migrations; 9 detected routes.
- **Functions:** `fromPath`, `dimensionsFromPath`, `hasApiKey`, `submit`, `poll`, `templates`, `resolveContentUrl`, `store`, `checkStatus`.
- **Table:** `ai_captions_videos`.
- **Detected integration:** VideoEditor.
- **Review:** validate remote content URLs, media duration/size, tenant ownership, caption timing, polling recovery, and storage cleanup.

## AI Image Pro (`AIImagePro`) — v1.8

Large multi-model image-generation studio with model registry, model selection, prompt enhancement, parallel dispatch, tool categories, galleries, likes, history, provider settings, and integrations into chat, creative, social, and advanced-image systems.

- **Shape:** 151 files; 2 controllers; 2 services; 2 models; 7 migrations; 29 detected routes.
- **Functions:** `generate`, `getAllAvailableModels`, `getValidationRulesFor`, `getSelectedModelSlugs`, `getActiveImageModels`, `getDefaultSelectedModels`, `getModelsForTagInput`, `dispatchImageGenerationJob`, `enhancePrompt`, `getToolsConfiguration`, `getToolsByCategory`, `getToolById`.
- **Tables:** `ai_image_pro`, `ai_image_pro_likes` plus detected model/settings schema extensions.
- **Detected integrations:** AdvancedImage, AIChatPro image chat, CreativeSuite, ContentManager, FluxPro, NanoBanana, SeeDream V4, and SocialMedia.
- **Provider:** Together AI plus registered image-provider extensions.
- **Review:** use the shared provider registry, normalised model capabilities, queue budgets, deterministic job ownership, asset provenance, content policy, and provider-level retries.

## AI Music (`AiMusic`) — v1.5

Song generation and user music library.

- **Shape:** 12 files; 1 controller; 1 service; 1 model; 1 migration; 2 detected routes.
- **Functions:** `generateSong`, `index`, `create`, `store`, `delete`.
- **Table:** `user_music`.
- **Provider:** AIML API.
- **Review:** add generation job state, licensing/provenance metadata, duration and file limits, retries, deletion policy, and cost accounting.

## AI Music Pro (`AiMusicPro`) — v1.7

Enhanced music generation, settings, history, and deletion.

- **Shape:** 16 files; 2 controllers; 1 service; 1 model; 3 migrations; 5 detected routes.
- **Functions:** `generate`, `index`, `update`, `delete`.
- **Table:** `ai_music_pro`.
- **Review:** provider identity and licensing rules require explicit documentation and tests; avoid parallel canonical music libraries without a migration plan.

## AI Persona (`AiPersona`) — v2.9

HeyGen avatar/persona creation with user avatars, script enhancement, asset upload, video generation, status polling, bulk deletion, and persona libraries.

- **Shape:** 24 files; 2 controllers; 2 services; 2 models; 6 migrations; 11 detected routes.
- **Functions:** `uploadAsset`, `enhanceScript`, `storeAvatar`, `listUserAvatars`, `checkAvatars`, `deleteAvatar`, `checkVideoStatus`, and video CRUD.
- **Tables:** `ai_persona_avatars`, `user_heygen`.
- **Provider:** HeyGen.
- **Review:** likeness consent, uploaded-asset validation, tenant ownership, moderation, job idempotency, polling recovery, and deletion propagation require tests.

## AI Photoshoot (`AIPhotoshoot`) — v1.2

Product/photo studio with backgrounds, products, user settings, text/image generation, model registry, image editing, video generation, status polling, upload limits, and provider-specific payload resolution.

- **Shape:** 95 files; 13 controllers; 4 services; 3 models; 5 migrations; 32 detected routes.
- **Functions:** `generate`, `generateFromText`, `check`, `generateVideo`, `checkVideo`, `models`, `getDefaultModel`, `getProviderFor`, `supportsEdit`, upload-size and settings functions.
- **Tables:** `ai_photo_studio_backgrounds`, `ai_photo_studio_products`, `ai_photo_studio_user_settings`.
- **Detected integration:** AIChatPro image chat.
- **Providers:** Fal and OpenAI-assisted prompting.
- **Review:** consolidate model/provider discovery, media validation, object storage, provenance, quotas, async recovery, and tenant access.

## AI Presentation (`AiPresentation`) — v1.4

AI slide-deck generation with credit prediction, sufficiency checks, model categories, generation submission, status polling, update, deletion, and Gamma-hosted output.

- **Shape:** 28 files; 2 controllers; 2 services; 2 models; 3 migrations; 8 detected routes.
- **Functions:** `predictCredits`, `checkSufficientCredits`, `getEstimateMessage`, `getModelCategory`, `generatePresentation`, `getGenerationStatus`, `updatePresentationStatus`, `generate`, `checkStatus`, `delete`.
- **Table:** `ai_presentations`.
- **Provider:** Gamma.
- **Review:** credit estimates, polling, remote URLs, ownership, export retention, provider failures, and content provenance require tests.

## AI Realtime Image (`AIRealtimeImage`) — v1.14

Realtime image generation with success callback, storage download, settings, gallery, and deletion.

- **Shape:** 27 files; 2 controllers; 1 service; 1 model; 4 migrations; 3 detected routes.
- **Functions:** `generate`, `success`, `downloadImageToStorage`, `getApiKey`, `store`, `gallery`, `destroy`.
- **Table:** `ai_realtime_images`.
- **Provider:** Together AI.
- **Review:** callback authentication, remote-download validation, SSRF protection, idempotency, tenant/job resolution, and usage metering require verification.

## AI Video Pro (`AiVideoPro`) — v3.6

AI video/avatar generation with provider configuration, prompt enhancement, status polling, remote download, history, bulk deletion, and social-media handoff.

- **Shape:** 25 files; 1 controller; 2 services; 1 model; 6 migrations; 4 detected routes.
- **Functions:** `getConfig`, `generate`, `getStatus`, `getVideo`, `downloadFromUrl`, `bulkDelete`, `checkVideoStatus`, `enhancePrompt`.
- **Table:** `user_fall`.
- **Detected integration:** SocialMedia.
- **Providers:** OpenAI for prompt enhancement plus a configured video provider.
- **Manifest defect:** the source manifest calls this package `AI Avatar Pro`, while the folder and implementation are broader AI video-generation capability. Catalogue naming is capability-derived.
- **Review:** correct the manifest before marketplace publication and add provider identity, secure remote downloads, queue recovery, media limits, provenance, deletion, and budget tests.

## AI Video-to-Video (`AIVideoToVideo`) — v1.6

Video-transformation pipeline with generation requests, single/bulk status checks, and configurable OpenAI assistance.

- **Shape:** 17 files; 1 controller; 6 services; 2 migrations; 3 detected routes.
- **Functions:** `generate`, `request`, `checked`, `checkedAll`, `setOpenai`, `getOpenai`.
- **Provider:** Fal.
- **Review:** validate source videos and URLs, isolate jobs, cap media resources, make polling resumable, and account for provider usage.

## AI Viral Clips (`AiViralClips`) — v1.5

Long-video-to-short-clips workflow with clip generation, task polling, previews, export, and final-result storage.

- **Shape:** 21 files; 4 controllers; 14 detected routes.
- **Functions:** `generateShorts`, `checkTaskStatus`, `previewLists`, `exportClips`, `checkExportStatus`, `storeFinalVideoKlap`, `storeFinalVideoVizard`, `retrieveClips`.
- **Detected provider signals:** Klap and Vizard-related handlers; final provider mapping requires runtime verification.
- **Manifest defect:** the source manifest identifies this package as `Example` with an example description. The catalogue name is capability-derived.
- **Review:** correct the manifest and add source validation, duration/size limits, provider-job idempotency, polling recovery, export security, and storage lifecycle tests.

## AI Voice Isolator (`AIVoiceIsolator`) — v2.3

UI integration for isolating speech/voice from uploaded audio or video.

- **Shape:** 4 files; view integration only in the selected package.
- **Review:** the catalogue must not imply a complete local processing engine from this folder alone. Identify the host/provider operation it invokes and test file validation, limits, retention, and errors.

## AI Writer Templates (`AIWriterTemplates`) — v2.1

Adds AI-writer template records/migrations to the host generation system.

- **Shape:** 5 files; 1 migration.
- **Review:** this is primarily a schema/content extension, not a standalone generation engine. Migration ownership and uninstall behaviour require verification.

## Creative Suite (`CreativeSuite`) — v1.15

Editable visual-document workspace with uploads, document CRUD, duplication, naming, editor/status views, and persisted structured document state.

- **Shape:** 111 files; 5 controllers; 1 model; 1 migration; 11 detected routes.
- **Functions:** `__invoke`, `editor`, `status`, `updateOrCreate`, `show`, `duplicate`, `name`, `destroy`, `index`, `update`.
- **Table:** `ext_creative_suite_documents`.
- **Detected add-ons:** AI Template and Annotations. These extend CreativeSuite and are not proven prerequisites of the core package.
- **Review:** preserve structured document versioning, validate uploads/assets, isolate tenants, and formalise add-on capability discovery.

## Creative Suite AI Template (`CreativeSuiteAITemplate`) — v1.2

Generates editable structured templates rather than flat images, processes AI template JSON, tracks generation tasks, and launches placeholder-image generation.

- **Shape:** 10 files; 1 controller; 2 detected routes.
- **Functions:** `generateTemplate`, `templateStatus`, `processAiTemplateResponse`, `getTemplateSystemPrompt`, `submitPlaceholderImageGenerations`.
- **Required integration:** CreativeSuite document model/editor.
- **Review:** validate generated JSON against a versioned schema, cap node/asset counts, sanitise URLs/text, preserve provenance, and make placeholder jobs idempotent.

## Creative Suite Annotations (`CreativeSuiteAnnotations`) — v1.1

Region-aware visual analysis and editing with per-region prompts, masks, text replacement, prompt construction, source compositing, and asynchronous status.

- **Shape:** 17 files; 2 controllers; 2 services; 4 detected routes.
- **Functions:** `analyze`, `dispatch`, `supportsMask`, `buildCommentPrompt`, `buildReplaceTextPrompt`, `buildRegionPrompt`, `compositeBackToSource`, `computeMaskBounds`, `edit`, `status`.
- **Providers:** OpenAI, Anthropic, Gemini, and xAI vision/model endpoints.
- **Required integration:** CreativeSuite editor/document surface.
- **Review:** one of the strongest reusable engines. Generalise its coordinate-grounded analysis behind a visual-inspection contract, with strict image limits, mask validation, tenant storage, provider normalisation, and reproducible edit history.

## Fashion Studio (`FashionStudio`) — v1.7

Virtual-fashion/photoshoot system with model, wardrobe, pose, background, generation, edit, settings, gallery, and video workflows.

- **Shape:** 109 files; 14 controllers; 2 services; 17 models; 9 migrations; 46 detected routes.
- **Functions:** `models`, `getDefaultModel`, `getProviderFor`, `supportsEdit`, `getModelsForAdminSelect`, `isModelEnabled`, quality/size resolution, generation, status, assets, and CRUD across fashion entities.
- **Tables:** `background`, `fashion_model`, `wardrobe`, `pose`, `fashion_studio_user_settings` plus related pivots/settings.
- **Detected integration:** AI Video Pro.
- **Provider:** Fal.
- **Review:** biometric/likeness consent, asset ownership, upload security, model/provider normalisation, media limits, moderation, provenance, and deletion require tests.

## Influencer Avatar (`InfluencerAvatar`) — v1.6

Short influencer-avatar video generation with task status and final-result retrieval.

- **Shape:** 7 files; 1 controller; 4 detected routes.
- **Functions:** `generateShortVideo`, `checkStatus`, `getFinalVideo`.
- **Review:** provider identity, consent, input validation, job ownership, polling, remote download, and storage lifecycle require explicit documentation and tests.

## Product Photography (`ProductPhotography`) — v2.6

Product-background removal and themed background generation with saved results and settings.

- **Shape:** 14 files; 2 controllers; 2 services; 1 model; 2 migrations; 3 detected routes.
- **Functions:** `getThemes`, `removeBg`, `createBg`, `query`, and CRUD.
- **Table:** `pebblely`.
- **Provider:** Pebblely.
- **Review:** validate images, protect tenant assets, normalise provider errors, preserve original/generated provenance, and test deletion and quotas.

## UGC Creator (`UGCCreator`) — v1.2

User-generated-content studio with reusable assets, voices, reference images, multiple generation models, provider-specific payloads, audio support, video history, and VideoEditor handoff.

- **Shape:** 38 files; 5 controllers; 2 services; 2 models; 4 migrations; 12 detected routes.
- **Functions:** model registry/resolution, quality and upload limits, asset CRUD, voice listing, generation, polling, video retrieval, and editor export.
- **Tables:** `ugc_creator_assets`, `ugc_creator_videos`.
- **Detected integration:** VideoEditor.
- **Providers:** OpenAI, ElevenLabs, and Fal.
- **Review:** consent, voices/likeness rights, asset validation, provider normalisation, async recovery, provenance, moderation, and storage lifecycle require tests.

## UGC Factory (`UGCFactory`) — v1.1

Actor and UGC-ad factory with actor upload/generation, prompt templates, presets, voiceover selection, resolution, task submission, polling, and video persistence.

- **Shape:** 54 files; 5 controllers; 2 services; 2 models; 3 migrations; 14 detected routes.
- **Functions:** `submit`, `check`, upload limits, voiceover/provider resolution, actor image model/provider selection, prompt construction, preset discovery, actor CRUD, and video operations.
- **Tables:** `ugc_factory_actors`, `ugc_factory_videos`.
- **Providers:** OpenAI, ElevenLabs, and Fal.
- **Review:** same consent, actor/voice rights, validation, moderation, provider, budget, and retention controls as UGC Creator; consider a shared UGC engine rather than parallel authority.

## URL to Video (`UrlToVideo`) — v1.9

Converts product/site URLs into marketing-video projects with task submission, script listing/editing, captions, voices, avatars, ethnicities, templates, exports, and provider-specific result storage.

- **Shape:** 37 files; 5 controllers; 39 detected routes.
- **Functions:** `storeCreatifyFinalVideo`, `storeTopviewFinalVideo`, `marketingVideoSubmitTask`, `marketingVideoQueryTask`, `marketingVideoListScripts`, `marketingVideoUpdateScriptContent`, `marketingVideoExport`, `captionList`, `voiceQuery`, `aiAvatarQuery`, `ethnicityQuery`.
- **Detected provider signals:** Creatify and Topview.
- **Review:** URL ingestion requires SSRF protection, redirect/private-IP checks, content and crawl limits, provider-job idempotency, source rights, secure exports, and tenant storage.

## Video Dubbing (`VideoDubbing`) — v1.2

Translation and dubbing pipeline with uploaded-file storage, duration estimation, provider/language selection, pending jobs, submission, polling, and dubbed-file download.

- **Shape:** 25 files; 2 controllers; 3 services; 1 model; 2 migrations; 7 detected routes.
- **Functions:** `getDefaultProvider`, `storeUploadedFile`, `estimateUploadedDuration`, `createPendingDubbing`, `submitDubbing`, `checkStatus`, `getLanguagesForProvider`, `createDubbing`, `getDubbingStatus`, `downloadDubbedFile`, `createTranslation`, `getTranslationStatus`.
- **Table:** `video_dubbings`.
- **Providers:** HeyGen and ElevenLabs.
- **Review:** media validation, duration accounting, language support, consent, voice rights, async recovery, secure downloads, and storage retention require tests.

## Video Editor (`VideoEditor`) — v1.0

Timeline-based multi-track editor with media upload/import, metadata and thumbnail extraction, project/timeline persistence, AI-generation hooks, export planning, FFmpeg composition, export jobs, and download/status APIs.

- **Shape:** 39 files; 5 controllers; 3 services; 3 models; 6 migrations; 26 detected routes.
- **Functions:** `export`, `extractMetadata`, `generateThumbnail`, `plan`, `buildVisualClipCommand`, `buildAudioClipCommand`, `buildComposeCommand`, project/media CRUD, timeline updates, status, and download.
- **Tables:** `video_editor_projects`, `video_editor_media`, `video_editor_export_jobs`.
- **Detected integrations:** AI Music Pro and AI Video Pro.
- **Providers/tools:** OpenAI and ElevenLabs generation helpers; FFmpeg for server-side processing.
- **Security review:** five legitimate `proc_open`/`exec` matches require allow-listed binaries and paths, argument-array execution where possible, strict escaping, isolated workers, sandboxed temporary directories, time/memory/output limits, archive/media validation, and command-template injection tests.

> No bundled automated tests were detected in these 26 selected extension folders; tests may exist elsewhere upstream.
