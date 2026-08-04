# Model Providers & Orchestration

Functions and routes are statically detected. Provider references do not prove that credentials, webhook validation, retries, quotas, or callbacks are production-ready. See [Documentation Audit](../DOCUMENTATION-AUDIT.md).

## Azure OpenAI (`AzureOpenai`) — v1.5

Adds Azure-hosted OpenAI streaming and provider configuration to MagicAI.

- **Shape:** 9 files; 1 controller; 1 service.
- **Functions:** `azureOpenaiStream`, `azureOpenaiOtherStream`, `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- **Manifest defect:** the source `extension.json` identifies this package as `Example` with an example description. The catalogue name is capability-derived from the folder and implementation.
- **Review:** no bundled tests detected; one unfinished uninstall/TODO path. Deployment, endpoint/API-version validation, credential encryption, model/deployment mapping, retries, and usage accounting require tests.

## Azure TTS (`AzureTTS`) — v2.2

Azure Text-to-Speech integration.

- **Shape:** 6 files; 1 service.
- **Function:** `synthesizeSpeech`.
- **Review:** no bundled tests detected. Voice/model validation, SSML sanitisation, file lifecycle, quotas, retries, and cost accounting require verification.

## Flux Pro (`FluxPro`) — v2.5

Fal-hosted Flux image-generation webhook integration.

- **Shape:** 14 files; 1 controller; 1 detected route.
- **Function:** `__invoke`.
- **Route:** `ANY generator/webhook/fal-ai`.
- **Critical collision:** NanoBanana and SeeDreamV4 declare the same method/path. Registration order can redirect callbacks to the wrong controller. Replace these routes with unique package paths or one authenticated Fal callback dispatcher.
- **Review:** provider signatures, replay protection, tenant/job resolution, idempotency, and callback status transitions require tests.

## Midjourney (`Midjourney`) — v2.6

Midjourney generation, status polling, webhook handling, and storage download through PiAPI.

- **Shape:** 11 files; 3 controllers; 1 service; 3 detected routes.
- **Functions:** `generate`, `check`, `downloadImageToStorage`, `__invoke`, `updateImages`, `index`, `update`.
- **Routes:** `ANY generator/webhook/midjourney`, `GET piapi-ai`, `POST piapi-ai`.
- **External service:** PiAPI.
- **Review:** no bundled tests detected. Webhook authentication, remote-download validation, SSRF controls, content limits, idempotency, and tenant/job mapping require verification.

## Model Council (`ModelCouncil`) — v1.5

Runs multiple model responses, synthesises a council result, streams council output, and records the user's accepted answer.

- **Shape:** 12 files; 1 controller; 1 service; 1 model; 2 migrations.
- **Functions:** `streamCouncilResponse`, `streamCouncilSummaryFromShared`, `generateCouncilResponse`, `acceptResponse`.
- **Route:** `POST /accept-response`.
- **Table:** `model_council_responses`.
- **Detected integrations:** AIChatPro and MultiModel.
- **Providers:** OpenAI, Anthropic, Gemini, DeepSeek, xAI, and OpenRouter.
- **Critical collision:** MultiModel declares the same `POST /accept-response` route. Prefix or name routes by package, or consolidate acceptance through one dispatcher.
- **Review:** no bundled tests detected. Parallel-call budgets, cancellation, partial failure, model provenance, synthesis transparency, prompt injection, and preference-record ownership require tests.

## MultiModel (`MultiModel`) — v1.3

Presents parallel model answers and records the preferred response.

- **Shape:** 11 files; 1 controller; 1 model; 1 migration.
- **Function:** `acceptResponse`.
- **Route:** `POST /accept-response`.
- **Critical collision:** ModelCouncil declares the same method/path.
- **Review:** no bundled tests detected. Route ownership, response provenance, permissions, duplicate submissions, and preference storage require verification.

## Nano Banana (`NanoBanana`) — v1.6

Fal webhook adapter for Nano Banana image generation.

- **Shape:** 17 files; 1 controller; 1 detected route.
- **Function:** `__invoke`.
- **Route:** `ANY generator/webhook/fal-ai`.
- **Critical collision:** FluxPro and SeeDreamV4 declare the same method/path.
- **Review:** no bundled tests detected. Use a unique callback or a shared dispatcher with signed payload verification and provider-job routing.

## OpenRouter (`OpenRouter`) — v1.2

Adds OpenRouter model access and settings.

- **Shape:** 15 files; 2 controllers; 1 service.
- **Functions:** `response`, `show`, `update`.
- **External service:** OpenRouter.
- **Review:** no bundled tests detected; three TODO/FIXME markers. Model discovery, provider routing, credential encryption, headers, retries, error normalisation, budgets, and usage accounting require tests.

## Perplexity (`Perplexity`) — v1.1

Adds Perplexity provider configuration.

- **Shape:** 6 files; 1 controller; 2 detected routes.
- **Functions:** `index`, `update`.
- **Routes:** `GET perplexity`, `POST perplexity`.
- **Review:** no bundled tests detected. Credential lifecycle, model validation, citations, rate limits, and usage accounting require verification.

## SeeDream V4 (`SeeDreamV4`) — v1.3

Fal webhook adapter for SeeDream V4 image generation.

- **Shape:** 12 files; 1 controller; 1 detected route.
- **Function:** `__invoke`.
- **Route:** `ANY generator/webhook/fal-ai`.
- **Critical collision:** FluxPro and NanoBanana declare the same method/path.
- **Review:** no bundled tests detected. Use a unique callback or shared authenticated dispatcher.

## Required provider-runtime upgrades

1. One shared provider registry with stable provider/model identifiers.
2. Vault references instead of raw credentials in extension settings.
3. Normalised request, response, usage, error, timeout, and retry contracts.
4. Tenant-aware budget and rate-limit policy.
5. Signed callback verification, replay prevention, idempotency, and job ownership checks.
6. Unique routes or provider-aware callback dispatchers.
7. Central usage ledger and model/deployment provenance.
8. Provider health checks and capability-level fallback rather than blind whole-stack replacement.
