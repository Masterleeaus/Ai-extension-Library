# Titan Zero Chatbot PWA v4

This upgrade is additive. It does not replace or restructure the chatbot builder.

## Capabilities

- Installable on iOS, iPadOS, Android and desktop browsers that support PWAs.
- Root-scoped service worker with guarded caching.
- Network-first navigation with offline fallback.
- Stale-while-revalidate static assets with cache size limits.
- Auth, API, admin and private routes excluded from caching.
- Update detection with an in-app update control.
- Online/offline state events and status UI.
- iOS install-help event and native install prompt API.
- Push notification and notification-click service-worker hooks.
- Web Share API helper with clipboard fallback.
- Optional IndexedDB task queue API that does not intercept forms or requests.
- Manifest screenshots, shortcuts, maskable icons and launch handling.
- Safe-area and standalone-mode behaviour.

## Publish

```bash
php artisan vendor:publish --tag=extension --force
php artisan optimize:clear
```

Confirm these return HTTP 200:

- `/chatbot.webmanifest`
- `/chatbot-sw.js`
- `/chatbot-pwa/offline.html`

PWA installation requires HTTPS outside localhost.

## JavaScript APIs

```js
await window.chatbotPwaInstall();
await window.chatbotPwa.requestNotifications();
await window.chatbotPwa.share({ title: 'Chat', url: location.href });
await window.chatbotPwa.queueTask({ type: 'custom-action', payload: {} });
```

The task queue is deliberately opt-in. Existing chatbot requests are never captured or replayed automatically.
