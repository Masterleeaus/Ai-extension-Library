# Content, SEO, Social & Growth AI

Functions are detected public executable methods; dynamically registered behaviour may add more.

## AI Plagiarism (`AIPlagiarism`) — v2.2

Plagiarism checking and AI-content detection with report persistence and admin settings.

- **Shape:** 9 files; 1 controller; 1 service; 1 migration; 8 routes.
- **Functions:** `checkPlagiarism`, `detectAIContent`, `plagiarismCheck`, `detectAIContentCheck`, `plagiarismSave`, `detectAIContentSave`, `plagiarismSetting`, `plagiarismSettingSave`.
- **Routes:** plagiarism and AI-content detection pages, checks, saves, and settings.
- **Providers:** PlagiarismCheck and Serper.
- **Review:** no bundled tests detected; two TODO/FIXME markers.

## AI Social Media (`AISocialMedia`) — v4.12

Social-platform connection, content generation, campaign scheduling, publishing, and automation.

- **Shape:** 71 files; 7 controllers; 6 services; 6 models; 13 migrations; 29 routes.
- **Functions:** `getProfile`, `shareNone`, `share`, `platforms`, `find`, `findAutomationPlatform`, upload, post generation, scheduling, campaign operations, and platform connection lifecycle.
- **Tables:** `automation_campaigns`, `automation_platforms`, `automations`, `linkedin_tokens`, `scheduled_posts`, `twitter_settings`.
- **Providers:** Meta, LinkedIn, X/Twitter, and OpenAI.
- **Review:** no bundled tests detected; three TODO/FIXME markers.

## BlogPilot (`BlogPilot`) — v1.4

AI blog planning and generation with agents, topic generation, bulk posts, calendar, image generation, and status tracking.

- **Shape:** 74 files; 1 controller; 2 services; 2 models; 2 migrations; 21 routes.
- **Functions:** `generateImageForPost`, `checkStatus`, `setModel`, `generatePost`, `generateBulkPosts`, `generateTopics`, `__invoke`.
- **Tables:** `ext_blogpilot`, `ext_blogpilot_posts`.
- **Providers:** OpenAI and Fal.
- **Review:** no bundled tests detected; one TODO marker and one commented process-execution match.

## Content Manager (`ContentManager`) — v1.6

Central content listing and update surface for generated content.

- **Shape:** 10 files; 1 controller; 1 model; 2 routes.
- **Functions:** `index`, `update`.
- **Routes:** `GET /`, `POST /update`.
- **Review:** no bundled tests detected.

## SEO Tool (`SEOTool`) — v3.6

Keyword discovery, AI article analysis, content improvement, SEO generation, and related-question discovery.

- **Shape:** 13 files; 1 controller; 2 services; 1 model; 7 routes.
- **Functions:** `getKeywords`, `analiyzeWithAI`, `improveWithAI`, `generateSEO`, `getSearchQuestions`, `suggestKeywords`, `analyseArticle`.
- **Routes:** keyword suggestions, keyword generation, related questions, SEO generation, article analysis, and improvement.
- **Provider:** Google Serper.
- **Review:** no bundled tests detected. Two static scanner matches came from JavaScript regex `.exec()` calls, not shell execution.

## AI Social Media Pro (`SocialMedia`) — v5.13

Full social publishing and analytics platform with platform OAuth, webhooks, campaigns, posts, metrics, shared logs, bulk upload, and media processing.

- **Shape:** 143 files; 20 controllers; 15 services; 6 models; 19 migrations; 43 routes.
- **Functions:** `sync`, `fetch`, `splitAndUpload`, `cleanup`, `storeBulk`, platform redirects/callbacks, webhook handlers, publishing, analytics, and campaign operations.
- **Tables:** `ext_social_media_analyses`, `ext_social_media_campaigns`, `ext_social_media_platforms`, `ext_social_media_post_daily_metrics`, `ext_social_media_posts`, `ext_social_media_shared_logs`.
- **Dependencies:** SocialMediaAgent and SocialMediaAutomation.
- **Providers:** Meta, LinkedIn, X, Google/YouTube, and TikTok.
- **Review:** no bundled tests detected.
