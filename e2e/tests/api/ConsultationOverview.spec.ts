import { test, expect } from '../../fixtures';
import { setApiEnabled } from '../../utils/test-api';

const SUBDOMAIN = 'stdparteitag';
const CONSULTATION = 'std-parteitag';
const BASE_URI = `http://test.antragsgruen.test/${SUBDOMAIN}/`;

test.describe('API: Consultation overview endpoint', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('returns consultation overview after enabling API', async ({ request, page }) => {
        const response1 = await request.get(`/${SUBDOMAIN}/rest/${CONSULTATION}`);
        expect(response1.status()).toBe(403);
        expect(await response1.json()).toEqual({
            success: false,
            message: 'Public API disabled',
        });

        await setApiEnabled(request, true, SUBDOMAIN, CONSULTATION);

        await page.goto(`/${SUBDOMAIN}/${CONSULTATION}`);
        await page.locator('#loginLink').click();
        await page.locator('#username').fill('testadmin@example.org');
        await page.locator('#passwordInput').fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await page.locator('#logoutLink').waitFor({ state: 'visible' });

        await page.goto(`/${SUBDOMAIN}/${CONSULTATION}/admin/index/appearance`);
        await page.evaluate(() => {
            const el = document.querySelector('#apiEnabled') as HTMLElement | null as HTMLInputElement | null;
            if (el) {
                el.checked = true;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        const response2 = await request.get(`/${SUBDOMAIN}/rest/${CONSULTATION}`);
        expect(response2.status()).toBe(200);
        const body = await response2.json();
        expect(body.title).toBe('Test2');
        expect(body.title_short).toBe('Test2');
        expect(body.speaking_lists).toBeNull();
        expect(body.page_links).toEqual([]);
        expect(body.motion_links).toHaveLength(7);
        expect(body.motion_links[0].id).toBe(2);
        expect(body.motion_links[0].prefix).toBe('A2');
        expect(body.motion_links[0].type).toBe('motion');
        expect(body.url_json).toBe(`${BASE_URI}rest/std-parteitag`);
        expect(body.url_html).toBe(`${BASE_URI}std-parteitag`);
    });
});
