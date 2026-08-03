# Model Providers & Orchestration

Functions are detected public executable methods; dynamically registered behaviour may add more.

## Azure OpenAI (`AzureOpenai`) — v1.5

Adds Azure-hosted OpenAI streaming and provider configuration to MagicAI.

- **Shape:** 9 files; 1 controller; 1 service.
- **Functions:** `azureOpenaiStream`, `azureOpenaiOtherStream`, `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- **Review:** no bundled tests detected; one unfinished uninstall/TODO path.

## Azure TTS (`AzureTTS`) — v2.2

Azure Text-to-Speech integration.

- **Shape:** 6 files; 1 service.
- **Functions:** `synthesizeSpeech`.
- **Review:** no bundled tests detected.

## Flux Pro (`FluxPro`) — v2.5

Fal-hosted Flux image-generation webhook integration.

- **Shape:** 14 files; 1 controller; 1 detected route.
- **Functions:** `__invoke`.
- **Route:** `ANY generator/webhook/fal-ai`.
- **Review:** no bundled tests detected.

## Midjourney (`Midjourney`) — v2.6

Midjourney generation, status polling, webhook handling, and storage download through PiAPI.

- **Shape:** 11 files; 3 controllers; 1 service; 3 detected routes.
- **Functions:** `generate`, `check`, `downloadImageToStorage`, `__invoke`, `updateImages`, `index`, `update`.
- **Routes:** `ANY generator/webhook/midjourney`, `GET piapi-ai`, `POST piapi-ai`.
- **External service:** PiAPI.
- **Review:** no bundled tests detected.

## Model Council (`ModelCouncil`) — v1.5

Runs multiple model responses, synthesises a council result, streams council output, and records the user's accepted answer.

- **Shape:** 12 files; 1 controller; 1 service; 1 model; 2 migrations.
- **Functions:** `streamCouncilResponse`, `streamCouncilSummaryFromShared`, `generateCouncilResponse`, `acceptResponse`.
- **Route:** `POST /accept-response`.
- **Table:** `model_council_responses`.
- **Dependencies:** AIChatPro and MultiModel.
- **Providers:** OpenAI, Anthropic, Gemini, DeepSeek, xAI, and OpenRouter.
- **Review:** no bundled tests detected.

## MultiModel (`MultiModel`) — v1.3

Presents parallel model answers and records the preferred response.

- **Shape:** 11 files; 1 controller; 1 model; 1 migration.
- **Functions:** `acceptResponse`.
- **Route:** `POST /accept-response`.
- **Review:** no bundled tests detected.

## Nano Banana (`NanoBanana`) — v1.6

Fal webhook adapter for Nano Banana image generation.

- **Shape:** 17 files; 1 controller; 1 detected route.
- **Functions:** `__invoke`.
- **Route:** `ANY generator/webhook/fal-ai`.
- **Review:** no bundled tests detected.

## OpenRouter (`OpenRouter`) — v1.2

Adds OpenRouter model access and settings.

- **Shape:** 15 files; 2 controllers; 1 service.
- **Functions:** `response`, `show`, `update`.
- **External service:** OpenRouter.
- **Review:** no bundled tests detected; three TODO/FIXME markers.

## Perplexity (`Perplexity`) — v1.1

Adds Perplexity provider configuration.

- **Shape:** 6 files; 1 controller; 2 detected routes.
- **Functions:** `index`, `update`.
- **Routes:** `GET perplexity`, `POST perplexity`.
- **Review:** no bundled tests detected.

## SeeDream V4 (`SeeDreamV4`) — v1.3

Fal webhook adapter for SeeDream V4 image generation.

- **Shape:** 12 files; 1 controller; 1 detected route.
- **Functions:** `__invoke`.
- **Route:** `ANY generator/webhook/fal-ai`.
- **Review:** no bundled tests detected.
