(function (global) {
    'use strict';
    const root = '/dashboard/chatbot/inbox/team/channels';
    async function request(url, options = {}) {
        const headers = {'Accept':'application/json','Content-Type':'application/json', ...(options.headers || {})};
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf) headers['X-CSRF-TOKEN'] = csrf;
        const fetchOptions = {credentials:'same-origin', ...options, headers};
        if (options.body !== undefined && typeof options.body !== 'string') fetchOptions.body = JSON.stringify(options.body);
        const response = await fetch(url, fetchOptions);
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw Object.assign(new Error(payload.message || `Channel request failed (${response.status})`), {status: response.status, payload});
        return payload;
    }
    const api = {
        list(params) { const q = new URLSearchParams(params || {}); return request(`${root}?${q}`); },
        discover(params) { return api.list(Object.assign({}, params || {}, {discover: 1})); },
        create(payload) { return request(root, {method:'POST', body:payload}); },
        update(id, payload) { return request(`${root}/${id}`, {method:'PATCH', body:payload}); },
        join(id) { return request(`${root}/${id}/join`, {method:'POST', body:{}}); },
        archive(id, archived) { return request(`${root}/${id}/archive`, {method:'PATCH', body:{archived:!!archived}}); },
        linked(entityType, entityId) { return api.list({linked_entity_type: entityType, linked_entity_id: entityId}); }
    };
    global.TitanBusinessChannels = Object.freeze(api);
})(window);
