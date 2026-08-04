# Team Chat Wiring Repair Manifest

Base: `chatbot-TEAM-CHAT-EXTRACTION-FINAL-FULL-PASS4.zip`

## Modified

- `resources/views/staff-inbox/index.blade.php`
- `resources/views/staff-inbox/team-chat/panel.blade.php`
- `resources/views/staff-inbox/team-chat/channels.blade.php`
- `resources/assets/js/team-chat/business-channels.js`
- `resources/pwa/chatbot-pwa/team-chat-local-first.js`
- `resources/pwa/chatbot-pwa/business-channels-local-first.js`
- `resources/pwa/chatbot-pwa/team-chat-local-ui.js`
- `System/Events/TeamChat/TeamMessageCreated.php`

## Added

- `tests/integration/test_team_chat_wiring.py`
- `FILE-CHANGE-MANIFEST-TEAM-CHAT-WIRING-REPAIR.md`

## Removed

- `resources/pwa/chatbot-pwa/team-chat-local-first.js.tmp`

## Repaired behaviour

- Visible workspace navigation for customer inbox, team chat and channels
- Functional group/direct creation modal
- Functional channel creation and discovery UI
- Channel list and channel detail rendering
- Online message payload alignment (`body`)
- Realtime channel and event-name alignment
- Realtime message text normalization
- CSRF protection for channel mutations
- Proper offline-first channel creation through the channel API
- Removal of temporary production artifact
