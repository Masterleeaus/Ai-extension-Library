# Creative, Image, Audio & Video AI

Functions are detected public executable methods; dynamically registered behaviour may add more.

## Advanced Image (`AdvancedImage`) — v2.14

Multi-provider image transformation engine covering prompt extraction, sketch-to-image, background removal/replacement, cleanup, text removal, inpainting, reimagining, style transfer, relighting, and asynchronous task status.

- **Shape:** 47 files; 8 controllers; 10 services; 2 migrations; 13 routes.
- **Functions:** `generate`, `sketch_to_image`, `remove_background`, `cleanup`, `remove_text`, `inpainting`, `replace_background`, `reimagine`, `style_transfer`, `image_relight`, `responseArray`, `checkStatus`.
- **Providers:** Freepik, Novita, Clipdrop, Fal, and Google-hosted assets.
- **Dependencies:** Product Photography, AI Video Pro, Nano Banana, SeeDream V4, and other image providers.

## AI Avatar (`AiAvatar`) — v2.5

Synthesia-backed avatar/video creation with avatar/background discovery, video list/retrieval/delete, generation, and status checks.

- **Shape:** 16 files; 2 controllers; 2 services; 1 model; 1 migration; 4 routes.
- **Functions:** `createVideo`, `listVideos`, `retrieveVideo`, `deleteVideo`, `listAvatars`, `listBackgrounds`, `checkVideoStatus`, CRUD.
- **Table:** `user_synthesia`.

## AI Captions (`AiCaptions`) — v1.2

Adds stylised animated captions to uploaded/generated videos through the Captions API, with template selection, dimensions, submission, polling, and content URL resolution.

- **Shape:** 27 files; 2 controllers; 2 services; 1 model; 2 migrations; 9 routes.
- **Functions:** `fromPath`, `dimensionsFromPath`, `hasApiKey`, `submit`, `poll`, `templates`, `resolveContentUrl`, `store`, `checkStatus`.
- **Table:** `ai_captions_videos`.
- **Dependency:** VideoEditor.

## AI Image Pro (`AIImagePro`) — v1.8

Large multi-model image-generation studio with model registry, model selection, prompt enhancement, parallel dispatch, tool categories, galleries, likes, history, provider settings, and integrations into chat, creative, social, and advanced-image systems.

- **Shape:** 151 files; 2 controllers; 2 services; 2 models; 7 migrations; 29 routes.
- **Functions:** `generate`, `getAllAvailableModels`, `getValidationRulesFor`, `getSelectedModelSlugs`, `getActiveImageModels`, `getDefaultSelectedModels`, `getModelsForTagInput`, `dispatchImageGenerationJob`, `enhancePrompt`, `getToolsConfiguration`, `getToolsByCategory`, `getToolById`.
- **Tables:** `ai_image_pro`, `ai_image_pro_likes` plus schema extensions for model/settings metadata.
- **Dependencies:** AdvancedImage, AIChatPro Image Chat, CreativeSuite, ContentManager, FluxPro, NanoBanana, SeeDream V4, and SocialMedia.
- **Provider:** Together AI plus registered image-provider extensions.

## AI Music (`AiMusic`) — v1.5

Song generation and user music library.

- **Shape:** 12 files; 1 controller; 1 service; 1 model; 1 migration; 2 routes.
- **Functions:** `generateSong`, `index`, `create`, `store`, `delete`.
- **Table:** `user_music`.
- **Provider:** AIML API.

## AI Music Pro (`AiMusicPro`) — v1.7

Enhanced music generation, settings, history, and deletion.

- **Shape:** 16 files; 2 controllers; 1 service; 1 model; 3 migrations; 5 routes.
- **Functions:** `generate`, `index`, `update`, `delete`.
- **Table:** `ai_music_pro`.

## AI Persona (`AiPersona`) — v2.9

HeyGen avatar/persona creation with user avatars, script enhancement, asset upload, video generation, status polling, bulk deletion, and persona libraries.

- **Shape:** 24 files; 2 controllers; 2 services; 2 models; 6 migrations; 11 routes.
- **Functions:** `uploadAsset`, `enhanceScript`, `storeAvatar`, `listUserAvatars`, `checkAvatars`, `deleteAvatar`, `checkVideoStatus`, video CRUD.
- **Tables:** `ai_persona_avatars`, `user_heygen`.
- **Provider:** HeyGen.

## AI Photoshoot (`AIPhotoshoot`) — v1.2

Product/photo studio with backgrounds, products, user settings, text/image generation, model registry, image editing, video generation, status polling, upload limits, and provider-specific payload resolution.

- **Shape:** 95 files; 13 controllers; 4 services; 3 models; 5 migrations; 32 routes.
- **Functions:** `generate`, `generateFromText`, `check`, `generateVideo`, `checkVideo`, `models`, `getDefaultModel`, `getProviderFor`, `supportsEdit`, upload-size and settings functions.
- **Tables:** `ai_photo_studio_backgrounds`, `ai_photo_studio_products`, `ai_photo_studio_user_settings`.
- **Dependency:** AIChatPro Image Chat.
- **Provider:** Fal and OpenAI-assisted prompting.

