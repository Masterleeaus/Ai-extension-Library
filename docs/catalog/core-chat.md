# AIChatPro & Chat Workspace

Functions are detected public executable methods; dynamically registered behaviour may add more. Cross-extension relationship bullets are static integration signals and do not by themselves prove hard dependency direction. See [Documentation Audit](../DOCUMENTATION-AUDIT.md).

## AIChatPro (`AIChatPro`) — v3.7

Internal streamed AI workspace with connector-enabled tool calling, message suggestions, image editing, plan gates, connector access scopes, credential lifecycle, and admin customisation.

- **Shape:** 47 files; 4 controllers; 1 service; 1 model; 7 migrations; 14 detected routes.
- **Functions:** `openAiTools`, `anthropicTools`, `geminiTools`, `handle`, `callFunction`, `generateImage`, `index`, `getMessageSuggestions`, `destroy`, `preConsent`, `accessShow`, `accessUpdate`, `togglePause`, `buildStreamedOutput`.
- **Table:** `ext_ai_chat_pro_connectors`; also extends existing chat/message tables.
- **Detected add-on integrations:** Deep Research, Entity Highlight, File Chat, Smart Image, Canvas, Temp Chat, Model Council, MultiModel, and OpenAI Realtime Chat. These packages generally extend or consume AIChatPro; they are not all proven prerequisites of AIChatPro itself.
- **Providers:** OpenAI and Serper; connector abstraction also emits Anthropic and Gemini tool schemas.
- **Review:** no bundled tests detected; uninstall remains unfinished; credential storage and add-on capability negotiation require production verification.

## AI Chat Pro Deep Research (`AIChatProDeepResearch`) — v1.3

Long-running OpenAI/Gemini research sessions with polling, cancellation, status extraction, sources, thinking steps, and persisted editor content.

- **Shape:** 18 files; 2 controllers; 3 services; 2 models; 2 migrations; 7 detected routes.
- **Functions:** `startResearch`, `checkStatus`, `extractThinkingSteps`, `extractSources`, `countSearches`, `getDriver`, `getDefaultEngine`.
- **Tables:** `deep_research_sessions`, `dr_tiptap_contents`.
- **Detected integration:** Canvas.
- **Review:** no bundled tests detected; cancellation, polling idempotency, provider limits, and citation persistence need runtime tests.

## AI Chat Pro Entity Highlight (`AiChatProEntityHighlight`) — v1.3

Annotates entities in responses and opens detail/image drawers using search and AI enrichment.

- **Shape:** 16 files; 2 controllers; 2 services; 1 model; 2 migrations; 4 detected routes.
- **Functions:** `isEnabled`, `systemPromptAddition`, `parseAnnotations`, `stripAnnotationBlock`, `parseSuggestions`, `saveForMessage`, `fetchDetails`.
- **Table:** `chat_entity_highlights`.
- **Providers:** OpenAI, Perplexity, and Serper.
- **Review:** provider output parsing and annotation sanitisation require adversarial tests.

## AIChatPro File Chat (`AIChatProFileChat`) — v1.4

Adds file validation and file-aware analysis to AIChatPro conversations.

- **Shape:** 9 files; 1 service.
- **Function:** `validateAndAnalyzeFile`.
- **Review:** no bundled tests detected; file validation, storage, parser limits, malware scanning, and tenant isolation require verification.

## AI Chat Pro Folders (`AIChatProFolders`) — v1.4

Conversation folders with chat assignment and folder CRUD.

- **Shape:** 12 files; 1 controller; 2 models; 2 migrations; 6 detected routes.
- **Functions:** `store`, `update`, `destroy`, `getChats`, `getFolders`.
- **Table:** `ai_chat_pro_folders`.

## AI Chat Pro Highlight to Ask (`AiChatProHighlightToAsk`) — v1.1

Turns selected response text into a follow-up prompt and exposes feature settings.

- **Shape:** 12 files; 1 controller; 1 service; 2 migrations; 2 detected routes.
- **Functions:** `isEnabled`, `index`, `update`.

## AI Chat Pro Skills (`AIChatProSkills`) — v1.1

Reusable public/private AI skills with discovery, ownership, assignment, tool-schema conversion, provider-neutral execution, import/export, and GitHub-backed resources.

