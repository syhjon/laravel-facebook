export class ApiError extends Error {
    constructor(message, errors = {}, status = 0) {
        super(message);
        this.name = 'ApiError';
        this.errors = errors;
        this.status = status;
    }
}

export async function apiRequest(endpointUrl, { method = 'GET', requestBody = null, csrfToken = '' } = {}) {
    const requestHeaders = { Accept: 'application/json' };
    const requestContainsFormData = requestBody instanceof FormData;

    if (requestBody !== null && !requestContainsFormData) {
        requestHeaders['Content-Type'] = 'application/json';
    }

    if (csrfToken) {
        requestHeaders['X-CSRF-TOKEN'] = csrfToken;
    }

    const httpResponse = await fetch(endpointUrl, {
        method,
        credentials: 'same-origin',
        headers: requestHeaders,
        body: requestBody === null || requestContainsFormData ? requestBody : JSON.stringify(requestBody),
    });

    if (httpResponse.status === 401) {
        window.location.assign(window.applicationData.routes.login);
        throw new ApiError('登入已逾時，正在返回登入頁。', {}, httpResponse.status);
    }

    if (httpResponse.redirected) {
        window.location.assign(httpResponse.url);
        throw new ApiError('正在重新導向，請稍候。', {}, httpResponse.status);
    }

    const responsePayload = await httpResponse.json().catch(() => {
        throw new ApiError('伺服器回應格式不正確，請重新整理頁面後再試。', {}, httpResponse.status);
    });

    if (!httpResponse.ok) {
        throw new ApiError(
            responsePayload.message ?? '操作失敗，請稍後再試。',
            responsePayload.errors ?? {},
            httpResponse.status,
        );
    }

    return responsePayload;
}

export function buildPostRoute(routePattern, postId) {
    return routePattern.replace('{postId}', String(postId));
}
