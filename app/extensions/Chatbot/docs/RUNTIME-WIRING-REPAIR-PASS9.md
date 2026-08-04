# Runtime Wiring Repair Pass 9

Adds missing field-service skill/tool discovery, matching APIs, BYO execution bridge, host WorkCore integration contracts and diagnostics, and broader WorkCore tool-management support.

## New authenticated API endpoints
- GET `/api/v2/chatbot/titan-ai/skills`
- POST `/api/v2/chatbot/titan-ai/skills/match`
- GET `/api/v2/chatbot/titan-ai/tools`
- POST `/api/v2/chatbot/titan-ai/tools/match`

These endpoints expose definitions and matching only. State-changing execution remains governed through the existing Titan AI → WorkCore path.
