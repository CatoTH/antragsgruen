import { test, expect } from '../../fixtures';
import { setApiEnabled } from '../../utils/test-api';

const SUBDOMAIN = 'stdparteitag';
const CONSULTATION = 'std-parteitag';
const BASE_URI = `http://test.antragsgruen.test/${SUBDOMAIN}/`;

test.describe('API: Motion view endpoint', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('returns amendment view after enabling API', async ({ request, page }) => {
        const response1 = await request.get(
            `/${SUBDOMAIN}/rest/${CONSULTATION}/motion/Testing_proposed_changes-630/amendment/283`,
        );
        expect(response1.status()).toBe(403);
        expect(await response1.json()).toEqual({
            success: false,
            message: 'Public API disabled',
        });

        await setApiEnabled(request, true, SUBDOMAIN, CONSULTATION);

        await page.goto(`/${SUBDOMAIN}/${CONSULTATION}`);
        await page.locator('#loginLink').click();
        await page.locator('#username').first().fill('testadmin@example.org');
        await page.locator('#passwordInput').first().fill('testadmin');
        await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
        await page.locator('#logoutLink').waitFor({ state: 'visible' });

        await page.goto(`/${SUBDOMAIN}/${CONSULTATION}/admin/appearance`);
        await page.evaluate(() => {
            const el = document.querySelector('#apiEnabled') as HTMLElement | null as HTMLInputElement | null;
            if (el) {
                el.checked = true;
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        const response2 = await request.get(
            `/${SUBDOMAIN}/rest/${CONSULTATION}/motion/Testing_proposed_changes-630/amendment/283`,
        );
        expect(response2.status()).toBe(200);

        const body = await response2.json();
        expect(body.type).toBe('amendment');
        expect(body.id).toBe(283);
        expect(body.prefix).toBe('Ä4');
        expect(body.title).toBe('Ä4 zu A8: Testing proposed changes');
        expect(body.title_with_prefix).toBe('Ä4 zu A8: Testing proposed changes');
        expect(body.first_line).toBe(24);
        expect(body.status_id).toBe(3);
        expect(body.status_title).toBe('<span class="screened">Geprüft</span>');
        expect(body.date_published).toBe('2018-11-03T06:14:01+00:00');
        expect(body.motion.id).toBe(118);
        expect(body.motion.prefix).toBe('A8');
        expect(body.motion.title).toBe('Testing proposed changes');
        expect(body.supporters).toEqual([]);
        expect(body.initiators).toEqual([
            { gender: null, id: 499, type: 'person', name: 'Testuser', organization: '' },
        ]);
        expect(body.initiators_html).toBe('Testuser');
        expect(body.sections).toHaveLength(2);
        expect(body.sections[0].type).toBe('TextSimple');
        expect(body.sections[0].title).toBe('Antragstext');
        expect(body.sections[1].type).toBe('TextSimple');
        expect(body.sections[1].title).toBe('Antragstext 2');
        expect(body.sections[1].html).toBe('');
        expect(body.proposed_procedure).toBeNull();
        expect(body.url_json).toBe(
            `${BASE_URI}rest/std-parteitag/motion/Testing_proposed_changes-630/amendment/283`,
        );
        expect(body.url_html).toBe(
            `${BASE_URI}std-parteitag/Testing_proposed_changes-630/283`,
        );
    });
});
