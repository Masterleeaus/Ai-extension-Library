(() => {
  'use strict';
  const root = document.getElementById('chatbot-pwa-device-status');
  if (!root) return;
  const pending = root.querySelector('[data-pwa-pending]');
  const sync = root.querySelector('[data-pwa-sync]');

  async function refresh() {
    try {
      const items = await window.TitanOutbox.list();
      const count = items.filter(item => item.status !== 'conflict').length;
      pending.textContent = String(count);
      root.dataset.visible = count > 0 || !navigator.onLine ? 'true' : 'false';
      root.dataset.online = navigator.onLine ? 'true' : 'false';
    } catch (_) {}
  }

  sync?.addEventListener('click', async () => {
    sync.disabled = true;
    try { await window.TitanOutbox.flush(); } finally { sync.disabled = false; refresh(); }
  });
  ['online', 'offline', 'chatbot:outbox-changed', 'chatbot:sync-finished'].forEach(name => window.addEventListener(name, refresh));
  refresh();
})();