## AI Presentation (`AiPresentation`) — v1.4

AI slide-deck generation with credit prediction, sufficiency checks, model categories, generation submission, status polling, update, deletion, and Gamma-hosted output.

- **Shape:** 28 files; 2 controllers; 2 services; 2 models; 3 migrations; 8 routes.
- **Functions:** `predictCredits`, `checkSufficientCredits`, `getEstimateMessage`, `getModelCategory`, `generatePresentation`, `getGenerationStatus`, `updatePresentationStatus`, `generate`, `checkStatus`, `delete`.
- **Table:** `ai_presentations`.
- **Provider:** Gamma.

## AI Realtime Image (`AIRealtimeImage`) — v1.14

Realtime image generation with success callback, storage download, settings, gallery, and deletion.

- **Shape:** 27 files; 2 controllers; 1 service; 1 model; 4 migrations; 3 routes.
- **Functions:** `generate`, `success`, `downloadImageToStorage`, `getApiKey`, `store`, `gallery`, `destroy`.
- **Table:** `ai_realtime_images`.
- **Provider:** Together AI.

## AI Video Pro (`AiVideoPro`) — v3.6

AI video/avatar generation with provider configuration, prompt enhancement, status polling, remote download, history, bulk deletion, and social-media handoff.

- **Shape:** 25 files; 1 controller; 2 services; 1 model; 6 migrations; 4 routes.
- **Functions:** `getConfig`, `generate`, `getStatus`, `getVideo`, `downloadFromUrl`, `bulkDelete`, `checkVideoStatus`, `enhancePrompt`.
- **Table:** `user_fall`.
- **Dependencies:** SocialMedia.
- **Provider:** OpenAI for prompt enhancement plus configured video provider.

## AI Video-to-Video (`AIVideoToVideo`) — v1.6

Video transformation pipeline with generation requests, single/bulk status checks, and configurable OpenAI assistance.

- **Shape:** 17 files; 1 controller; 6 services; 2 migrations; 3 routes.
- **Functions:** `generate`, `request`, `checked`, `checkedAll`, `setOpenai`, `getOpenai`.
- **Provider:** Fal.

## AI Viral Clips (`AiViralClips`) — v1.5

Long-video-to-short-clips workflow with clip generation, task polling, previews, export, and result storage for Klap/Vizard-style providers.

- **Shape:** 21 files; 4 controllers; 14 routes.
- **Functions:** `generateShorts`, `checkTaskStatus`, `previewLists`, `exportClips`, `checkExportStatus`, `storeFinalVideoKlap`, `storeFinalVideoVizard`, `retrieveClips`.
- **Providers:** Klap and Fal-hosted media endpoints.

## AI Voice Isolator (`AIVoiceIsolator`) — v2.3

UI integration for isolating speech/voice from uploaded audio or video.

- **Shape:** 4 files; view integration only.

## AI Writer Templates (`AIWriterTemplates`) — v2.1

Adds AI writer template records/migrations to the host generation system.

- **Shape:** 5 files; 1 migration.

## Creative Suite (`CreativeSuite`) — v1.15

Editable visual-document workspace with uploads, document CRUD, duplication, naming, editor/status views, and persisted structured document state.

- **Shape:** 111 files; 5 controllers; 1 model; 1 migration; 11 routes.
- **Functions:** `__invoke`, `editor`, `status`, `updateOrCreate`, `show`, `duplicate`, `name`, `destroy`, `index`, `update`.
- **Table:** `ext_creative_suite_documents`.
- **Dependencies:** AI Template and Annotations add-ons.

## Creative Suite AI Template (`CreativeSuiteAITemplate`) — v1.2

Generates editable structured templates rather than flat images, processes AI template JSON, tracks generation tasks, and launches placeholder-image generation.

- **Shape:** 10 files; 1 controller; 2 routes.
- **Functions:** `generateTemplate`, `templateStatus`, `processAiTemplateResponse`, `getTemplateSystemPrompt`, `submitPlaceholderImageGenerations`.
- **Dependency:** CreativeSuite.

## Creative Suite Annotations (`CreativeSuiteAnnotations`) — v1.1

Region-aware visual analysis and editing with per-region prompts, masks, text replacement, prompt construction, source compositing, and asynchronous status.

- **Shape:** 17 files; 2 controllers; 2 services; 4 routes.
- **Functions:** `analyze`, `dispatch`, `supportsMask`, `buildCommentPrompt`, `buildReplaceTextPrompt`, `buildRegionPrompt`, `compositeBackToSource`, `computeMaskBounds`, `edit`, `status`.
- **Providers:** OpenAI, Anthropic, Gemini, and xAI vision/model endpoints.

## Fashion Studio (`FashionStudio`) — v1.7

Full virtual-fashion/photoshoot system with model, wardrobe, pose, background, generation, edit, user-setting, gallery, and video workflows.

