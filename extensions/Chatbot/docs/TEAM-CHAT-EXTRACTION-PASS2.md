# Team Chat Extraction Pass 2

Pass 2 hardens the extracted direct/group chat foundation without importing the donor application shell.

## Added

- Owner, administrator, moderator and member roles.
- Central conversation permission policy.
- Owner-safe leave flow with deterministic ownership transfer.
- Add, remove and role-change participant services.
- Group metadata update control restricted to owners and administrators.
- Private tenant-and-membership-scoped broadcast authorization.
- Realtime typing, message-read and conversation-update events.
- Browser client methods for participant management, typing and subscriptions.

## Safety rules

- Owners cannot be removed by another participant.
- Administrators cannot remove owners or other administrators.
- The final owner cannot abandon a group with no successor.
- Conversation access remains tenant-scoped and membership-scoped.
- Permanent conversation deletion is not introduced.

## Deferred ownership

- Business channels and linked rooms: extraction Pass 3.
- IndexedDB/offline merge for team records: extraction Pass 4.
- Sync transport and server acknowledgements: Agent 2.
- Conflict policy and audit centre: Agent 3.
- Attachment upload pipeline: Agent 4.
