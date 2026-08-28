import { test, expect } from '../../fixtures';
import { setApiEnabled } from '../../utils/test-api';

const SUBDOMAIN = 'stdparteitag';
const CONSULTATION = 'std-parteitag';
const BASE_URI = `http://test.antragsgruen.test/${SUBDOMAIN}/`;

test.describe('API: Consultation list endpoint', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('lists consultations only after enabling API', async ({ request, page }) => {
        const response1 = await request.get(`/${SUBDOMAIN}/rest`);
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

        await page.goto(`/${SUBDOMAIN}/${CONSULTATION}/admin/appearance`);
        await expect(page.locator('#apiEnabled')).not.toBeChecked();
        await expect(page.locator('.apiBaseUrl')).toHaveCount(0);
        await page.evaluate(() => {
            const el = document.querySelector('#apiEnabled') as HTMLElement | null as HTMLInputElement | null;
            if (el) {
                el.checked = true;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.waitForTimeout(300);
        await expect(page.locator('.apiBaseUrl')).toBeVisible();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        const response2 = await request.get(`/${SUBDOMAIN}/rest`);
        expect(response2.status()).toBe(200);
        expect(await response2.json()).toEqual([
            {
                title: 'Test2',
                title_short: 'Test2',
                date_published: '2015-11-16T22:35:58+00:00',
                url_path: 'std-parteitag',
                url_json: `${BASE_URI}rest/std-parteitag`,
                url_html: `${BASE_URI}std-parteitag`,
            },
        ]);
    });
});
