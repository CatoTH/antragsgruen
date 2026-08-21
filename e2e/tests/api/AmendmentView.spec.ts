import { test, expect } from '../../fixtures';
import { setApiEnabled } from '../../utils/test-api';

const SUBDOMAIN = 'stdparteitag';
const CONSULTATION = 'std-parteitag';
const BASE_URI = `http://test.antragsgruen.test/${SUBDOMAIN}/`;

test.describe('API: default disabled and amendment view', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('API is disabled by default and returns 403', async ({ request }) => {
        const response = await request.get(
            `/${SUBDOMAIN}/rest/${CONSULTATION}/motion/Moving_test-47262/amendment/278`,
        );
        expect(response.status()).toBe(403);
        expect(await response.json()).toEqual({
            success: false,
            message: 'Public API disabled',
        });
    });

    test('API returns amendment data after being enabled', async ({ request, page }) => {
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

        const response = await request.get(
            `/${SUBDOMAIN}/rest/${CONSULTATION}/motion/Moving_test-47262/amendment/278`,
        );
        expect(response.status()).toBe(200);

        const expected = {
            type: 'amendment',
            id: 278,
            prefix: 'Ä1',
            title: 'Ä1 zu A7: Moving test',
            title_with_prefix: 'Ä1 zu A7: Moving test',
            first_line: 5,
            status_id: 3,
            status_title: '<span class="screened">Geprüft</span>',
            date_published: '2017-04-30T00:00:04+00:00',
            motion: {
                id: 117,
                agenda_item: null,
                prefix: 'A7',
                title: 'Moving test',
                title_with_intro: 'Moving test',
                title_with_prefix: 'A7: Moving test',
                initiators_html: 'Testuser (Anonymous)',
                url_json: `${BASE_URI}rest/std-parteitag/motion/Moving_test-47262`,
                url_html: `${BASE_URI}std-parteitag/Moving_test-47262`,
            },
            supporters: [],
            initiators: [
                {
                    gender: null,
                    id: 493,
                    type: 'person',
                    name: 'Mover',
                    organization: 'Moving',
                },
            ],
            initiators_html: 'Mover (Moving)',
            sections: [
                {
                    type: 'TextSimple',
                    title: 'Antragstext',
                    html: expect.stringMatching(/<div class="text motionTextFormattings textOrig">/),
                },
                {
                    type: 'TextSimple',
                    title: 'Antragstext 2',
                    html: '',
                },
            ],
            proposed_procedure: null,
            url_json: `${BASE_URI}rest/std-parteitag/motion/Moving_test-47262/amendment/278`,
            url_html: `${BASE_URI}std-parteitag/Moving_test-47262/278`,
        };
        const body = await response.json();
        expect(body.type).toBe(expected.type);
        expect(body.id).toBe(expected.id);
        expect(body.prefix).toBe(expected.prefix);
        expect(body.title).toBe(expected.title);
        expect(body.title_with_prefix).toBe(expected.title_with_prefix);
        expect(body.first_line).toBe(expected.first_line);
        expect(body.status_id).toBe(expected.status_id);
        expect(body.status_title).toBe(expected.status_title);
        expect(body.date_published).toBe(expected.date_published);
        expect(body.motion).toEqual(expected.motion);
        expect(body.supporters).toEqual(expected.supporters);
        expect(body.initiators).toEqual(expected.initiators);
        expect(body.initiators_html).toBe(expected.initiators_html);
        expect(body.sections[0]?.type).toBe(expected.sections[0]?.type);
        expect(body.sections[0]?.title).toBe(expected.sections[0]?.title);
        expect(body.sections[0].html).toMatch(/motionTextFormattings/);
        expect(body.sections[1]).toEqual(expected.sections[1]);
        expect(body.proposed_procedure).toBeNull();
        expect(body.url_json).toBe(expected.url_json);
        expect(body.url_html).toBe(expected.url_html);
    });
});
