# Team Chat Extraction Pass 3

Adds first-class business channels without importing a parallel chat application.

## Added
- Public and private channels
- Stable tenant-scoped slugs
- Channel categories and descriptions
- Announcement mode and posting policies
- Business-linked rooms through `linked_entity_type` and `linked_entity_id`
- Public channel discovery and join
- Safe channel archive/restore
- Staff-inbox channel panel and API client

## Boundaries
Attachment transport, sync endpoints, conflict policy, service-worker changes, and offline pack infrastructure remain outside this pass.
