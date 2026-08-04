(() => {
    'use strict';

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                ...(options.headers || {}),
            },
        });
        const payload = await response.json().catch(() => ({ ok: false, error: { code: 'MAPS_INVALID_RESPONSE' } }));
        if (!response.ok) {
            throw new Error(payload?.error?.message || 'Titan Maps request failed.');
        }
        return payload;
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-titan-maps-search-form]');
        if (!form) return;
        event.preventDefault();
        const status = form.querySelector('[data-titan-maps-form-status]');
        const data = Object.fromEntries(new FormData(form).entries());
        data.open_now = form.elements.open_now?.checked === true;
        for (const field of ['maximum_results', 'radius_metres', 'minimum_rating', 'latitude', 'longitude']) {
            if (data[field] === '') delete data[field];
            else if (field in data) data[field] = Number(data[field]);
        }
        try {
            if (status) status.textContent = 'Creating search…';
            const payload = await request(form.action, { method: 'POST', body: JSON.stringify(data) });
            if (status) status.textContent = 'Search queued.';
            document.dispatchEvent(new CustomEvent('titan-maps:search-created', { detail: payload.data }));
        } catch (error) {
            if (status) status.textContent = error.message;
        }
    });

    document.addEventListener('click', async (event) => {
        const cancel = event.target.closest('[data-titan-maps-cancel-search]');
        if (cancel) {
            const payload = await request(cancel.dataset.endpoint, { method: 'POST', body: '{}' });
            document.dispatchEvent(new CustomEvent('titan-maps:search-cancelled', { detail: payload.data }));
            return;
        }

        const action = event.target.closest('[data-titan-maps-candidate-action]');
        if (!action) return;
        const card = action.closest('[data-candidate-id]');
        document.dispatchEvent(new CustomEvent('titan-maps:candidate-action', {
            detail: { candidateId: card?.dataset.candidateId, action: action.dataset.titanMapsCandidateAction },
        }));
    });

    window.TitanMapsIntelligence = { request };
})();
