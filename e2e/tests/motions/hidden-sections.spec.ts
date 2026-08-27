import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { dispatchClick, setCkEditorContent } from '../../utils/dom';
import {
    FIRST_FREE_MOTION_ID,
    FIRST_FREE_MOTION_SECTION,
    FIRST_FREE_MOTION_TYPE,
} from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminAppearancePage } from '../../pages/AdminAppearancePage';

const TYPE_TEXT_SIMPLE = '1';

test.describe('Hidden motion sections', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a non-public section is only visible to the initiator and admins', async ({
        page,
        request,
    }) => {
        const admin = new AdminIndexPage(page);
        await admin.open();
        await loginAsStdAdmin(page);

        await page.locator('.motionTypeCreate a').click();
        await page.locator('.presetMotion').first().check();
        await expect(page.locator('#typeTitleSingular')).toHaveValue('Antrag');
        await page.locator('#typeTitleSingular').first().fill('Hidden motion');
        await page.locator('#typeTitlePlural').first().fill('Hidden motions');
        await page.locator('#typeCreateTitle').first().fill('Create hidden');
        await page.locator('.motionTypeCreateForm [name="create"]').click();

        await dispatchClick(page, '.sectionAdder');
        await page.locator('#sectionTypenew0').first().selectOption(TYPE_TEXT_SIMPLE);
        await page.locator('.sectionnew0 .sectionTitle input').first().fill('Message to the admin');
        await expect(page.locator('.sectionnew0 .nonPublicRow input')).not.toBeChecked();
        await expect(page.locator('.sectionnew0 .amendmentRow').first()).toBeVisible();
        await page.locator('.sectionnew0 .nonPublicRow input').first().check();
        await expect(page.locator('.sectionnew0 .amendmentRow').filter({ visible: true })).toHaveCount(0);
        await page.locator('.adminTypeForm [name="save"]').click();

        await expect(
            page.locator(`.section${FIRST_FREE_MOTION_SECTION + 3} .nonPublicRow input`),
        ).toBeChecked();

        const appearance = new AdminAppearancePage(page);
        await appearance.open();
        await page.evaluate(() => {
            const w = window as any;
            w.$('#apiEnabled').prop('checked', true).trigger('change');
        });
        await appearance.saveForm();

        const home = new ConsultationHomePage(page);
        await home.open();
        await page.locator(`#sidebar .createMotion${FIRST_FREE_MOTION_TYPE}`).click();
        await expect(page.locator('#section_holder_54')).toContainText(
            'nur für dich und Administrierende',
        );

        await page.locator("input[name='tags[]'][value='1']").first().check();
        await page.locator(`#sections_${FIRST_FREE_MOTION_SECTION}`).first().fill('New motion');
        await setCkEditorContent(
            page,
            `sections_${FIRST_FREE_MOTION_SECTION + 1}_wysiwyg`,
            '<p>Public text</p>',
        );
        await setCkEditorContent(
            page,
            `sections_${FIRST_FREE_MOTION_SECTION + 2}_wysiwyg`,
            '<p>Reason</p>',
        );
        await setCkEditorContent(
            page,
            `sections_${FIRST_FREE_MOTION_SECTION + 3}_wysiwyg`,
            '<p>Internal hint for the admins</p>',
        );
        await page.locator('input[name=otherInitiator]').first().uncheck();
        await page.locator('#initiatorPrimaryName').first().fill('My name');
        await page.locator('#initiatorEmail').first().fill('test@example.org');
        await page.locator('#motionEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText('nur für dich und Administrierende');
        await expect(page.locator('body')).toContainText('Internal hint for the admins');
        await page.locator('#motionConfirmForm [name="confirm"]').click();

        await home.open();
        await home.gotoMotionView(FIRST_FREE_MOTION_ID);
        await expect(page.locator(`#section_${FIRST_FREE_MOTION_SECTION + 1}`)).toContainText(
            'Public text',
        );
        await expect(page.locator(`#section_${FIRST_FREE_MOTION_SECTION + 3}`)).toContainText(
            'nur für dich als Antragsteller*in',
        );
        await expect(page.locator(`#section_${FIRST_FREE_MOTION_SECTION + 3}`)).toContainText(
            'Internal hint for the admins',
        );

        await page.locator('#sidebar .adminEdit').click();
        await dispatchClick(page, '#motionTextEditCaller button');
        await expect(page.locator('#section_holder_54')).toContainText(
            'Internal hint for the admins',
        );

        await home.open();
        await logout(page);
        await loginAsStdUser(page);
        await page.locator(`.motionLink${FIRST_FREE_MOTION_ID}`).click();
        await expect(page.locator(`#section_${FIRST_FREE_MOTION_SECTION + 1}`)).toContainText(
            'Public text',
        );
        await expect(page.locator('body')).not.toContainText('nur für dich als Antragsteller*in', { useInnerText: true });
        await expect(page.locator('body')).not.toContainText('Internal hint for the admins', { useInnerText: true });

        const consultationResponse = await request.get('/stdparteitag/rest/std-parteitag');
        expect(consultationResponse.ok()).toBe(true);
        const consultationRest = await consultationResponse.json();
        const links = consultationRest.motion_links;
        const motionLink = links[links.length - 1];
        expect(motionLink.title).toBe('New motion');

        const motionResponse = await request.get(motionLink.url_json);
        expect(motionResponse.status()).toBe(200);
        const motionRest = await motionResponse.json();
        expect(motionRest.sections).toHaveLength(2);
        expect(motionRest.sections[0].title).toBe('Antragstext');
        expect(motionRest.sections[1].title).toBe('Begründung');
    });
});
