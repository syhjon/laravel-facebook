export class ApiError extends Error {
    constructor(message, errors = {}, status = 0) {
        super(message);
        this.name = 'ApiError';
        this.errors = errors;
        this.status = status;
    }
}

export async function apiRequest(url, { method = 'GET', body = null, csrfToken = '' } = {}) {
    const headers = { Accept: 'application/json' };

    if (body !== null) {
        headers['Content-Type'] = 'application/json';
    }

    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers,
        body: body === null ? null : JSON.stringify(body),
    });

    if (response.status === 401) {
        window.location.assign(window.appData.routes.login);
        throw new ApiError('登入已逾時，正在返回登入頁。', {}, response.status);
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new ApiError(data.message ?? '操作失敗，請稍後再試。', data.errors ?? {}, response.status);
    }

    return data;
}

export function routeFor(pattern, postId) {
    return pattern.replace('{postId}', String(postId));
}
