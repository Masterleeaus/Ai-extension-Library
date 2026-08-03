(() => {
  'use strict';
  const prefix = 'titan-chatbot-ui:';
  const local = window.localStorage;
  const parseContext = key => {
    const parts = String(key).split(':');
    return { conversationId: parts[2] || null, messageId: parts.slice(3).join(':') || null };
  };
  window.TitanGenerativeUiStorage = {
    getItem(key) { return local.getItem(key); },
    setItem(key, value) {
      local.setItem(key, value);
      if (String(key).startsWith(prefix) && window.TitanOfflineStore?.saveUiState) {
        let parsed = value;
        try { parsed = JSON.parse(value); } catch (_) {}
        window.TitanOfflineStore.saveUiState(key, parsed, parseContext(key)).catch(() => {});
      }
    },
    removeItem(key) {
      local.removeItem(key);
      if (String(key).startsWith(prefix)) window.TitanOfflineStore?.deleteUiState?.(key)?.catch?.(() => {});
    }
  };
})();