- **Shape:** 109 files; 14 controllers; 2 services; 17 models; 9 migrations; 46 routes.
- **Functions:** `models`, `getDefaultModel`, `getProviderFor`, `supportsEdit`, `getModelsForAdminSelect`, `isModelEnabled`, OpenAI quality/size resolution, generation, status, assets, and CRUD across fashion entities.
- **Tables:** `background`, `fashion_model`, `wardrobe`, `pose`, `fashion_studio_user_settings` plus related pivots/settings.
- **Dependency:** AI Video Pro.
- **Provider:** Fal.

## Influencer Avatar (`InfluencerAvatar`) — v1.6

Short influencer-avatar video generation with task status and final-result retrieval.

- **Shape:** 7 files; 1 controller; 4 routes.
- **Functions:** `generateShortVideo`, `checkStatus`, `getFinalVideo`.

## Product Photography (`ProductPhotography`) — v2.6

Product-background removal and themed background generation with saved results and settings.

- **Shape:** 14 files; 2 controllers; 2 services; 1 model; 2 migrations; 3 routes.
- **Functions:** `getThemes`, `removeBg`, `createBg`, `query`, CRUD.
- **Table:** `pebblely`.
- **Provider:** Pebblely.

## UGC Creator (`UGCCreator`) — v1.2

User-generated-content studio with reusable assets, voices, reference images, multiple generation models, provider-specific payload shapes, audio support, video history, and VideoEditor handoff.

- **Shape:** 38 files; 5 controllers; 2 services; 2 models; 4 migrations; 12 routes.
- **Functions:** model registry/resolution, quality and upload limits, asset CRUD, voice listing, generation, polling, video retrieval, and editor export.
- **Tables:** `ugc_creator_assets`, `ugc_creator_videos`.
- **Dependency:** VideoEditor.
- **Providers:** OpenAI, ElevenLabs, and Fal.

## UGC Factory (`UGCFactory`) — v1.1

Actor and UGC-ad factory with actor upload/generation, prompt templates, presets, voiceover provider selection, resolution, task submission, polling, and video persistence.

- **Shape:** 54 files; 5 controllers; 2 services; 2 models; 3 migrations; 14 routes.
- **Functions:** `submit`, `check`, upload limits, voiceover/provider resolution, actor image model/provider selection, prompt construction, preset discovery, actor CRUD, and video operations.
- **Tables:** `ugc_factory_actors`, `ugc_factory_videos`.
- **Providers:** OpenAI, ElevenLabs, and Fal.

## URL to Video (`UrlToVideo`) — v1.9

Converts product/site URLs into marketing-video projects with task submission, script listing/editing, captions, voices, avatars, ethnicities, templates, exports, and provider-specific result storage.

- **Shape:** 37 files; 5 controllers; 39 routes.
- **Functions:** `storeCreatifyFinalVideo`, `storeTopviewFinalVideo`, `marketingVideoSubmitTask`, `marketingVideoQueryTask`, `marketingVideoListScripts`, `marketingVideoUpdateScriptContent`, `marketingVideoExport`, `captionList`, `voiceQuery`, `aiAvatarQuery`, `ethnicityQuery`.

## Video Dubbing (`VideoDubbing`) — v1.2

Translation and dubbing pipeline with uploaded-file storage, duration estimation, provider/language selection, pending jobs, submission, polling, and dubbed-file download.

- **Shape:** 25 files; 2 controllers; 3 services; 1 model; 2 migrations; 7 routes.
- **Functions:** `getDefaultProvider`, `storeUploadedFile`, `estimateUploadedDuration`, `createPendingDubbing`, `submitDubbing`, `checkStatus`, `getLanguagesForProvider`, `createDubbing`, `getDubbingStatus`, `downloadDubbedFile`, `createTranslation`, `getTranslationStatus`.
- **Table:** `video_dubbings`.
- **Providers:** HeyGen and ElevenLabs.

## Video Editor (`VideoEditor`) — v1.0

Timeline-based multi-track editor with media upload/import, metadata and thumbnail extraction, project/timeline persistence, AI generation hooks, export planning, FFmpeg composition, export jobs, and download/status APIs.

- **Shape:** 39 files; 5 controllers; 3 services; 3 models; 6 migrations; 26 routes.
- **Functions:** `export`, `extractMetadata`, `generateThumbnail`, `plan`, `buildVisualClipCommand`, `buildAudioClipCommand`, `buildComposeCommand`, project/media CRUD, timeline updates, status, and download.
- **Tables:** `video_editor_projects`, `video_editor_media`, `video_editor_export_jobs`.
- **Dependencies:** AI Music Pro and AI Video Pro.
- **Providers:** OpenAI and ElevenLabs for generation helpers; FFmpeg for server-side processing.
- **Security review:** five legitimate `proc_open`/`exec` matches require strict argument escaping, allow-listed paths, resource limits, and isolated workers.

> No bundled automated tests were detected in these 26 extensions.
