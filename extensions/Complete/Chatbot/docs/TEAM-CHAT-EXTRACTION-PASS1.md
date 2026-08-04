# Team Chat Extraction — Pass 1

## Extracted

- Direct conversation creation with existing-conversation reuse.
- Group conversation creation.
- Tenant-scoped participants.
- Local UUID and device-origin metadata.
- Message persistence and read receipts.
- Private realtime message event contract.
- Per-user archive and safe leave behaviour.
- Staff inbox API client and initial panel mount.

## Deliberately excluded

- Standalone donor authentication, dashboard and settings.
- Donor service worker and PWA shell.
- Public maintenance routes.
- Hard deletion of conversations and attachments.
- Donor AI assistant service.
- Unrestricted group member administration.
- Attachment upload implementation, owned by Agent 4.
- General bidirectional sync, owned by Agent 2.

## Next extraction pass

Pass 2 adds group ownership policies, member administration, typing/presence/read events, group editing, and a functional staff-inbox group UI.
