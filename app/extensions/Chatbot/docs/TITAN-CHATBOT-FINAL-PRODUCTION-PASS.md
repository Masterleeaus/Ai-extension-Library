# Titan Zero Chatbot Final Production Pass

## Scope

This pass hardens the cumulative 14-app chatbot shell for production integration.

## Corrections

- Aligned resumable attachment URLs and payloads with the merge-ready WorkCore Laravel package.
- Replaced the unavailable offline ping endpoint with the authenticated WorkCore action index for reachability checks.
- Escaped all WorkCore projection text and encoded data attributes before rendering operational cards.
- Added focus return and initial focus for the hamburger and gear dialogs.
- Added `aria-expanded`, `aria-controls`, and `aria-current` state.
- Added the operational shell, shell CSS, and settings runtime to the service-worker precache.
- Bumped the service-worker cache generation to v13.

## Authoritative boundary

WorkCore remains authoritative. Browser actions remain local projections or proposal-only commands until Laravel accepts them.

## Optional server capabilities

Offline knowledge-pack endpoints remain optional progressive enhancements. Their absence does not block jobs, changes, operations, attachments, settings, or the 14 operational workspaces.

## Deployment

After merging into the chatbot extension:

```bash
php artisan migrate
php artisan optimize:clear
php artisan test
```

Deploy alongside `Unified-WorkCore-Merge-Ready-PWA-Connected.zip` or an equivalent WorkCore backend exposing the documented v1 sync, job-pack, changes, action, and attachment routes.
