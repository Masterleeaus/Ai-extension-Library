/* Titan Zero Chatbot PWA service worker.
 * Additive only: no builder routes, forms, or authenticated API responses are cached.
 */
const VERSION = 'chatbot-pwa-v15-offline-integrity';
const CACHE_PREFIX = 'chatbot-pwa-';
const STATIC_CACHE = `${VERSION}-static`;
const PAGE_CACHE = `${VERSION}-pages`;
const ASSET_CACHE = `${VERSION}-assets`;
const OFFLINE_URL = '/chatbot-pwa/offline.html';
const MAX_ASSET_ENTRIES = 80;
const MAX_PAGE_ENTRIES = 15;

const PRECACHE = [
  "/chatbot-pwa/offline.html",
  "/chatbot-pwa/crypto-vault.js",
  "/chatbot-pwa/device-db.js",
  "/chatbot-pwa/frontend-local-first.js",
  "/chatbot-pwa/icons/apple-touch-icon.png",
  "/chatbot-pwa/icons/badge-96.png",
  "/chatbot-pwa/icons/icon-192-maskable.png",
  "/chatbot-pwa/icons/icon-192.png",
  "/chatbot-pwa/icons/icon-512-maskable.png",
  "/chatbot-pwa/icons/icon-512.png",
  "/chatbot-pwa/install.js",
  "/chatbot-pwa/local-repositories.js",
  "/chatbot-pwa/local-search.js",
  "/chatbot-pwa/local-services.js",
  "/chatbot-pwa/local-ui-bridge.js",
  "/chatbot-pwa/offline-store.js",
  "/chatbot-pwa/outbox.js",
  "/chatbot-pwa/phase1-ui.js",
  "/chatbot-pwa/pwa.css",
  "/chatbot-pwa/register.js",
  "/chatbot-pwa/agents/event-bus.js",
  "/chatbot-pwa/agents/agent-runtime.js",
  "/chatbot-pwa/agents/device-agents.js",
  "/chatbot-pwa/agents/index.js",
  "/chatbot-pwa/runtime.js",
  "/chatbot-pwa/team-chat-local-first.js",
  "/chatbot-pwa/business-channels-local-first.js",
  "/chatbot-pwa/team-chat-local-ui.js",
  "/vendor/chatbot/css/titan-generative-ui.css",
  "/vendor/chatbot/js/titan-generative-ui.js",
  "/vendor/chatbot/js/titan-operational-screens.js",
  "/vendor/chatbot/css/titan-app-shell.css",
  "/chatbot-pwa/settings-runtime.js",
  "/chatbot-pwa/generative-ui-state.js",
  "/chatbot-pwa/screenshots/chatbot-mobile.png",
  "/chatbot-pwa/screenshots/chatbot-wide.png",
  "/chatbot-pwa/security-integrity.js",
  "/chatbot-pwa/sync/adapter.js",
  "/chatbot-pwa/sync/engine.js",
  "/chatbot-pwa/workcore/attachments.js",
  "/chatbot-pwa/workcore/client.js",
  "/chatbot-pwa/workcore/contracts.js",
  "/chatbot-pwa/workcore/database.js",
  "/chatbot-pwa/workcore/device-capabilities.js",
  "/chatbot-pwa/workcore/field-workspace.js",
  "/chatbot-pwa/workcore/hardening.js",
  "/chatbot-pwa/workcore/knowledge.js",
  "/chatbot-pwa/workcore/network.js",
  "/chatbot-pwa/workcore/offline-packs.js",
  "/chatbot-pwa/workcore/readiness.js",
  "/chatbot-pwa/workcore/resilience-ui.js",
  "/chatbot-pwa/workcore/self-tests.js",
  "/chatbot-pwa/workcore/storage.js",
  "/chatbot-pwa/workcore/ui.js",
  "/chatbot-pwa/workcore/vault.js",
  "/chatbot.webmanifest",
];

const SENSITIVE_PATHS = [
  '/login', '/logout', '/register', '/password', '/admin', '/dashboard',
  '/api/', '/broadcasting/', '/sanctum/', '/storage/private/'
];

function isSensitive(url) {
  return SENSITIVE_PATHS.some(path => url.pathname.startsWith(path));
}

async function trimCache(cacheName, maxEntries) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  if (keys.length <= maxEntries) return;
  await Promise.all(keys.slice(0, keys.length - maxEntries).map(key => cache.delete(key)));
}

async function safePut(cacheName, request, response, maxEntries) {
  if (!response || !response.ok || response.type === 'opaque') return;
  if (response.headers.get('Cache-Control')?.includes('no-store')) return;
  if (response.headers.has('Set-Cookie')) return;
  if (response.headers.get('Vary')?.includes('Cookie')) return;
  if (response.headers.get('X-Private-Response') === '1') return;
  if (response.headers.get('X-Authenticated-User')) return;
  if (response.headers.get('Authorization')) return;
  const cache = await caches.open(cacheName);
  await cache.put(request, response.clone());
  await trimCache(cacheName, maxEntries);
}

