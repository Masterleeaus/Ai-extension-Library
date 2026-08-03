(() => {
  'use strict';
  const ctx = () => window.TitanChatbotContext || {};
  const repos = () => window.TitanChatbotRepositories;
  const normalizer = raw => window.TitanTeamChatLocalFirst.normalizeConversation(raw, 'channel');
  const unwrapList = payload => payload?.data?.data ?? payload?.data ?? payload?.channels ?? payload ?? [];

  async function local(params = {}) {
    return window.TitanTeamChatLocalFirst.localConversations({ ...params, type: 'channel' });
  }

  async function list(params = {}) {
    const cached = await local(params);
    window.dispatchEvent(new CustomEvent('team-chat:channels', { detail: { records: cached, source: 'local', stale: !navigator.onLine } }));
    if (!navigator.onLine) return { data: cached, source: 'local', stale: true };
    try {
      const remote = await window.TitanBusinessChannelsNetwork.list(params);
      const records = [];
      for (const raw of unwrapList(remote)) records.push(await repos().conversations.mergeCanonical(normalizer(raw)));
      window.dispatchEvent(new CustomEvent('team-chat:channels', { detail: { records, source: 'server', stale: false } }));
      return { ...remote, data: records, source: 'server' };
    } catch (error) {
      return { data: cached, source: 'local', stale: true, error: error.message };
    }
  }

  async function create(payload) {
    const normalized = normalizer({ ...payload, conversation_type: 'channel', type: 'channel', sync_status: 'queued' });
    const local = await repos().conversations.upsert(normalized);
    if (!navigator.onLine) return { data: local, source: 'local', queued: true };
    try {
      const remote = await window.TitanBusinessChannelsNetwork.create({ ...payload, device_id: ctx().device_id });
      const merged = await repos().conversations.mergeCanonical({ ...normalizer(remote), local_uuid: local.local_uuid, sync_status: 'synced' });
      return { ...remote, data: merged, source: 'server' };
    } catch (error) {
      await repos().conversations.updateSyncStatus(local.local_uuid, error.status === 409 || error.status === 412 ? 'conflict' : 'queued', { last_error: error.message });
      return { data: local, source: 'local', queued: true, error: error.message };
    }
  }

  async function update(id, patch) {
    const conversation = await window.TitanTeamChat.resolveConversation(id);
    if (!conversation) throw new Error('Channel not found locally.');
    const local = await repos().conversations.upsert({ ...conversation, ...patch, sync_status: 'queued', updated_at: new Date().toISOString() });
    if (!navigator.onLine || !conversation.server_id) return { data: local, source: 'local', queued: true };
    try {
      const remote = await window.TitanBusinessChannelsNetwork.update(conversation.server_id, { ...patch, local_uuid: local.local_uuid, device_id: ctx().device_id });
      const merged = await repos().conversations.mergeCanonical({ ...normalizer(remote), local_uuid: local.local_uuid, sync_status: 'synced' });
      return { ...remote, data: merged, source: 'server' };
    } catch (error) {
      return { data: local, source: 'local', queued: true, error: error.message };
    }
  }

  function install() {
    if (!window.TitanBusinessChannels || !window.TitanTeamChatLocalFirst?.state.loaded) return false;
    if (window.TitanBusinessChannelsNetwork) return true;
    window.TitanBusinessChannelsNetwork = window.TitanBusinessChannels;
    const network = window.TitanBusinessChannelsNetwork;
    window.TitanBusinessChannels = Object.freeze({
      ...network,
      list,
      discover: params => list({ ...(params || {}), discover: 1 }),
      create,
      update,
      linked: (entityType, entityId) => list({ linked_entity_type: entityType, linked_entity_id: entityId }),
      local,
    });
    window.dispatchEvent(new CustomEvent('team-chat:channels-local-first-ready'));
    return true;
  }

  let attempts = 0;
  const timer = setInterval(() => {
    attempts += 1;
    if (install() || attempts > 100) clearInterval(timer);
  }, 50);
})();
