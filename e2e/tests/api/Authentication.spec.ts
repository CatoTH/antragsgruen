import { test, expect } from '../../fixtures';
import { RestAuth } from '../../utils/auth';

const SUBDOMAIN = 'stdparteitag';
const CONSULTATION = 'std-parteitag';

test.describe('API: Authentication via JWT token', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('JWT token from /token page authorizes /rest/user', async ({ request, page }) => {
        await page.goto(`/${SUBDOMAIN}/${CONSULTATION}`);
        await page.locator('#loginLink').click();
        await page.locator('#username').first().fill('testuser@example.org');
        await page.locator('#passwordInput').first().fill('testuser');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await page.locator('#logoutLink').waitFor({ state: 'visible' });

        await page.goto(`/${SUBDOMAIN}/${CONSULTATION}/token`);
        const html = await page.content();
        const match = html.match(/"token":"([^"]+)"/);
        expect(match).not.toBeNull();
        const token = match![1];

        const noTokenResponse = await request.get(`/${SUBDOMAIN}/rest/user`);
        expect(noTokenResponse.status()).toBe(403);

        const response = await request.get(`/${SUBDOMAIN}/rest/user`, {
            headers: { Authorization: `Bearer ${token}` },
        });
        expect(response.status()).toBe(200);
        expect(await response.json()).toEqual({ auth: 'email:testuser@example.org' });
    });
});