- **Shape:** 23 files; 3 controllers; 2 services; 2 models; 2 migrations; 25 detected routes.
- **Functions:** `openAiTools`, `anthropicTools`, `geminiTools`, `handleSkillCall`, `getSkillMeta`, public search/list/show, CRUD, assign, unassign, import, export, and resource operations.
- **Tables:** `skills`, `user_skills`.
- **Provider/integration:** GitHub and raw GitHub content for skill resources.
- **Review:** a skill is not automatically an executable tool. Registry integration should preserve distinct skill and tool contracts, versioning, permissions, provenance, checksums, tests, approval, deprecation, and revocation.

## AI Chat Pro Smart Image (`AiChatProSmartImage`) — v1.2

Searches for context-relevant images, formats them for chat, stores message associations, and exposes model tool definitions.

- **Shape:** 13 files; 2 controllers; 2 services; 1 model; 1 migration; 3 detected routes.
- **Functions:** `isEnabled`, `getMaxImages`, `searchAndFormat`, `saveForMessage`, `geminiToolDefinition`, `toolDefinition`, `systemPromptAddition`, `search`.
- **Table:** `chat_smart_images`.
- **Providers:** Perplexity and Serper.

## AI Web Chat (`AIWebChat`) — v2.9

Standalone web chat interface with workbook opening, chat creation, streaming, and output endpoints.

- **Shape:** 18 files; 1 controller; 1 model; 2 migrations; 4 detected routes.
- **Functions:** `openAIGeneratorWorkbook`, `openChatAreaContainer`, `startNewChat`, `chatStream`, `chatOutput`.
- **Providers:** OpenAI, Perplexity, and Serper.

## AI Chat Pro Canvas (`Canvas`) — v1.11

Persists editable Tiptap canvas content and titles alongside AIChatPro sessions.

- **Shape:** 10 files; 1 controller; 1 model; 2 detected routes.
- **Functions:** `storeContent`, `saveTitle`.
- **Table:** `user_tiptap_contents`.

## Temporary Chat (`ChatProTempChat`) — v1.4

Adds ephemeral/non-persistent chat behaviour and UI components to AIChatPro.

- **Shape:** 12 files; component and view integration.
- **Review:** no bundled tests detected. “Temporary” must be validated across messages, logs, analytics, provider retention, files, caches, and backups—not only the primary chat table.

## Chat Setting (`ChatSetting`) — v3.3

Chat-category administration and knowledge training through text, Q&A, PDF uploads, and website sources.

- **Shape:** 24 files; 4 controllers; 8 detected routes.
- **Functions:** category CRUD, `qa`, `text`, `uploadPdf`, `getWebSites`, `training`.
- **Review:** overlaps other training implementations and should delegate to the dedicated Knowledge Engine through compatibility adapters.

## Chat Share (`ChatShare`) — v2.7

Creates shareable links and renders shared chat/message views.

- **Shape:** 8 files; 1 controller; 1 model; 2 detected routes.
- **Functions:** `share`, `createLink`.
- **Review:** expiration, revocation, optional password protection, tenant scoping, sensitive-content policy, and search-engine indexing controls require verification.

## ElevenLabs Voice Chat (`ElevenLabsVoiceChat`) — v1.7

Voice-chat agent creation, voice selection, knowledge-base training, balance checks, and agent lifecycle management.

- **Shape:** 24 files; 2 controllers; 1 service; 2 models; 2 migrations; 9 detected routes.
- **Functions:** `fetchVoiceChatbot`, `getVoices`, `createAgent`, `updateAgent`, `updateAgentWithKnowledgebase`, `deleteKnowledgebase`, `addKnowledgebase`, `checkVoiceBalance`.
- **Tables:** `voice_chat_bot_trains`, `voice_chat_bots`.
- **Review:** overlaps ChatbotVoice and PhoneCallAgent. Consolidation should separate telephony, realtime conversation, speech recognition, speech synthesis, recording, and provider lifecycle capabilities.

## Focus Mode (`FocusMode`) — v2.5

Adds a reduced-distraction AI workspace presentation mode.

- **Shape:** 7 files; component/view integration.

## OpenAI Realtime Chat (`OpenAIRealtimeChat`) — v1.10

Creates OpenAI realtime sessions and checks available balance before starting realtime chat.

- **Shape:** 7 files; 1 controller; 1 migration; 2 detected routes.
- **Functions:** `checkBalance`, `session`.
- **Provider:** OpenAI Realtime API.
- **Review:** session creation, ephemeral credentials, origin checks, rate limits, budget enforcement, and transcript retention require runtime tests.

> Except for the canonical Chatbot package elsewhere in the repository, no bundled tests were detected in these selected extension folders.