self.addEventListener('install', event => {
  event.waitUntil((async () => {
    const cache = await caches.open(STATIC_CACHE);
    const requests = PRECACHE.map(url => new Request(url, { cache: 'reload', credentials: 'same-origin' }));
    const responses = await Promise.all(requests.map(request => fetch(request)));
    for (let i = 0; i < requests.length; i += 1) {
      if (!responses[i].ok) throw new Error(`Precache failed: ${requests[i].url}`);
      await cache.put(requests[i], responses[i]);
    }
    await cache.put('/chatbot-pwa/cache-manifest.json', new Response(JSON.stringify({ version: VERSION, assets: PRECACHE, installed_at: new Date().toISOString() }), { headers: { 'Content-Type': 'application/json' } }));
  })());
});

self.addEventListener('activate', event => {
  event.waitUntil((async () => {
    const allowed = new Set([STATIC_CACHE, PAGE_CACHE, ASSET_CACHE]);
    const keys = await caches.keys();
    const old = keys.filter(key => key.startsWith(CACHE_PREFIX) && !allowed.has(key)).sort().reverse();
    // Keep one previous static shell for rollback; remove older generations only.
    const previousStatic = old.find(key => key.endsWith('-static'));
    await Promise.all(old.filter(key => key !== previousStatic).map(key => caches.delete(key)));
    await self.clients.claim();
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    clients.forEach(client => client.postMessage({ type: 'PWA_UPDATED', version: VERSION }));
  })());
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin || isSensitive(url)) return;

  if (request.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        const response = await fetch(request);
        // Authenticated/navigation HTML is always network-only to avoid cross-user leakage.
        return response;
      } catch (_) {
        return (await caches.match(request)) || (await caches.match(OFFLINE_URL));
      }
    })());
    return;
  }

  const destination = request.destination;
  const isStatic = ['style', 'script', 'font', 'image'].includes(destination);
  if (!isStatic) return;

  event.respondWith((async () => {
    const cached = await caches.match(request);
    const networkPromise = fetch(request).then(response => {
      event.waitUntil(safePut(ASSET_CACHE, request, response, MAX_ASSET_ENTRIES));
      return response;
    }).catch(() => null);

    const response = cached || (await networkPromise);
    if (response) return response;
    // Corrupt/missing current shell assets may still exist in the previous generation.
    const keys = (await caches.keys()).filter(name => name.startsWith(CACHE_PREFIX) && name.endsWith('-static') && name !== STATIC_CACHE).sort().reverse();
    for (const name of keys) { const fallback = await (await caches.open(name)).match(request); if (fallback) return fallback; }
    return Response.error();
  })());
});

self.addEventListener('message', event => {
  const data = event.data || {};
  if (data === 'SKIP_WAITING' || data.type === 'SKIP_WAITING') self.skipWaiting();
  if (data.type === 'CLEAR_PWA_CACHES') {
    event.waitUntil(Promise.all([PAGE_CACHE, ASSET_CACHE].map(name => caches.delete(name))));
  }
  if (data.type === 'VERIFY_PWA_CACHE' && event.ports?.[0]) {
    event.waitUntil((async () => {
      const cache = await caches.open(STATIC_CACHE);
      const missing = [];
      for (const url of PRECACHE) if (!(await cache.match(url))) missing.push(url);
      event.ports[0].postMessage({ version: VERSION, healthy: missing.length === 0, missing });
    })());
  }
  if (data.type === 'GET_VERSION' && event.ports?.[0]) {
    event.ports[0].postMessage({ version: VERSION });
  }
});

self.addEventListener('push', event => {
  let payload = {};
  try { payload = event.data?.json() || {}; } catch (_) { payload = { body: event.data?.text() }; }
  const title = payload.title || 'Titan Zero Chatbot';
  const options = {
    body: payload.body || 'You have a new update.',
    icon: payload.icon || '/chatbot-pwa/icons/icon-192.png',
    badge: payload.badge || '/chatbot-pwa/icons/badge-96.png',
    tag: payload.tag || 'chatbot-update',
    renotify: Boolean(payload.renotify),
    data: { url: payload.url || '/chatbot?source=notification' },
    actions: payload.actions || []
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const target = new URL(event.notification.data?.url || '/chatbot', self.location.origin).href;
  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    const existing = windows.find(client => client.url.startsWith(self.location.origin));
    if (existing) {
      await existing.focus();
      if ('navigate' in existing) await existing.navigate(target);
      return;
    }
    await self.clients.openWindow(target);
  })());
});


// Background Sync does not access IndexedDB directly. It wakes an authenticated
// client, which owns WorkCore encryption, tenant context and API credentials.
self.addEventListener('sync', event => {
  if (!['workcore-outbox', 'titan-chatbot-outbox'].includes(event.tag)) return;
  event.waitUntil((async () => {
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of clients) { client.postMessage({ type: 'WORKCORE_SYNC_REQUEST' }); client.postMessage({ type: 'TITAN_FLUSH_OUTBOX' }); }
  })());
});

self.addEventListener('periodicsync', event => {
  if (!['workcore-outbox', 'titan-chatbot-outbox'].includes(event.tag)) return;
  event.waitUntil((async () => {
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of clients) { client.postMessage({ type: 'WORKCORE_SYNC_REQUEST' }); client.postMessage({ type: 'TITAN_FLUSH_OUTBOX' }); }
  })());
});
