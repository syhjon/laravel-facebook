import assert from 'node:assert/strict';
import { afterEach, beforeEach, mock, test } from 'node:test';
import { ApiError, apiRequest } from '../../resources/js/services/http.js';

let navigationTargets;

beforeEach(() => {
    navigationTargets = [];
    globalThis.window = {
        applicationData: { routes: { login: '/login' } },
        location: { assign: (navigationTarget) => navigationTargets.push(navigationTarget) },
    };
});

afterEach(() => {
    mock.restoreAll();
    delete globalThis.window;
});

test('successful authentication preserves the dashboard redirect in JSON', async () => {
    mock.method(globalThis, 'fetch', async () => Response.json({ redirect: '/dashboard' }));

    const authenticationResponsePayload = await apiRequest('/login', { method: 'POST' });

    assert.equal(authenticationResponsePayload.redirect, '/dashboard');
    assert.deepEqual(navigationTargets, []);
});

for (const redirectPath of ['/dashboard', '/login']) {
    test(`an HTML redirect to ${redirectPath} navigates without returning an empty payload`, async () => {
        const redirectUrl = `http://127.0.0.1:8000${redirectPath}`;
        const redirectedResponse = new Response('<!DOCTYPE html><title>WeTalk</title>');
        Object.defineProperties(redirectedResponse, {
            redirected: { value: true },
            url: { value: redirectUrl },
        });
        mock.method(globalThis, 'fetch', async () => redirectedResponse);

        await assert.rejects(apiRequest('/login', { method: 'POST' }), ApiError);

        assert.deepEqual(navigationTargets, [redirectUrl]);
    });
}

test('an unexpected HTML response fails instead of allowing navigation to undefined', async () => {
    mock.method(globalThis, 'fetch', async () => new Response('<!DOCTYPE html><title>WeTalk</title>'));

    await assert.rejects(apiRequest('/login', { method: 'POST' }), (error) => {
        assert.ok(error instanceof ApiError);
        assert.equal(error.status, 200);
        return true;
    });

    assert.deepEqual(navigationTargets, []);
});

test('JSON validation errors preserve their message and field errors', async () => {
    const validationErrors = { email: ['Email 或密碼不正確。'] };
    mock.method(globalThis, 'fetch', async () => Response.json({
        message: '登入失敗',
        errors: validationErrors,
    }, { status: 422 }));

    await assert.rejects(apiRequest('/login', { method: 'POST' }), (error) => {
        assert.ok(error instanceof ApiError);
        assert.equal(error.message, '登入失敗');
        assert.equal(error.status, 422);
        assert.deepEqual(error.errors, validationErrors);
        return true;
    });

    assert.deepEqual(navigationTargets, []);
});

test('an expired session returns to the configured login page', async () => {
    mock.method(globalThis, 'fetch', async () => new Response(null, { status: 401 }));

    await assert.rejects(apiRequest('/feed'), (error) => {
        assert.ok(error instanceof ApiError);
        assert.equal(error.status, 401);
        return true;
    });

    assert.deepEqual(navigationTargets, ['/login']);
});
