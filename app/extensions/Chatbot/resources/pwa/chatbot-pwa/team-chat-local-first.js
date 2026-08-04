(() => {
  'use strict';

  const state = {
    activeConversationUuid: null,
    loaded: false,
    stale: false,
    unsubscribe: null,
  };

  const repos = () => window.TitanChatbotRepositories;
  const services = () => window.TitanChatbotLocalServices;
  const ctx = () => window.TitanChatbotContext || {};
  const now = () => new Date().toISOString();
  const unwrap = payload => payload?.data ?? payload?.conversation ?? payload?.message ?? payload;
  const listOf = payload => payload?.data?.data ?? payload?.data ?? payload?.conversations ?? payload?.messages ?? payload ?? [];

  function normalizeConversation(raw, fallbackType = 'group') {
    const item = unwrap(raw) || {};
    const type = item.conversation_type || item.type || fallbackType;
    return {
      local_uuid: item.local_uuid || item.uuid || crypto.randomUUID(),
      server_id: item.server_id ?? item.id ?? null,
      tenant_id: item.tenant_id ?? ctx().tenant_id ?? null,
      user_id: item.user_id ?? ctx().user_id ?? null,
      device_id: item.device_id ?? ctx().device_id ?? null,
      entity_type: 'team-conversation',
      conversation_type: type,
      channel: type,
      title: item.title || item.name || item.channel_name || 'Untitled conversation',
      slug: item.slug || null,
      description: item.description || '',
      visibility: item.visibility || null,
      posting_policy: item.posting_policy || 'all_members',
      linked_entity_type: item.linked_entity_type || null,
      linked_entity_id: item.linked_entity_id || null,
      avatar_url: item.avatar_url || item.avatar || null,
      last_message: item.last_message?.message || item.last_message || '',
      unread_count: Number(item.unread_count || 0),
      pinned: Boolean(item.pinned),
      archived: Boolean(item.archived_at || item.archived),
      version: Number(item.version || 0),
      sync_sequence: Number(item.sync_sequence || 0),
      sync_status: item.sync_status || (item.id ? 'synced' : 'local-only'),
      created_at: item.created_at || now(),
      updated_at: item.updated_at || now(),
      deleted_at: item.deleted_at || null,
      last_synced_at: item.last_synced_at || (item.id ? now() : null),
      payload: item,
    };
  }

  function normalizeMessage(raw, conversationUuid) {
    const item = unwrap(raw) || {};
    return {
      local_uuid: item.local_uuid || item.uuid || crypto.randomUUID(),
      server_id: item.server_id ?? item.id ?? null,
      tenant_id: item.tenant_id ?? ctx().tenant_id ?? null,
      user_id: item.user_id ?? item.sender_id ?? ctx().user_id ?? null,
      device_id: item.device_id ?? ctx().device_id ?? null,
      entity_type: 'team-message',
      conversation_uuid: conversationUuid || item.conversation_uuid,
      message: item.body || item.message || item.content || '',
      content: item.body || item.content || item.message || '',
      sender_name: item.sender_name || item.user?.name || '',
      role: item.role || (String(item.user_id ?? item.sender_id) === String(ctx().user_id) ? 'assistant' : 'user'),
      version: Number(item.version || 0),
      sync_sequence: Number(item.sync_sequence || 0),
      sync_status: item.sync_status || (item.id ? 'synced' : 'local-only'),
      created_at: item.created_at || now(),
      updated_at: item.updated_at || item.created_at || now(),
      deleted_at: item.deleted_at || null,
      last_synced_at: item.last_synced_at || (item.id ? now() : null),
      payload: item,
    };
  }

  async function cacheConversations(payload, fallbackType) {
    const records = [];
    for (const raw of listOf(payload)) {
      const normalized = normalizeConversation(raw, fallbackType);
      records.push(await repos().conversations.mergeCanonical(normalized));
    }
    return records;
  }

  async function localConversations(filters = {}) {
    return repos().conversations.filter(record => {
      const type = record.conversation_type || record.channel;
      const teamType = ['direct', 'group', 'channel'].includes(type);
      if (!teamType || record.archived && !filters.archived) return false;
      if (filters.type && type !== filters.type) return false;
      if (filters.linked_entity_type && record.linked_entity_type !== filters.linked_entity_type) return false;
      if (filters.linked_entity_id && String(record.linked_entity_id) !== String(filters.linked_entity_id)) return false;
      return true;
    }).then(items => items.sort((a, b) => Number(b.pinned) - Number(a.pinned) || String(b.updated_at).localeCompare(String(a.updated_at))));
  }

  async function listTeam(params = {}) {
    const cached = await localConversations(params);
    window.dispatchEvent(new CustomEvent('team-chat:conversations', { detail: { records: cached, source: 'local', stale: !navigator.onLine } }));
    if (!navigator.onLine) return { data: cached, source: 'local', stale: true };
    try {
      const remote = await window.TitanTeamChatNetwork.list(params);
      const records = await cacheConversations(remote, params.type || 'group');
      state.stale = false;
      window.dispatchEvent(new CustomEvent('team-chat:conversations', { detail: { records, source: 'server', stale: false } }));
      return { ...remote, data: records, source: 'server' };
    } catch (error) {
      state.stale = true;
      window.dispatchEvent(new CustomEvent('team-chat:network-error', { detail: { error, fallback: cached } }));
      return { data: cached, source: 'local', stale: true, error: error.message };
    }
  }

  async function listMessages(conversationId, params = {}) {
    const conversation = await resolveConversation(conversationId);
    const uuid = conversation?.local_uuid || String(conversationId);
    const cached = await repos().messages.listByConversation(uuid);
    window.dispatchEvent(new CustomEvent('team-chat:messages', { detail: { conversation, records: cached, source: 'local', stale: !navigator.onLine } }));
    if (!navigator.onLine || !conversation?.server_id) return { data: cached, source: 'local', stale: true };
    try {
      const remote = await window.TitanTeamChatNetwork.messages(conversation.server_id, params);
      const records = [];
      for (const raw of listOf(remote)) records.push(await repos().messages.mergeCanonical(normalizeMessage(raw, uuid)));
      const ordered = await repos().messages.listByConversation(uuid);
      window.dispatchEvent(new CustomEvent('team-chat:messages', { detail: { conversation, records: ordered, source: 'server', stale: false } }));
      return { ...remote, data: ordered, source: 'server' };
    } catch (error) {
      return { data: cached, source: 'local', stale: true, error: error.message };
    }
  }

  async function createConversation(payload) {
    const local = await services().createLocalConversation({
      ...normalizeConversation(payload, payload.conversation_type || payload.type || 'group'),
      entity_type: 'team-conversation',
      sync_status: 'queued',
    });
    if (!navigator.onLine) return { data: local, source: 'local' };
    try {
      const remote = await window.TitanTeamChatNetwork.create({ ...payload, local_uuid: local.local_uuid, device_id: ctx().device_id });
      const merged = await repos().conversations.mergeCanonical({ ...normalizeConversation(remote, local.conversation_type), local_uuid: local.local_uuid, sync_status: 'synced' });
      await window.TitanOutbox?.cancelForEntity?.(local.local_uuid);
      return { ...remote, data: merged, source: 'server' };
    } catch (error) {
      await repos().conversations.updateSyncStatus(local.local_uuid, error.status === 409 || error.status === 412 ? 'conflict' : 'queued');
      return { data: local, source: 'local', queued: true, error: error.message };
    }
  }

  async function sendMessage(conversationId, payload) {
    const conversation = await resolveConversation(conversationId);
    if (!conversation) throw new Error('Conversation not found locally.');
    const local = await services().sendLocalMessage({
      conversation_uuid: conversation.local_uuid,
      entity_type: 'team-message',
      body: payload.body || payload.message || payload.content || '',
      message: payload.body || payload.message || payload.content || '',
      content: payload.body || payload.content || payload.message || '',
      sender_name: payload.sender_name || '',
      role: 'assistant',
      payload: { ...payload, conversation_local_uuid: conversation.local_uuid },
      dependencies: conversation.server_id ? [] : [conversation.local_uuid],
    });
    if (!navigator.onLine || !conversation.server_id) return { data: local, source: 'local', queued: true };
    try {
      const remote = await window.TitanTeamChatNetwork.send(conversation.server_id, { ...payload, body: payload.body || payload.message || payload.content || '', local_uuid: local.local_uuid, device_id: ctx().device_id });
      const merged = await repos().messages.mergeCanonical({ ...normalizeMessage(remote, conversation.local_uuid), local_uuid: local.local_uuid, sync_status: 'synced' });
      await window.TitanOutbox?.cancelForEntity?.(local.local_uuid);
      return { ...remote, data: merged, source: 'server' };
    } catch (error) {
      const status = error.status === 409 || error.status === 412 ? 'conflict' : 'queued';
      await repos().messages.updateSyncStatus(local.local_uuid, status, { last_error: error.message });
      return { data: { ...local, sync_status: status }, source: 'local', queued: true, error: error.message };
    }
  }

  async function resolveConversation(id) {
    const byLocal = await repos().conversations.findByLocalUuid(String(id));
    return byLocal || repos().conversations.findByServerId(id);
  }

  async function search(query, limit = 50) {
    const [conversations, messages] = await Promise.all([
      repos().conversations.search(query, limit),
      repos().messages.search(query, limit),
    ]);
    return {
      conversations: conversations.filter(item => ['direct', 'group', 'channel'].includes(item.conversation_type || item.channel)),
      messages: messages.filter(item => item.entity_type === 'team-message' || item.payload?.entity_type === 'team-message'),
    };
  }

  function bindRealtime(conversation) {
    state.unsubscribe?.();
    if (!conversation || !navigator.onLine) return;
    state.unsubscribe = window.TitanTeamChatNetwork.subscribe({
      tenantId: conversation.tenant_id || ctx().tenant_id,
      conversationUuid: conversation.local_uuid,
      onEvent: async (type, payload) => {
        if (type === 'message-created') {
          const message = await repos().messages.mergeCanonical(normalizeMessage(payload.message || payload, conversation.local_uuid));
          window.dispatchEvent(new CustomEvent('team-chat:message-upserted', { detail: { message } }));
        }
        if (type === 'conversation-updated') {
          const record = await repos().conversations.mergeCanonical(normalizeConversation(payload.conversation || payload, conversation.conversation_type));
          window.dispatchEvent(new CustomEvent('team-chat:conversation-upserted', { detail: { conversation: record } }));
        }
      },
    });
  }

  function install() {
    if (!window.TitanTeamChat || !window.TitanChatbotRepositories || !window.TitanChatbotLocalServices) return false;
    if (state.loaded) return true;
    window.TitanTeamChatNetwork = window.TitanTeamChat;
    const network = window.TitanTeamChatNetwork;
    window.TitanTeamChat = Object.freeze({
      ...network,
      list: listTeam,
      create: createConversation,
      messages: listMessages,
      send: sendMessage,
      localSearch: search,
      localConversations,
      bindRealtime,
      resolveConversation,
      editQueued: (uuid, patch) => services().editQueuedMessage(uuid, patch),
      cancelQueued: uuid => services().cancelQueuedMessage(uuid),
      retryFailed: uuid => services().retryFailedMessage(uuid),
    });
    state.loaded = true;
    window.dispatchEvent(new CustomEvent('team-chat:local-first-ready'));
    return true;
  }

  let attempts = 0;
  const timer = setInterval(() => {
    attempts += 1;
    if (install() || attempts > 100) clearInterval(timer);
  }, 50);

  window.TitanTeamChatLocalFirst = Object.freeze({ install, state, normalizeConversation, normalizeMessage, search, localConversations });
})();
