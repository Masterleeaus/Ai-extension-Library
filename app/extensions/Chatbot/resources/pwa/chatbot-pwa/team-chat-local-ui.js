(() => {
  'use strict';

  const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
  const statusLabel = status => ({
    'local-only': 'Local', queued: 'Queued', sending: 'Sending', synced: 'Synced', failed: 'Failed',
    'needs-review': 'Needs review', conflict: 'Conflict', cancelled: 'Cancelled',
    'deleted-locally': 'Deleted locally', 'deleted-remotely': 'Deleted remotely',
  }[status] || status || 'Local');
  const parseIds = value => [...new Set(String(value || '').split(',').map(item => Number(item.trim())).filter(Number.isInteger))];

  function setWorkspace(name) {
    document.querySelectorAll('[data-chatbot-workspace]').forEach(element => element.classList.toggle('hidden', element.dataset.chatbotWorkspace !== name));
    document.querySelectorAll('[data-chatbot-workspace-tab]').forEach(button => {
      const active = button.dataset.chatbotWorkspaceTab === name;
      button.classList.toggle('lqd-btn-primary', active);
      button.classList.toggle('lqd-btn-ghost', !active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    if (name === 'team') window.TitanTeamChat?.list();
    if (name === 'channels') window.TitanBusinessChannels?.list();
  }

  function showModal(selector, show = true) {
    const modal = document.querySelector(selector);
    if (!modal) return;
    modal.classList.toggle('hidden', !show);
    modal.classList.toggle('flex', show);
    if (show) modal.querySelector('input, select, textarea')?.focus();
  }

  function showError(selector, error) {
    const target = document.querySelector(selector);
    if (!target) return;
    const validation = error?.payload?.errors;
    target.textContent = validation ? Object.values(validation).flat().join(' ') : (error?.message || 'Request failed.');
    target.classList.remove('hidden');
  }

  function renderConversationList(records) {
    const target = document.querySelector('[data-team-chat-list]');
    if (!target) return;
    target.innerHTML = records.length ? records.map(record => `
      <button type="button" class="block w-full border-b p-3 text-left hover:bg-foreground/5" data-team-local-conversation="${escapeHtml(record.local_uuid)}">
        <span class="flex items-center justify-between gap-2"><strong>${escapeHtml(record.title)}</strong><small>${escapeHtml(statusLabel(record.sync_status))}</small></span>
        <span class="mt-1 block truncate text-xs opacity-60">${escapeHtml(record.last_message || record.description || record.conversation_type)}</span>
      </button>`).join('') : '<p class="p-4 text-sm opacity-60">No team conversations yet.</p>';
  }

  function renderChannelList(records, discovering = false) {
    const target = document.querySelector('[data-team-channel-list]');
    if (!target) return;
    target.innerHTML = records.length ? records.map(record => `
      <article class="mb-2 rounded-lg border p-3" data-team-channel-record="${escapeHtml(record.local_uuid)}">
        <button type="button" class="block w-full text-left" data-team-channel-select="${escapeHtml(record.local_uuid)}">
          <span class="flex items-center justify-between gap-2"><strong># ${escapeHtml(record.title)}</strong><small>${escapeHtml(record.visibility || 'private')}</small></span>
          <span class="mt-1 block text-xs opacity-60">${escapeHtml(record.description || record.category || 'Channel')}</span>
        </button>
        ${discovering && record.server_id ? `<button type="button" class="mt-2 text-xs underline" data-team-channel-join="${escapeHtml(record.server_id)}">Join</button>` : ''}
      </article>`).join('') : '<p class="p-2 text-sm opacity-60">No channels found.</p>';
  }

  function renderMessages(detail) {
    const target = document.querySelector('[data-team-chat-thread]');
    if (!target) return;
    const conversation = detail.conversation || {};
    const records = detail.records || [];
    target.innerHTML = `
      <header class="border-b p-4"><strong>${escapeHtml(conversation.title || 'Conversation')}</strong>${detail.stale ? '<span class="ml-2 text-xs opacity-60">Offline data</span>' : ''}</header>
      <div class="max-h-[calc(100vh-190px)] space-y-3 overflow-y-auto p-4" data-team-local-message-list>
        ${records.map(message => `<article class="rounded-lg border p-3" data-local-message="${escapeHtml(message.local_uuid)}">
          <div class="text-sm">${escapeHtml(message.body || message.message || message.content)}</div>
          <footer class="mt-2 flex items-center gap-2 text-xs opacity-60"><span>${escapeHtml(message.sender_name || '')}</span><span>${escapeHtml(statusLabel(message.sync_status))}</span>
          ${['queued','failed','needs-review'].includes(message.sync_status) ? `<button type="button" class="underline" data-team-message-edit="${escapeHtml(message.local_uuid)}">Edit</button><button type="button" class="underline" data-team-message-cancel="${escapeHtml(message.local_uuid)}">Cancel</button>` : ''}
          ${['failed','needs-review','conflict'].includes(message.sync_status) ? `<button type="button" class="underline" data-team-message-retry="${escapeHtml(message.local_uuid)}">Retry</button>` : ''}</footer>
        </article>`).join('') || '<p class="text-sm opacity-60">No messages stored locally.</p>'}
      </div>
      <form class="border-t p-3" data-team-local-compose><div class="flex gap-2"><textarea class="form-control min-h-12" name="message" required></textarea><button class="lqd-btn lqd-btn-primary" type="submit">Send</button></div><p class="mt-2 hidden text-xs text-red-600" data-team-compose-error></p></form>`;
    window.TitanTeamChat?.bindRealtime(conversation);
  }

  function renderChannelThread(conversation) {
    const target = document.querySelector('[data-team-channel-thread]');
    if (!target) return;
    target.innerHTML = `<div class="rounded-xl border p-5"><div class="flex items-center justify-between"><h3 class="font-semibold"># ${escapeHtml(conversation.title)}</h3><span class="text-xs opacity-60">${escapeHtml(statusLabel(conversation.sync_status))}</span></div><p class="mt-2 text-sm opacity-70">${escapeHtml(conversation.description || '')}</p><dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="opacity-60">Visibility</dt><dd>${escapeHtml(conversation.visibility || 'private')}</dd></div><div><dt class="opacity-60">Posting</dt><dd>${escapeHtml(conversation.posting_policy || 'members')}</dd></div><div><dt class="opacity-60">Linked record</dt><dd>${escapeHtml(conversation.linked_entity_type || '—')} ${escapeHtml(conversation.linked_entity_id || '')}</dd></div><div><dt class="opacity-60">Status</dt><dd>${escapeHtml(statusLabel(conversation.sync_status))}</dd></div></dl><button type="button" class="lqd-btn lqd-btn-primary mt-5" data-team-open-channel-chat="${escapeHtml(conversation.local_uuid)}">Open conversation</button></div>`;
  }

  async function selectConversation(localUuid) {
    const conversation = await window.TitanTeamChat.resolveConversation(localUuid);
    if (!conversation) return;
    window.TitanTeamChatLocalFirst.state.activeConversationUuid = conversation.local_uuid;
    const result = await window.TitanTeamChat.messages(conversation.local_uuid);
    renderMessages({ conversation, records: result.data || [], stale: result.stale });
  }

  async function performSearch(query) {
    if (!query.trim()) {
      const result = await window.TitanTeamChat.list();
      renderConversationList(result.data || []);
      return;
    }
    const result = await window.TitanTeamChat.localSearch(query);
    renderConversationList(result.conversations || []);
  }

  async function createGroup(form) {
    const type = form.elements.type.value;
    const userIds = parseIds(form.elements.user_ids.value);
    const payload = { type, user_ids: userIds };
    if (type === 'group') payload.name = form.elements.name.value.trim();
    if (!userIds.length || (type === 'group' && !payload.name)) throw new Error('Provide a group name and at least one valid user ID.');
    const result = await window.TitanTeamChat.create(payload);
    showModal('[data-team-group-modal]', false);
    form.reset();
    await window.TitanTeamChat.list();
    const conversation = result.data || result;
    if (conversation.local_uuid) await selectConversation(conversation.local_uuid);
  }

  async function createChannel(form) {
    const payload = Object.fromEntries(new FormData(form).entries());
    payload.user_ids = parseIds(payload.user_ids);
    payload.is_announcement = Boolean(form.elements.is_announcement.checked);
    if (!payload.linked_entity_type) delete payload.linked_entity_type;
    if (!payload.linked_entity_id) delete payload.linked_entity_id;
    if (!payload.category) delete payload.category;
    const result = await window.TitanBusinessChannels.create(payload);
    showModal('[data-team-channel-modal]', false);
    form.reset();
    await window.TitanBusinessChannels.list();
    const channel = result.data || result;
    if (channel.local_uuid) renderChannelThread(channel);
  }

  function bind() {
    const search = document.querySelector('[data-team-chat-local-search]');
    let searchTimer;
    search?.addEventListener('input', event => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => performSearch(event.target.value), 150);
    });

    document.addEventListener('click', async event => {
      const tab = event.target.closest('[data-chatbot-workspace-tab]');
      if (tab) return setWorkspace(tab.dataset.chatbotWorkspaceTab);
      if (event.target.closest('[data-team-chat-new]')) return showModal('[data-team-group-modal]');
      if (event.target.closest('[data-team-channel-new]')) return showModal('[data-team-channel-modal]');
      if (event.target.closest('[data-team-modal-close]')) return showModal(event.target.closest('[data-team-group-modal]') ? '[data-team-group-modal]' : '[data-team-channel-modal]', false);
      const discover = event.target.closest('[data-team-channel-discover]');
      if (discover) {
        const result = await window.TitanBusinessChannels.discover();
        return renderChannelList(result.data || [], true);
      }
      const conversationButton = event.target.closest('[data-team-local-conversation]');
      if (conversationButton) return selectConversation(conversationButton.dataset.teamLocalConversation);
      const channelButton = event.target.closest('[data-team-channel-select]');
      if (channelButton) {
        const conversation = await window.TitanTeamChat.resolveConversation(channelButton.dataset.teamChannelSelect);
        if (conversation) renderChannelThread(conversation);
        return;
      }
      const openChannel = event.target.closest('[data-team-open-channel-chat]');
      if (openChannel) { setWorkspace('team'); return selectConversation(openChannel.dataset.teamOpenChannelChat); }
      const join = event.target.closest('[data-team-channel-join]');
      if (join) { await window.TitanBusinessChannels.join(join.dataset.teamChannelJoin); const result = await window.TitanBusinessChannels.list(); return renderChannelList(result.data || []); }
      const edit = event.target.closest('[data-team-message-edit]');
      if (edit) {
        const current = await window.TitanChatbotRepositories.messages.findByLocalUuid(edit.dataset.teamMessageEdit);
        const message = prompt('Edit queued message', current?.body || current?.message || current?.content || '');
        if (message !== null) await window.TitanTeamChat.editQueued(current.local_uuid, { body: message, message, content: message });
        return selectConversation(window.TitanTeamChatLocalFirst.state.activeConversationUuid);
      }
      const cancel = event.target.closest('[data-team-message-cancel]');
      if (cancel) await window.TitanTeamChat.cancelQueued(cancel.dataset.teamMessageCancel);
      const retry = event.target.closest('[data-team-message-retry]');
      if (retry) await window.TitanTeamChat.retryFailed(retry.dataset.teamMessageRetry);
      if (cancel || retry) return selectConversation(window.TitanTeamChatLocalFirst.state.activeConversationUuid);
    });

    document.addEventListener('submit', async event => {
      const compose = event.target.closest('[data-team-local-compose]');
      if (compose) {
        event.preventDefault();
        const textarea = compose.elements.message;
        const message = textarea.value.trim();
        if (!message) return;
        textarea.value = '';
        try {
          await window.TitanTeamChat.send(window.TitanTeamChatLocalFirst.state.activeConversationUuid, { body: message, message, content: message });
          await selectConversation(window.TitanTeamChatLocalFirst.state.activeConversationUuid);
        } catch (error) {
          const target = compose.querySelector('[data-team-compose-error]');
          target.textContent = error.message;
          target.classList.remove('hidden');
        }
        return;
      }
      const group = event.target.closest('[data-team-group-form]');
      if (group) { event.preventDefault(); try { await createGroup(group); } catch (error) { showError('[data-team-group-error]', error); } return; }
      const channel = event.target.closest('[data-team-channel-form]');
      if (channel) { event.preventDefault(); try { await createChannel(channel); } catch (error) { showError('[data-team-channel-error]', error); } }
    });

    document.addEventListener('change', event => {
      if (event.target.matches('[data-team-group-form] [name="type"]')) document.querySelector('[data-team-group-name-field]')?.classList.toggle('hidden', event.target.value === 'direct');
    });

    window.addEventListener('team-chat:conversations', event => renderConversationList(event.detail.records || []));
    window.addEventListener('team-chat:channels', event => renderChannelList(event.detail.records || []));
    window.addEventListener('team-chat:messages', event => renderMessages(event.detail));
    window.addEventListener('team-chat:message-upserted', () => selectConversation(window.TitanTeamChatLocalFirst.state.activeConversationUuid));
    window.addEventListener('online', () => { document.querySelector('[data-chatbot-workspace-status]').textContent = 'Online'; window.TitanTeamChat?.list(); });
    window.addEventListener('offline', () => { document.querySelector('[data-chatbot-workspace-status]').textContent = 'Offline'; document.querySelector('[data-team-chat-stale]')?.removeAttribute('hidden'); });
    window.addEventListener('online', () => document.querySelector('[data-team-chat-stale]')?.setAttribute('hidden', 'hidden'));

    setWorkspace('customer');
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind, { once: true }); else bind();
})();
