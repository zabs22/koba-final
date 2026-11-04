const token = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/** JSON request with Laravel's CSRF token; throws with the server's message on failure. */
export async function json(url, { method = 'GET', body } = {}) {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(method !== 'GET' ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token() } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    if (response.status === 401 || response.status === 419) {
        window.location.reload();
        throw new Error('Your session has expired.');
    }

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Something went wrong. Please try again.');
    }
    return data;
}

/** Copies the current URL with some query values replaced (empty values removed). */
export function withQuery(base, params) {
    const url = new URL(base, window.location.origin);
    Object.entries(params).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') url.searchParams.delete(key);
        else url.searchParams.set(key, value);
    });
    return url.toString();
}
