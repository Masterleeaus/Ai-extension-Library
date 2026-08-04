(function (global) {
    'use strict';

    const listeners = new Map();

    const api = {
        async list(params = {}) {
            const query = new URLSearchParams(params).toString();
            return request(`/dashboard/chatbot/inbox/team/conversations${query ? `?${query}` : ''}`);
        },
        async create(payload) {
            return request('/dashboard/chatbot/inbox/team/conversations', { method: 'POST', body: payload });
        },
        async update(conversationId, payload) {
            return request(`/dashboard/chatbot/inbox/team/conversations/${conversationId}`, { method: 'PATCH', body: payload });
        },
        async messages(conversationId, params = {}) {
            const query = new URLSearchParams(params).toString();
            return request(`/dashboard/chatbot/inbox/team/conversations/${conversationId}/messages${query ? `?${query}` : ''}`);
        },
        async send(conversationId, payload) {
            return request(`/dashboard/chatbot/inbox/team/conversations/${conversationId}/messages`, { method: 'POST', body: payload });
        },
        async markRead(messageId) {
            return request(`/dashboard/chatbot/inbox/team/messages/${messageId}/read`, { method: 'POST' });
        },
        async typing(conversationId, typing) {
            return request(`/dashboard/chatbot/inbox/team/conversations/${conversationId}/typing`, { method: 'POST', body: { typing: Boolean(typing) } });
        },
        async addParticipant(conversationId, userId, role = 'member') {
            return request(`/dashboard/chatbot/inbox/team/conversations/${conversationId}/participants`, { method: 'POST', body: { user_id: userId, role } });
        },
        async changeParticipantRole(conversationId, participantId, role) {
            return request(`/dashboard/chatbot/inbox/team/conversations/${conversationId}/participants/${participantId}`, { method: 'PATCH', body: { role } });
        },
        async removeParticipant(conversationId, participantId) {
            return request(`/dashboard/chatbot/inbox/team/conversations/${conversationId}/participants/${participantId}`, { method: 'DELETE' });
        },
        subscribe({ tenantId, conversationUuid, echo = global.Echo, onEvent }) {
            if (!echo || !tenantId || !conversationUuid || typeof onEvent !== 'function') return () => {};
            const key = `${tenantId}:${conversationUuid}`;
            const channelName = `chatbot.team.${tenantId}.conversation.${conversationUuid}`;
            const channel = echo.private(channelName)
                .listen('.team.message.created', payload => onEvent('message-created', payload))
                .listen('.team.message.read', payload => onEvent('message-read', payload))
                .listen('.team.user.typing', payload => onEvent('typing', payload))
                .listen('.team.conversation.updated', payload => onEvent('conversation-updated', payload));
            listeners.set(key, { echo, channelName });
            return () => api.unsubscribe(tenantId, conversationUuid);
        },
        unsubscribe(tenantId, conversationUuid) {
            const key = `${tenantId}:${conversationUuid}`;
            const entry = listeners.get(key);
            if (entry) {
                entry.echo.leave(entry.channelName);
                listeners.delete(key);
            }
        },
    };

    async function request(url, options = {}) {
        const headers = { Accept: 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) };
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf) headers['X-CSRF-TOKEN'] = csrf;
        const fetchOptions = { credentials: 'same-origin', ...options, headers };
        if (options.body !== undefined) fetchOptions.body = JSON.stringify(options.body);
        const response = await fetch(url, fetchOptions);
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw Object.assign(new Error(payload.message || 'Team chat request failed.'), { status: response.status, payload });
        return payload;
    }

    global.TitanTeamChat = Object.freeze(api);
})(window);
