# Integrated Staff Inbox

The former standalone chatbot-agent extension is now part of the Chatbot extension.

## Canonical locations

- Controller: `System/Http/Controllers/StaffInbox/StaffInboxController.php`
- Realtime settings: `System/Http/Controllers/StaffInbox/RealtimeSettingsController.php`
- Realtime services: `System/Services/StaffInbox/`
- Realtime events: `System/Events/StaffInbox/`
- Views: `resources/views/staff-inbox/`
- Styles: `resources/assets/scss/staff-inbox.scss`

## Routes

- Staff inbox: `/dashboard/chatbot/inbox`
- Realtime settings: `/dashboard/admin/settings/chatbot-realtime`

## Boundaries

- The existing chatbot builder remains unchanged.
- Existing Chatbot models, resources, policies, and `ChatbotService` remain authoritative.
- Customer-tag, review, Telegram, and WhatsApp features remain optional and are only used when their corresponding extensions are installed.
- No standalone `ChatbotAgentServiceProvider` is required.
