import { test, expect } from '../../fixtures';
import { setApiEnabled } from '../../utils/test-api';

const SUBDOMAIN = 'stdparteitag';
const CONSULTATION = 'std-parteitag';

test.describe('API: User login and JWT lifecycle', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('login denied when API disabled', async ({ request }) => {
        const response = await request.post(`/${SUBDOMAIN}/rest/user/login`, {
            data: { username: 'testuser@example.org', password: 'testuser' },
            headers: { 'Content-Type': 'application/json' },
        });
        expect(response.status()).toBe(403);
        expect(await response.json()).toEqual({
            success: false,
            message: 'Public API disabled',
        });
    });

    test('login succeeds with valid credentials and authorizes /rest/user', async ({ request }) => {
        await setApiEnabled(request, true, SUBDOMAIN, CONSULTATION);

        const loginResponse = await request.post(`/${SUBDOMAIN}/rest/user/login`, {
            data: { username: 'testuser@example.org', password: 'testuser' },
            headers: { 'Content-Type': 'application/json' },
        });
        expect(loginResponse.status()).toBe(200);

        const body = await loginResponse.json();
        expect(Object.keys(body)).toHaveLength(2);
        expect(typeof body.token).toBe('string');
        expect(body.token.length).toBeGreaterThan(0);
        expect(typeof body.exp).toBe('number');
        expect(body.exp).toBeGreaterThan(Math.floor(Date.now() / 1000));

        const userResponse = await request.get(`/${SUBDOMAIN}/rest/user`, {
            headers: { Authorization: `Bearer ${body.token}` },
        });
        expect(userResponse.status()).toBe(200);
        expect(await userResponse.json()).toEqual({ auth: 'email:testuser@example.org' });
    });

    test('login with wrong password returns 401', async ({ request }) => {
        await setApiEnabled(request, true, SUBDOMAIN, CONSULTATION);

        const response = await request.post(`/${SUBDOMAIN}/rest/user/login`, {
            data: { username: 'testuser@example.org', password: 'not-the-password' },
            headers: { 'Content-Type': 'application/json' },
        });
        expect(response.status()).toBe(401);
        const body = await response.json();
        expect(body.success).toBe(false);
    });

    test('login with unknown username returns identical error to wrong password', async ({ request }) => {
        await setApiEnabled(request, true, SUBDOMAIN, CONSULTATION);

        const wrongPasswordBody = await (
            await request.post(`/${SUBDOMAIN}/rest/user/login`, {
                data: { username: 'testuser@example.org', password: 'not-the-password' },
                headers: { 'Content-Type': 'application/json' },
            })
        ).json();

        const unknownUserResponse = await request.post(`/${SUBDOMAIN}/rest/user/login`, {
            data: { username: 'does-not-exist@example.org', password: 'whatever' },
            headers: { 'Content-Type': 'application/json' },
        });
        expect(unknownUserResponse.status()).toBe(401);
        const unknownUserBody = await unknownUserResponse.json();
        expect(unknownUserBody.success).toBe(false);
        expect(JSON.stringify(unknownUserBody)).toBe(JSON.stringify(wrongPasswordBody));
    });

    test('malformed bearer token returns 401', async ({ request }) => {
        await setApiEnabled(request, true, SUBDOMAIN, CONSULTATION);

        const response = await request.get(`/${SUBDOMAIN}/rest/user`, {
            headers: { Authorization: 'Bearer not-a-jwt' },
        });
        expect(response.status()).toBe(401);
        const body = await response.json();
        expect(body.success).toBe(false);
    });

    test('login with malformed request body returns 400', async ({ request }) => {
        await setApiEnabled(request, true, SUBDOMAIN, CONSULTATION);

        const response = await request.post(`/${SUBDOMAIN}/rest/user/login`, {
            data: { username: 'testuser@example.org' },
            headers: { 'Content-Type': 'application/json' },
        });
        expect(response.status()).toBe(400);
        const body = await response.json();
        expect(body.success).toBe(false);
    });
});
