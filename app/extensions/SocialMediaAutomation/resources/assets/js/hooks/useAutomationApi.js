const getData = () => window.__automationData ?? {};

function getHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getData().csrfToken ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        Accept: 'application/json',
    };
}

export async function saveAutomation(automationId, payload) {
    const url = automationId
        ? `/dashboard/user/social-media/automation/${automationId}`
        : '/dashboard/user/social-media/automation';
    const method = automationId ? 'PUT' : 'POST';

    const response = await fetch(url, {
        method,
        headers: getHeaders(),
        body: JSON.stringify(payload),
    });

    return response.json();
}

export async function toggleStatus(automationId) {
    const response = await fetch(`/dashboard/user/social-media/automation/${automationId}/status`, {
        method: 'PATCH',
        headers: getHeaders(),
    });

    return response.json();
}

export async function fetchPosts(platformId, search = '') {
    const params = new URLSearchParams({ platform_id: platformId, search });
    const url = getData().routes?.posts ?? '/dashboard/user/social-media/automation/posts';

    const response = await fetch(`${url}?${params}`);
    const data = await response.json();

    return data.data ?? [];
}

export async function uploadImage(file) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('_token', getData().csrfToken ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '');

    const url = getData().routes?.uploadImage ?? '/dashboard/user/social-media/automation/upload-image';
    const response = await fetch(url, { method: 'POST', body: formData });

    return response.json();
}
