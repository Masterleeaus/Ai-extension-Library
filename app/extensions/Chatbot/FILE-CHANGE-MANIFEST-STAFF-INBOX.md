# Staff Inbox File Change Manifest

## Added

- `System/Http/Controllers/StaffInbox/StaffInboxController.php`
- `System/Http/Controllers/StaffInbox/RealtimeSettingsController.php`
- `System/Services/StaffInbox/ChatbotForFrameEventAbly.php`
- `System/Services/StaffInbox/ChatbotForPanelEventAbly.php`
- `System/Services/StaffInbox/Contracts/AblyService.php`
- `System/Events/StaffInbox/ChatbotForMenuEvent.php`
- `System/Events/StaffInbox/ChatbotForPanelEvent.php`
- `System/Events/StaffInbox/NewConversationForPanelEvent.php`
- `resources/views/staff-inbox/index.blade.php`
- `resources/views/staff-inbox/setting.blade.php`
- `resources/views/staff-inbox/header/inbox-notification.blade.php`
- `resources/views/staff-inbox/partials/*.blade.php`
- `resources/assets/scss/staff-inbox.scss`
- `docs/STAFF-INBOX-INTEGRATION.md`

## Modified

- `System/ChatbotServiceProvider.php`
- `System/Http/Controllers/Api/ChatbotApplicationController.php`
- `extension.json`

## Explicitly not added

- No `App/Extensions/ChatbotAgent` tree
- No legacy folder
- No source folder
- No integration folder
- No duplicate builder
- No duplicate extension service provider
